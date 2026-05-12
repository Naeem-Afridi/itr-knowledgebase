<?php
/**
 * Admin article list filters.
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
 * Class ITR_KB_List_Filters
 *
 * Adds category and tag dropdown filters to the KB articles list table.
 * Also adds a column showing assigned categories.
 */
class ITR_KB_List_Filters {

	/**
	 * Register all hooks directly in constructor.
	 */
	public function __construct() {
		// Add filter dropdowns above the articles list.
		add_action( 'restrict_manage_posts', array( $this, 'render_filters' ) );

		// Apply the selected filters to the query.
		add_filter( 'parse_query', array( $this, 'apply_filters' ) );

		// Add custom columns to articles list.
		add_filter( 'manage_itr_kb_article_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_itr_kb_article_posts_custom_column', array( $this, 'render_column' ), 10, 2 );

		// Make category column sortable.
		add_filter( 'manage_edit-itr_kb_article_sortable_columns', array( $this, 'sortable_columns' ) );
	}

	/**
	 * Render category and tag filter dropdowns.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public function render_filters( $post_type ) {
		if ( 'itr_kb_article' !== $post_type ) {
			return;
		}

		// ── Category filter ───────────────────────────────────────────
		$selected_cat = isset( $_GET['itr_kb_cat'] ) ? absint( $_GET['itr_kb_cat'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		$categories = get_terms( array(
			'taxonomy'   => 'itr_kb_category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			echo '<select name="itr_kb_cat" id="itr-kb-filter-cat">';
			echo '<option value="0">' . esc_html__( 'All Categories', 'itr-knowledgebase' ) . '</option>';

			foreach ( $categories as $cat ) {
				$indent = $cat->parent ? '&nbsp;&nbsp;&nbsp;' : '';
				printf(
					'<option value="%d" %s>%s%s</option>',
					absint( $cat->term_id ),
					selected( $selected_cat, $cat->term_id, false ),
					$indent,
					esc_html( $cat->name )
				);
			}

			echo '</select>';
		}

		// ── Tag filter ────────────────────────────────────────────────
		$selected_tag = isset( $_GET['itr_kb_tag'] ) ? absint( $_GET['itr_kb_tag'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		$tags = get_terms( array(
			'taxonomy'   => 'itr_kb_tag',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
			echo '<select name="itr_kb_tag" id="itr-kb-filter-tag">';
			echo '<option value="0">' . esc_html__( 'All Tags', 'itr-knowledgebase' ) . '</option>';

			foreach ( $tags as $tag ) {
				printf(
					'<option value="%d" %s>%s</option>',
					absint( $tag->term_id ),
					selected( $selected_tag, $tag->term_id, false ),
					esc_html( $tag->name )
				);
			}

			echo '</select>';
		}
	}

	/**
	 * Apply selected filters to the WP_Query on the list screen.
	 *
	 * @param \WP_Query $query Current query object.
	 * @return void
	 */
	public function apply_filters( $query ) {
		global $pagenow;

		if (
			! is_admin() ||
			'edit.php' !== $pagenow ||
			! isset( $_GET['post_type'] ) || // phpcs:ignore WordPress.Security.NonceVerification
			'itr_kb_article' !== $_GET['post_type'] // phpcs:ignore WordPress.Security.NonceVerification
		) {
			return;
		}

		$tax_query = array();

		// Category filter.
		$cat_id = isset( $_GET['itr_kb_cat'] ) ? absint( $_GET['itr_kb_cat'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $cat_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'itr_kb_category',
				'field'    => 'term_id',
				'terms'    => $cat_id,
			);
		}

		// Tag filter.
		$tag_id = isset( $_GET['itr_kb_tag'] ) ? absint( $_GET['itr_kb_tag'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $tag_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'itr_kb_tag',
				'field'    => 'term_id',
				'terms'    => $tag_id,
			);
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$query->set( 'tax_query', $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}
	}

	/**
	 * Add custom columns to the articles list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_columns( $columns ) {
		// Insert Categories column after title.
		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['itr_kb_categories'] = esc_html__( 'Categories', 'itr-knowledgebase' );
				$new_columns['itr_kb_tags']       = esc_html__( 'Tags', 'itr-knowledgebase' );
				$new_columns['itr_kb_featured']   = esc_html__( 'Featured', 'itr-knowledgebase' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'itr_kb_categories':
				$terms = wp_get_post_terms( $post_id, 'itr_kb_category' );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$links = array();
					foreach ( $terms as $term ) {
						$links[] = sprintf(
							'<a href="%s">%s</a>',
							esc_url( add_query_arg( array(
								'post_type' => 'itr_kb_article',
								'itr_kb_cat' => $term->term_id,
							), admin_url( 'edit.php' ) ) ),
							esc_html( $term->name )
						);
					}
					echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo '<span style="color:#999;">—</span>';
				}
				break;

			case 'itr_kb_tags':
				$terms = wp_get_post_terms( $post_id, 'itr_kb_tag' );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$links = array();
					foreach ( $terms as $term ) {
						$links[] = sprintf(
							'<a href="%s">%s</a>',
							esc_url( add_query_arg( array(
								'post_type' => 'itr_kb_article',
								'itr_kb_tag' => $term->term_id,
							), admin_url( 'edit.php' ) ) ),
							esc_html( $term->name )
						);
					}
					echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo '<span style="color:#999;">—</span>';
				}
				break;

			case 'itr_kb_featured':
				$is_featured = get_post_meta( $post_id, '_itr_kb_featured', true );
				if ( '1' === $is_featured ) {
					echo '<span class="dashicons dashicons-star-filled" style="color:#f0b429;" title="' . esc_attr__( 'Featured', 'itr-knowledgebase' ) . '"></span>';
				} else {
					echo '<span class="dashicons dashicons-star-empty" style="color:#ccc;"></span>';
				}
				break;
		}
	}

	/**
	 * Make columns sortable.
	 *
	 * @param array $columns Existing sortable columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['itr_kb_featured'] = 'itr_kb_featured';
		return $columns;
	}
}