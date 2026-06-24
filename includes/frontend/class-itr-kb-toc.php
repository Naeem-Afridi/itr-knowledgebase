<?php
/**
 * Table of Contents generator.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/frontend
 */

namespace ITR_Knowledgebase\Frontend;

use ITR_Knowledgebase\Helpers\ITR_KB_Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_TOC
 *
 * Auto-generates a Table of Contents from article headings (H2, H3, H4).
 * Injects anchor IDs into headings and outputs the TOC above content.
 *
 * Features:
 * - Parses H2, H3, H4 headings from content
 * - Adds unique anchor IDs to each heading
 * - Outputs sticky TOC above content
 * - Can be disabled globally or per-article
 * - Available as standalone method for Elementor widget
 */
class ITR_KB_TOC {

	/**
	 * Heading tags to parse.
	 *
	 * @var array
	 */
	private $heading_tags = array( 'h2', 'h3', 'h4' );

	/**
	 * Inject TOC into article content via the_content filter.
	 * Disabled on single KB articles because the template renders
	 * the TOC in the right sidebar directly.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function inject_toc( $content ) {
		// Single KB articles use the right sidebar TOC from the template.
		// No injection needed here.
		return $content;
	}

	/**
	 * Parse headings from content.
	 *
	 * @param string $content Post content.
	 * @return array Array of heading data: [ tag, text, id ].
	 */
	public function parse_headings( $content ) {
    $headings = array();
    // Capture attributes group separately so we can detect existing id=
    $pattern  = '/<(h[2-4])([^>]*)>(.*?)<\/h[2-4]>/is';

    preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

    $id_counts = array();

    foreach ( $matches as $match ) {
        $tag   = strtolower( $match[1] );
        $attrs = $match[2];
        $text  = wp_strip_all_tags( $match[3] );

        // If Gutenberg (or any editor) already assigned an id to this
        // heading, use it directly so the TOC link matches the real DOM id.
        // Otherwise generate our own itr-kb- prefixed id as before.
        $existing_id = '';
        if ( preg_match( '/\bid=["\']([^"\']+)["\']/', $attrs, $id_match ) ) {
            $existing_id = $id_match[1];
        }

        $id = $existing_id ? $existing_id : $this->generate_heading_id( $text, $id_counts );

        $headings[] = array(
            'tag'  => $tag,
            'text' => $text,
            'id'   => $id,
        );
    }

    return $headings;
}

	// public function parse_headings( $content ) {
	// 	$headings = array();
	// 	$pattern  = '/<(h[2-4])[^>]*>(.*?)<\/h[2-4]>/is';

	// 	preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

	// 	$id_counts = array();

	// 	foreach ( $matches as $match ) {
	// 		$tag  = strtolower( $match[1] );
	// 		$text = wp_strip_all_tags( $match[2] );
	// 		$id   = $this->generate_heading_id( $text, $id_counts );

	// 		$headings[] = array(
	// 			'tag'  => $tag,
	// 			'text' => $text,
	// 			'id'   => $id,
	// 		);
	// 	}

	// 	return $headings;
	// }

	/**
	 * Generate a unique slug-based ID for a heading.
	 *
	 * @param string $text      Heading text.
	 * @param array  &$id_counts Reference to ID count tracker for deduplication.
	 * @return string
	 */
	private function generate_heading_id( $text, &$id_counts ) {
		$id = 'itr-kb-' . sanitize_title( $text );

		if ( isset( $id_counts[ $id ] ) ) {
			$id_counts[ $id ]++;
			$id = $id . '-' . $id_counts[ $id ];
		} else {
			$id_counts[ $id ] = 0;
		}

		return $id;
	}

	/**
	 * Add anchor IDs to headings in content (public wrapper).
	 *
	 * @param string $content  Post content.
	 * @param array  $headings Parsed headings with IDs.
	 * @return string
	 */
	public function add_heading_anchors_public( $content, $headings ) {
		return $this->add_heading_anchors( $content, $headings );
	}

	/**
	 * Add anchor IDs to headings in content.
	 *
	 * @param string $content  Post content.
	 * @param array  $headings Parsed headings with IDs.
	 * @return string
	 */

	private function add_heading_anchors( $content, $headings ) {
    if ( empty( $headings ) || empty( $content ) ) {
        return $content;
    }

    // Build a slug → id lookup keyed by the normalised heading text.
    // Text-based matching is immune to the index drift that occurs when
    // add_heading_anchors() runs on raw Gutenberg content (block comments
    // included) while parse_headings() ran on a slightly different version,
    // causing position-based indices to go out of sync for specific headings.
    $id_map = array();
    foreach ( $headings as $heading ) {
$key = sanitize_title( html_entity_decode( $heading['text'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ); 
       if ( $key && ! isset( $id_map[ $key ] ) ) {
            $id_map[ $key ] = $heading['id'];
        }
    }

    // The u flag treats the pattern and subject as UTF-8, which correctly
    // handles multi-byte characters like curly quotes (\u{201C}, \u{201D})
    // that appear in heading text — without it those sequences can confuse
    // the regex engine and cause specific headings to be silently skipped.
    $pattern = '/<(h[2-4])([^>]*)>(.*?)<\/(h[2-4])>/isu';

    $result = preg_replace_callback(
        $pattern,
        function ( $matches ) use ( $id_map ) {
            $tag   = $matches[1];
            $attrs = $matches[2];
            $inner = $matches[3];

            // Skip if heading already has an id.
            if ( strpos( $attrs, 'id=' ) !== false ) {
                return $matches[0];
            }

            // Normalise inner HTML to a slug for lookup — decode entities
            // first so &#8220; and the actual Unicode curly quote both
            // produce the same slug and correctly match the id_map key.
            $slug = sanitize_title(
                html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES | ENT_HTML5, 'UTF-8' )
            );

            if ( ! isset( $id_map[ $slug ] ) ) {
                return $matches[0];
            }

            return sprintf(
                '<%s%s id="%s">%s</%s>',
                esc_html( $tag ),
                $attrs,
                esc_attr( $id_map[ $slug ] ),
                $inner,
                esc_html( $tag )
            );
        },
        $content
    );

    // preg_replace_callback returns null on error — fall back to original.
    return $result ?? $content;
}

	// private function add_heading_anchors( $content, $headings ) {
	// 	$index   = 0;
	// 	$pattern = '/<(h[2-4])([^>]*)>(.*?)<\/(h[2-4])>/is';

	// 	$content = preg_replace_callback(
	// 		$pattern,
	// 		function ( $matches ) use ( $headings, &$index ) {
	// 			if ( ! isset( $headings[ $index ] ) ) {
	// 				return $matches[0];
	// 			}

	// 			$tag        = $matches[1];
	// 			$attrs      = $matches[2];
	// 			$text       = $matches[3];
	// 			$heading_id = esc_attr( $headings[ $index ]['id'] );

	// 			// Avoid duplicate IDs if heading already has one.
	// 			if ( strpos( $attrs, 'id=' ) !== false ) {
	// 				$index++;
	// 				return $matches[0];
	// 			}

	// 			$index++;

	// 			return sprintf(
	// 				'<%s%s id="%s">%s</%s>',
	// 				esc_html( $tag ),
	// 				$attrs,
	// 				$heading_id,
	// 				$text,
	// 				esc_html( $tag )
	// 			);
	// 		},
	// 		$content
	// 	);

	// 	return $content;
	// }

	/**
	 * Build TOC HTML from headings array.
	 *
	 * @param array $headings Parsed headings.
	 * @return string
	 */
	public function build_toc_html( $headings ) {
		if ( empty( $headings ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="itr-kb-toc" id="itr-kb-toc" role="navigation" aria-label="<?php esc_attr_e( 'Table of Contents', 'itr-knowledgebase' ); ?>">
			<div class="itr-kb-toc__header">
				<span class="itr-kb-toc__title"><?php esc_html_e( 'Table of Contents', 'itr-knowledgebase' ); ?></span>
				<button
					class="itr-kb-toc__toggle"
					aria-expanded="true"
					aria-controls="itr-kb-toc-list"
					aria-label="<?php esc_attr_e( 'Toggle Table of Contents', 'itr-knowledgebase' ); ?>"
				>
					<span class="itr-kb-toc__toggle-icon" aria-hidden="true">&#9660;</span>
				</button>
			</div>

			<ol class="itr-kb-toc__list" id="itr-kb-toc-list">
				<?php
				$prev_level = 2;
				$depth      = 0;

				foreach ( $headings as $heading ) {
					$level = (int) substr( $heading['tag'], 1 );

					if ( $level > $prev_level ) {
						echo '<ol class="itr-kb-toc__sublist">';
						$depth++;
					} elseif ( $level < $prev_level && $depth > 0 ) {
						echo '</ol>';
						$depth--;
					}

					printf(
						'<li class="itr-kb-toc__item itr-kb-toc__item--%s"><a href="#%s" class="itr-kb-toc__link">%s</a></li>',
						esc_attr( $heading['tag'] ),
						esc_attr( $heading['id'] ),
						esc_html( $heading['text'] )
					);

					$prev_level = $level;
				}

				// Close any open sublists.
				for ( $i = 0; $i < $depth; $i++ ) {
					echo '</ol>';
				}
				?>
			</ol>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get TOC for a specific post (for Elementor widget or shortcode use).
	 *
	 * @param int $post_id Post ID.
	 * @return string TOC HTML or empty string.
	 */
	public static function get_toc_for_post( $post_id ) {
		$post = get_post( absint( $post_id ) );

		if ( ! $post ) {
			return '';
		}

		$instance = new self();
		$headings = $instance->parse_headings( $post->post_content );

		if ( count( $headings ) < 2 ) {
			return '';
		}

		return $instance->build_toc_html( $headings );
	}
}