<?php
/**
 * Utility helper class.
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
 * Class ITR_KB_Utils
 *
 * General utility methods used across the plugin.
 */
class ITR_KB_Utils {

	/**
	 * Get plugin option with fallback.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_option( $key, $default = false ) {
		return get_option( $key, $default );
	}

	/**
	 * Check if we are on a KB article single page.
	 *
	 * @return bool
	 */
	public static function is_kb_single() {
		return is_singular( 'itr_kb_article' );
	}

	/**
	 * Check if we are on a KB category/archive page.
	 *
	 * @return bool
	 */
	public static function is_kb_archive() {
		return is_post_type_archive( 'itr_kb_article' ) || is_tax( 'itr_kb_category' ) || is_tax( 'itr_kb_tag' );
	}

	/**
	 * Check if we are on any KB page.
	 *
	 * @return bool
	 */
	public static function is_kb_page() {
		return self::is_kb_single() || self::is_kb_archive();
	}

	/**
	 * Get template file path, checking theme override first.
	 *
	 * @param string $template_name Template file name (e.g. single-itr-kb.php).
	 * @return string Full path to template.
	 */
	public static function get_template_path( $template_name ) {
		// Allow theme to override templates.
		$theme_template = get_stylesheet_directory() . '/itr-knowledgebase/' . $template_name;

		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}

		// Fall back to plugin templates.
		return ITR_KB_PATH . 'templates/' . $template_name;
	}

	/**
	 * Load a template file with optional data.
	 *
	 * @param string $template_name Template file name.
	 * @param array  $args          Variables to pass to the template.
	 * @return void
	 */
	public static function load_template( $template_name, $args = array() ) {
		$template_path = self::get_template_path( $template_name );

		if ( ! file_exists( $template_path ) ) {
			return;
		}

		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
		}

		include $template_path;
	}

	/**
	 * Get the KB article post type name.
	 *
	 * @return string
	 */
	public static function get_post_type() {
		return 'itr_kb_article';
	}

	/**
	 * Get the KB author post type name.
	 *
	 * @return string
	 */
	public static function get_author_post_type() {
		return 'itr_kb_author';
	}

	/**
	 * Get the KB category taxonomy name.
	 *
	 * @return string
	 */
	public static function get_category_taxonomy() {
		return 'itr_kb_category';
	}

	/**
	 * Get the KB tag taxonomy name.
	 *
	 * @return string
	 */
	public static function get_tag_taxonomy() {
		return 'itr_kb_tag';
	}

	/**
	 * Format a date for display.
	 *
	 * @param string $date   Date string.
	 * @param string $format Date format. Default WordPress date format.
	 * @return string
	 */
	public static function format_date( $date, $format = '' ) {
		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}
		return date_i18n( $format, strtotime( $date ) );
	}

	/**
	 * Get post meta value safely.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $default  Default value.
	 * @return mixed
	 */
	public static function get_meta( $post_id, $meta_key, $default = '' ) {
		$value = get_post_meta( absint( $post_id ), sanitize_key( $meta_key ), true );
		return ( '' !== $value && false !== $value ) ? $value : $default;
	}

	/**
	 * Get a contextual article URL that includes the full category hierarchy.
	 *
	 * Used when linking to articles from category pages. When the
	 * "Category in Article URL" option is OFF, or when no term context is
	 * provided, the standard canonical permalink is returned unchanged.
	 *
	 * Example (option ON, term_id = sub-category ID):
	 *   /knowledgebase/parent/child/sub/article-slug/
	 *
	 * Example (option OFF, or no term_id):
	 *   /knowledgebase/article-slug/
	 *
	 * @param int $post_id The KB article post ID.
	 * @param int $term_id The category term ID the user is coming from (0 = none).
	 * @return string URL.
	 */
	public static function get_contextual_article_url( $post_id, $term_id = 0 ) {
		if ( ! get_option( 'itr_kb_category_in_url', false ) || ! $term_id ) {
			return get_permalink( $post_id );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return get_permalink( $post_id );
		}

		// Build the full ancestor chain from root → $term_id.
		$ancestor_ids = array_reverse(
			get_ancestors( (int) $term_id, 'itr_kb_category', 'taxonomy' )
		);
		$chain = array_merge( $ancestor_ids, array( (int) $term_id ) );

		$slugs = array();
		foreach ( $chain as $tid ) {
			$term = get_term( (int) $tid, 'itr_kb_category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$slugs[] = $term->slug;
			}
		}

		if ( empty( $slugs ) ) {
			return get_permalink( $post_id );
		}

		$kb_slug = sanitize_title( get_option( 'itr_kb_slug', 'knowledge-base' ) );

		return trailingslashit(
			home_url( '/' . $kb_slug . '/' . implode( '/', $slugs ) . '/' . $post->post_name )
		);
	}

	/**
	 * Extract the deepest category term from the current request URL when
	 * the "Category in Article URL" option is ON.
	 *
	 * Used by the breadcrumb to reflect the path the user navigated through
	 * rather than the article's auto-detected primary category.
	 *
	 * Returns 0 (no context) when:
	 *   - The option is OFF.
	 *   - The URL has no category segments before the article slug.
	 *   - The extracted slug doesn't match any term in the DB.
	 *
	 * @param int $post_id The current KB article post ID.
	 * @return int Term ID, or 0.
	 */
	public static function get_term_id_from_url( $post_id ) {
		if ( ! get_option( 'itr_kb_category_in_url', false ) ) {
			return 0;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return 0;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		$path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
		$kb_prefix = trim( sanitize_title( get_option( 'itr_kb_slug', 'knowledge-base' ) ), '/' );

		// URL must start with the KB prefix.
		if ( 0 !== strpos( $path, $kb_prefix . '/' ) ) {
			return 0;
		}

		// Strip the KB prefix.
		$path = trim( substr( $path, strlen( $kb_prefix ) ), '/' );

		// Split into segments — last one is the article slug.
		$segments = array_values( array_filter( explode( '/', $path ) ) );

		// Need at least 2 segments (one category + one article slug).
		if ( count( $segments ) < 2 ) {
			return 0;
		}

		// Remove the article slug (last segment).
		array_pop( $segments );

		// The last remaining segment is the deepest category slug.
		$cat_slug = end( $segments );
		$term     = get_term_by( 'slug', $cat_slug, 'itr_kb_category' );

		return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
	}
}