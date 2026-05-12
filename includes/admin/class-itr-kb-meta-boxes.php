<?php
/**
 * Meta boxes for KB Articles.
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
 * Class ITR_KB_Meta_Boxes
 */
class ITR_KB_Meta_Boxes {

	/**
	 * Register all meta boxes.
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'itr_kb_author_reviewer',
			esc_html__( 'Author & Reviewers', 'itr-knowledgebase' ),
			array( $this, 'render_author_reviewer_box' ),
			array( 'itr_kb_article', 'post' ),
			'side',
			'high'
		);

		add_meta_box(
			'itr_kb_article_options',
			esc_html__( 'Article Options', 'itr-knowledgebase' ),
			array( $this, 'render_article_options_box' ),
			'itr_kb_article',
			'side',
			'default'
		);

		$author_cpt = new ITR_KB_Author_CPT();
		$author_cpt->register_role_meta_box();
	}

	/**
	 * Render Author & Reviewer meta box.
	 */
	public function render_author_reviewer_box( $post ) {
		wp_nonce_field( 'itr_kb_meta_boxes', 'itr_kb_meta_nonce' );

		$author_id    = (int) get_post_meta( $post->ID, '_itr_kb_author_id', true );
		$reviewer_ids = get_post_meta( $post->ID, '_itr_kb_reviewer_ids', true );
		$reviewer_ids = is_array( $reviewer_ids ) ? $reviewer_ids : array();

		$info                = ITR_KB_Inheritance::get_display_info( $post->ID );
		$author_badge_text   = ITR_KB_Inheritance::badge_text( $info['author_status'], $info['author_source_name'] );
		$reviewer_badge_text = ITR_KB_Inheritance::badge_text( $info['reviewer_status'], $info['reviewer_source_name'] );

		$all_authors   = ITR_KB_Author_CPT::get_all_for_select( 'author' );
		$all_reviewers = ITR_KB_Author_CPT::get_all_for_select( 'reviewer' );
		?>
		<div class="itr-kb-meta-box">

			<!-- Author -->
			<div class="itr-kb-field">
				<label for="itr_kb_author_id">
					<strong><?php esc_html_e( 'Author', 'itr-knowledgebase' ); ?></strong>
				</label>
				<select id="itr_kb_author_id" name="itr_kb_author_id" style="width:100%;margin-top:5px;">
					<option value=""><?php esc_html_e( '— None —', 'itr-knowledgebase' ); ?></option>
					<?php foreach ( $all_authors as $id => $name ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $author_id, (int) $id ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<div class="itr-kb-inheritance-badge-wrap" style="margin-top:5px;min-height:20px;">
					<span
						id="itr-kb-author-status-badge"
						class="itr-kb-status-badge itr-kb-status-badge--<?php echo esc_attr( $info['author_status'] ); ?>"
						data-status="<?php echo esc_attr( $info['author_status'] ); ?>"
						<?php echo $author_badge_text ? '' : 'style="display:none;"'; ?>
					><?php echo esc_html( $author_badge_text ); ?></span>

					<a
						href="#"
						class="itr-kb-clear-override"
						id="itr-kb-author-clear-override"
						data-field="author"
						data-post-id="<?php echo esc_attr( $post->ID ); ?>"
						<?php echo 'manual' === $info['author_status'] ? '' : 'style="display:none;"'; ?>
					><?php esc_html_e( 'Clear Override', 'itr-knowledgebase' ); ?></a>
				</div>

				<input type="hidden" id="_itr_kb_author_changed" name="_itr_kb_author_changed" value="0" />
			</div>

			<hr style="margin:12px 0;" />

			<!-- Reviewers -->
			<div class="itr-kb-field">
				<label>
					<strong><?php esc_html_e( 'Reviewers', 'itr-knowledgebase' ); ?></strong>
				</label>
				<div style="margin-top:5px;">
					<?php if ( ! empty( $all_reviewers ) ) : ?>
						<?php foreach ( $all_reviewers as $id => $name ) : ?>
							<label style="display:block;margin-bottom:4px;">
								<input
									type="checkbox"
									name="itr_kb_reviewer_ids[]"
									value="<?php echo esc_attr( $id ); ?>"
									<?php checked( in_array( (string) $id, array_map( 'strval', $reviewer_ids ), true ) ); ?>
								/>
								<?php echo esc_html( $name ); ?>
							</label>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="description">
							<?php
							printf(
								/* translators: %s: link to add new author */
								esc_html__( 'No reviewers found. %s', 'itr-knowledgebase' ),
								'<a href="' . esc_url( admin_url( 'post-new.php?post_type=itr_kb_author' ) ) . '">' . esc_html__( 'Add one', 'itr-knowledgebase' ) . '</a>'
							);
							?>
						</p>
					<?php endif; ?>
				</div>

				<div class="itr-kb-inheritance-badge-wrap" style="margin-top:6px;min-height:20px;">
					<span
						id="itr-kb-reviewer-status-badge"
						class="itr-kb-status-badge itr-kb-status-badge--<?php echo esc_attr( $info['reviewer_status'] ); ?>"
						data-status="<?php echo esc_attr( $info['reviewer_status'] ); ?>"
						<?php echo $reviewer_badge_text ? '' : 'style="display:none;"'; ?>
					><?php echo esc_html( $reviewer_badge_text ); ?></span>

					<a
						href="#"
						class="itr-kb-clear-override"
						id="itr-kb-reviewer-clear-override"
						data-field="reviewer"
						data-post-id="<?php echo esc_attr( $post->ID ); ?>"
						<?php echo 'manual' === $info['reviewer_status'] ? '' : 'style="display:none;"'; ?>
					><?php esc_html_e( 'Clear Override', 'itr-knowledgebase' ); ?></a>
				</div>

				<input type="hidden" id="_itr_kb_reviewer_changed" name="_itr_kb_reviewer_changed" value="0" />
			</div>

		</div>
		<?php
	}

	/**
	 * Render Article Options meta box.
	 */
	public function render_article_options_box( $post ) {
		$is_featured  = get_post_meta( $post->ID, '_itr_kb_featured', true );
		$toc_disabled = get_post_meta( $post->ID, '_itr_kb_toc_disabled', true );
		?>
		<div class="itr-kb-meta-box">
			<div class="itr-kb-field">
				<label>
					<input type="checkbox" name="itr_kb_featured" value="1" <?php checked( $is_featured, '1' ); ?> />
					<strong><?php esc_html_e( 'Mark as Featured', 'itr-knowledgebase' ); ?></strong>
				</label>
				<p class="description"><?php esc_html_e( 'Featured articles appear in the Featured Articles section.', 'itr-knowledgebase' ); ?></p>
			</div>
			<hr style="margin:12px 0;" />
			<div class="itr-kb-field">
				<label>
					<input type="checkbox" name="itr_kb_toc_disabled" value="1" <?php checked( $toc_disabled, '1' ); ?> />
					<strong><?php esc_html_e( 'Disable Table of Contents', 'itr-knowledgebase' ); ?></strong>
				</label>
				<p class="description"><?php esc_html_e( 'Hide TOC for this article only.', 'itr-knowledgebase' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Article Stats meta box (read-only).
	 */
	public function render_article_stats_box( $post ) {
		$view_count     = absint( get_post_meta( $post->ID, '_itr_kb_view_count', true ) );
		$trending_score = absint( get_post_meta( $post->ID, '_itr_kb_trending_score', true ) );
		?>
		<div class="itr-kb-meta-box itr-kb-stats-box">
			<table style="width:100%;border-collapse:collapse;">
				<tr>
					<td style="padding:6px 0;"><span class="dashicons dashicons-visibility" style="color:#666;"></span> <strong><?php esc_html_e( 'Total Views', 'itr-knowledgebase' ); ?></strong></td>
					<td style="text-align:right;"><strong><?php echo esc_html( number_format_i18n( $view_count ) ); ?></strong></td>
				</tr>
				<tr>
					<td style="padding:6px 0;"><span class="dashicons dashicons-chart-line" style="color:#666;"></span> <strong><?php esc_html_e( 'Trending Score', 'itr-knowledgebase' ); ?></strong></td>
					<td style="text-align:right;"><strong><?php echo esc_html( number_format_i18n( $trending_score ) ); ?></strong></td>
				</tr>
			</table>
			<p class="description" style="margin-top:8px;"><?php esc_html_e( 'Stats are updated automatically.', 'itr-knowledgebase' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Save all meta box data (priority 10).
	 * Saves submitted values directly. Inheritance status is handled by
	 * apply_inheritance_on_save() at priority 20.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		if (
			! isset( $_POST['itr_kb_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['itr_kb_meta_nonce'] ) ), 'itr_kb_meta_boxes' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$allowed_types = array( 'itr_kb_article', 'post' );
		if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
			return;
		}

		if ( 'itr_kb_article' === $post->post_type ) {
			if ( ! current_user_can( 'edit_itr_kb_article', $post_id ) ) {
				return;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$author_id = isset( $_POST['itr_kb_author_id'] ) ? absint( $_POST['itr_kb_author_id'] ) : 0;
		update_post_meta( $post_id, '_itr_kb_author_id', $author_id );

		$reviewer_ids = array();
		if ( isset( $_POST['itr_kb_reviewer_ids'] ) && is_array( $_POST['itr_kb_reviewer_ids'] ) ) {
			$reviewer_ids = array_map( 'absint', $_POST['itr_kb_reviewer_ids'] );
		}
		update_post_meta( $post_id, '_itr_kb_reviewer_ids', $reviewer_ids );

		$featured = isset( $_POST['itr_kb_featured'] ) ? '1' : '0';
		update_post_meta( $post_id, '_itr_kb_featured', $featured );

		$toc_disabled = isset( $_POST['itr_kb_toc_disabled'] ) ? '1' : '0';
		update_post_meta( $post_id, '_itr_kb_toc_disabled', $toc_disabled );
	}

	/**
	 * Apply inheritance status after the meta box values are saved (priority 20).
	 *
	 * Reads _itr_kb_author_changed and _itr_kb_reviewer_changed hidden fields
	 * that JS sets to '1' only when the admin actually changes a field value.
	 *
	 * Per field:
	 *   flag=1  → admin changed it → status = 'manual'
	 *   flag=0 + status already 'manual' → keep manual (unchanged)
	 *   flag=0 + status not 'manual'     → re-apply inheritance (new/unchanged articles)
	 */
	public function apply_inheritance_on_save( $post_id, $post ) {
		if (
			! isset( $_POST['itr_kb_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['itr_kb_meta_nonce'] ) ), 'itr_kb_meta_boxes' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$allowed_types = array( 'itr_kb_article', 'post' );
		if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
			return;
		}

		if ( 'itr_kb_article' === $post->post_type ) {
			if ( ! current_user_can( 'edit_itr_kb_article', $post_id ) ) {
				return;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Standard posts have no KB category inheritance — just mark any change as manual.
		if ( 'post' === $post->post_type ) {
			$author_changed   = isset( $_POST['_itr_kb_author_changed'] )   && '1' === sanitize_text_field( $_POST['_itr_kb_author_changed'] );
			$reviewer_changed = isset( $_POST['_itr_kb_reviewer_changed'] ) && '1' === sanitize_text_field( $_POST['_itr_kb_reviewer_changed'] );
			if ( $author_changed ) {
				update_post_meta( $post_id, '_itr_kb_author_status', 'manual' );
			}
			if ( $reviewer_changed ) {
				update_post_meta( $post_id, '_itr_kb_reviewer_status', 'manual' );
			}
			return;
		}

		$author_changed   = isset( $_POST['_itr_kb_author_changed'] )   && '1' === sanitize_text_field( $_POST['_itr_kb_author_changed'] );
		$reviewer_changed = isset( $_POST['_itr_kb_reviewer_changed'] ) && '1' === sanitize_text_field( $_POST['_itr_kb_reviewer_changed'] );

		$current_author_status   = get_post_meta( $post_id, '_itr_kb_author_status', true );
		$current_reviewer_status = get_post_meta( $post_id, '_itr_kb_reviewer_status', true );

		// ── Author ───────────────────────────────────────────────────────────────
		if ( $author_changed ) {
			update_post_meta( $post_id, '_itr_kb_author_status', 'manual' );
			delete_post_meta( $post_id, '_itr_kb_author_source_term' );
		} elseif ( 'manual' !== $current_author_status ) {
			$resolved   = ITR_KB_Inheritance::resolve( $post_id );
			$new_status = $resolved['author_id'] ? 'inherited' : 'empty';
			update_post_meta( $post_id, '_itr_kb_author_id', $resolved['author_id'] );
			update_post_meta( $post_id, '_itr_kb_author_status', $new_status );
			update_post_meta( $post_id, '_itr_kb_author_source_term', $resolved['author_source_term'] );
		}

		// ── Reviewer ─────────────────────────────────────────────────────────────
		if ( $reviewer_changed ) {
			update_post_meta( $post_id, '_itr_kb_reviewer_status', 'manual' );
			delete_post_meta( $post_id, '_itr_kb_reviewer_source_term' );
		} elseif ( 'manual' !== $current_reviewer_status ) {
			$resolved   = ITR_KB_Inheritance::resolve( $post_id );
			$new_status = ! empty( $resolved['reviewer_ids'] ) ? 'inherited' : 'empty';
			update_post_meta( $post_id, '_itr_kb_reviewer_ids', $resolved['reviewer_ids'] );
			update_post_meta( $post_id, '_itr_kb_reviewer_status', $new_status );
			update_post_meta( $post_id, '_itr_kb_reviewer_source_term', $resolved['reviewer_source_term'] );
		}
	}

	/**
	 * AJAX: clear a manual override and restore the inherited value.
	 * Returns updated badge info and new field values for the JS to refresh the UI.
	 */
	public function ajax_clear_override() {
		check_ajax_referer( 'itr_kb_clear_override', 'nonce' );

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$field   = sanitize_key( $_POST['field'] ?? '' );

		if ( ! $post_id || ! in_array( $field, array( 'author', 'reviewer' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'itr-knowledgebase' ) ) );
		}

		$post_type = get_post_type( $post_id );
		$allowed_types = array( 'itr_kb_article', 'post' );
		if ( ! in_array( $post_type, $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post.', 'itr-knowledgebase' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'itr-knowledgebase' ) ) );
		}

		ITR_KB_Inheritance::clear_override( $post_id, $field );

		$info = ITR_KB_Inheritance::get_display_info( $post_id );

		if ( 'author' === $field ) {
			wp_send_json_success( array(
				'field'      => 'author',
				'status'     => $info['author_status'],
				'badge_text' => ITR_KB_Inheritance::badge_text( $info['author_status'], $info['author_source_name'] ),
				'author_id'  => (int) get_post_meta( $post_id, '_itr_kb_author_id', true ),
			) );
		} else {
			$reviewer_ids = get_post_meta( $post_id, '_itr_kb_reviewer_ids', true );
			wp_send_json_success( array(
				'field'        => 'reviewer',
				'status'       => $info['reviewer_status'],
				'badge_text'   => ITR_KB_Inheritance::badge_text( $info['reviewer_status'], $info['reviewer_source_name'] ),
				'reviewer_ids' => is_array( $reviewer_ids ) ? array_map( 'intval', $reviewer_ids ) : array(),
			) );
		}
	}
}