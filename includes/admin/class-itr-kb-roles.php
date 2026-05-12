<?php
/**
 * Role-based access control.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/admin
 */

namespace ITR_Knowledgebase\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Roles
 *
 * Registers custom capabilities for KB content management.
 *
 * Capabilities added:
 * - edit_itr_kb_article           : Edit a single article
 * - edit_itr_kb_articles          : Edit articles (list view)
 * - edit_others_itr_kb_articles   : Edit other users' articles
 * - publish_itr_kb_articles       : Publish articles
 * - read_private_itr_kb_articles  : Read private articles
 * - delete_itr_kb_article         : Delete a single article
 * - delete_itr_kb_articles        : Delete articles
 * - delete_others_itr_kb_articles : Delete others' articles
 * - delete_published_itr_kb_articles : Delete published articles
 * - manage_itr_kb_categories      : Manage categories/tags
 * - edit_itr_kb_author            : Edit single author
 * - edit_itr_kb_authors           : Edit authors (list view)
 * - publish_itr_kb_authors        : Publish author profiles
 * - delete_itr_kb_author          : Delete single author
 * - delete_itr_kb_authors         : Delete authors
 */
class ITR_KB_Roles {

	/**
	 * All plugin capabilities mapped to WP roles.
	 *
	 * @var array
	 */
	private $capabilities = array(
		'administrator' => array(
			// Article caps.
			'edit_itr_kb_article'                  => true,
			'edit_itr_kb_articles'                 => true,
			'edit_others_itr_kb_articles'          => true,
			'publish_itr_kb_articles'              => true,
			'read_private_itr_kb_articles'         => true,
			'delete_itr_kb_article'                => true,
			'delete_itr_kb_articles'               => true,
			'delete_others_itr_kb_articles'        => true,
			'delete_published_itr_kb_articles'     => true,
			// Category/tag caps.
			'manage_itr_kb_categories'             => true,
			// Author caps.
			'edit_itr_kb_author'                   => true,
			'edit_itr_kb_authors'                  => true,
			'publish_itr_kb_authors'               => true,
			'delete_itr_kb_author'                 => true,
			'delete_itr_kb_authors'                => true,
		),
		'editor'        => array(
			'edit_itr_kb_article'                  => true,
			'edit_itr_kb_articles'                 => true,
			'edit_others_itr_kb_articles'          => true,
			'publish_itr_kb_articles'              => true,
			'read_private_itr_kb_articles'         => true,
			'delete_itr_kb_article'                => true,
			'delete_itr_kb_articles'               => true,
			'delete_others_itr_kb_articles'        => true,
			'delete_published_itr_kb_articles'     => true,
			'manage_itr_kb_categories'             => true,
			'edit_itr_kb_author'                   => true,
			'edit_itr_kb_authors'                  => true,
			'publish_itr_kb_authors'               => true,
			'delete_itr_kb_author'                 => true,
			'delete_itr_kb_authors'                => true,
		),
		'author'        => array(
			'edit_itr_kb_article'                  => true,
			'edit_itr_kb_articles'                 => true,
			'publish_itr_kb_articles'              => true,
			'delete_itr_kb_article'                => true,
			'delete_itr_kb_articles'               => true,
			'delete_published_itr_kb_articles'     => true,
		),
		'contributor'   => array(
			'edit_itr_kb_article'                  => true,
			'edit_itr_kb_articles'                 => true,
		),
	);

	/**
	 * Register capabilities on admin_init.
	 * Runs once and stores a flag to avoid re-running on every request.
	 *
	 * @return void
	 */
	public function register_roles() {
		// Only run if caps haven't been added yet or plugin was updated.
		if ( get_option( 'itr_kb_caps_version' ) === ITR_KB_VERSION ) {
			return;
		}

		$this->add_capabilities();

		update_option( 'itr_kb_caps_version', ITR_KB_VERSION );
	}

	/**
	 * Add capabilities to roles.
	 *
	 * @return void
	 */
	public function add_capabilities() {
		foreach ( $this->capabilities as $role_name => $caps ) {
			$role = get_role( $role_name );

			if ( ! $role ) {
				continue;
			}

			foreach ( $caps as $cap => $grant ) {
				$role->add_cap( $cap, $grant );
			}
		}
	}

	/**
	 * Remove all plugin capabilities from all roles.
	 * Called on plugin uninstall.
	 *
	 * @return void
	 */
	public static function remove_capabilities() {
		$all_caps = array(
			'edit_itr_kb_article',
			'edit_itr_kb_articles',
			'edit_others_itr_kb_articles',
			'publish_itr_kb_articles',
			'read_private_itr_kb_articles',
			'delete_itr_kb_article',
			'delete_itr_kb_articles',
			'delete_others_itr_kb_articles',
			'delete_published_itr_kb_articles',
			'manage_itr_kb_categories',
			'edit_itr_kb_author',
			'edit_itr_kb_authors',
			'publish_itr_kb_authors',
			'delete_itr_kb_author',
			'delete_itr_kb_authors',
		);

		$role_names = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

		foreach ( $role_names as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $all_caps as $cap ) {
				$role->remove_cap( $cap );
			}
		}

		delete_option( 'itr_kb_caps_version' );
	}
}