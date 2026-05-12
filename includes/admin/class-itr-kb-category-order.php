<?php
/**
 * Category drag-drop ordering.
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
 * Class ITR_KB_Category_Order
 *
 * Handles drag-and-drop reordering of KB categories.
 * Order is stored in term meta: itr_kb_category_order.
 */
class ITR_KB_Category_Order {

	/**
	 * Save category order via AJAX.
	 *
	 * Expected POST data:
	 * - nonce      : security nonce
	 * - order      : JSON array of term IDs in new order
	 *
	 * @return void
	 */
	public function save_order() {
		// Verify nonce.
		ITR_KB_Security::verify_ajax_nonce(
			isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '',
			'itr_kb_category_order'
		);

		// Check capability.
		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Permission denied.', 'itr-knowledgebase' ) ),
				403
			);
		}

		// Validate and decode order array.
		if ( ! isset( $_POST['order'] ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'No order data received.', 'itr-knowledgebase' ) )
			);
		}

		$order = json_decode( sanitize_text_field( wp_unslash( $_POST['order'] ) ), true );

		if ( ! is_array( $order ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Invalid order data.', 'itr-knowledgebase' ) )
			);
		}

		// Save each term's order position.
		foreach ( $order as $position => $term_id ) {
			$term_id  = absint( $term_id );
			$position = absint( $position );

			if ( $term_id > 0 ) {
				update_term_meta( $term_id, 'itr_kb_category_order', $position );
			}
		}

		wp_send_json_success(
			array( 'message' => esc_html__( 'Category order saved.', 'itr-knowledgebase' ) )
		);
	}

	/**
	 * Get categories sorted by custom order.
	 *
	 * @param int $parent Parent term ID. Default 0 (top level).
	 * @return array Sorted array of WP_Term objects.
	 */
	public static function get_ordered_categories( $parent = 0 ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'itr_kb_category',
				'hide_empty' => false,
				'parent'     => absint( $parent ),
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		// Sort by custom order meta.
		usort(
			$terms,
			function ( $a, $b ) {
				$order_a = (int) get_term_meta( $a->term_id, 'itr_kb_category_order', true );
				$order_b = (int) get_term_meta( $b->term_id, 'itr_kb_category_order', true );
				return $order_a <=> $order_b;
			}
		);

		return $terms;
	}

	/**
	 * Get full category tree with children, sorted by order.
	 *
	 * @param int $parent Parent term ID. Default 0.
	 * @return array Nested array of term objects with 'children' key.
	 */
	public static function get_category_tree( $parent = 0 ) {
		$terms = self::get_ordered_categories( $parent );
		$tree  = array();

		foreach ( $terms as $term ) {
			$term->children = self::get_category_tree( $term->term_id );
			$tree[]         = $term;
		}

		return $tree;
	}
}