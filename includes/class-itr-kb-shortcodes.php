<?php
/**
 * ITR KB Shortcodes
 *
 * Provides shortcodes for displaying KB article meta, share/download buttons,
 * and author/reviewer names anywhere via Elementor's Shortcode widget or
 * standard WordPress content.
 *
 * Available shortcodes:
 *   [itr_kb_meta]            — Posted date, Updated date, Author, Reviewed by
 *   [itr_kb_share]           — Share dropdown + Print + PDF buttons
 *   [itr_kb_author_name]     — Author name (plain text)
 *   [itr_kb_reviewer_names]  — Reviewer names, comma-separated
 *
 * @package ITR_Knowledgebase
 */

namespace ITR_Knowledgebase\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ITR_KB_Shortcodes {

	/**
	 * Register all shortcodes.
	 *
	 * @return void
	 */
	public static function register() {
		add_shortcode( 'itr_kb_meta',            array( static::class, 'render_meta' ) );
		add_shortcode( 'itr_kb_share',           array( static::class, 'render_share' ) );
		add_shortcode( 'itr_kb_print',           array( static::class, 'render_print' ) );
		add_shortcode( 'itr_kb_pdf',             array( static::class, 'render_pdf' ) );
		add_shortcode( 'itr_kb_author_name',     array( static::class, 'render_author_name' ) );
		add_shortcode( 'itr_kb_reviewer_names',  array( static::class, 'render_reviewer_names' ) );
		add_shortcode( 'itr_kb_nav',             array( static::class, 'render_nav' ) );
		add_shortcode( 'itr_kb_prev',            array( static::class, 'render_prev' ) );
		add_shortcode( 'itr_kb_next',            array( static::class, 'render_next' ) );
	}

	// =========================================================================
	// [itr_kb_meta]
	// =========================================================================

	/**
	 * Render article meta row.
	 *
	 * Attributes:
	 *   show_posted   yes|no  Show posted date.           Default: yes
	 *   show_updated  yes|no  Show last updated date.     Default: yes
	 *   show_author   yes|no  Show author name.           Default: yes
	 *   show_reviewer yes|no  Show reviewer name(s).      Default: yes
	 *   separator     string  Separator between items.    Default: " · "
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_meta( $atts ) {
		$atts = shortcode_atts( array(
			'show_posted'   => 'yes',
			'show_updated'  => 'yes',
			'show_author'   => 'yes',
			'show_reviewer' => 'yes',
			'separator'     => '&nbsp;&nbsp; ',
		), $atts, 'itr_kb_meta' );

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$items = array();

		// Posted date.
		if ( 'yes' === $atts['show_posted'] ) {
			$items[] = '<span class="itr-kb-meta__posted">'
				. esc_html__( 'Posted:', 'itr-knowledgebase' ) . ' '
				. '<time datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">'
				. esc_html( get_the_date( '', $post_id ) )
				. '</time>'
				. '</span>';
		}

		// Updated date (only if different from posted date).
		if ( 'yes' === $atts['show_updated'] ) {
			$updated = get_the_modified_date( '', $post_id );
			$posted  = get_the_date( '', $post_id );
			if ( $updated && $updated !== $posted ) {
				$items[] = '<span class="itr-kb-meta__updated">'
					. esc_html__( 'Updated On:', 'itr-knowledgebase' ) . ' '
					. '<time datetime="' . esc_attr( get_the_modified_date( 'c', $post_id ) ) . '">'
					. esc_html( $updated )
					. '</time>'
					. '</span>';
			}
		}

		// Author name.
		if ( 'yes' === $atts['show_author'] ) {
			$author_name = self::get_author_name( $post_id );
			if ( $author_name ) {
				$items[] = '<span class="itr-kb-meta__author">'
					. esc_html__( 'Author:', 'itr-knowledgebase' ) . ' '
					. '<p class="itr-meta-author">' . esc_html( $author_name ) . '</p>'
					. '</span>';
			}
		}

		// Reviewer names.
		if ( 'yes' === $atts['show_reviewer'] ) {
			$reviewer_names = self::get_reviewer_names( $post_id );
			if ( ! empty( $reviewer_names ) ) {
				$items[] = '<span class="itr-kb-meta__reviewer">'
					. esc_html__( 'Reviewed by:', 'itr-knowledgebase' ) . ' '
					. '<p class="itr-meta-author">' . esc_html( implode( ', ', $reviewer_names ) ) . '</p>'
					. '</span>';
			}
		}

		if ( empty( $items ) ) {
			return '';
		}

		return '<div class="itr-kb-meta">'
			. implode( $atts['separator'], $items )
			. '</div>';
	}

	// =========================================================================
	// [itr_kb_print]
	// =========================================================================

	/**
	 * Render print button only.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_print( $atts ) {
		$atts = shortcode_atts( array(
			'hide_text'      => 'no',
			'custom_icon_id' => 0,
			'label'          => '',
		), $atts, 'itr_kb_print' );

		$hide_text = 'yes' === $atts['hide_text'];
		$label     = $atts['label'] ?: esc_html__( 'Print', 'itr-knowledgebase' );
		$icon_html = self::get_action_icon( absint( $atts['custom_icon_id'] ), 'dashicons-printer' );

		ob_start();
		?>
		<button
			class="itr-kb-single-article__action-btn itr-kb-single-article__print"
			onclick="itrKbPrintArticleButton()"
			aria-label="<?php echo esc_attr( $label ); ?>"
		>
			<?php echo wp_kses_post( $icon_html ); ?>
			<?php if ( ! $hide_text ) : ?>
				<span class="itr-kb-sc-btn-text"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</button>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [itr_kb_pdf]
	// =========================================================================

	/**
	 * Render PDF download button only.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_pdf( $atts ) {
		$atts = shortcode_atts( array(
			'hide_text'      => 'no',
			'custom_icon_id' => 0,
			'label'          => '',
		), $atts, 'itr_kb_pdf' );

		$hide_text = 'yes' === $atts['hide_text'];
		$label     = $atts['label'] ?: esc_html__( 'PDF', 'itr-knowledgebase' );
		$icon_html = self::get_action_icon( absint( $atts['custom_icon_id'] ), 'dashicons-download' );

		ob_start();
		?>
		<button
			class="itr-kb-single-article__action-btn itr-kb-single-article__download"
			onclick="itrKbPrintArticle()"
			aria-label="<?php echo esc_attr( $label ); ?>"
		>
			<?php echo wp_kses_post( $icon_html ); ?>
			<?php if ( ! $hide_text ) : ?>
				<span class="itr-kb-sc-btn-text"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</button>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [itr_kb_share]
	// =========================================================================

	/**
	 * Render share dropdown button only.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render_share( $atts ) {
		$atts = shortcode_atts( array(
			'hide_text'      => 'no',
			'custom_icon_id' => 0,
			'label'          => '',
		), $atts, 'itr_kb_share' );

		$post_id   = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$hide_text = 'yes' === $atts['hide_text'];
		$label     = $atts['label'] ?: esc_html__( 'Share', 'itr-knowledgebase' );
		$icon_html = self::get_action_icon( absint( $atts['custom_icon_id'] ), 'dashicons-share' );
		$url       = esc_url( get_permalink( $post_id ) );
		$title     = esc_attr( get_the_title( $post_id ) );
		$uid       = 'itr-kb-sc-share-' . $post_id;

		ob_start();
		?>
		<div class="itr-kb-share" data-url="<?php echo esc_attr( $url ); ?>" data-title="<?php echo esc_attr( $title ); ?>">
			<button
				class="itr-kb-single-article__action-btn itr-kb-share__toggle"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $uid ); ?>"
				aria-label="<?php echo esc_attr( $label ); ?>"
			>
				<?php echo wp_kses_post( $icon_html ); ?>
				<?php if ( ! $hide_text ) : ?>
					<span class="itr-kb-sc-btn-text"><?php echo esc_html( $label ); ?></span>
				<?php endif; ?>
			</button>
			<div class="itr-kb-share__dropdown" id="<?php echo esc_attr( $uid ); ?>" hidden>
				<a class="itr-kb-share__option" href="#" data-platform="twitter" target="_blank" rel="noopener noreferrer">
					<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
					Twitter / X
				</a>
				<a class="itr-kb-share__option" href="#" data-platform="facebook" target="_blank" rel="noopener noreferrer">
					<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
					Facebook
				</a>
				<a class="itr-kb-share__option" href="#" data-platform="linkedin" target="_blank" rel="noopener noreferrer">
					<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
					LinkedIn
				</a>
				<a class="itr-kb-share__option" href="#" data-platform="whatsapp" target="_blank" rel="noopener noreferrer">
					<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
					WhatsApp
				</a>
				<a class="itr-kb-share__option" href="#" data-platform="email">
					<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Email', 'itr-knowledgebase' ); ?>
				</a>
				<button class="itr-kb-share__option itr-kb-share__copy" data-platform="copy">
					<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
					<span class="itr-kb-share__copy-label"><?php esc_html_e( 'Copy Link', 'itr-knowledgebase' ); ?></span>
				</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// [itr_kb_author_name]
	// =========================================================================

	/**
	 * Render author name only.
	 *
	 * Attributes:
	 *   before  string  Text before the name.  Default: ""
	 *   after   string  Text after the name.   Default: ""
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_author_name( $atts ) {
		$atts = shortcode_atts( array(
			'before' => '',
			'after'  => '',
		), $atts, 'itr_kb_author_name' );

		$name = self::get_author_name( get_the_ID() );
		if ( ! $name ) {
			return '';
		}

		return esc_html( $atts['before'] )
			. '<span class="itr-kb-sc-author-name">' . esc_html( $name ) . '</span>'
			. esc_html( $atts['after'] );
	}

	// =========================================================================
	// [itr_kb_reviewer_names]
	// =========================================================================

	/**
	 * Render reviewer name(s) only.
	 *
	 * Attributes:
	 *   separator  string  Separator between names.  Default: ", "
	 *   before     string  Text before names.        Default: ""
	 *   after      string  Text after names.         Default: ""
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_reviewer_names( $atts ) {
		$atts = shortcode_atts( array(
			'separator' => ', ',
			'before'    => '',
			'after'     => '',
		), $atts, 'itr_kb_reviewer_names' );

		$names = self::get_reviewer_names( get_the_ID() );
		if ( empty( $names ) ) {
			return '';
		}

		return esc_html( $atts['before'] )
			. '<span class="itr-kb-sc-reviewer-names">' . esc_html( implode( $atts['separator'], $names ) ) . '</span>'
			. esc_html( $atts['after'] );
	}


	// =========================================================================
	// Action icon helper
	// =========================================================================

	/**
	 * Get icon HTML for action buttons.
	 * Priority: custom media library image → default dashicon.
	 *
	 * @param int    $icon_id        Attachment ID (0 = use default).
	 * @param string $default_dashicon Dashicon class name e.g. 'dashicons-printer'.
	 * @return string HTML.
	 */
	private static function get_action_icon( $icon_id, $default_dashicon ) {
		if ( $icon_id ) {
			$url = wp_get_attachment_image_url( $icon_id, 'thumbnail' );
			if ( $url ) {
				return '<img class="itr-kb-sc-btn-icon" src="' . esc_url( $url ) . '" alt="" aria-hidden="true" />';
			}
		}
		return '<span class="dashicons ' . esc_attr( $default_dashicon ) . '" aria-hidden="true"></span>';
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Get the KB author name for a post.
	 *
	 * @param int $post_id
	 * @return string Empty string if not set.
	 */
	private static function get_author_name( $post_id ) {
		if ( ! $post_id ) {
			return '';
		}
		$author_id = get_post_meta( $post_id, '_itr_kb_author_id', true );
		if ( ! $author_id ) {
			return '';
		}
		$author = get_post( absint( $author_id ) );
		if ( ! $author || 'itr_kb_author' !== $author->post_type ) {
			return '';
		}
		return $author->post_title;
	}

	/**
	 * Get an array of KB reviewer names for a post.
	 *
	 * @param int $post_id
	 * @return string[]
	 */
	private static function get_reviewer_names( $post_id ) {
		if ( ! $post_id ) {
			return array();
		}
		$reviewer_ids = get_post_meta( $post_id, '_itr_kb_reviewer_ids', true );
		if ( empty( $reviewer_ids ) || ! is_array( $reviewer_ids ) ) {
			return array();
		}
		$names = array();
		foreach ( $reviewer_ids as $rid ) {
			$rp = get_post( absint( $rid ) );
			if ( $rp && 'itr_kb_author' === $rp->post_type ) {
				$names[] = $rp->post_title;
			}
		}
		return $names;
	}

	// =========================================================================
	// [itr_kb_nav] / [itr_kb_prev] / [itr_kb_next]
	// =========================================================================

	/**
	 * [itr_kb_nav] — renders both previous and next article navigation.
	 *
	 * @param array $atts Unused.
	 * @return string
	 */
	public static function render_nav( $atts ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$nav = new \ITR_Knowledgebase\Frontend\ITR_KB_Navigation();
		return $nav->get_navigation_html( $post_id );
	}

	/**
	 * [itr_kb_prev] — renders only the previous article link.
	 *
	 * Attributes:
	 *   label  string  Link label prefix. Default: "← Previous"
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_prev( $atts ) {
		$atts = shortcode_atts( array(
			'label' => '&larr; ' . esc_html__( 'Previous Article', 'itr-knowledgebase' ),
		), $atts, 'itr_kb_prev' );

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$prev = \ITR_Knowledgebase\Helpers\ITR_KB_Query::get_previous_article( $post_id );
		if ( ! $prev ) {
			return '';
		}

		$url   = get_permalink( $prev->ID );
		$title = get_the_title( $prev->ID );

		return '<a class="itr-kb-sc-nav itr-kb-sc-nav--prev" href="' . esc_url( $url ) . '">'
			. '<span class="itr-kb-sc-nav__label">' . wp_kses_post( $atts['label'] ) . '</span>'
			. '<span class="itr-kb-sc-nav__title">' . esc_html( $title ) . '</span>'
			. '</a>';
	}

	/**
	 * [itr_kb_next] — renders only the next article link.
	 *
	 * Attributes:
	 *   label  string  Link label prefix. Default: "Next →"
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_next( $atts ) {
		$atts = shortcode_atts( array(
			'label' => esc_html__( 'Next Article', 'itr-knowledgebase' ) . ' &rarr;',
		), $atts, 'itr_kb_next' );

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$next = \ITR_Knowledgebase\Helpers\ITR_KB_Query::get_next_article( $post_id );
		if ( ! $next ) {
			return '';
		}

		$url   = get_permalink( $next->ID );
		$title = get_the_title( $next->ID );

		return '<a class="itr-kb-sc-nav itr-kb-sc-nav--next" href="' . esc_url( $url ) . '">'
			. '<span class="itr-kb-sc-nav__label">' . wp_kses_post( $atts['label'] ) . '</span>'
			. '<span class="itr-kb-sc-nav__title">' . esc_html( $title ) . '</span>'
			. '</a>';
	}

}