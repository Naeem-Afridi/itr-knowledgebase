<?php
/**
 * REST API endpoints.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/api
 */

namespace ITR_Knowledgebase\API;

use ITR_Knowledgebase\Helpers\ITR_KB_Query;
use ITR_Knowledgebase\Admin\ITR_KB_Category_Order;
use ITR_Knowledgebase\Taxonomies\ITR_KB_Category;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Rest_API
 *
 * Registers REST API routes used by the frontend JS (live search, autocomplete).
 *
 * Namespace : itr-kb/v1
 *
 * Routes:
 * GET /itr-kb/v1/search          - Live search
 * GET /itr-kb/v1/articles        - Article list (by type/category)
 * GET /itr-kb/v1/categories      - Category tree
 * GET /itr-kb/v1/article/{id}    - Single article data
 */
class ITR_KB_Rest_API {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'itr-kb/v1';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Search route.
		register_rest_route(
			self::NAMESPACE,
			'/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'keyword' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $value ) {
							return strlen( $value ) >= 2;
						},
					),
					'count'   => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 5,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Articles list route.
		register_rest_route(
			self::NAMESPACE,
			'/articles',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_articles' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'type'        => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => 'latest',
						'enum'              => array( 'latest', 'recently_updated', 'popular', 'trending', 'featured', 'recommended' ),
						'sanitize_callback' => 'sanitize_key',
					),
					'count'       => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 5,
						'sanitize_callback' => 'absint',
					),
					'post_id'     => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'category_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Categories route.
		register_rest_route(
			self::NAMESPACE,
			'/categories',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_categories' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'parent' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Single article route.
		register_rest_route(
			self::NAMESPACE,
			'/article/(?P<id>[\d]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_article' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Handle search request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function search( $request ) {
		$keyword = $request->get_param( 'keyword' );
		$count   = $request->get_param( 'count' );
		$highlight = (bool) get_option( 'itr_kb_search_highlight', true );

		$query   = ITR_KB_Query::search( $keyword, $count );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id    = get_the_ID();
				$title      = get_the_title();
				$categories = wp_get_post_terms( $post_id, 'itr_kb_category', array( 'fields' => 'names' ) );

				if ( $highlight ) {
					$title = $this->highlight( $title, $keyword );
				}

				$results[] = array(
					'id'         => $post_id,
					'title'      => $title,
					'url'        => esc_url( get_permalink() ),
					'excerpt'    => esc_html( wp_trim_words( get_the_excerpt(), 15 ) ),
					'categories' => ! is_wp_error( $categories ) ? $categories : array(),
					'thumbnail'  => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: '',
				);
			}
			wp_reset_postdata();
		}

		return rest_ensure_response( array(
			'results' => $results,
			'total'   => $query->found_posts,
			'keyword' => sanitize_text_field( $keyword ),
		));
	}

	/**
	 * Handle articles list request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_articles( $request ) {
		$type        = $request->get_param( 'type' );
		$count       = $request->get_param( 'count' );
		$post_id     = $request->get_param( 'post_id' );
		$category_id = $request->get_param( 'category_id' );

		switch ( $type ) {
			case 'latest':
				$query = ITR_KB_Query::get_latest( $count );
				break;
			case 'recently_updated':
				$query = ITR_KB_Query::get_recently_updated( $count );
				break;
			case 'popular':
				$query = ITR_KB_Query::get_popular( $count );
				break;
			case 'trending':
				$query = ITR_KB_Query::get_trending( $count );
				break;
			case 'featured':
				$query = ITR_KB_Query::get_featured( $count );
				break;
			case 'recommended':
				$query = $post_id ? ITR_KB_Query::get_recommended( $post_id, $count ) : null;
				break;
			default:
				$query = ITR_KB_Query::get_latest( $count );
		}

		if ( ! $query ) {
			return rest_ensure_response( array( 'articles' => array() ) );
		}

		$articles = $this->format_articles( $query );

		return rest_ensure_response( array(
			'articles' => $articles,
			'total'    => $query->found_posts,
		));
	}

	/**
	 * Handle categories request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_categories( $request ) {
		$parent = $request->get_param( 'parent' );
		$tree   = ITR_KB_Category_Order::get_category_tree( $parent );

		$formatted = $this->format_categories( $tree );

		return rest_ensure_response( array(
			'categories' => $formatted,
		));
	}

	/**
	 * Handle single article request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_article( $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || 'itr_kb_article' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new \WP_Error(
				'itr_kb_not_found',
				esc_html__( 'Article not found.', 'itr-knowledgebase' ),
				array( 'status' => 404 )
			);
		}

		$categories   = wp_get_post_terms( $post_id, 'itr_kb_category', array( 'fields' => 'names' ) );
		$tags         = wp_get_post_terms( $post_id, 'itr_kb_tag', array( 'fields' => 'names' ) );

		return rest_ensure_response( array(
			'id'           => $post->ID,
			'title'        => esc_html( $post->post_title ),
			'excerpt'      => esc_html( wp_trim_words( $post->post_excerpt ?: $post->post_content, 30 ) ),
			'url'          => esc_url( get_permalink( $post->ID ) ),
			'date'         => esc_html( get_the_date( '', $post ) ),
			'modified'     => esc_html( get_the_modified_date( '', $post ) ),
			'thumbnail'    => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: '',
			'categories'   => ! is_wp_error( $categories ) ? $categories : array(),
			'tags'         => ! is_wp_error( $tags ) ? $tags : array(),
			'view_count'   => absint( get_post_meta( $post_id, '_itr_kb_view_count', true ) ),
		));
	}

	/**
	 * Format WP_Query results into article data arrays.
	 *
	 * @param \WP_Query $query Query object.
	 * @return array
	 */
	private function format_articles( $query ) {
		$articles = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id    = get_the_ID();
				$categories = wp_get_post_terms( $post_id, 'itr_kb_category', array( 'fields' => 'names' ) );

				$articles[] = array(
					'id'         => $post_id,
					'title'      => esc_html( get_the_title() ),
					'excerpt'    => esc_html( wp_trim_words( get_the_excerpt(), 20 ) ),
					'url'        => esc_url( get_permalink() ),
					'date'       => esc_html( get_the_date() ),
					'modified'   => esc_html( get_the_modified_date() ),
					'thumbnail'  => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: '',
					'categories' => ! is_wp_error( $categories ) ? $categories : array(),
					'view_count' => absint( get_post_meta( $post_id, '_itr_kb_view_count', true ) ),
				);
			}
			wp_reset_postdata();
		}

		return $articles;
	}

	/**
	 * Format category tree into REST-friendly arrays.
	 *
	 * @param array $tree Category tree array.
	 * @return array
	 */
	private function format_categories( $tree ) {
		$formatted = array();

		foreach ( $tree as $term ) {
			$formatted[] = array(
				'id'          => $term->term_id,
				'name'        => esc_html( $term->name ),
				'slug'        => $term->slug,
				'url'         => esc_url( get_term_link( $term ) ),
				'count'       => absint( $term->count ),
				'icon'        => esc_attr( ITR_KB_Category::get_icon( $term->term_id ) ),
				'image'       => esc_url( ITR_KB_Category::get_image_url( $term->term_id ) ),
				'children'    => ! empty( $term->children ) ? $this->format_categories( $term->children ) : array(),
			);
		}

		return $formatted;
	}

	/**
	 * Highlight keyword in text.
	 *
	 * @param string $text    Input text.
	 * @param string $keyword Keyword.
	 * @return string
	 */
	private function highlight( $text, $keyword ) {
		$escaped = preg_quote( $keyword, '/' );
		return preg_replace(
			'/(' . $escaped . ')/i',
			'<mark class="itr-kb-search-highlight">$1</mark>',
			esc_html( $text )
		);
	}
}