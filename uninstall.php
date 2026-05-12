<?php
/**
 * Fired when the plugin is uninstalled (deleted).
 *
 * IMPORTANT:
 * All plugin data — articles, authors, categories, tags, and settings —
 * are intentionally preserved on uninstall.
 *
 * This ensures that reinstalling the plugin restores everything exactly
 * as it was with zero data loss.
 *
 * If a full data wipe is ever needed, it must be done manually via
 * the plugin settings before uninstalling.
 *
 * @package ITR_Knowledgebase
 */

// Exit if accessed directly or not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Nothing to do — all data and settings are preserved intentionally.