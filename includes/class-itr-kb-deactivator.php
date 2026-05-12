<?php
/**
 * Fired during plugin deactivation.
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
 * Class ITR_KB_Deactivator
 *
 * Handles all logic that runs during plugin deactivation.
 */
class ITR_KB_Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * - Flushes rewrite rules.
	 * - Does NOT delete data (that is handled by uninstall.php).
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}