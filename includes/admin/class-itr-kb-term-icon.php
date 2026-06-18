<?php
/**
 * Category Icon Admin Field
 *
 * Adds an "Icon Image" field to the KB Category taxonomy
 * so each category can have a custom icon shown in the
 * Category Grid and Category Accordion widgets.
 *
 * Meta key stored: itr_kb_category_icon_id  (attachment ID)
 *
 * @package ITR_Knowledgebase
 */

namespace ITR_Knowledgebase\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ITR_KB_Term_Icon {

	const TAXONOMY = 'itr_kb_category';
	const META_KEY = 'itr_kb_category_icon_id';

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( self::TAXONOMY . '_add_form_fields',  array( $this, 'add_form_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'edit_form_fields' ) );
		add_action( 'created_' . self::TAXONOMY,          array( $this, 'save_meta' ) );
		add_action( 'edited_' . self::TAXONOMY,           array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts',               array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue media uploader scripts on taxonomy edit pages.
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ( $_GET['taxonomy'] ?? '' ) !== self::TAXONOMY ) {
			return;
		}
		wp_enqueue_media();
	}

	/**
	 * Add icon field on Add New Category screen.
	 */
	public function add_form_fields() {
		wp_nonce_field( 'itr_kb_term_icon_save', 'itr_kb_term_icon_nonce' );
		?>
		<div class="form-field">
			<label for="itr_kb_category_icon"><?php esc_html_e( 'Category Icon', 'itr-knowledgebase' ); ?></label>
			<div id="itr-kb-icon-preview" style="margin-bottom:8px;"></div>
			<input type="hidden" id="itr_kb_category_icon_id" name="itr_kb_category_icon_id" value="">
			<button type="button" class="button" id="itr-kb-upload-icon">
				<?php esc_html_e( 'Upload / Select Icon', 'itr-knowledgebase' ); ?>
			</button>
			<button type="button" class="button" id="itr-kb-remove-icon" style="display:none;">
				<?php esc_html_e( 'Remove', 'itr-knowledgebase' ); ?>
			</button>
			<p class="description"><?php esc_html_e( 'Optional icon image shown in Category Grid and Accordion widgets.', 'itr-knowledgebase' ); ?></p>
			<?php $this->icon_uploader_script(); ?>
		</div>
		<?php
	}

	/**
	 * Add icon field on Edit Category screen.
	 *
	 * @param \WP_Term $term Current term.
	 */
	public function edit_form_fields( $term ) {
		$icon_id  = (int) get_term_meta( $term->term_id, self::META_KEY, true );
		$icon_url = $icon_id ? wp_get_attachment_image_url( $icon_id, 'thumbnail' ) : '';
		wp_nonce_field( 'itr_kb_term_icon_save', 'itr_kb_term_icon_nonce' );
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="itr_kb_category_icon_id"><?php esc_html_e( 'Category Icon', 'itr-knowledgebase' ); ?></label>
			</th>
			<td>
				<div id="itr-kb-icon-preview" style="margin-bottom:8px;">
					<?php if ( $icon_url ) : ?>
						<img src="<?php echo esc_url( $icon_url ); ?>" style="max-width:80px;max-height:80px;border-radius:6px;" />
					<?php endif; ?>
				</div>
				<input type="hidden" id="itr_kb_category_icon_id" name="itr_kb_category_icon_id" value="<?php echo esc_attr( $icon_id ); ?>">
				<button type="button" class="button" id="itr-kb-upload-icon">
					<?php esc_html_e( 'Upload / Select Icon', 'itr-knowledgebase' ); ?>
				</button>
				<button type="button" class="button" id="itr-kb-remove-icon" <?php echo $icon_id ? '' : 'style="display:none;"'; ?>>
					<?php esc_html_e( 'Remove', 'itr-knowledgebase' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Optional icon image shown in Category Grid and Accordion widgets.', 'itr-knowledgebase' ); ?></p>
				<?php $this->icon_uploader_script( $icon_id ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output the media uploader JS for the icon field.
	 *
	 * @param int $current_id Currently selected attachment ID.
	 */
	private function icon_uploader_script( $current_id = 0 ) {
		?>
		<script>
		(function($){
			var frame;
			$('#itr-kb-upload-icon').on('click', function(e){
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({ title: '<?php esc_html_e( 'Select Category Icon', 'itr-knowledgebase' ); ?>', multiple: false });
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					$('#itr_kb_category_icon_id').val(att.id);
					$('#itr-kb-icon-preview').html('<img src="'+(att.sizes&&att.sizes.thumbnail?att.sizes.thumbnail.url:att.url)+'" style="max-width:80px;max-height:80px;border-radius:6px;" />');
					$('#itr-kb-remove-icon').show();
				});
				frame.open();
			});
			$('#itr-kb-remove-icon').on('click', function(e){
				e.preventDefault();
				$('#itr_kb_category_icon_id').val('');
				$('#itr-kb-icon-preview').html('');
				$(this).hide();
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save the icon meta on term create/update.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_meta( $term_id ) {
		if ( ! isset( $_POST['itr_kb_term_icon_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['itr_kb_term_icon_nonce'] ), 'itr_kb_term_icon_save' ) ) {
			return;
		}
		$icon_id = absint( $_POST['itr_kb_category_icon_id'] ?? 0 );
		if ( $icon_id ) {
			update_term_meta( $term_id, self::META_KEY, $icon_id );
		} else {
			delete_term_meta( $term_id, self::META_KEY );
		}
	}

	/**
	 * Get the icon URL for a term.
	 *
	 * @param int    $term_id    Term ID.
	 * @param string $size       Image size. Default 'thumbnail'.
	 * @return string Empty string if no icon set.
	 */
	public static function get_icon_url( $term_id, $size = 'thumbnail' ) {
		$icon_id = (int) get_term_meta( absint( $term_id ), self::META_KEY, true );
		if ( ! $icon_id ) {
			return '';
		}
		return (string) wp_get_attachment_image_url( $icon_id, $size );
	}
}
