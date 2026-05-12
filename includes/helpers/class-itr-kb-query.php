<?php
/**
 * Shared query functions.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/helpers
 */

namespace ITR_Knowledgebase\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Query
 *
 * Centralized query layer for fetching KB content.
 */
class ITR_KB_Query {

	/**
	 * Base query args for KB articles.
	 *
	 * @return array
	 */
	private static function base_args() {
		return array(
			'post_type'      => 'itr_kb_article',
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		);
	}

	/**
	 * Get latest articles by publish date.
	 *
	 * @param int $count Number of articles.
	 * @return WP_Query
	 */
	public static function get_latest( $count = 5 ) {
		$args = array_merge(
			self::base_args(),
			array(
				'posts_per_page' => absint( $count ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		return new \WP_Query( $args );
	}

	/**
	 * Get recently updated articles.
	 *
	 * @param int $count Number of articles.
	 * @return WP_Query
	 */
	public static function get_recently_updated( $count = 5 ) {
		$args = array_merge(
			self::base_args(),
			array(
				'posts_per_page' => absint( $count ),
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		return new \WP_Query( $args );
	}

	/**
	 * Get popular articles by view count.
	 *
	 * @param int $count Number of articles.
	 * @return WP_Query
	 */
	public static function get_popular( $count = 5 ) {
		$args = array_merge(
			self::base_args(),
			array(
				'posts_per_page' => absint( $count ),
				'meta_key'       => '_itr_kb_view_count',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			)
		);
		return new \WP_Query( $args );
	}

	/**
	 * Get trending articles (views in last 7 days).
	 *
	 * @param int $count Number of articles.
	 * @return WP_Query
	 */
	public static function get_trending( $count = 5 ) {
		$args = array_merge(
			self::base_args(),
			array(
				'posts_per_page' => absint( $count ),
				'meta_key'       => '_itr_kb_trending_score',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			)
		);
		return new \WP_Query( $args );
	}

	/**
	 * Get featured articles (manually toggled).
	 *
	 * @param int $count Number of articles.
	 * @return WP_Query
	 */
	public static function get_featured( $count = 5 ) {
		$args = array_merge(
			self::base_args(),
			array(
				'posts_per_page' => absint( $count ),
				'meta_query'     => array(
					array(
						'key'     => '_itr_kb_featured',
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);
		return new \WP_Query( $args );
	}

	/**
	 * Get recommended articles (same category or tag).
	 *
	 * @param int   $post_id  Current article ID.
	 * @param int   $count    Number of articles.
	 * @return WP_Query
	 */
	public static function get_recommended( $post_id, $count = 5 ) {
		$post_id = absint( $post_id );
		$categories = wp_get_post_terms( $post_id, 'itr_kb_category', array( 'fields' => 'ids' ) );
		$tags       = wp_get_post_terms( $post_id, 'itr_kb_tag', array( 'fields' => 'ids' ) );

		$tax_query = array( 'relation' => 'OR' );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$tax_query[] = array(
				'taxonomy' => 'itr_kb_category',
				'field'    => 'term_id',
				'terms'    => $categories,
			);
		}

		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			$tax_query[] = array(
				'taxonomy' => 'itr_kb_tag',
				'field'    => 'term_id',
				'terms'    => $tags,
			);
		}

		$args = array_merge(
			self::base_args(),
			array(
				'posts_per_page' => absint( $count ),
				'post__not_in'   => array( $post_id ),
				'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return new \WP_Query( $args );
	}

	/**
	 * Get articles by category term ID.
	 *
	 * @param int $term_id  Category term ID.
	 * @param int $per_page Articles per page.
	 * @param int $page     Current page number.
	 * @return WP_Query
	 */
	public static function get_by_category( $term_id, $per_page = 10, $page = 1 ) {
		$args = array_merge(
			self::base_args(),
			array(
				'posts_per_page' => absint( $per_page ),
				'paged'          => absint( $page ),
				'no_found_rows'  => false,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => 'itr_kb_category',
						'field'    => 'term_id',
						'terms'    => absint( $term_id ),
					),
				),
			)
		);
		return new \WP_Query( $args );
	}

	/**
	 * Search KB articles.
	 *
	 * @param string $keyword  Search keyword.
	 * @param int    $per_page Results per page.
	 * @return WP_Query
	 */
	public static function search( $keyword, $per_page = 10 ) {
		$args = array_merge(
			self::base_args(),
			array(
				's'              => sanitize_text_field( $keyword ),
				'posts_per_page' => absint( $per_page ),
				'no_found_rows'  => false,
			)
		);
		return new \WP_Query( $args );
	}

	/**
	 * Get previous article in the same category.
	 *
	 * @param int $post_id Current post ID.
	 * @return WP_Post|null
	 */
	public static function get_previous_article( $post_id ) {
		$prev = get_previous_post( true, '', 'itr_kb_category' );
		return $prev instanceof \WP_Post ? $prev : null;
	}

	/**
	 * Get next article in the same category.
	 *
	 * @param int $post_id Current post ID.
	 * @return WP_Post|null
	 */
	public static function get_next_article( $post_id ) {
		$next = get_next_post( true, '', 'itr_kb_category' );
		return $next instanceof \WP_Post ? $next : null;
	}
}