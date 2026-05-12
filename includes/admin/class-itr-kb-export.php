<?php
/**
 * Export KB data.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/admin
 */

namespace ITR_Knowledgebase\Admin;

use ITR_Knowledgebase\Helpers\ITR_KB_Security;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Export
 *
 * Exports KB articles, categories, tags, and authors as a JSON file.
 */
class ITR_KB_Export {

	/**
	 * Handle export form submission.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! isset( $_POST['itr_kb_action'] ) || 'export' !== $_POST['itr_kb_action'] ) {
			return;
		}

		// Verify nonce.
		ITR_KB_Security::verify_nonce(
			isset( $_POST['itr_kb_export_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['itr_kb_export_nonce'] ) ) : '',
			'itr_kb_export'
		);

		// Check capability.
		ITR_KB_Security::require_capability( 'manage_itr_kb_categories' );

		$export_data = array(
			'plugin'    => 'itr-knowledgebase',
			'version'   => ITR_KB_VERSION,
			'exported'  => current_time( 'c' ),
			'articles'  => array(),
			'categories'=> array(),
			'tags'      => array(),
			'authors'   => array(),
		);

		// Export articles.
		if ( isset( $_POST['export_articles'] ) ) {
			$export_data['articles'] = $this->export_articles();
		}

		// Export categories.
		if ( isset( $_POST['export_categories'] ) ) {
			$export_data['categories'] = $this->export_categories();
		}

		// Export tags.
		if ( isset( $_POST['export_tags'] ) ) {
			$export_data['tags'] = $this->export_tags();
		}

		// Export authors.
		if ( isset( $_POST['export_authors'] ) ) {
			$export_data['authors'] = $this->export_authors();
		}

		$filename = 'itr-knowledgebase-export-' . date( 'Y-m-d' ) . '.json';
		$json     = wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		// Stream file download.
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	/**
	 * Export all KB articles.
	 *
	 * @return array
	 */
	private function export_articles() {
		$posts = get_posts(
			array(
				'post_type'      => 'itr_kb_article',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'ASC',
			)
		);

		$articles = array();

		foreach ( $posts as $post ) {
			$categories   = wp_get_post_terms( $post->ID, 'itr_kb_category', array( 'fields' => 'slugs' ) );
			$tags         = wp_get_post_terms( $post->ID, 'itr_kb_tag', array( 'fields' => 'slugs' ) );
			$author_id    = get_post_meta( $post->ID, '_itr_kb_author_id', true );
			$reviewer_ids = get_post_meta( $post->ID, '_itr_kb_reviewer_ids', true );

			$articles[] = array(
				'post_id'       => $post->ID,
				'title'         => $post->post_title,
				'content'       => $post->post_content,
				'excerpt'       => $post->post_excerpt,
				'status'        => $post->post_status,
				'slug'          => $post->post_name,
				'date'          => $post->post_date,
				'modified'      => $post->post_modified,
				'categories'    => ! is_wp_error( $categories ) ? $categories : array(),
				'tags'          => ! is_wp_error( $tags ) ? $tags : array(),
				'author_id'     => absint( $author_id ),
				'reviewer_ids'  => is_array( $reviewer_ids ) ? array_map( 'absint', $reviewer_ids ) : array(),
				'featured'      => get_post_meta( $post->ID, '_itr_kb_featured', true ),
				'toc_disabled'  => get_post_meta( $post->ID, '_itr_kb_toc_disabled', true ),
				'view_count'    => absint( get_post_meta( $post->ID, '_itr_kb_view_count', true ) ),
			);
		}

		return $articles;
	}

	/**
	 * Export all KB categories.
	 *
	 * @return array
	 */
	private function export_categories() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'itr_kb_category',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$categories = array();

		foreach ( $terms as $term ) {
			$parent_slug = '';
			if ( $term->parent ) {
				$parent_term = get_term( $term->parent, 'itr_kb_category' );
				if ( $parent_term && ! is_wp_error( $parent_term ) ) {
					$parent_slug = $parent_term->slug;
				}
			}

			$categories[] = array(
				'term_id'     => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent'      => $term->parent,
				'parent_slug' => $parent_slug,
				'count'       => $term->count,
				'icon'        => get_term_meta( $term->term_id, 'itr_kb_category_icon', true ),
				'order'       => absint( get_term_meta( $term->term_id, 'itr_kb_category_order', true ) ),
			);
		}

		return $categories;
	}

	/**
	 * Export all KB tags.
	 *
	 * @return array
	 */
	private function export_tags() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'itr_kb_tag',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$tags = array();

		foreach ( $terms as $term ) {
			$tags[] = array(
				'term_id'     => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'count'       => $term->count,
			);
		}

		return $tags;
	}

	/**
	 * Export all Authors & Reviewers.
	 *
	 * @return array
	 */
	private function export_authors() {
		$posts = get_posts(
			array(
				'post_type'      => 'itr_kb_author',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		$authors = array();

		foreach ( $posts as $post ) {
			$authors[] = array(
				'post_id'     => $post->ID,
				'name'        => $post->post_title,
				'bio'         => $post->post_content,
				'slug'        => $post->post_name,
				'photo_url'   => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
			);
		}

		return $authors;
	}
}