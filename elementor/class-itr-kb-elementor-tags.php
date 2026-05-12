<?php
/**
 * Elementor Dynamic Tags for KB data.
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
 * Class ITR_KB_Elementor_Tags
 *
 * Registers Elementor Pro dynamic tags so designers can pull
 * KB-specific data (publish date, last updated, view count,
 * author name) directly into any Elementor text widget.
 */
class ITR_KB_Elementor_Tags {

	/**
	 * Register dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor dynamic tags manager.
	 * @return void
	 */
	public static function register( $dynamic_tags_manager ) {
		// Register tag group.
		$dynamic_tags_manager->register_group(
			'itr-kb',
			array( 'title' => esc_html__( 'ITR Knowledgebase', 'itr-knowledgebase' ) )
		);

		// Load and register each tag.
		$tags = array(
			'ITR_KB_Tag_Publish_Date',
			'ITR_KB_Tag_Updated_Date',
			'ITR_KB_Tag_View_Count',
			'ITR_KB_Tag_Author_Name',
		);

		foreach ( $tags as $tag_class ) {
			$full_class = 'ITR_Knowledgebase\\Elementor\\' . $tag_class;
			if ( class_exists( $full_class ) ) {
				$dynamic_tags_manager->register( new $full_class() );
			}
		}
	}
}

/**
 * Dynamic Tag: Article Publish Date
 */
class ITR_KB_Tag_Publish_Date extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() { return 'itr-kb-publish-date'; }
	public function get_title() { return esc_html__( 'KB Publish Date', 'itr-knowledgebase' ); }
	public function get_group() { return 'itr-kb'; }
	public function get_categories() { return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ); }

	public function render() {
		echo esc_html( get_the_date() );
	}
}

/**
 * Dynamic Tag: Article Last Updated Date
 */
class ITR_KB_Tag_Updated_Date extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() { return 'itr-kb-updated-date'; }
	public function get_title() { return esc_html__( 'KB Last Updated Date', 'itr-knowledgebase' ); }
	public function get_group() { return 'itr-kb'; }
	public function get_categories() { return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ); }

	public function render() {
		echo esc_html( get_the_modified_date() );
	}
}

/**
 * Dynamic Tag: Article View Count
 */
class ITR_KB_Tag_View_Count extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() { return 'itr-kb-view-count'; }
	public function get_title() { return esc_html__( 'KB View Count', 'itr-knowledgebase' ); }
	public function get_group() { return 'itr-kb'; }
	public function get_categories() { return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ); }

	public function render() {
		$count = absint( get_post_meta( get_the_ID(), '_itr_kb_view_count', true ) );
		echo esc_html( number_format_i18n( $count ) );
	}
}

/**
 * Dynamic Tag: Author Name
 */
class ITR_KB_Tag_Author_Name extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() { return 'itr-kb-author-name'; }
	public function get_title() { return esc_html__( 'KB Author Name', 'itr-knowledgebase' ); }
	public function get_group() { return 'itr-kb'; }
	public function get_categories() { return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ); }

	public function render() {
		$author_id = absint( get_post_meta( get_the_ID(), '_itr_kb_author_id', true ) );
		if ( $author_id ) {
			echo esc_html( get_the_title( $author_id ) );
		}
	}
}