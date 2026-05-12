<?php
/**
 * Elementor integration bootstrap.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor
 */

namespace ITR_Knowledgebase\Elementor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Elementor
 *
 * Bootstraps all Elementor widgets and dynamic tags for the plugin.
 * Requires Elementor Pro for full template design support.
 */
class ITR_KB_Elementor {

	/**
	 * Elementor widget category slug.
	 *
	 * @var string
	 */
	const CATEGORY = 'itr-knowledgebase';

	/**
	 * Register the custom Elementor widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => esc_html__( 'ITR Knowledgebase', 'itr-knowledgebase' ),
				'icon'  => 'eicon-book',
			)
		);
	}

	/**
	 * Register all plugin widgets with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		$this->load_widget_files();

		$widgets = array(
			'ITR_KB_Widget_Search',
			'ITR_KB_Widget_Breadcrumb',
			'ITR_KB_Widget_Category_Tree',
			'ITR_KB_Widget_Category_Grid',
			'ITR_KB_Widget_Article_List',
			'ITR_KB_Widget_Article_Accordion',
			'ITR_KB_Widget_TOC',
			'ITR_KB_Widget_Author_Box',
			'ITR_KB_Widget_Content_Sections',
			'ITR_KB_Widget_Category_Accordion',
		);

		foreach ( $widgets as $widget_class ) {
			$full_class = 'ITR_Knowledgebase\\Elementor\\Widgets\\' . $widget_class;
			if ( class_exists( $full_class ) ) {
				$widgets_manager->register( new $full_class() );
			}
		}
	}

	/**
	 * Load all widget class files.
	 *
	 * @return void
	 */
	private function load_widget_files() {
		$widget_files = array(
			'class-itr-kb-widget-search.php',
			'class-itr-kb-widget-breadcrumb.php',
			'class-itr-kb-widget-category-tree.php',
			'class-itr-kb-widget-category-grid.php',
			'class-itr-kb-widget-article-list.php',
			'class-itr-kb-widget-article-accordion.php',
			'class-itr-kb-widget-toc.php',
			'class-itr-kb-widget-author-box.php',
			'class-itr-kb-widget-content-sections.php',
			'class-itr-kb-widget-category-accordion.php',
		);

		foreach ( $widget_files as $file ) {
			$path = ITR_KB_PATH . 'elementor/widgets/' . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}

		// Load dynamic tags.
		require_once ITR_KB_PATH . 'elementor/class-itr-kb-elementor-tags.php';
	}
}