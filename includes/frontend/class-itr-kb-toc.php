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
		$pattern  = '/<(h[2-4])[^>]*>(.*?)<\/h[2-4]>/is';

		preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

		$id_counts = array();

		foreach ( $matches as $match ) {
			$tag  = strtolower( $match[1] );
			$text = wp_strip_all_tags( $match[2] );
			$id   = $this->generate_heading_id( $text, $id_counts );

			$headings[] = array(
				'tag'  => $tag,
				'text' => $text,
				'id'   => $id,
			);
		}

		return $headings;
	}

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
		$index   = 0;
		$pattern = '/<(h[2-4])([^>]*)>(.*?)<\/(h[2-4])>/is';

		$content = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $headings, &$index ) {
				if ( ! isset( $headings[ $index ] ) ) {
					return $matches[0];
				}

				$tag        = $matches[1];
				$attrs      = $matches[2];
				$text       = $matches[3];
				$heading_id = esc_attr( $headings[ $index ]['id'] );

				// Avoid duplicate IDs if heading already has one.
				if ( strpos( $attrs, 'id=' ) !== false ) {
					$index++;
					return $matches[0];
				}

				$index++;

				return sprintf(
					'<%s%s id="%s">%s</%s>',
					esc_html( $tag ),
					$attrs,
					$heading_id,
					$text,
					esc_html( $tag )
				);
			},
			$content
		);

		return $content;
	}

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