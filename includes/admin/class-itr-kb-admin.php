<?php
/**
 * Admin core class.
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
 * Class ITR_KB_Admin
 *
 * Handles admin asset enqueueing.
 */
class ITR_KB_Admin {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_styles( $hook ) {
		if ( ! $this->is_kb_admin_page( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'itr-kb-admin',
			ITR_KB_URL . 'assets/css/itr-kb-admin.css',
			array(),
			$this->version
		);

		// WordPress media uploader styles.
		wp_enqueue_media();
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! $this->is_kb_admin_page( $hook ) ) {
			return;
		}

		// jQuery UI Sortable for drag-drop.
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'itr-kb-admin',
			ITR_KB_URL . 'assets/js/itr-kb-admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			$this->version,
			true
		);

		wp_localize_script(
			'itr-kb-admin',
			'itrKbAdmin',
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'itr_kb_admin_nonce' ),
				'categoryOrderNonce'   => wp_create_nonce( 'itr_kb_category_order' ),
				'clearOverrideNonce'   => wp_create_nonce( 'itr_kb_clear_override' ),
				'uploadChunkNonce'     => wp_create_nonce( 'itr_kb_upload_chunk' ),
				'mediaTitle'           => esc_html__( 'Select Category Image', 'itr-knowledgebase' ),
				'mediaButton'          => esc_html__( 'Use this image', 'itr-knowledgebase' ),
				'confirmDelete'        => esc_html__( 'Are you sure you want to delete this?', 'itr-knowledgebase' ),
				'importSuccess'        => esc_html__( 'Import completed successfully.', 'itr-knowledgebase' ),
				'importError'          => esc_html__( 'Import failed. Please check your file.', 'itr-knowledgebase' ),
				'strings'              => array(
					'manuallySet'     => esc_html__( 'Manually set', 'itr-knowledgebase' ),
					'clearingOverride' => esc_html__( 'Clearing…', 'itr-knowledgebase' ),
				),
			)
		);
	}

	/**
	 * Check if the current page is a KB admin page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool
	 */
	private function is_kb_admin_page( $hook ) {
		global $post_type;

		$kb_post_types = array( 'itr_kb_article', 'itr_kb_author' );
		$kb_hooks      = array(
			'itr_kb_article_page_itr-kb-import-export',
			'itr_kb_article_page_itr-kb-settings',
		);

		if ( in_array( $post_type, $kb_post_types, true ) ) {
			return true;
		}

		if ( in_array( $hook, $kb_hooks, true ) ) {
			return true;
		}

		// Also load on taxonomy edit pages.
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->taxonomy, array( 'itr_kb_category', 'itr_kb_tag' ), true ) ) {
			return true;
		}

		return false;
	}
}