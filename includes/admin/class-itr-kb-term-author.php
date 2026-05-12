<?php
/**
 * Author & Reviewer fields for KB Category taxonomy screens.
 *
 * Adds the same Author dropdown and Reviewer checkboxes that exist on
 * articles to every category Add / Edit screen. Saves values as term meta.
 * Triggers backfill when values actually change.
 *
 * Term meta keys:
 *   itr_kb_term_author_id    — single author post ID
 *   itr_kb_term_reviewer_ids — array of reviewer post IDs
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/admin
 */

namespace ITR_Knowledgebase\Admin;

use ITR_Knowledgebase\PostTypes\ITR_KB_Author_CPT;
use ITR_Knowledgebase\Includes\ITR_KB_Inheritance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Term_Author
 *
 * Attaches author/reviewer fields to the itr_kb_category taxonomy screens.
 */
class ITR_KB_Term_Author {

	/**
	 * Taxonomy key.
	 *
	 * @var string
	 */
	const TAXONOMY = 'itr_kb_category';

	/**
	 * Register all WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Fields on the Add New Category form.
		add_action( self::TAXONOMY . '_add_form_fields', array( $this, 'add_form_fields' ) );

		// Fields on the Edit Category form.
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'edit_form_fields' ) );

		// Save term meta after create.
		add_action( 'created_' . self::TAXONOMY, array( $this, 'save_term_meta' ) );

		// Save term meta after edit — also triggers backfill if values changed.
		add_action( 'edited_' . self::TAXONOMY, array( $this, 'save_term_meta' ) );
	}

	/**
	 * Render author & reviewer fields on the Add New Category form.
	 *
	 * @return void
	 */
	public function add_form_fields() {
		$all_authors   = ITR_KB_Author_CPT::get_all_for_select( 'author' );
		$all_reviewers = ITR_KB_Author_CPT::get_all_for_select( 'reviewer' );

		wp_nonce_field( 'itr_kb_term_author_meta', 'itr_kb_term_author_nonce' );
		?>

		<!-- Default Author -->
		<div class="form-field">
			<label for="itr_kb_term_author_id">
				<?php esc_html_e( 'Default Author', 'itr-knowledgebase' ); ?>
			</label>
			<select id="itr_kb_term_author_id" name="itr_kb_term_author_id" style="width:100%;">
				<option value=""><?php esc_html_e( '— None —', 'itr-knowledgebase' ); ?></option>
				<?php foreach ( $all_authors as $id => $name ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>">
						<?php echo esc_html( $name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p>
				<?php esc_html_e( 'Articles in this category will inherit this author if they do not have one set manually.', 'itr-knowledgebase' ); ?>
			</p>
		</div>

		<!-- Default Reviewers -->
		<div class="form-field">
			<label>
				<strong><?php esc_html_e( 'Default Reviewers', 'itr-knowledgebase' ); ?></strong>
			</label>
			<div style="margin-top:6px;">
				<?php if ( ! empty( $all_reviewers ) ) : ?>
					<?php foreach ( $all_reviewers as $id => $name ) : ?>
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="itr_kb_term_reviewer_ids[]"
								value="<?php echo esc_attr( $id ); ?>"
							/>
							<?php echo esc_html( $name ); ?>
						</label>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="description">
						<?php esc_html_e( 'No reviewers found.', 'itr-knowledgebase' ); ?>
					</p>
				<?php endif; ?>
			</div>
			<p>
				<?php esc_html_e( 'Articles in this category will inherit these reviewers if they do not have any set manually.', 'itr-knowledgebase' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render author & reviewer fields on the Edit Category form.
	 *
	 * @param \WP_Term $term Current term being edited.
	 * @return void
	 */
	public function edit_form_fields( $term ) {
		$saved_author_id    = (int) get_term_meta( $term->term_id, 'itr_kb_term_author_id', true );
		$saved_reviewer_ids = get_term_meta( $term->term_id, 'itr_kb_term_reviewer_ids', true );
		$saved_reviewer_ids = is_array( $saved_reviewer_ids ) ? array_map( 'strval', $saved_reviewer_ids ) : array();

		$all_authors   = ITR_KB_Author_CPT::get_all_for_select( 'author' );
		$all_reviewers = ITR_KB_Author_CPT::get_all_for_select( 'reviewer' );

		wp_nonce_field( 'itr_kb_term_author_meta', 'itr_kb_term_author_nonce' );
		?>

		<!-- Default Author -->
		<tr class="form-field">
			<th scope="row">
				<label for="itr_kb_term_author_id">
					<?php esc_html_e( 'Default Author', 'itr-knowledgebase' ); ?>
				</label>
			</th>
			<td>
				<select id="itr_kb_term_author_id" name="itr_kb_term_author_id" style="width:100%;max-width:400px;">
					<option value=""><?php esc_html_e( '— None —', 'itr-knowledgebase' ); ?></option>
					<?php foreach ( $all_authors as $id => $name ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $saved_author_id, (int) $id ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Articles in this category will inherit this author if they do not have one set manually.', 'itr-knowledgebase' ); ?>
				</p>
			</td>
		</tr>

		<!-- Default Reviewers -->
		<tr class="form-field">
			<th scope="row">
				<label><?php esc_html_e( 'Default Reviewers', 'itr-knowledgebase' ); ?></label>
			</th>
			<td>
				<?php if ( ! empty( $all_reviewers ) ) : ?>
					<?php foreach ( $all_reviewers as $id => $name ) : ?>
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="itr_kb_term_reviewer_ids[]"
								value="<?php echo esc_attr( $id ); ?>"
								<?php checked( in_array( (string) $id, $saved_reviewer_ids, true ) ); ?>
							/>
							<?php echo esc_html( $name ); ?>
						</label>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="description">
						<?php esc_html_e( 'No reviewers found.', 'itr-knowledgebase' ); ?>
					</p>
				<?php endif; ?>
				<p class="description" style="margin-top:6px;">
					<?php esc_html_e( 'Articles in this category will inherit these reviewers if they do not have any set manually.', 'itr-knowledgebase' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term meta when a category is created or edited.
	 * Detects actual value changes before triggering a backfill so that
	 * re-saving without changes never re-processes articles needlessly.
	 *
	 * @param int $term_id Term ID being saved.
	 * @return void
	 */
	public function save_term_meta( $term_id ) {
		// Verify nonce.
		if (
			! isset( $_POST['itr_kb_term_author_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['itr_kb_term_author_nonce'] ) ),
				'itr_kb_term_author_meta'
			)
		) {
			return;
		}

		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			return;
		}

		// Capture old values for change detection.
		$old_author_id    = (int) get_term_meta( $term_id, 'itr_kb_term_author_id', true );
		$old_reviewer_ids = get_term_meta( $term_id, 'itr_kb_term_reviewer_ids', true );
		$old_reviewer_ids = is_array( $old_reviewer_ids ) ? array_map( 'absint', $old_reviewer_ids ) : array();

		// Save new author.
		$new_author_id = isset( $_POST['itr_kb_term_author_id'] ) ? absint( $_POST['itr_kb_term_author_id'] ) : 0;
		update_term_meta( $term_id, 'itr_kb_term_author_id', $new_author_id );

		// Save new reviewers.
		$new_reviewer_ids = array();
		if ( isset( $_POST['itr_kb_term_reviewer_ids'] ) && is_array( $_POST['itr_kb_term_reviewer_ids'] ) ) {
			$new_reviewer_ids = array_map( 'absint', $_POST['itr_kb_term_reviewer_ids'] );
		}
		update_term_meta( $term_id, 'itr_kb_term_reviewer_ids', $new_reviewer_ids );

		// Only backfill when values actually changed.
		sort( $old_reviewer_ids );
		sort( $new_reviewer_ids );

		$author_changed   = $old_author_id !== $new_author_id;
		$reviewer_changed = $old_reviewer_ids !== $new_reviewer_ids;

		if ( $author_changed || $reviewer_changed ) {
			ITR_KB_Inheritance::backfill( $term_id );
		}
	}
}