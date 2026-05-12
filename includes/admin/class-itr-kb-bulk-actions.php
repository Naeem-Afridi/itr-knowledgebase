<?php
/**
 * Bulk actions for KB articles.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/admin
 */

namespace ITR_Knowledgebase\Admin;

use ITR_Knowledgebase\Helpers\ITR_KB_Security;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Bulk_Actions
 *
 * Adds custom bulk actions to the KB Articles list table.
 *
 * Custom actions:
 * - itr_kb_mark_featured   : Mark selected articles as featured
 * - itr_kb_unmark_featured : Remove featured flag
 */
class ITR_KB_Bulk_Actions {

	/**
	 * Constructor — registers hooks directly.
	 */
	public function __construct() {
		add_filter( 'bulk_actions-edit-itr_kb_article', array( $this, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-itr_kb_article', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );
	}

	/**
	 * Register custom bulk actions.
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array
	 */
	public function register_bulk_actions( $bulk_actions ) {
		$bulk_actions['itr_kb_mark_featured']   = esc_html__( 'Mark as Featured', 'itr-knowledgebase' );
		$bulk_actions['itr_kb_unmark_featured'] = esc_html__( 'Remove from Featured', 'itr-knowledgebase' );
		return $bulk_actions;
	}

	/**
	 * Handle custom bulk actions.
	 *
	 * @param string $redirect_url Redirect URL after action.
	 * @param string $action       Action name.
	 * @param array  $post_ids     Selected post IDs.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_url, $action, $post_ids ) {
		if ( ! in_array( $action, array( 'itr_kb_mark_featured', 'itr_kb_unmark_featured' ), true ) ) {
			return $redirect_url;
		}

		// Verify nonce via WordPress bulk action nonce (already verified by WP core).
		if ( ! current_user_can( 'edit_itr_kb_articles' ) ) {
			return $redirect_url;
		}

		$processed = 0;

		foreach ( $post_ids as $post_id ) {
			$post_id = absint( $post_id );

			if ( 'itr_kb_article' !== get_post_type( $post_id ) ) {
				continue;
			}

			if ( ! current_user_can( 'edit_itr_kb_article', $post_id ) ) {
				continue;
			}

			if ( 'itr_kb_mark_featured' === $action ) {
				update_post_meta( $post_id, '_itr_kb_featured', '1' );
			} elseif ( 'itr_kb_unmark_featured' === $action ) {
				update_post_meta( $post_id, '_itr_kb_featured', '0' );
			}

			$processed++;
		}

		$redirect_url = add_query_arg(
			array(
				'itr_kb_bulk_action' => sanitize_key( $action ),
				'itr_kb_processed'   => $processed,
			),
			$redirect_url
		);

		return $redirect_url;
	}

	/**
	 * Display admin notice after bulk action.
	 *
	 * @return void
	 */
	public function bulk_action_notices() {
		if ( ! isset( $_GET['itr_kb_bulk_action'] ) || ! isset( $_GET['itr_kb_processed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$action    = sanitize_key( $_GET['itr_kb_bulk_action'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$processed = absint( $_GET['itr_kb_processed'] ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( 'itr_kb_mark_featured' === $action ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %d: number of articles updated */
					esc_html( _n( '%d article marked as featured.', '%d articles marked as featured.', $processed, 'itr-knowledgebase' ) ),
					$processed
				)
			);
		} elseif ( 'itr_kb_unmark_featured' === $action ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %d: number of articles updated */
					esc_html( _n( '%d article removed from featured.', '%d articles removed from featured.', $processed, 'itr-knowledgebase' ) ),
					$processed
				)
			);
		}
	}
}