<?php
/**
 * Banner Inheritance Engine.
 *
 * Resolves the correct banner image + URL for each of the four banner
 * positions by walking the article's category ancestor chain at render time.
 * Nothing is stored on the article itself — resolved fresh on every request.
 *
 * Four positions:
 *   desktop_toc        — below TOC sidebar (desktop only)
 *   desktop_categories — below Categories sidebar (desktop only)
 *   mobile_top         — above article content (mobile only)
 *   mobile_bottom      — below article content (mobile only)
 *
 * Term meta keys (per position):
 *   itr_kb_banner_{position}_image — attachment ID
 *   itr_kb_banner_{position}_url   — destination URL (optional)
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
 * Class ITR_KB_Banner
 */
class ITR_KB_Banner {

	/**
	 * The four valid banner position keys.
	 *
	 * @var array
	 */
	public static $positions = array(
		'desktop_toc',
		'desktop_categories',
		'mobile_top',
		'mobile_bottom',
	);

	/**
	 * Resolve a single banner position for a given post.
	 *
	 * Walks: assigned term → parent → grandparent → … → root.
	 * First ancestor that has an image set for this position wins.
	 * Image and URL always travel as a pair — never split across levels.
	 *
	 * @param int    $post_id  KB article post ID.
	 * @param string $position One of self::$positions.
	 * @return array {
	 *     @type int    $image_id  Attachment ID (0 if nothing found).
	 *     @type string $image_url Full image URL (empty if nothing found).
	 *     @type string $url       Destination URL (empty if not set).
	 * }
	 */
	public static function resolve( $post_id, $position ) {
		$empty = array( 'image_id' => 0, 'image_url' => '', 'url' => '' );

		if ( ! in_array( $position, self::$positions, true ) ) {
			return $empty;
		}

		$image_meta_key = 'itr_kb_banner_' . $position . '_image';
		$url_meta_key   = 'itr_kb_banner_' . $position . '_url';

		// Get all categories assigned to this article.
		$terms = wp_get_post_terms( $post_id, 'itr_kb_category', array( 'fields' => 'all' ) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $empty;
		}

		// Sort by depth descending — check the most specific category first.
		usort( $terms, function ( $a, $b ) {
			$depth_a = count( get_ancestors( $a->term_id, 'itr_kb_category', 'taxonomy' ) );
			$depth_b = count( get_ancestors( $b->term_id, 'itr_kb_category', 'taxonomy' ) );
			return $depth_b - $depth_a;
		} );

		foreach ( $terms as $term ) {
			// Build chain: [self, parent, grandparent, …, root].
			$chain = array_merge(
				array( (int) $term->term_id ),
				get_ancestors( $term->term_id, 'itr_kb_category', 'taxonomy' )
			);

			foreach ( $chain as $tid ) {
				$image_id = (int) get_term_meta( $tid, $image_meta_key, true );

				if ( $image_id ) {
					$image_url = wp_get_attachment_image_url( $image_id, 'full' );
					if ( ! $image_url ) {
						continue; // attachment deleted — skip
					}
					$dest_url = (string) get_term_meta( $tid, $url_meta_key, true );
					return array(
						'image_id'  => $image_id,
						'image_url' => $image_url,
						'url'       => $dest_url,
					);
				}
			}
		}

		return $empty;
	}

	/**
	 * Resolve all four banner positions for a given post at once.
	 *
	 * @param int $post_id KB article post ID.
	 * @return array Keyed by position slug.
	 */
	public static function resolve_all( $post_id ) {
		$result = array();
		foreach ( self::$positions as $position ) {
			$result[ $position ] = self::resolve( $post_id, $position );
		}
		return $result;
	}

	/**
	 * Render a single banner position.
	 *
	 * Outputs nothing (not even whitespace) if no image is resolved.
	 * Image is wrapped in <a> only when a URL is set.
	 *
	 * @param int    $post_id  KB article post ID.
	 * @param string $position One of self::$positions.
	 * @return void
	 */
	public static function render( $post_id, $position ) {
		$banner = self::resolve( $post_id, $position );

		if ( ! $banner['image_url'] ) {
			return;
		}

		$alt = self::get_alt_text( $banner['image_id'] );

		echo '<div class="itr-kb-banner itr-kb-banner--' . esc_attr( $position ) . '">';

		if ( $banner['url'] ) {
			echo '<a href="' . esc_url( $banner['url'] ) . '" target="_blank" rel="noopener noreferrer">';
		}

		echo '<img'
			. ' src="' . esc_url( $banner['image_url'] ) . '"'
			. ' alt="' . esc_attr( $alt ) . '"'
			. ' class="itr-kb-banner__img"'
			. ' loading="lazy"'
			. ' />';

		if ( $banner['url'] ) {
			echo '</a>';
		}

		echo '</div>';
	}

	/**
	 * Register the [itr_kb_banner] shortcode.
	 *
	 * Usage: [itr_kb_banner position="desktop_toc"]
	 *
	 * @return void
	 */
	public static function register_shortcode() {
		add_shortcode( 'itr_kb_banner', array( static::class, 'shortcode_handler' ) );
	}

	/**
	 * Shortcode handler for [itr_kb_banner].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function shortcode_handler( $atts ) {
		$atts = shortcode_atts(
			array( 'position' => 'desktop_toc' ),
			$atts,
			'itr_kb_banner'
		);

		$position = sanitize_key( $atts['position'] );

		if ( ! in_array( $position, self::$positions, true ) ) {
			return '';
		}

		// Resolve post ID from current context.
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			global $post;
			$post_id = $post->ID ?? 0;
		}

		if ( ! $post_id ) {
			return '';
		}

		ob_start();
		self::render( $post_id, $position );
		return ob_get_clean();
	}

	/**
	 * Get alt text for a banner attachment.
	 * Falls back to the attachment title if no alt text is set.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string
	 */
	private static function get_alt_text( $attachment_id ) {
		if ( ! $attachment_id ) {
			return '';
		}
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( $alt ) {
			return $alt;
		}
		return get_the_title( $attachment_id ) ?: '';
	}
}