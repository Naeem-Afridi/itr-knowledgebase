<?php
/**
 * Knowledge Base Article Custom Post Type.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/post-types
 */

namespace ITR_Knowledgebase\PostTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Post_Type
 *
 * Registers the Knowledge Base Article custom post type.
 */
class ITR_KB_Post_Type {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	const POST_TYPE = 'itr_kb_article';

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'                  => _x( 'KB Articles', 'Post type general name', 'itr-knowledgebase' ),
			'singular_name'         => _x( 'KB Article', 'Post type singular name', 'itr-knowledgebase' ),
			'menu_name'             => _x( 'Knowledgebase', 'Admin Menu text', 'itr-knowledgebase' ),
			'name_admin_bar'        => _x( 'KB Article', 'Add New on Toolbar', 'itr-knowledgebase' ),
			'add_new'               => __( 'Add New', 'itr-knowledgebase' ),
			'add_new_item'          => __( 'Add New Article', 'itr-knowledgebase' ),
			'new_item'              => __( 'New Article', 'itr-knowledgebase' ),
			'edit_item'             => __( 'Edit Article', 'itr-knowledgebase' ),
			'view_item'             => __( 'View Article', 'itr-knowledgebase' ),
			'all_items'             => __( 'All Articles', 'itr-knowledgebase' ),
			'search_items'          => __( 'Search Articles', 'itr-knowledgebase' ),
			'parent_item_colon'     => __( 'Parent Articles:', 'itr-knowledgebase' ),
			'not_found'             => __( 'No articles found.', 'itr-knowledgebase' ),
			'not_found_in_trash'    => __( 'No articles found in Trash.', 'itr-knowledgebase' ),
			'featured_image'        => __( 'Article Cover Image', 'itr-knowledgebase' ),
			'set_featured_image'    => __( 'Set cover image', 'itr-knowledgebase' ),
			'remove_featured_image' => __( 'Remove cover image', 'itr-knowledgebase' ),
			'use_featured_image'    => __( 'Use as cover image', 'itr-knowledgebase' ),
			'archives'              => __( 'Article Archives', 'itr-knowledgebase' ),
			'insert_into_item'      => __( 'Insert into article', 'itr-knowledgebase' ),
			'uploaded_to_this_item' => __( 'Uploaded to this article', 'itr-knowledgebase' ),
			'items_list'            => __( 'Articles list', 'itr-knowledgebase' ),
			'items_list_navigation' => __( 'Articles list navigation', 'itr-knowledgebase' ),
			'filter_items_list'     => __( 'Filter articles list', 'itr-knowledgebase' ),
		);

		$slug = get_option( 'itr_kb_slug', 'knowledgebase' );

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => sanitize_title( $slug ),
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'has_archive'        => sanitize_title( $slug ),
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-book-alt',
			'supports'           => array(
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'revisions',
				'author',
				'page-attributes',
			),
			'show_in_rest'       => true,
			'rest_base'          => 'itr-kb-articles',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Get the post type key.
	 *
	 * @return string
	 */
	public static function get_post_type() {
		return self::POST_TYPE;
	}

	/**
	 * Add rewrite rule for category-prefixed article URLs.
	 *
	 * Only runs when the "Category in Article URL" option is ON.
	 *
	 * Matches: /kb-slug/cat/sub-cat/any-depth/article-slug/
	 * Resolves to: index.php?itr_kb_article=article-slug
	 *
	 * The negative lookahead (?!{cat_slug}/) prevents this rule from
	 * accidentally catching category archive URLs, which use the taxonomy
	 * rewrite slug as their first path segment (e.g. /kb/kb-category/term/).
	 * Those continue to be handled by WordPress's own taxonomy rewrite rules.
	 *
	 * The existing /kb-slug/article-slug/ rule is unaffected and keeps
	 * working for all canonical/direct/admin URLs.
	 *
	 * @return void
	 */
	public function add_category_permalink_rules() {
		if ( ! get_option( 'itr_kb_category_in_url', false ) ) {
			return;
		}

		$kb_slug = sanitize_title( get_option( 'itr_kb_slug', 'knowledge-base' ) );

		// Derive the first path segment used by category archive URLs
		// so we can exclude it from our pattern (prevents false matches).
		$raw_cat_slug    = get_option( 'itr_kb_category_slug', 'kb-category' );
		$cat_segments    = array_filter( explode( '/', $raw_cat_slug ) );
		$cat_segments    = array_map( 'sanitize_title', $cat_segments );
		$first_cat_seg   = reset( $cat_segments ) ?: 'kb-category';

		// Also exclude the tag taxonomy slug to be safe.
		$tag_slug = sanitize_title( get_option( 'itr_kb_tag_slug', 'kb-tag' ) );

		$exclude = preg_quote( $first_cat_seg, '/' );
		if ( $tag_slug && $tag_slug !== $first_cat_seg ) {
			$exclude .= '|' . preg_quote( $tag_slug, '/' );
		}

		// Pattern: kb-slug / (anything except taxonomy slugs) / article-slug /
		// (.+) is greedy so it consumes all category segments.
		// ([^/]+) anchors to the article slug (last segment before trailing slash).
		add_rewrite_rule(
			'^' . preg_quote( $kb_slug, '/' ) . '/(?!' . $exclude . '/)(.+)/([^/]+)/?$',
			'index.php?itr_kb_article=$matches[2]',
			'top'
		);
	}
}