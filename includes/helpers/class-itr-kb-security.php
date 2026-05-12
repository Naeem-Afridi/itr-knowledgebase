<?php
/**
 * Security helper class.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/helpers
 */

namespace ITR_Knowledgebase\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Security
 *
 * Provides security utility methods used throughout the plugin.
 */
class ITR_KB_Security {

	/**
	 * Verify a nonce and die on failure.
	 *
	 * @param string $nonce  The nonce value.
	 * @param string $action The nonce action string.
	 * @return void
	 */
	public static function verify_nonce( $nonce, $action ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), $action ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'itr-knowledgebase' ),
				esc_html__( 'Security Error', 'itr-knowledgebase' ),
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Verify nonce for AJAX requests and send JSON error on failure.
	 *
	 * @param string $nonce  The nonce value.
	 * @param string $action The nonce action string.
	 * @return void
	 */
	public static function verify_ajax_nonce( $nonce, $action ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), $action ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Security check failed.', 'itr-knowledgebase' ) ),
				403
			);
		}
	}

	/**
	 * Check if the current user has a required capability.
	 *
	 * @param string $capability WordPress capability string.
	 * @return bool
	 */
	public static function current_user_can( $capability ) {
		return current_user_can( $capability );
	}

	/**
	 * Check capability and die if not allowed.
	 *
	 * @param string $capability WordPress capability string.
	 * @return void
	 */
	public static function require_capability( $capability ) {
		if ( ! current_user_can( $capability ) ) {
			wp_die(
				esc_html__( 'You do not have permission to perform this action.', 'itr-knowledgebase' ),
				esc_html__( 'Permission Denied', 'itr-knowledgebase' ),
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Sanitize a text field from request data.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function sanitize_text( $value ) {
		return sanitize_text_field( wp_unslash( $value ) );
	}

	/**
	 * Sanitize a textarea field from request data.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function sanitize_textarea( $value ) {
		return sanitize_textarea_field( wp_unslash( $value ) );
	}

	/**
	 * Sanitize an integer value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function sanitize_int( $value ) {
		return absint( $value );
	}

	/**
	 * Sanitize a URL.
	 *
	 * @param string $value Raw URL.
	 * @return string
	 */
	public static function sanitize_url( $value ) {
		return esc_url_raw( wp_unslash( $value ) );
	}

	/**
	 * Get a sanitized value from $_POST.
	 *
	 * @param string $key     The POST key.
	 * @param string $type    Type: text|textarea|int|url. Default text.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed
	 */
	public static function get_post( $key, $type = 'text', $default = '' ) {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $default;
		}

		$value = $_POST[ $key ]; // phpcs:ignore WordPress.Security.NonceVerification

		switch ( $type ) {
			case 'textarea':
				return self::sanitize_textarea( $value );
			case 'int':
				return self::sanitize_int( $value );
			case 'url':
				return self::sanitize_url( $value );
			default:
				return self::sanitize_text( $value );
		}
	}

	/**
	 * Get a sanitized value from $_GET.
	 *
	 * @param string $key     The GET key.
	 * @param string $type    Type: text|int|url. Default text.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed
	 */
	public static function get_query( $key, $type = 'text', $default = '' ) {
		if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $default;
		}

		$value = $_GET[ $key ]; // phpcs:ignore WordPress.Security.NonceVerification

		switch ( $type ) {
			case 'int':
				return self::sanitize_int( $value );
			case 'url':
				return self::sanitize_url( $value );
			default:
				return self::sanitize_text( $value );
		}
	}
}