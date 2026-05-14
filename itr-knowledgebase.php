<?php
/**
 * Plugin Name:       ITR Knowledgebase
 * Plugin URI:        https://itr.com/knowledgebase
 * Description:       A powerful, fully customizable Knowledge Base plugin for WordPress with Elementor support.
 * Version:           1.0.0
 * Author:            ITR
 * Author URI:        https://itroadway.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       itr-knowledgebase
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package ITR_Knowledgebase
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
define( 'ITR_KB_VERSION', '1.0.0' );

// Plugin directory path.
define( 'ITR_KB_PATH', plugin_dir_path( __FILE__ ) );

// Plugin directory URL.
define( 'ITR_KB_URL', plugin_dir_url( __FILE__ ) );

// Plugin basename.
define( 'ITR_KB_BASENAME', plugin_basename( __FILE__ ) );

// Plugin minimum WordPress version.
define( 'ITR_KB_MIN_WP', '6.0' );

// Plugin minimum PHP version.
define( 'ITR_KB_MIN_PHP', '7.4' );

/**
 * Check PHP and WordPress version compatibility.
 *
 * @return bool
 */
function itr_kb_check_compatibility() {
	if ( version_compare( PHP_VERSION, ITR_KB_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', 'itr_kb_php_notice' );
		return false;
	}

	if ( version_compare( get_bloginfo( 'version' ), ITR_KB_MIN_WP, '<' ) ) {
		add_action( 'admin_notices', 'itr_kb_wp_notice' );
		return false;
	}

	return true;
}

/**
 * Admin notice for PHP version.
 */
function itr_kb_php_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'ITR Knowledgebase requires PHP 7.4 or higher. Please upgrade your PHP version.', 'itr-knowledgebase' ) .
		'</p></div>';
}

/**
 * Admin notice for WordPress version.
 */
function itr_kb_wp_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'ITR Knowledgebase requires WordPress 6.0 or higher. Please upgrade WordPress.', 'itr-knowledgebase' ) .
		'</p></div>';
}

/**
 * Load required files.
 */
function itr_kb_load_files() {
	require_once ITR_KB_PATH . 'includes/class-itr-kb-activator.php';
	require_once ITR_KB_PATH . 'includes/class-itr-kb-deactivator.php';
	require_once ITR_KB_PATH . 'includes/class-itr-kb-loader.php';
	require_once ITR_KB_PATH . 'includes/class-itr-kb-plugin.php';
}

/**
 * Activation hook.
 */
function itr_kb_activate() {
	require_once ITR_KB_PATH . 'includes/class-itr-kb-activator.php';
	ITR_Knowledgebase\Includes\ITR_KB_Activator::activate();
}

/**
 * Deactivation hook.
 */
function itr_kb_deactivate() {
	require_once ITR_KB_PATH . 'includes/class-itr-kb-deactivator.php';
	ITR_Knowledgebase\Includes\ITR_KB_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'itr_kb_activate' );
register_deactivation_hook( __FILE__, 'itr_kb_deactivate' );

/**
 * Bootstrap the plugin.
 */
function itr_kb_init() {
	if ( ! itr_kb_check_compatibility() ) {
		return;
	}

	itr_kb_load_files();

	$plugin = new ITR_Knowledgebase\Includes\ITR_KB_Plugin();
	$plugin->run();
}

add_action( 'plugins_loaded', 'itr_kb_init' );
