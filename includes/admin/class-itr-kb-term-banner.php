<?php
/**
 * Banner fields for KB Category taxonomy screens.
 *
 * Adds four banner pairs (image + URL) to each category Add/Edit screen:
 *   - Desktop TOC Banner
 *   - Desktop Categories Banner
 *   - Mobile Top Banner
 *   - Mobile Bottom Banner
 *
 * Each image uses the WP media uploader (same pattern as existing category
 * image field). Images are stored as attachment IDs. URLs are optional.
 *
 * Term meta keys:
 *   itr_kb_banner_{position}_image  — attachment ID (integer)
 *   itr_kb_banner_{position}_url    — destination URL (string)
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/admin
 */

namespace ITR_Knowledgebase\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Term_Banner
 */
class ITR_KB_Term_Banner {

	const TAXONOMY = 'itr_kb_category';

	/**
	 * Max upload size in bytes (5 MB).
	 */
	const MAX_SIZE = 5242880;

	/**
	 * Banner position definitions.
	 *
	 * @var array
	 */
	private static $banner_positions = array(
		'desktop_toc'        => array(
			'label'       => 'Desktop TOC Banner',
			'description' => 'Shown below the Table of Contents sidebar on desktop.',
		),
		'desktop_categories' => array(
			'label'       => 'Desktop Categories Banner',
			'description' => 'Shown below the Categories sidebar on desktop.',
		),
		'mobile_top'         => array(
			'label'       => 'Mobile Top Banner',
			'description' => 'Shown above the article content on mobile.',
		),
		'mobile_bottom'      => array(
			'label'       => 'Mobile Bottom Banner',
			'description' => 'Shown below the article content on mobile.',
		),
	);

	/**
	 * Register all WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( self::TAXONOMY . '_add_form_fields',  array( $this, 'add_form_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'edit_form_fields' ) );
		add_action( 'created_' . self::TAXONOMY,          array( $this, 'save_term_meta' ) );
		add_action( 'edited_' . self::TAXONOMY,           array( $this, 'save_term_meta' ) );
		add_action( 'wp_ajax_itr_kb_validate_banner',     array( $this, 'ajax_validate_banner' ) );
	}

	/**
	 * Render banner fields on the Add New Category form.
	 *
	 * @return void
	 */
	public function add_form_fields() {
		wp_nonce_field( 'itr_kb_banner_meta', 'itr_kb_banner_nonce' );
		?>
		<div class="itr-kb-banner-fields">
			<h3 class="itr-kb-banner-fields__heading">
				<?php esc_html_e( 'Ad Banners', 'itr-knowledgebase' ); ?>
			</h3>
			<p class="description">
				<?php esc_html_e( 'Set banner images for this category. Articles in this category will inherit these banners. Accepted formats: JPG, PNG, GIF, WebP. Maximum size: 5 MB. All fields are optional.', 'itr-knowledgebase' ); ?>
			</p>
			<?php foreach ( self::$banner_positions as $position => $meta ) : ?>
				<?php $this->render_banner_pair( $position, $meta, 0, 'add' ); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render banner fields on the Edit Category form.
	 *
	 * @param \WP_Term $term Current term being edited.
	 * @return void
	 */
	public function edit_form_fields( $term ) {
		wp_nonce_field( 'itr_kb_banner_meta', 'itr_kb_banner_nonce' );
		?>
		<tr class="form-field itr-kb-banner-row">
			<th scope="row" colspan="2">
				<h3 class="itr-kb-banner-fields__heading" style="margin-bottom:4px;">
					<?php esc_html_e( 'Ad Banners', 'itr-knowledgebase' ); ?>
				</h3>
				<p class="description">
					<?php esc_html_e( 'Accepted formats: JPG, PNG, GIF, WebP. Maximum size: 5 MB. All fields are optional.', 'itr-knowledgebase' ); ?>
				</p>
			</th>
		</tr>
		<?php foreach ( self::$banner_positions as $position => $meta ) : ?>
			<?php $this->render_banner_pair( $position, $meta, $term->term_id, 'edit' ); ?>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Render a single banner pair (image + URL fields).
	 *
	 * @param string $position  Position slug.
	 * @param array  $meta      Label and description for this position.
	 * @param int    $term_id   Current term ID (0 on Add form).
	 * @param string $context   'add' or 'edit'.
	 * @return void
	 */
	private function render_banner_pair( $position, $meta, $term_id, $context ) {
		$image_meta_key = 'itr_kb_banner_' . $position . '_image';
		$url_meta_key   = 'itr_kb_banner_' . $position . '_url';

		$image_id  = $term_id ? (int) get_term_meta( $term_id, $image_meta_key, true ) : 0;
		$url_value = $term_id ? (string) get_term_meta( $term_id, $url_meta_key, true ) : '';
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

		$field_id_image  = 'itr_kb_banner_' . $position . '_image';
		$field_id_url    = 'itr_kb_banner_' . $position . '_url';

		if ( 'edit' === $context ) :
			?>
			<tr class="form-field itr-kb-banner-pair">
				<th scope="row">
					<label><?php echo esc_html( $meta['label'] ); ?></label>
					<p class="description" style="font-weight:normal;"><?php echo esc_html( $meta['description'] ); ?></p>
				</th>
				<td>
					<?php $this->render_pair_fields( $field_id_image, $field_id_url, $image_id, $image_url, $url_value, $position ); ?>
				</td>
			</tr>
			<?php
		else :
			?>
			<div class="form-field itr-kb-banner-pair">
				<label><?php echo esc_html( $meta['label'] ); ?></label>
				<p class="description"><?php echo esc_html( $meta['description'] ); ?></p>
				<?php $this->render_pair_fields( $field_id_image, $field_id_url, $image_id, $image_url, $url_value, $position ); ?>
			</div>
			<?php
		endif;
	}

	/**
	 * Render the inner pair HTML (image uploader + URL input).
	 */
	private function render_pair_fields( $field_id_image, $field_id_url, $image_id, $image_url, $url_value, $position ) {
		?>
		<div class="itr-kb-banner-pair__inner" data-position="<?php echo esc_attr( $position ); ?>">

			<!-- Image upload row -->
			<div class="itr-kb-banner-pair__upload-row">
				<input
					type="hidden"
					id="<?php echo esc_attr( $field_id_image ); ?>"
					name="<?php echo esc_attr( $field_id_image ); ?>"
					value="<?php echo esc_attr( $image_id ); ?>"
					class="itr-kb-banner-image-id"
				/>

				<button type="button" class="button itr-kb-banner-upload">
					<?php esc_html_e( 'Upload / Choose Image', 'itr-knowledgebase' ); ?>
				</button>

				<?php if ( $image_id ) : ?>
					<button type="button" class="button itr-kb-banner-remove" style="margin-left:6px;color:#b32d2e;">
						<?php esc_html_e( 'Remove', 'itr-knowledgebase' ); ?>
					</button>
				<?php else : ?>
					<button type="button" class="button itr-kb-banner-remove" style="margin-left:6px;color:#b32d2e;display:none;">
						<?php esc_html_e( 'Remove', 'itr-knowledgebase' ); ?>
					</button>
				<?php endif; ?>

				<span class="itr-kb-banner-error" style="display:none;color:#b32d2e;margin-left:8px;font-size:12px;"></span>
			</div>

			<!-- Image preview -->
			<div class="itr-kb-banner-pair__preview" style="margin-top:8px;">
				<?php if ( $image_url ) : ?>
					<img
						src="<?php echo esc_url( $image_url ); ?>"
						alt=""
						class="itr-kb-banner-preview-img"
						style="max-width:300px;max-height:100px;object-fit:contain;border:1px solid #ddd;border-radius:4px;padding:4px;"
					/>
				<?php else : ?>
					<img
						src=""
						alt=""
						class="itr-kb-banner-preview-img"
						style="display:none;max-width:300px;max-height:100px;object-fit:contain;border:1px solid #ddd;border-radius:4px;padding:4px;"
					/>
				<?php endif; ?>
			</div>

			<!-- URL input -->
			<div class="itr-kb-banner-pair__url-row" style="margin-top:8px;">
				<label
					for="<?php echo esc_attr( $field_id_url ); ?>"
					style="font-size:12px;color:#666;display:block;margin-bottom:3px;"
				>
					<?php esc_html_e( 'Destination URL (optional — leave empty for non-clickable banner)', 'itr-knowledgebase' ); ?>
				</label>
				<input
					type="url"
					id="<?php echo esc_attr( $field_id_url ); ?>"
					name="<?php echo esc_attr( $field_id_url ); ?>"
					value="<?php echo esc_attr( $url_value ); ?>"
					class="regular-text itr-kb-banner-url"
					placeholder="https://example.com"
				/>
				<span class="itr-kb-banner-url-error" style="display:none;color:#b32d2e;font-size:12px;margin-left:6px;"></span>
			</div>

		</div>
		<?php
	}

	/**
	 * Save banner term meta when a category is created or edited.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_term_meta( $term_id ) {
		if (
			! isset( $_POST['itr_kb_banner_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['itr_kb_banner_nonce'] ) ),
				'itr_kb_banner_meta'
			)
		) {
			return;
		}

		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			return;
		}

		foreach ( array_keys( self::$banner_positions ) as $position ) {
			$image_key = 'itr_kb_banner_' . $position . '_image';
			$url_key   = 'itr_kb_banner_' . $position . '_url';

			// Save image ID.
			$image_id = isset( $_POST[ $image_key ] ) ? absint( $_POST[ $image_key ] ) : 0;
			update_term_meta( $term_id, $image_key, $image_id );

			// Save URL — only if image is set; clear URL if image removed.
			if ( $image_id ) {
				$url = isset( $_POST[ $url_key ] ) ? esc_url_raw( wp_unslash( $_POST[ $url_key ] ) ) : '';
				update_term_meta( $term_id, $url_key, $url );
			} else {
				update_term_meta( $term_id, $url_key, '' );
			}
		}
	}

	/**
	 * AJAX: validate banner image before upload (size + mime type check).
	 * Called by JS before passing to wp.media to provide early user feedback.
	 *
	 * @return void
	 */
	public function ajax_validate_banner() {
		check_ajax_referer( 'itr_kb_banner_validate', 'nonce' );

		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'itr-knowledgebase' ) ) );
		}

		$attachment_id = absint( $_POST['attachment_id'] ?? 0 );
		if ( ! $attachment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attachment.', 'itr-knowledgebase' ) ) );
		}

		// Check mime type.
		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		$mime          = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			wp_send_json_error( array(
				'message' => __( 'Unsupported file format. Please use JPG, PNG, GIF, or WebP.', 'itr-knowledgebase' ),
			) );
		}

		// Check file size.
		$file_path = get_attached_file( $attachment_id );
		if ( $file_path && file_exists( $file_path ) ) {
			$size = filesize( $file_path );
			if ( $size > self::MAX_SIZE ) {
				wp_send_json_error( array(
					'message' => sprintf(
						/* translators: %s: human-readable file size */
						__( 'File too large. Maximum allowed size is %s.', 'itr-knowledgebase' ),
						size_format( self::MAX_SIZE )
					),
				) );
			}
		}

		wp_send_json_success();
	}

	/**
	 * Register all 8 term meta keys with WordPress.
	 * Called from class-itr-kb-category.php register_term_meta().
	 *
	 * @return void
	 */
	public static function register_term_meta() {
		foreach ( array_keys( self::$banner_positions ) as $position ) {
			register_term_meta(
				self::TAXONOMY,
				'itr_kb_banner_' . $position . '_image',
				array(
					'type'              => 'integer',
					'description'       => 'Banner image attachment ID for position: ' . $position,
					'single'            => true,
					'sanitize_callback' => 'absint',
					'show_in_rest'      => false,
				)
			);
			register_term_meta(
				self::TAXONOMY,
				'itr_kb_banner_' . $position . '_url',
				array(
					'type'              => 'string',
					'description'       => 'Banner destination URL for position: ' . $position,
					'single'            => true,
					'sanitize_callback' => 'esc_url_raw',
					'show_in_rest'      => false,
				)
			);
		}
	}
}