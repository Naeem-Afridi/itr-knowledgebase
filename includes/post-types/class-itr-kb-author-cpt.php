<?php
/**
 * Author & Reviewer Custom Post Type.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/post-types
 */

namespace ITR_Knowledgebase\PostTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Author_CPT
 *
 * Registers the Author/Reviewer custom post type.
 * These are independent entities, completely separate from WordPress users.
 */
class ITR_KB_Author_CPT {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'itr_kb_author';

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'                  => _x( 'Authors & Reviewers', 'Post type general name', 'itr-knowledgebase' ),
			'singular_name'         => _x( 'Author / Reviewer', 'Post type singular name', 'itr-knowledgebase' ),
			'menu_name'             => _x( 'Authors & Reviewers', 'Admin Menu text', 'itr-knowledgebase' ),
			'name_admin_bar'        => _x( 'Author / Reviewer', 'Add New on Toolbar', 'itr-knowledgebase' ),
			'add_new'               => __( 'Add New', 'itr-knowledgebase' ),
			'add_new_item'          => __( 'Add New Author / Reviewer', 'itr-knowledgebase' ),
			'new_item'              => __( 'New Author / Reviewer', 'itr-knowledgebase' ),
			'edit_item'             => __( 'Edit Author / Reviewer', 'itr-knowledgebase' ),
			'view_item'             => __( 'View Author / Reviewer', 'itr-knowledgebase' ),
			'all_items'             => __( 'All Authors & Reviewers', 'itr-knowledgebase' ),
			'search_items'          => __( 'Search Authors & Reviewers', 'itr-knowledgebase' ),
			'not_found'             => __( 'No authors found.', 'itr-knowledgebase' ),
			'not_found_in_trash'    => __( 'No authors found in Trash.', 'itr-knowledgebase' ),
			'featured_image'        => __( 'Author Photo', 'itr-knowledgebase' ),
			'set_featured_image'    => __( 'Set author photo', 'itr-knowledgebase' ),
			'remove_featured_image' => __( 'Remove author photo', 'itr-knowledgebase' ),
			'use_featured_image'    => __( 'Use as author photo', 'itr-knowledgebase' ),
			'items_list'            => __( 'Authors list', 'itr-knowledgebase' ),
			'items_list_navigation' => __( 'Authors list navigation', 'itr-knowledgebase' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=itr_kb_article',
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array(
				'title',
				'editor',
				'thumbnail',
			),
			'show_in_rest'       => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register hooks for author deletion cleanup.
	 *
	 * @return void
	 */
	public function register_deletion_hooks() {
		// Fires when an author is moved to Trash.
		add_action( 'wp_trash_post', array( $this, 'on_author_removed' ) );
		// Fires before permanent deletion (empty trash).
		add_action( 'before_delete_post', array( $this, 'on_author_removed' ) );
	}

	/**
	 * Clean up all article references when an author/reviewer is trashed or deleted.
	 *
	 * For each affected article:
	 *   - If the article's author was this person → clear it, reset status so
	 *     inheritance can re-apply from the category chain.
	 *   - If this person is in the article's reviewer list → remove them from
	 *     the array, reset reviewer status so inheritance can re-apply.
	 *
	 * Uses direct update_post_meta() (never wp_update_post) to avoid side effects.
	 * Processes in batches of 100 for performance on large sites.
	 *
	 * @param int $post_id The author/reviewer post ID being removed.
	 * @return void
	 */
	public function on_author_removed( $post_id ) {
		// Only act on our author CPT.
		if ( get_post_type( $post_id ) !== self::POST_TYPE ) {
			return;
		}

		$batch = 100;
		$paged = 1;

		// ── Clear author references ───────────────────────────────────────────
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
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'   => '_itr_kb_author_id',
						'value' => $post_id,
					),
				),
			) );

			if ( empty( $query->posts ) ) {
				break;
			}

			foreach ( $query->posts as $article_id ) {
				update_post_meta( $article_id, '_itr_kb_author_id', 0 );
				// Reset status so inheritance can re-apply from category chain.
				delete_post_meta( $article_id, '_itr_kb_author_status' );
				delete_post_meta( $article_id, '_itr_kb_author_source_term' );
				// Re-apply inheritance for author field only.
				\ITR_Knowledgebase\Includes\ITR_KB_Inheritance::apply( $article_id );
			}

			$paged++;
		} while ( $paged <= $query->max_num_pages );

		// ── Remove from reviewer arrays ───────────────────────────────────────
		$paged = 1;

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
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'key'     => '_itr_kb_reviewer_ids',
						'value'   => '"' . $post_id . '"',
						'compare' => 'LIKE',
					),
				),
			) );

			if ( empty( $query->posts ) ) {
				break;
			}

			foreach ( $query->posts as $article_id ) {
				$current = get_post_meta( $article_id, '_itr_kb_reviewer_ids', true );
				$current = is_array( $current ) ? $current : array();
				$updated = array_values( array_filter( $current, function ( $rid ) use ( $post_id ) {
					return (int) $rid !== (int) $post_id;
				} ) );

				update_post_meta( $article_id, '_itr_kb_reviewer_ids', $updated );
				// Reset status so inheritance can re-apply.
				delete_post_meta( $article_id, '_itr_kb_reviewer_status' );
				delete_post_meta( $article_id, '_itr_kb_reviewer_source_term' );
				// Re-apply inheritance for reviewer field only.
				\ITR_Knowledgebase\Includes\ITR_KB_Inheritance::apply( $article_id );
			}

			$paged++;
		} while ( $paged <= $query->max_num_pages );
	}

	/**
	 * Get the post type key.
	 *
	 * @return string
	 */
	public static function get_post_type() {
		return self::POST_TYPE;
	}

	/**
	 * Get authors or reviewers for select dropdowns, filtered by role.
	 *
	 * @param string $role Role: 'author', 'reviewer', or '' for all.
	 * @return array Array of [ post_id => title ] pairs.
	 */
	public static function get_all_for_select( $role = '' ) {
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $role ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				array(
					'key'     => '_itr_kb_person_role',
					'value'   => array( $role, 'both' ),
					'compare' => 'IN',
				),
				// Also include people with no role set yet (legacy records).
				array(
					'key'     => '_itr_kb_person_role',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$posts   = get_posts( $args );
		$options = array();

		foreach ( $posts as $post ) {
			$options[ $post->ID ] = esc_html( $post->post_title );
		}

		return $options;
	}

	/**
	 * Register role meta box on the author CPT edit screen.
	 *
	 * @return void
	 */
	public function register_role_meta_box() {
		add_meta_box(
			'itr_kb_person_role',
			esc_html__( 'Person Role', 'itr-knowledgebase' ),
			array( $this, 'render_role_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render the role meta box.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_role_meta_box( $post ) {
		wp_nonce_field( 'itr_kb_person_role', 'itr_kb_person_role_nonce' );
		$role = get_post_meta( $post->ID, '_itr_kb_person_role', true );
		$role = $role ?: 'author';
		?>
		<div class="itr-kb-meta-box">
			<p style="margin-top:0;color:#646970;font-size:13px;">
				<?php esc_html_e( 'Select the role for this person:', 'itr-knowledgebase' ); ?>
			</p>
			<label style="display:block;margin-bottom:8px;">
				<input type="radio" name="itr_kb_person_role" value="author" <?php checked( $role, 'author' ); ?> />
				<?php esc_html_e( 'Author only', 'itr-knowledgebase' ); ?>
			</label>
			<label style="display:block;margin-bottom:8px;">
				<input type="radio" name="itr_kb_person_role" value="reviewer" <?php checked( $role, 'reviewer' ); ?> />
				<?php esc_html_e( 'Reviewer only', 'itr-knowledgebase' ); ?>
			</label>
			<label style="display:block;">
				<input type="radio" name="itr_kb_person_role" value="both" <?php checked( $role, 'both' ); ?> />
				<?php esc_html_e( 'Both Author & Reviewer', 'itr-knowledgebase' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Save the role meta box value.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_role_meta_box( $post_id ) {
		if (
			! isset( $_POST['itr_kb_person_role_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['itr_kb_person_role_nonce'] ) ), 'itr_kb_person_role' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( get_post_type( $post_id ) !== self::POST_TYPE ) return;

		$allowed = array( 'author', 'reviewer', 'both' );
		$role    = isset( $_POST['itr_kb_person_role'] ) ? sanitize_key( $_POST['itr_kb_person_role'] ) : 'author';

		if ( ! in_array( $role, $allowed, true ) ) {
			$role = 'author';
		}

		update_post_meta( $post_id, '_itr_kb_person_role', $role );
	}
}