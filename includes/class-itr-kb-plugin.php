<?php
/**
 * The core plugin class.
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
 * Class ITR_KB_Plugin
 *
 * Bootstraps the plugin by loading all dependencies,
 * defining hooks, and starting the loader.
 */
class ITR_KB_Plugin {

	/**
	 * The loader that manages hooks.
	 *
	 * @var ITR_KB_Loader $loader
	 */
	protected $loader;

	/**
	 * Plugin slug.
	 *
	 * @var string $plugin_name
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string $version
	 */
	protected $version;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_name = 'itr-knowledgebase';
		$this->version     = ITR_KB_VERSION;

		$this->load_dependencies();
		$this->define_post_type_hooks();
		$this->define_taxonomy_hooks();
		$this->define_admin_hooks();
		$this->define_frontend_hooks();
		$this->define_api_hooks();
		$this->define_elementor_hooks();
	}

	/**
	 * Load all required class files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// Helpers.
		require_once ITR_KB_PATH . 'includes/helpers/class-itr-kb-security.php';
		require_once ITR_KB_PATH . 'includes/helpers/class-itr-kb-utils.php';
		require_once ITR_KB_PATH . 'includes/helpers/class-itr-kb-query.php';

		// Inheritance engine — must load before any admin or frontend class that uses it.
		require_once ITR_KB_PATH . 'includes/class-itr-kb-inheritance.php';

		// Banner engine.
		require_once ITR_KB_PATH . 'includes/class-itr-kb-banner.php';
		require_once ITR_KB_PATH . 'includes/class-itr-kb-shortcodes.php';

		// Post Types.
		require_once ITR_KB_PATH . 'includes/post-types/class-itr-kb-post-type.php';
		require_once ITR_KB_PATH . 'includes/post-types/class-itr-kb-author-cpt.php';

		// Taxonomies.
		require_once ITR_KB_PATH . 'includes/taxonomies/class-itr-kb-category.php';
		require_once ITR_KB_PATH . 'includes/taxonomies/class-itr-kb-tag.php';

		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-list-filters.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-category-reorder-ui.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-admin.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-menu.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-meta-boxes.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-term-author.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-term-banner.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-term-icon.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-category-order.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-bulk-actions.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-import.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-export.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-roles.php';
		require_once ITR_KB_PATH . 'includes/admin/class-itr-kb-settings.php';

		// Frontend.
		require_once ITR_KB_PATH . 'includes/frontend/class-itr-kb-frontend.php';
		require_once ITR_KB_PATH . 'includes/frontend/class-itr-kb-toc.php';
		require_once ITR_KB_PATH . 'includes/frontend/class-itr-kb-breadcrumb.php';
		require_once ITR_KB_PATH . 'includes/frontend/class-itr-kb-navigation.php';
		require_once ITR_KB_PATH . 'includes/frontend/class-itr-kb-search.php';
		require_once ITR_KB_PATH . 'includes/frontend/class-itr-kb-sections.php';

		// REST API.
		require_once ITR_KB_PATH . 'includes/api/class-itr-kb-rest-api.php';

		// Elementor.
		if ( did_action( 'elementor/loaded' ) ) {
			require_once ITR_KB_PATH . 'elementor/class-itr-kb-elementor.php';
		}

		// Loader (must be last).
		$this->loader = new ITR_KB_Loader();
	}

	/**
	 * Register post type hooks.
	 *
	 * @return void
	 */
	private function define_post_type_hooks() {
		$kb_post_type  = new \ITR_Knowledgebase\PostTypes\ITR_KB_Post_Type();
		$kb_author_cpt = new \ITR_Knowledgebase\PostTypes\ITR_KB_Author_CPT();

		$this->loader->add_action( 'init', $kb_post_type, 'register' );
		$this->loader->add_action( 'init', $kb_post_type, 'add_category_permalink_rules' );
		$this->loader->add_action( 'init', $kb_author_cpt, 'register' );

		// Flush rewrite rules whenever the category-in-url option is toggled.
		add_action( 'update_option_itr_kb_category_in_url', function() {
			flush_rewrite_rules();
		} );
	}

	/**
	 * Register taxonomy hooks.
	 *
	 * @return void
	 */
	private function define_taxonomy_hooks() {
		$kb_category = new \ITR_Knowledgebase\Taxonomies\ITR_KB_Category();
		$kb_tag      = new \ITR_Knowledgebase\Taxonomies\ITR_KB_Tag();

		$this->loader->add_action( 'init', $kb_category, 'register' );
		$this->loader->add_action( 'init', $kb_tag, 'register' );
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		$admin          = new \ITR_Knowledgebase\Admin\ITR_KB_Admin( $this->plugin_name, $this->version );
		$menu           = new \ITR_Knowledgebase\Admin\ITR_KB_Menu();
		$meta_boxes     = new \ITR_Knowledgebase\Admin\ITR_KB_Meta_Boxes();
		$term_author    = new \ITR_Knowledgebase\Admin\ITR_KB_Term_Author();
		$category_order = new \ITR_Knowledgebase\Admin\ITR_KB_Category_Order();
		$bulk_actions   = new \ITR_Knowledgebase\Admin\ITR_KB_Bulk_Actions();
		$import = new \ITR_Knowledgebase\Admin\ITR_KB_Import(); // hooks registered in constructor
		$export         = new \ITR_Knowledgebase\Admin\ITR_KB_Export();
		$roles          = new \ITR_Knowledgebase\Admin\ITR_KB_Roles();
		$settings       = new \ITR_Knowledgebase\Admin\ITR_KB_Settings();
		$author_cpt     = new \ITR_Knowledgebase\PostTypes\ITR_KB_Author_CPT();

		// New classes — self-register hooks in constructor.
		new \ITR_Knowledgebase\Admin\ITR_KB_List_Filters();
		new \ITR_Knowledgebase\Admin\ITR_KB_Category_Reorder_UI();

		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $menu, 'register_menus' );
		$this->loader->add_action( 'add_meta_boxes', $meta_boxes, 'register_meta_boxes' );
		$this->loader->add_action( 'wp_ajax_itr_kb_clear_override', $meta_boxes, 'ajax_clear_override' );

		// Priority 10: save raw submitted values.
		$this->loader->add_action( 'save_post', $meta_boxes, 'save_meta_boxes', 10, 2 );
		// Priority 20: apply inheritance status after values are saved.
		$this->loader->add_action( 'save_post', $meta_boxes, 'apply_inheritance_on_save', 20, 2 );

		$this->loader->add_action( 'save_post_itr_kb_author', $author_cpt, 'save_role_meta_box' );
		$this->loader->add_action( 'wp_trash_post',     $author_cpt, 'on_author_removed' );
		$this->loader->add_action( 'before_delete_post', $author_cpt, 'on_author_removed' );
		$this->loader->add_action( 'wp_ajax_itr_kb_category_order', $category_order, 'save_order' );
		// Import AJAX hooks registered in ITR_KB_Import constructor - no loader needed.
		$this->loader->add_action( 'admin_init', $export, 'handle_export' );
		$this->loader->add_action( 'admin_init', $settings, 'register_settings' );
		$this->loader->add_action( 'admin_init', $roles, 'register_roles' );

		// Term author/reviewer fields on category screens.
		$term_author->register_hooks();

		// Category deletion — re-apply inheritance when a category is deleted.
		$this->loader->add_action( 'pre_delete_term',        '\ITR_Knowledgebase\Includes\ITR_KB_Inheritance', 'on_category_pre_delete', 10, 2 );
		$this->loader->add_action( 'delete_itr_kb_category', '\ITR_Knowledgebase\Includes\ITR_KB_Inheritance', 'on_category_deleted',    10, 1 );

		// Term banner fields on category screens.
		$term_banner = new \ITR_Knowledgebase\Admin\ITR_KB_Term_Banner();
		$term_banner->register_hooks();

		$term_icon = new \ITR_Knowledgebase\Admin\ITR_KB_Term_Icon();
		$term_icon->register_hooks();

		// Register banner shortcode.
		// NOTE: moved to define_frontend_hooks so it runs on frontend too.

	}

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	private function define_frontend_hooks() {
		$frontend   = new \ITR_Knowledgebase\Frontend\ITR_KB_Frontend( $this->plugin_name, $this->version );
		$toc        = new \ITR_Knowledgebase\Frontend\ITR_KB_TOC();
		$breadcrumb = new \ITR_Knowledgebase\Frontend\ITR_KB_Breadcrumb();
		$navigation = new \ITR_Knowledgebase\Frontend\ITR_KB_Navigation();
		$search     = new \ITR_Knowledgebase\Frontend\ITR_KB_Search();
		$sections   = new \ITR_Knowledgebase\Frontend\ITR_KB_Sections();

		// Register shortcodes here (not in admin hooks) so they work on the frontend too.
		\ITR_Knowledgebase\Includes\ITR_KB_Banner::register_shortcode();
		\ITR_Knowledgebase\Includes\ITR_KB_Shortcodes::register();

		$this->loader->add_action( 'wp_enqueue_scripts', $frontend, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $frontend, 'enqueue_scripts' );
		$this->loader->add_action( 'elementor/preview/enqueue_styles', $frontend, 'enqueue_styles' );
		$this->loader->add_action( 'pre_get_posts',      $frontend, 'set_articles_per_page' );
		$this->loader->add_filter( 'template_include', $frontend, 'load_templates', 999 );
		$this->loader->add_filter( 'the_content', $toc, 'inject_toc' );
		$this->loader->add_filter( 'the_content', $navigation, 'inject_navigation' );
		$this->loader->add_action( 'wp_ajax_itr_kb_search', $search, 'handle_search' );
		$this->loader->add_action( 'wp_ajax_nopriv_itr_kb_search', $search, 'handle_search' );
		$this->loader->add_action( 'wp_ajax_itr_kb_load_more', $search, 'handle_load_more' );
		$this->loader->add_action( 'wp_ajax_nopriv_itr_kb_load_more', $search, 'handle_load_more' );
	}

	/**
	 * Register REST API hooks.
	 *
	 * @return void
	 */
	private function define_api_hooks() {
		$rest_api = new \ITR_Knowledgebase\API\ITR_KB_Rest_API();
		$this->loader->add_action( 'rest_api_init', $rest_api, 'register_routes' );
	}

	/**
	 * Register Elementor hooks.
	 *
	 * @return void
	 */
	private function define_elementor_hooks() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$elementor = new \ITR_Knowledgebase\Elementor\ITR_KB_Elementor();
		$this->loader->add_action( 'elementor/widgets/register', $elementor, 'register_widgets' );
		$this->loader->add_action( 'elementor/elements/categories_registered', $elementor, 'register_category' );
	}

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Run the loader.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}
}