<?php
/**
 * Inheritance Engine for Author & Reviewer.
 *
 * Resolves author and reviewer values for KB articles by walking the
 * category ancestor chain. Each field (author, reviewer) is evaluated
 * independently. An article's own value always wins for that field;
 * if empty, the engine walks up: assigned term → parent → grandparent → …
 *
 * Post meta keys managed here:
 *   _itr_kb_author_id          — single author post ID (existing, unchanged)
 *   _itr_kb_reviewer_ids       — array of reviewer post IDs (existing, unchanged)
 *   _itr_kb_author_status      — 'manual' | 'inherited' | 'empty'
 *   _itr_kb_reviewer_status    — 'manual' | 'inherited' | 'empty'
 *   _itr_kb_author_source_term — term_id that supplied the author
 *   _itr_kb_reviewer_source_term — term_id that supplied the reviewers
 *
 * Term meta keys used (set by class-itr-kb-term-author.php):
 *   itr_kb_term_author_id      — author post ID on a category
 *   itr_kb_term_reviewer_ids   — reviewer post IDs on a category
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes
 */

namespace ITR_Knowledgebase\Includes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Inheritance
 */
class ITR_KB_Inheritance {

	/**
	 * Resolve author and reviewer for a given article by walking its
	 * category ancestor chain. Most specific (deepest) category wins.
	 *
	 * Each field is resolved independently:
	 *   — Walk: assigned term → parent → grandparent → … → root
	 *   — First term in that chain that has a value wins.
	 *
	 * @param int $post_id KB article post ID.
	 * @return array {
	 *     @type int   $author_id             Resolved author post ID (0 if none).
	 *     @type array $reviewer_ids          Resolved reviewer post IDs (empty if none).
	 *     @type int   $author_source_term    Term ID that supplied the author (0 if none).
	 *     @type int   $reviewer_source_term  Term ID that supplied the reviewers (0 if none).
	 * }
	 */
	public static function resolve( $post_id ) {
		$empty = array(
			'author_id'            => 0,
			'reviewer_ids'         => array(),
			'author_source_term'   => 0,
			'reviewer_source_term' => 0,
		);

		$terms = wp_get_post_terms( $post_id, 'itr_kb_category', array( 'fields' => 'all' ) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $empty;
		}

		// Sort terms by depth descending so the most specific category is checked first.
		usort( $terms, function ( $a, $b ) {
			$depth_a = count( get_ancestors( $a->term_id, 'itr_kb_category', 'taxonomy' ) );
			$depth_b = count( get_ancestors( $b->term_id, 'itr_kb_category', 'taxonomy' ) );
			return $depth_b - $depth_a;
		} );

		$author_id            = 0;
		$reviewer_ids         = array();
		$author_source_term   = 0;
		$reviewer_source_term = 0;

		foreach ( $terms as $term ) {
			// Build the full chain: [self, parent, grandparent, … , root].
			$chain = array_merge(
				array( $term->term_id ),
				get_ancestors( $term->term_id, 'itr_kb_category', 'taxonomy' )
			);

			// Resolve author — first hit in chain wins.
			if ( ! $author_id ) {
				foreach ( $chain as $tid ) {
					$val = (int) get_term_meta( $tid, 'itr_kb_term_author_id', true );
					if ( $val ) {
						$author_id          = $val;
						$author_source_term = (int) $tid;
						break;
					}
				}
			}

			// Resolve reviewers — first hit in chain wins.
			if ( empty( $reviewer_ids ) ) {
				foreach ( $chain as $tid ) {
					$val = get_term_meta( $tid, 'itr_kb_term_reviewer_ids', true );
					if ( ! empty( $val ) && is_array( $val ) ) {
						$reviewer_ids         = array_map( 'absint', $val );
						$reviewer_source_term = (int) $tid;
						break;
					}
				}
			}

			// Both fields resolved — no need to check remaining terms.
			if ( $author_id && ! empty( $reviewer_ids ) ) {
				break;
			}
		}

		return array(
			'author_id'            => $author_id,
			'reviewer_ids'         => $reviewer_ids,
			'author_source_term'   => $author_source_term,
			'reviewer_source_term' => $reviewer_source_term,
		);
	}

	/**
	 * Apply inherited values to an article.
	 *
	 * Only touches fields whose status is NOT 'manual'. Uses direct
	 * update_post_meta() calls (never wp_update_post) to avoid side effects.
	 *
	 * @param int $post_id KB article post ID.
	 * @return void
	 */
	public static function apply( $post_id ) {
		$author_status   = get_post_meta( $post_id, '_itr_kb_author_status', true );
		$reviewer_status = get_post_meta( $post_id, '_itr_kb_reviewer_status', true );

		// Nothing to do if both are manually overridden.
		if ( 'manual' === $author_status && 'manual' === $reviewer_status ) {
			return;
		}

		$resolved = self::resolve( $post_id );

		// Apply author if not manually overridden.
		if ( 'manual' !== $author_status ) {
			$new_status = $resolved['author_id'] ? 'inherited' : 'empty';
			update_post_meta( $post_id, '_itr_kb_author_id', $resolved['author_id'] );
			update_post_meta( $post_id, '_itr_kb_author_status', $new_status );
			update_post_meta( $post_id, '_itr_kb_author_source_term', $resolved['author_source_term'] );
		}

		// Apply reviewers if not manually overridden.
		if ( 'manual' !== $reviewer_status ) {
			$new_status = ! empty( $resolved['reviewer_ids'] ) ? 'inherited' : 'empty';
			update_post_meta( $post_id, '_itr_kb_reviewer_ids', $resolved['reviewer_ids'] );
			update_post_meta( $post_id, '_itr_kb_reviewer_status', $new_status );
			update_post_meta( $post_id, '_itr_kb_reviewer_source_term', $resolved['reviewer_source_term'] );
		}
	}

	/**
	 * Backfill all articles in a category (and its children) that have not
	 * been manually overridden. Processes in batches of 50 for performance.
	 *
	 * Only runs when a category's author/reviewer values actually change
	 * (the caller is responsible for the change-detection check).
	 *
	 * @param int $term_id Category term ID whose articles should be updated.
	 * @return void
	 */
	public static function backfill( $term_id ) {
		$paged     = 1;
		$batch     = 50;

		do {
			$query = new \WP_Query( array(
				'post_type'              => 'itr_kb_article',
				'post_status'            => 'any',
				'posts_per_page'         => $batch,
				'paged'                  => $paged,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy'         => 'itr_kb_category',
						'field'            => 'term_id',
						'terms'            => $term_id,
						'include_children' => true,
					),
				),
			) );

			if ( empty( $query->posts ) ) {
				break;
			}

			foreach ( $query->posts as $pid ) {
				self::apply( (int) $pid );
			}

			$paged++;
		} while ( $paged <= $query->max_num_pages );
	}

	/**
	 * Clear a manual override for a specific field and restore the inherited value.
	 *
	 * @param int    $post_id KB article post ID.
	 * @param string $field   'author' or 'reviewer'.
	 * @return void
	 */
	public static function clear_override( $post_id, $field ) {
		if ( 'author' === $field ) {
			delete_post_meta( $post_id, '_itr_kb_author_status' );
			delete_post_meta( $post_id, '_itr_kb_author_source_term' );
		} elseif ( 'reviewer' === $field ) {
			delete_post_meta( $post_id, '_itr_kb_reviewer_status' );
			delete_post_meta( $post_id, '_itr_kb_reviewer_source_term' );
		}

		// Re-apply inheritance — only the cleared field will be updated
		// since the other field's status is still set.
		self::apply( $post_id );
	}

	/**
	 * Get inheritance display info for a given article.
	 * Used by the meta box to render badges.
	 *
	 * @param int $post_id KB article post ID.
	 * @return array {
	 *     @type string $author_status         'manual' | 'inherited' | 'empty'
	 *     @type string $reviewer_status       'manual' | 'inherited' | 'empty'
	 *     @type int    $author_source_term    Term ID.
	 *     @type int    $reviewer_source_term  Term ID.
	 *     @type string $author_source_name    Term name (empty string if none).
	 *     @type string $reviewer_source_name  Term name (empty string if none).
	 * }
	 */
	public static function get_display_info( $post_id ) {
		$author_status   = get_post_meta( $post_id, '_itr_kb_author_status', true )   ?: 'empty';
		$reviewer_status = get_post_meta( $post_id, '_itr_kb_reviewer_status', true ) ?: 'empty';
		$author_source   = (int) get_post_meta( $post_id, '_itr_kb_author_source_term', true );
		$reviewer_source = (int) get_post_meta( $post_id, '_itr_kb_reviewer_source_term', true );

		$author_source_name   = '';
		$reviewer_source_name = '';

		if ( $author_source ) {
			$term = get_term( $author_source, 'itr_kb_category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$author_source_name = $term->name;
			}
		}

		if ( $reviewer_source ) {
			$term = get_term( $reviewer_source, 'itr_kb_category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$reviewer_source_name = $term->name;
			}
		}

		return array(
			'author_status'        => $author_status,
			'reviewer_status'      => $reviewer_status,
			'author_source_term'   => $author_source,
			'reviewer_source_term' => $reviewer_source,
			'author_source_name'   => $author_source_name,
			'reviewer_source_name' => $reviewer_source_name,
		);
	}

	/**
	 * Temporary store for article IDs collected in pre_delete_term.
	 * Keyed by term_id so concurrent deletions don't collide.
	 *
	 * @var array
	 */
	private static $pending_reapply = array();

	/**
	 * Fired by pre_delete_term for itr_kb_category BEFORE the term is deleted.
	 *
	 * At this point term relationships still exist so we can query
	 * get_objects_in_term() to get all affected article IDs.
	 * We also reset the inheritance status for any article whose
	 * source_term matches the term being deleted, so the re-apply
	 * step that follows can resolve fresh values.
	 *
	 * @param int    $term_id  Term ID being deleted.
	 * @param string $taxonomy Taxonomy name.
	 * @return void
	 */
	public static function on_category_pre_delete( $term_id, $taxonomy ) {
		if ( 'itr_kb_category' !== $taxonomy ) {
			return;
		}

		$term_id = (int) $term_id;

		// Get the term object to check if it has a parent.
		$term   = get_term( $term_id, 'itr_kb_category' );
		$parent = ( $term && ! is_wp_error( $term ) ) ? (int) $term->parent : 0;

		// Collect all article IDs assigned to this term.
		$object_ids = get_objects_in_term( $term_id, 'itr_kb_category' );
		if ( is_wp_error( $object_ids ) || empty( $object_ids ) ) {
			self::$pending_reapply[ $term_id ] = array();
			return;
		}

		$affected = array();

		foreach ( $object_ids as $post_id ) {
			$post_id = (int) $post_id;

			if ( get_post_type( $post_id ) !== 'itr_kb_article' ) {
				continue;
			}

			// ── Reassign to parent if article has ONLY this one category ────
			// We do this ourselves rather than relying on WordPress's built-in
			// reassignment which is unreliable for custom taxonomies.
			if ( $parent > 0 ) {
				$all_terms = wp_get_object_terms( $post_id, 'itr_kb_category', array( 'fields' => 'ids' ) );
				if ( ! is_wp_error( $all_terms ) && array( $term_id ) === array_map( 'intval', $all_terms ) ) {
					// Article has only this child term — assign parent before deletion.
					wp_set_object_terms( $post_id, $parent, 'itr_kb_category' );
				}
			}

			// ── Reset inheritance status if sourced from this term ──────────
			$reset_author   = false;
			$reset_reviewer = false;

			$author_source = (int) get_post_meta( $post_id, '_itr_kb_author_source_term', true );
			if ( $author_source === $term_id ) {
				delete_post_meta( $post_id, '_itr_kb_author_status' );
				delete_post_meta( $post_id, '_itr_kb_author_source_term' );
				$reset_author = true;
			}

			$reviewer_source = (int) get_post_meta( $post_id, '_itr_kb_reviewer_source_term', true );
			if ( $reviewer_source === $term_id ) {
				delete_post_meta( $post_id, '_itr_kb_reviewer_status' );
				delete_post_meta( $post_id, '_itr_kb_reviewer_source_term' );
				$reset_reviewer = true;
			}

			if ( $reset_author || $reset_reviewer ) {
				$affected[] = $post_id;
			}
		}

		self::$pending_reapply[ $term_id ] = $affected;
	}

	/**
	 * Fired by delete_itr_kb_category AFTER the term is deleted.
	 *
	 * Re-applies inheritance for all articles collected in pre_delete.
	 * The deleted term no longer exists so the engine will automatically
	 * fall back to a parent category or return empty if none found.
	 *
	 * Processes in batches of 50 to stay performant on large sites.
	 *
	 * @param int $term_id Term ID that was deleted.
	 * @return void
	 */
	public static function on_category_deleted( $term_id ) {
		$term_id  = (int) $term_id;
		$affected = self::$pending_reapply[ $term_id ] ?? array();

		if ( empty( $affected ) ) {
			unset( self::$pending_reapply[ $term_id ] );
			return;
		}

		// Process in batches.
		$batches = array_chunk( $affected, 50 );
		foreach ( $batches as $batch ) {
			foreach ( $batch as $post_id ) {
				self::apply( (int) $post_id );
			}
		}

		unset( self::$pending_reapply[ $term_id ] );
	}

	/**
	 * Build the human-readable badge text for a given status + source name.
	 *
	 * @param string $status      'manual' | 'inherited' | 'empty'.
	 * @param string $source_name Category name that is the source (if inherited).
	 * @return string
	 */
	public static function badge_text( $status, $source_name = '' ) {
		if ( 'manual' === $status ) {
			return esc_html__( 'Manually set', 'itr-knowledgebase' );
		}
		if ( 'inherited' === $status && $source_name ) {
			/* translators: %s: category name */
			return esc_html( sprintf( __( 'Inherited from: %s', 'itr-knowledgebase' ), $source_name ) );
		}
		return '';
	}
}