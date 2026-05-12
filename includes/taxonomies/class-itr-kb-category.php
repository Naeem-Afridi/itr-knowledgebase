<?php
/**
 * Knowledge Base Category Taxonomy.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/taxonomies
 */

namespace ITR_Knowledgebase\Taxonomies;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Category
 *
 * Registers the hierarchical KB Category taxonomy
 * with support for icons and images via term meta.
 */
class ITR_KB_Category {

	/**
	 * Taxonomy key.
	 *
	 * @var string
	 */
	const TAXONOMY = 'itr_kb_category';

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'                       => _x( 'KB Categories', 'taxonomy general name', 'itr-knowledgebase' ),
			'singular_name'              => _x( 'KB Category', 'taxonomy singular name', 'itr-knowledgebase' ),
			'search_items'               => __( 'Search Categories', 'itr-knowledgebase' ),
			'popular_items'              => __( 'Popular Categories', 'itr-knowledgebase' ),
			'all_items'                  => __( 'All Categories', 'itr-knowledgebase' ),
			'parent_item'                => __( 'Parent Category', 'itr-knowledgebase' ),
			'parent_item_colon'          => __( 'Parent Category:', 'itr-knowledgebase' ),
			'edit_item'                  => __( 'Edit Category', 'itr-knowledgebase' ),
			'update_item'                => __( 'Update Category', 'itr-knowledgebase' ),
			'add_new_item'               => __( 'Add New Category', 'itr-knowledgebase' ),
			'new_item_name'              => __( 'New Category Name', 'itr-knowledgebase' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'itr-knowledgebase' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'itr-knowledgebase' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories', 'itr-knowledgebase' ),
			'not_found'                  => __( 'No categories found.', 'itr-knowledgebase' ),
			'menu_name'                  => __( 'Categories', 'itr-knowledgebase' ),
			'back_to_items'              => __( '← Back to Categories', 'itr-knowledgebase' ),
		);

		$kb_slug  = get_option( 'itr_kb_slug', 'knowledge-base' );
		$cat_slug = get_option( 'itr_kb_category_slug', 'kb-category' );

		// Build full category rewrite slug: knowledge-base/category
		// Each segment is sanitized individually to preserve the slash.
		$cat_segments    = array_filter( explode( '/', $cat_slug ) );
		$sanitized_segs  = array_map( 'sanitize_title', $cat_segments );
		$rewrite_slug    = implode( '/', $sanitized_segs );

		// If category slug doesn't already start with the KB slug, prepend it.
		$first_seg = $sanitized_segs[0] ?? '';
		if ( $first_seg !== sanitize_title( $kb_slug ) ) {
			$rewrite_slug = sanitize_title( $kb_slug ) . '/' . $rewrite_slug;
		}

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'itr-kb-categories',
			'query_var'         => true,
			'rewrite'           => array(
				'slug'         => $rewrite_slug,
				'with_front'   => false,
				'hierarchical' => true,
			),
			'capabilities'      => array(
				'manage_terms' => 'manage_itr_kb_categories',
				'edit_terms'   => 'manage_itr_kb_categories',
				'delete_terms' => 'manage_itr_kb_categories',
				'assign_terms' => 'edit_itr_kb_articles',
			),
		);

		register_taxonomy( self::TAXONOMY, 'itr_kb_article', $args );

		/**
		 * Explicit top-priority rule for parent-only category URLs.
		 *
		 * WordPress has a known bug where hierarchical taxonomy rewrites
		 * can 404 on parent-only URLs (e.g. /knowledge-base/category/parent/)
		 * while child URLs (/parent/child/) work fine.
		 *
		 * Adding this rule with 'top' priority fixes the parent case.
		 * The [^/]+ pattern never matches a slash, so it only handles
		 * single-segment paths and never interferes with child URLs.
		 */
		add_rewrite_rule(
			'^' . $rewrite_slug . '/([^/]+)/?$',
			'index.php?itr_kb_category=$matches[1]',
			'top'
		);

		// Register term meta for icon and image.
		$this->register_term_meta();

		// Add custom fields to taxonomy form.
		add_action( self::TAXONOMY . '_add_form_fields', array( $this, 'add_form_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'edit_form_fields' ) );
		add_action( 'created_' . self::TAXONOMY, array( $this, 'save_term_meta' ) );
		add_action( 'edited_' . self::TAXONOMY, array( $this, 'save_term_meta' ) );
	}

	/**
	 * Register term meta fields.
	 *
	 * @return void
	 */
	private function register_term_meta() {
		register_term_meta(
			self::TAXONOMY,
			'itr_kb_category_icon',
			array(
				'type'              => 'string',
				'description'       => 'Category icon class or dashicons slug.',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);

		register_term_meta(
			self::TAXONOMY,
			'itr_kb_category_image',
			array(
				'type'              => 'integer',
				'description'       => 'Category image attachment ID.',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);

		register_term_meta(
			self::TAXONOMY,
			'itr_kb_category_order',
			array(
				'type'              => 'integer',
				'description'       => 'Custom sort order for categories.',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => false,
			)
		);

		// Author & reviewer term meta — used by the inheritance engine.
		register_term_meta(
			self::TAXONOMY,
			'itr_kb_term_author_id',
			array(
				'type'              => 'integer',
				'description'       => 'Default author (post ID) inherited by articles in this category.',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => false,
			)
		);

		register_term_meta(
			self::TAXONOMY,
			'itr_kb_term_reviewer_ids',
			array(
				'type'              => 'array',
				'description'       => 'Default reviewer post IDs inherited by articles in this category.',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return is_array( $value ) ? array_map( 'absint', $value ) : array();
				},
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Add custom fields to Add New Category form.
	 *
	 * @return void
	 */
	public function add_form_fields() {
		wp_nonce_field( 'itr_kb_category_meta', 'itr_kb_category_nonce' );
		?>
		<div class="form-field">
			<label for="itr_kb_category_icon">
				<?php esc_html_e( 'Category Icon', 'itr-knowledgebase' ); ?>
			</label>
			<input
				type="text"
				id="itr_kb_category_icon"
				name="itr_kb_category_icon"
				value=""
				placeholder="e.g. dashicons-book-alt"
			/>
			<p><?php esc_html_e( 'Enter a Dashicons class or custom icon class.', 'itr-knowledgebase' ); ?></p>
		</div>

		<div class="form-field">
			<label for="itr_kb_category_image">
				<?php esc_html_e( 'Category Image', 'itr-knowledgebase' ); ?>
			</label>
			<input
				type="hidden"
				id="itr_kb_category_image"
				name="itr_kb_category_image"
				value=""
			/>
			<button type="button" class="button itr-kb-upload-image">
				<?php esc_html_e( 'Upload / Choose Image', 'itr-knowledgebase' ); ?>
			</button>
			<div class="itr-kb-image-preview"></div>
		</div>
		<?php
	}

	/**
	 * Add custom fields to Edit Category form.
	 *
	 * @param \WP_Term $term Current taxonomy term object.
	 * @return void
	 */
	public function edit_form_fields( $term ) {
		$icon     = get_term_meta( $term->term_id, 'itr_kb_category_icon', true );
		$image_id = get_term_meta( $term->term_id, 'itr_kb_category_image', true );
		$image_url = $image_id ? wp_get_attachment_image_url( absint( $image_id ), 'thumbnail' ) : '';

		wp_nonce_field( 'itr_kb_category_meta', 'itr_kb_category_nonce' );
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="itr_kb_category_icon">
					<?php esc_html_e( 'Category Icon', 'itr-knowledgebase' ); ?>
				</label>
			</th>
			<td>
				<input
					type="text"
					id="itr_kb_category_icon"
					name="itr_kb_category_icon"
					value="<?php echo esc_attr( $icon ); ?>"
					placeholder="e.g. dashicons-book-alt"
				/>
				<p class="description">
					<?php esc_html_e( 'Enter a Dashicons class or custom icon class.', 'itr-knowledgebase' ); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="itr_kb_category_image">
					<?php esc_html_e( 'Category Image', 'itr-knowledgebase' ); ?>
				</label>
			</th>
			<td>
				<input
					type="hidden"
					id="itr_kb_category_image"
					name="itr_kb_category_image"
					value="<?php echo esc_attr( $image_id ); ?>"
				/>
				<button type="button" class="button itr-kb-upload-image">
					<?php esc_html_e( 'Upload / Choose Image', 'itr-knowledgebase' ); ?>
				</button>
				<div class="itr-kb-image-preview">
					<?php if ( $image_url ) : ?>
						<img src="<?php echo esc_url( $image_url ); ?>" style="max-width:150px;margin-top:10px;" />
					<?php endif; ?>
				</div>
				<p class="description">
					<?php esc_html_e( 'Select or upload a category thumbnail image.', 'itr-knowledgebase' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save custom term meta on create/edit.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_term_meta( $term_id ) {
		// Verify nonce.
		if (
			! isset( $_POST['itr_kb_category_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['itr_kb_category_nonce'] ) ), 'itr_kb_category_meta' )
		) {
			return;
		}

		// Save icon.
		if ( isset( $_POST['itr_kb_category_icon'] ) ) {
			update_term_meta(
				$term_id,
				'itr_kb_category_icon',
				sanitize_text_field( wp_unslash( $_POST['itr_kb_category_icon'] ) )
			);
		}

		// Save image attachment ID.
		if ( isset( $_POST['itr_kb_category_image'] ) ) {
			update_term_meta(
				$term_id,
				'itr_kb_category_image',
				absint( $_POST['itr_kb_category_image'] )
			);
		}
	}

	/**
	 * Get the taxonomy key.
	 *
	 * @return string
	 */
	public static function get_taxonomy() {
		return self::TAXONOMY;
	}

	/**
	 * Get category icon.
	 *
	 * @param int $term_id Term ID.
	 * @return string
	 */
	public static function get_icon( $term_id ) {
		return get_term_meta( absint( $term_id ), 'itr_kb_category_icon', true );
	}

	/**
	 * Get category image URL.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $size    Image size. Default thumbnail.
	 * @return string
	 */
	public static function get_image_url( $term_id, $size = 'thumbnail' ) {
		$image_id = get_term_meta( absint( $term_id ), 'itr_kb_category_image', true );
		if ( ! $image_id ) {
			return '';
		}
		return wp_get_attachment_image_url( absint( $image_id ), $size );
	}
}