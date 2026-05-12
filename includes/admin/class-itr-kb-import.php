<?php
/**
 * Import KB data — chunked file upload + chunked AJAX import.
 *
 * Upload flow (replaces standard HTML form POST for large file support):
 * 1. JS slices the file into 5 MB pieces and uploads each via AJAX
 * 2. Server appends pieces to a temp file; validates on the final piece
 * 3. On first import AJAX: full JSON is parsed once and split into small stage files
 * 4. Each subsequent AJAX call reads only its small stage file
 * 5. Temp files deleted on completion or cancel
 *
 * @package ITR_Knowledgebase
 */

namespace ITR_Knowledgebase\Admin;

use ITR_Knowledgebase\Helpers\ITR_KB_Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ITR_KB_Import {

	const CHUNK_SIZE    = 30;
	const TRANSIENT_PFX = 'itr_kb_imp_';

	public function __construct() {
		// Chunked file-upload handler (replaces old form-based handle_upload).
		add_action( 'wp_ajax_itr_kb_upload_chunk',   array( $this, 'ajax_upload_chunk'  ) );
		add_action( 'wp_ajax_itr_kb_import_prepare', array( $this, 'ajax_prepare'        ) );
		add_action( 'wp_ajax_itr_kb_import_chunk',   array( $this, 'ajax_chunk'          ) );
		add_action( 'wp_ajax_itr_kb_import_cancel',  array( $this, 'ajax_cancel'         ) );
	}

	// =========================================================================
	// STEP 1 — Chunked file upload (called repeatedly by JS with 5 MB slices)
	// =========================================================================

	public function ajax_upload_chunk() {
		check_ajax_referer( 'itr_kb_upload_chunk', 'nonce' );

		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$chunk_index  = isset( $_POST['chunk_index'] )  ? absint( $_POST['chunk_index'] )                                : 0;
		$total_chunks = isset( $_POST['total_chunks'] ) ? absint( $_POST['total_chunks'] )                               : 1;
		$session_id   = isset( $_POST['session_id'] )   ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) )     : '';
		$overwrite    = isset( $_POST['overwrite'] )    && '1' === sanitize_text_field( wp_unslash( $_POST['overwrite'] ) );

		if (
			empty( $_FILES['chunk']['tmp_name'] ) ||
			! is_uploaded_file( $_FILES['chunk']['tmp_name'] )
		) {
			wp_send_json_error( array( 'message' => 'No chunk data received.' ) );
		}

		// ── Ensure import directory exists ───────────────────────────────────
		$upload_dir = wp_upload_dir();
		$import_dir = trailingslashit( $upload_dir['basedir'] ) . 'itr-kb-imports/';

		if ( ! file_exists( $import_dir ) ) {
			wp_mkdir_p( $import_dir );
			file_put_contents( $import_dir . '.htaccess', 'Deny from all' ); // phpcs:ignore
			file_put_contents( $import_dir . 'index.php', '<?php // silence' ); // phpcs:ignore
		}

		// ── Generate session ID on first chunk ────────────────────────────────
		if ( 0 === $chunk_index || ! $session_id ) {
			$session_id = wp_generate_password( 20, false );
		}

		$dest = $import_dir . 'import-' . $session_id . '.json';

		// ── Read chunk from PHP temp file ─────────────────────────────────────
		$chunk_data = file_get_contents( $_FILES['chunk']['tmp_name'] ); // phpcs:ignore
		if ( false === $chunk_data ) {
			wp_send_json_error( array( 'message' => 'Failed to read chunk.' ) );
		}

		// ── Append chunk to destination file ─────────────────────────────────
		if ( 0 === $chunk_index ) {
			// First chunk: create / overwrite.
			file_put_contents( $dest, $chunk_data ); // phpcs:ignore
		} else {
			// Subsequent chunks: append.
			file_put_contents( $dest, $chunk_data, FILE_APPEND ); // phpcs:ignore
		}

		// ── Not last chunk — return progress ─────────────────────────────────
		if ( $chunk_index < $total_chunks - 1 ) {
			wp_send_json_success( array(
				'session_id'  => $session_id,
				'chunk_index' => $chunk_index,
				'status'      => 'uploading',
			) );
			return;
		}

		// ── Last chunk — validate file ────────────────────────────────────────
		$fh      = fopen( $dest, 'r' ); // phpcs:ignore
		$preview = fread( $fh, 512 );   // phpcs:ignore
		fclose( $fh );                  // phpcs:ignore

		if ( false === strpos( $preview, 'itr-knowledgebase' ) ) {
			wp_delete_file( $dest );
			wp_send_json_error( array(
				'message' => 'Invalid file. Only JSON files exported from ITR Knowledgebase or the Echo KB Exporter are supported.',
			) );
			return;
		}

		// ── Store session for the prepare + import steps ──────────────────────
		set_transient(
			self::TRANSIENT_PFX . $session_id,
			array(
				'raw_file'   => $dest,
				'import_dir' => $import_dir,
				'overwrite'  => $overwrite,
				'prepared'   => false,
				'totals'     => array(),
			),
			3 * HOUR_IN_SECONDS
		);

		wp_send_json_success( array(
			'session_id' => $session_id,
			'status'     => 'ready',
		) );
	}

	// =========================================================================
	// STEP 2 — AJAX: prepare (parse once, split into small stage files)
	// =========================================================================

	public function ajax_prepare() {
		check_ajax_referer( 'itr_kb_import_chunk', 'nonce' );
		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		ini_set( 'memory_limit', '512M' ); // phpcs:ignore
		set_time_limit( 300 );

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		$session    = $session_id ? get_transient( self::TRANSIENT_PFX . $session_id ) : null;

		if ( ! $session || ! file_exists( $session['raw_file'] ) ) {
			wp_send_json_error( array( 'message' => 'Session not found. Please upload the file again.' ) );
		}

		if ( $session['prepared'] ) {
			wp_send_json_success( array( 'totals' => $session['totals'] ) );
		}

		// ── Load JSON once ────────────────────────────────────────────────────
		$json = file_get_contents( $session['raw_file'] ); // phpcs:ignore
		if ( false === $json ) {
			wp_send_json_error( array( 'message' => 'Could not read import file.' ) );
		}

		$data = json_decode( $json, true );
		unset( $json );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => 'Invalid JSON structure.' ) );
		}

		$dir = $session['import_dir'];
		$pfx = 'stage-' . $session_id . '-';

		// ── Merge categories from both possible keys ───────────────────────
		$all_cats = array_merge(
			$data['categories']    ?? array(),
			$data['subcategories'] ?? array()
		);

		// ── Resolve parent_slug for formats that store parent as integer ID ──
		//
		// Build a map of  original_term_id → slug  from the export data.
		// This fixes imports where `parent` is an integer reference to another
		// category in the same file (e.g. Echo KB format).
		$id_to_slug = array();
		foreach ( $all_cats as $cat ) {
			$tid  = (int) ( $cat['term_id'] ?? $cat['id'] ?? 0 );
			$slug = sanitize_title( $cat['slug'] ?? '' );
			if ( $tid && $slug ) {
				$id_to_slug[ $tid ] = $slug;
			}
		}

		// Fill in missing parent_slug from integer parent ID.
		foreach ( $all_cats as &$cat ) {
			if ( empty( $cat['parent_slug'] ) ) {
				$pid = (int) ( $cat['parent'] ?? 0 );
				if ( $pid && isset( $id_to_slug[ $pid ] ) ) {
					$cat['parent_slug'] = $id_to_slug[ $pid ];
				}
			}
		}
		unset( $cat );

		// ── Sort: parents always before children (any depth) ──────────────────
		$slug_depth = array();
		foreach ( $all_cats as $cat ) {
			$s = sanitize_title( $cat['slug'] ?? '' );
			if ( $s ) {
				$slug_depth[ $s ] = empty( $cat['parent_slug'] ) ? 0 : null;
			}
		}
		// Iteratively resolve depths for nested children.
		for ( $pass = 0; $pass < 20; $pass++ ) {
			$all_resolved = true;
			foreach ( $all_cats as $cat ) {
				$s  = sanitize_title( $cat['slug']        ?? '' );
				$ps = sanitize_title( $cat['parent_slug'] ?? '' );
				if ( $s && null === ( $slug_depth[ $s ] ?? null ) && $ps ) {
					if ( isset( $slug_depth[ $ps ] ) && null !== $slug_depth[ $ps ] ) {
						$slug_depth[ $s ] = $slug_depth[ $ps ] + 1;
					} else {
						$all_resolved = false;
					}
				}
			}
			if ( $all_resolved ) {
				break;
			}
		}
		usort( $all_cats, function ( $a, $b ) use ( $slug_depth ) {
			$da = $slug_depth[ sanitize_title( $a['slug'] ?? '' ) ] ?? 0;
			$db = $slug_depth[ sanitize_title( $b['slug'] ?? '' ) ] ?? 0;
			return $da - $db;
		} );

		// ── Write stage files ─────────────────────────────────────────────────
		$stage_files = array();
		foreach ( array(
			'categories' => $all_cats,
			'tags'       => $data['tags']    ?? array(),
			'authors'    => $data['authors'] ?? array(),
		) as $stage => $items ) {
			$f = $dir . $pfx . $stage . '.json';
			file_put_contents( $f, wp_json_encode( $items ) ); // phpcs:ignore
			$stage_files[ $stage ] = $f;
		}

		// ── Split articles into 50-item chunk files ───────────────────────────
		$articles       = $data['articles'] ?? array();
		$total_articles = count( $articles );
		$chunk_size     = 50;
		$num_chunks     = (int) ceil( $total_articles / $chunk_size );
		$article_chunks = array();

		for ( $i = 0; $i < $num_chunks; $i++ ) {
			$slice      = array_slice( $articles, $i * $chunk_size, $chunk_size );
			$chunk_file = $dir . $pfx . 'articles-' . $i . '.json';
			file_put_contents( $chunk_file, wp_json_encode( $slice ) ); // phpcs:ignore
			$article_chunks[] = $chunk_file;
		}

		unset( $data, $articles, $all_cats );

		$totals = array(
			'categories' => count( json_decode( file_get_contents( $stage_files['categories'] ), true ) ?? array() ), // phpcs:ignore
			'tags'       => count( json_decode( file_get_contents( $stage_files['tags'] ),       true ) ?? array() ), // phpcs:ignore
			'authors'    => count( json_decode( file_get_contents( $stage_files['authors'] ),    true ) ?? array() ), // phpcs:ignore
			'articles'   => $total_articles,
		);

		$session['prepared']       = true;
		$session['totals']         = $totals;
		$session['stage_files']    = $stage_files;
		$session['article_chunks'] = $article_chunks;
		$session['article_total']  = $total_articles;
		set_transient( self::TRANSIENT_PFX . $session_id, $session, 3 * HOUR_IN_SECONDS );

		wp_delete_file( $session['raw_file'] );

		wp_send_json_success( array( 'totals' => $totals ) );
	}

	// =========================================================================
	// STEP 3 — AJAX: import one chunk from a small stage file
	// =========================================================================

	public function ajax_chunk() {
		check_ajax_referer( 'itr_kb_import_chunk', 'nonce' );
		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		ini_set( 'memory_limit', '128M' ); // phpcs:ignore
		set_time_limit( 60 );

		$session_id  = isset( $_POST['session_id'] )   ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		$stage       = isset( $_POST['stage'] )         ? sanitize_key( wp_unslash( $_POST['stage'] ) )             : '';
		$offset      = isset( $_POST['offset'] )        ? absint( $_POST['offset'] )                                : 0;
		$chunk_index = isset( $_POST['chunk_index'] )   ? absint( $_POST['chunk_index'] )                           : 0;

		$session = $session_id ? get_transient( self::TRANSIENT_PFX . $session_id ) : null;

		if ( ! $session || ! $session['prepared'] ) {
			wp_send_json_error( array( 'message' => 'Session not ready. Please refresh and try again.' ) );
		}

		$overwrite = (bool) $session['overwrite'];

		// ── Articles use pre-split chunk files ────────────────────────────────
		if ( 'articles' === $stage ) {
			$chunk_files = $session['article_chunks'] ?? array();
			$total       = $session['article_total']  ?? 0;

			if ( ! isset( $chunk_files[ $chunk_index ] ) || ! file_exists( $chunk_files[ $chunk_index ] ) ) {
				wp_send_json_success( array(
					'stage'       => 'articles',
					'done'        => $total,
					'total'       => $total,
					'offset'      => $total,
					'chunk_index' => $chunk_index,
					'finished'    => true,
				) );
				return;
			}

			$chunk_file = $chunk_files[ $chunk_index ];
			$items      = json_decode( file_get_contents( $chunk_file ), true ) ?? array(); // phpcs:ignore
			$done       = $this->do_articles( $items, $overwrite );

			wp_delete_file( $chunk_file );

			$next_index = $chunk_index + 1;
			$items_done = min( $offset + $done, $total );
			$all_done   = $next_index >= count( $chunk_files );

			wp_send_json_success( array(
				'stage'       => 'articles',
				'done'        => $items_done,
				'total'       => $total,
				'offset'      => $items_done,
				'chunk_index' => $next_index,
				'finished'    => $all_done,
			) );
			return;
		}

		// ── Other stages: categories, tags, authors ───────────────────────────
		$stage_file = $session['stage_files'][ $stage ] ?? '';

		if ( ! $stage_file || ! file_exists( $stage_file ) ) {
			wp_send_json_success( array(
				'stage'    => $stage,
				'done'     => 0,
				'total'    => 0,
				'offset'   => 0,
				'finished' => true,
			) );
			return;
		}

		$items = json_decode( file_get_contents( $stage_file ), true ) ?? array(); // phpcs:ignore
		$total = count( $items );
		$chunk = array_slice( $items, $offset, self::CHUNK_SIZE );
		unset( $items );

		switch ( $stage ) {
			case 'categories': $done = $this->do_categories( $chunk ); break;
			case 'tags':       $done = $this->do_tags( $chunk );       break;
			case 'authors':    $done = $this->do_authors( $chunk, $overwrite ); break;
			default:           wp_send_json_error( array( 'message' => 'Unknown stage.' ) );
		}

		$new_offset = $offset + $done;
		$finished   = $new_offset >= $total;

		if ( $finished ) {
			wp_delete_file( $stage_file );
		}

		wp_send_json_success( array(
			'stage'    => $stage,
			'done'     => $new_offset,
			'total'    => $total,
			'offset'   => $new_offset,
			'finished' => $finished,
		) );
	}

	// =========================================================================
	// AJAX: cancel / cleanup
	// =========================================================================

	public function ajax_cancel() {
		check_ajax_referer( 'itr_kb_import_chunk', 'nonce' );
		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			wp_send_json_error();
		}
		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		if ( $session_id ) {
			$this->cleanup( $session_id );
		}
		wp_send_json_success();
	}

	private function cleanup( $session_id ) {
		$session = get_transient( self::TRANSIENT_PFX . $session_id );
		if ( $session ) {
			if ( ! empty( $session['raw_file'] ) && file_exists( $session['raw_file'] ) ) {
				wp_delete_file( $session['raw_file'] );
			}
			foreach ( $session['stage_files'] ?? array() as $f ) {
				if ( file_exists( $f ) ) {
					wp_delete_file( $f );
				}
			}
			foreach ( $session['article_chunks'] ?? array() as $f ) {
				if ( file_exists( $f ) ) {
					wp_delete_file( $f );
				}
			}
		}
		delete_transient( self::TRANSIENT_PFX . $session_id );
	}

	// =========================================================================
	// Per-stage importers
	// =========================================================================

	/**
	 * Import a chunk of categories.
	 *
	 * The stage file is pre-sorted by depth (parents before children), so every
	 * parent is guaranteed to exist in the DB by the time its children are processed.
	 *
	 * After each wp_insert_term() we call clean_term_cache() to flush any stale
	 * object-cache entries so that subsequent get_term_by() calls within the
	 * same request always read fresh data from the DB.
	 */
	private function do_categories( $chunk ) {
		$done = 0;
		foreach ( $chunk as $cat ) {
			$name = sanitize_text_field( $cat['name'] ?? '' );
			$slug = sanitize_title( $cat['slug'] ?? '' );
			if ( ! $name ) {
				$done++;
				continue;
			}

			// Resolve parent term ID from parent_slug.
			$parent_id   = 0;
			$parent_slug = sanitize_title( $cat['parent_slug'] ?? '' );
			if ( $parent_slug ) {
				// Clean cache before lookup to avoid stale object-cache entries.
				// This matters when using persistent cache plugins (Redis, Memcached).
				wp_cache_delete( $parent_slug, 'itr_kb_category_slugs' );
				$pt = get_term_by( 'slug', $parent_slug, 'itr_kb_category' );
				if ( $pt ) {
					$parent_id = $pt->term_id;
				}
			}

			// Skip if the term already exists.
			if ( get_term_by( 'slug', $slug, 'itr_kb_category' ) ) {
				$done++;
				continue;
			}

			$result = wp_insert_term( $name, 'itr_kb_category', array(
				'slug'        => $slug,
				'parent'      => $parent_id,
				'description' => sanitize_textarea_field( $cat['description'] ?? '' ),
			) );

			if ( ! is_wp_error( $result ) ) {
				$tid = $result['term_id'];
				if ( ! empty( $cat['icon'] ) ) {
					update_term_meta( $tid, 'itr_kb_category_icon', sanitize_text_field( $cat['icon'] ) );
				}
				if ( isset( $cat['order'] ) ) {
					update_term_meta( $tid, 'itr_kb_category_order', absint( $cat['order'] ) );
				}
				// Flush term cache after insert so next get_term_by() in this
				// same request sees the newly created term immediately.
				clean_term_cache( array( $tid ), 'itr_kb_category' );
			}

			$done++;
		}
		return $done;
	}

	private function do_tags( $chunk ) {
		$done = 0;
		foreach ( $chunk as $tag ) {
			$name = sanitize_text_field( $tag['name'] ?? '' );
			$slug = sanitize_title( $tag['slug'] ?? '' );
			if ( $name && ! get_term_by( 'slug', $slug, 'itr_kb_tag' ) ) {
				wp_insert_term( $name, 'itr_kb_tag', array(
					'slug'        => $slug,
					'description' => sanitize_textarea_field( $tag['description'] ?? '' ),
				) );
			}
			$done++;
		}
		return $done;
	}

	private function do_authors( $chunk, $overwrite ) {
		$done = 0;
		foreach ( $chunk as $author ) {
			$name = sanitize_text_field( $author['name'] ?? '' );
			if ( ! $name ) {
				$done++;
				continue;
			}

			$q        = new \WP_Query( array(
				'post_type'      => 'itr_kb_author',
				'post_status'    => 'any',
				'title'          => $name,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			) );
			$existing = ! empty( $q->posts ) ? $q->posts[0] : null;

			if ( $existing && ! $overwrite ) {
				$done++;
				continue;
			}

			$post_data = array(
				'post_type'    => 'itr_kb_author',
				'post_title'   => $name,
				'post_content' => wp_kses_post( $author['bio'] ?? '' ),
				'post_status'  => 'publish',
				'post_name'    => sanitize_title( $author['slug'] ?? $name ),
			);

			if ( $existing ) {
				$post_data['ID'] = $existing;
				wp_update_post( $post_data );
			} else {
				wp_insert_post( $post_data );
			}

			$done++;
		}
		return $done;
	}

	private function do_articles( $chunk, $overwrite ) {
		$done = 0;
		foreach ( $chunk as $article ) {
			$title  = sanitize_text_field( $article['title'] ?? '' );
			$status = sanitize_key( $article['status'] ?? 'draft' );
			$slug   = sanitize_title( $article['slug'] ?? $title );
			if ( ! $title ) {
				$done++;
				continue;
			}
			if ( ! in_array( $status, array( 'publish', 'draft', 'private' ), true ) ) {
				$status = 'draft';
			}

			$q        = new \WP_Query( array(
				'post_type'      => 'itr_kb_article',
				'post_status'    => 'any',
				'title'          => $title,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			) );
			$existing = ! empty( $q->posts ) ? $q->posts[0] : null;

			if ( $existing && ! $overwrite ) {
				$done++;
				continue;
			}

			$post_data = array(
				'post_type'    => 'itr_kb_article',
				'post_title'   => $title,
				'post_content' => wp_kses_post( $article['content'] ?? '' ),
				'post_excerpt' => sanitize_textarea_field( $article['excerpt'] ?? '' ),
				'post_status'  => $status,
				'post_name'    => $slug,
				'post_date'    => sanitize_text_field( $article['date'] ?? current_time( 'mysql' ) ),
			);

			if ( $existing ) {
				$post_data['ID'] = $existing;
				$post_id         = wp_update_post( $post_data );
			} else {
				$post_id = wp_insert_post( $post_data );
			}

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				$done++;
				continue;
			}

			// Categories.
			$cats = $article['categories'] ?? array();
			if ( is_array( $cats ) && $cats ) {
				$ids = array();
				foreach ( $cats as $s ) {
					$t = get_term_by( 'slug', sanitize_title( $s ), 'itr_kb_category' );
					if ( $t ) {
						$ids[] = $t->term_id;
					}
				}
				if ( $ids ) {
					wp_set_post_terms( $post_id, $ids, 'itr_kb_category' );
				}
			}

			// Tags.
			$tags = $article['tags'] ?? array();
			if ( is_array( $tags ) && $tags ) {
				$ids = array();
				foreach ( $tags as $s ) {
					$t = get_term_by( 'slug', sanitize_title( $s ), 'itr_kb_tag' );
					if ( $t ) {
						$ids[] = $t->term_id;
					}
				}
				if ( $ids ) {
					wp_set_post_terms( $post_id, $ids, 'itr_kb_tag' );
				}
			}

			// Post meta.
			if ( ! empty( $article['featured'] ) ) {
				update_post_meta( $post_id, '_itr_kb_featured', '1' );
			}
			if ( ! empty( $article['view_count'] ) ) {
				update_post_meta( $post_id, '_itr_kb_view_count', absint( $article['view_count'] ) );
			}
			if ( ! empty( $article['toc_disabled'] ) ) {
				update_post_meta( $post_id, '_itr_kb_toc_disabled', '1' );
			}

			// Rank Math SEO meta.
			if ( ! empty( $article['rank_math'] ) && is_array( $article['rank_math'] ) ) {
				$allowed_rm_keys = array(
					'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword',
					'rank_math_robots', 'rank_math_canonical_url', 'rank_math_og_title',
					'rank_math_og_description', 'rank_math_og_image', 'rank_math_og_image_id',
					'rank_math_twitter_title', 'rank_math_twitter_description',
					'rank_math_twitter_image', 'rank_math_schema', 'rank_math_pillar_content',
				);
				foreach ( $article['rank_math'] as $rm_key => $rm_val ) {
					if ( in_array( $rm_key, $allowed_rm_keys, true ) && '' !== $rm_val ) {
						update_post_meta( $post_id, sanitize_key( $rm_key ), $rm_val );
					}
				}
			}

			$done++;
		}
		return $done;
	}
}