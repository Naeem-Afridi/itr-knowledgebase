<?php
/**
 * Fired during plugin activation.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes
 */

namespace ITR_Knowledgebase\Includes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Activator
 *
 * Handles all logic that runs during plugin activation.
 */
class ITR_KB_Activator {

	/**
	 * Run on plugin activation.
	 *
	 * - Creates default options.
	 * - Flushes rewrite rules.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_default_options();
		self::set_activation_flag();

		// Flush rewrite rules after CPTs are registered.
		flush_rewrite_rules();
	}

	/**
	 * Create default plugin options if not already set.
	 *
	 * @return void
	 */
	private static function create_default_options() {
		$defaults = array(
			'itr_kb_version'          => ITR_KB_VERSION,
			'itr_kb_slug'             => 'knowledgebase',
			'itr_kb_category_slug'    => 'kb-category',
			'itr_kb_search_enabled'   => true,
			'itr_kb_toc_enabled'      => true,
			'itr_kb_breadcrumb_enabled' => true,
			'itr_kb_print_enabled'    => true,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
	}

	/**
	 * Set a flag to indicate the plugin was just activated.
	 * Used to trigger setup notices or redirects.
	 *
	 * @return void
	 */
	private static function set_activation_flag() {
		update_option( 'itr_kb_activated', true );
	}
}