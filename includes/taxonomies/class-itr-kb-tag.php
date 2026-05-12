<?php
/**
 * Knowledge Base Tag Taxonomy.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/taxonomies
 */

namespace ITR_Knowledgebase\Taxonomies;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Tag
 *
 * Registers the non-hierarchical KB Tag taxonomy.
 */
class ITR_KB_Tag {

	/**
	 * Taxonomy key.
	 *
	 * @var string
	 */
	const TAXONOMY = 'itr_kb_tag';

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'                       => _x( 'KB Tags', 'taxonomy general name', 'itr-knowledgebase' ),
			'singular_name'              => _x( 'KB Tag', 'taxonomy singular name', 'itr-knowledgebase' ),
			'search_items'               => __( 'Search Tags', 'itr-knowledgebase' ),
			'popular_items'              => __( 'Popular Tags', 'itr-knowledgebase' ),
			'all_items'                  => __( 'All Tags', 'itr-knowledgebase' ),
			'edit_item'                  => __( 'Edit Tag', 'itr-knowledgebase' ),
			'update_item'                => __( 'Update Tag', 'itr-knowledgebase' ),
			'add_new_item'               => __( 'Add New Tag', 'itr-knowledgebase' ),
			'new_item_name'              => __( 'New Tag Name', 'itr-knowledgebase' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'itr-knowledgebase' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'itr-knowledgebase' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'itr-knowledgebase' ),
			'not_found'                  => __( 'No tags found.', 'itr-knowledgebase' ),
			'menu_name'                  => __( 'Tags', 'itr-knowledgebase' ),
			'back_to_items'              => __( '← Back to Tags', 'itr-knowledgebase' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rest_base'         => 'itr-kb-tags',
			'query_var'         => true,
			'rewrite'           => array(
				'slug'       => 'kb-tag',
				'with_front' => false,
			),
			'capabilities'      => array(
				'manage_terms' => 'manage_itr_kb_categories',
				'edit_terms'   => 'manage_itr_kb_categories',
				'delete_terms' => 'manage_itr_kb_categories',
				'assign_terms' => 'edit_itr_kb_articles',
			),
		);

		register_taxonomy( self::TAXONOMY, 'itr_kb_article', $args );
	}

	/**
	 * Get the taxonomy key.
	 *
	 * @return string
	 */
	public static function get_taxonomy() {
		return self::TAXONOMY;
	}
}