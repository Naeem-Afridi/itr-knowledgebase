<?php
/**
 * Frontend core class.
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
 * Class ITR_KB_Frontend
 *
 * Handles frontend asset loading and template routing.
 */
class ITR_KB_Frontend {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( ! ITR_KB_Utils::is_kb_page() ) {
			return;
		}

		// Load Google Fonts if set.
		$this->maybe_load_google_fonts();

		// Dashicons are only enqueued for logged-in users by default in WordPress.
		// Explicitly enqueue them so icons display for all visitors.
		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'itr-kb-frontend',
			ITR_KB_URL . 'assets/css/itr-kb-frontend.css',
			array(),
			$this->version
		);

		// Inject dynamic CSS from settings.
		$this->inject_dynamic_css();
	}

	/**
	 * Load Google Fonts if heading or body font is set.
	 *
	 * @return void
	 */
	private function maybe_load_google_fonts() {
		$system_fonts = array( 'Arial', 'Georgia', 'Times New Roman', 'Verdana', 'Trebuchet MS', 'Courier New', 'Impact' );

		$heading_font = sanitize_text_field( get_option( 'itr_kb_font_heading', '' ) );
		$body_font    = sanitize_text_field( get_option( 'itr_kb_font_body', '' ) );

		$google_fonts = array();

		if ( $heading_font && ! in_array( $heading_font, $system_fonts, true ) ) {
			$google_fonts[] = str_replace( ' ', '+', $heading_font ) . ':wght@400;600;700';
		}

		if ( $body_font && $body_font !== $heading_font && ! in_array( $body_font, $system_fonts, true ) ) {
			$google_fonts[] = str_replace( ' ', '+', $body_font ) . ':wght@400;500';
		}

		if ( ! empty( $google_fonts ) ) {
			$google_fonts_url = 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $google_fonts ) . '&display=swap';
			wp_enqueue_style( 'itr-kb-google-fonts', $google_fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters
		}
	}

	/**
	 * Enqueue frontend scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! ITR_KB_Utils::is_kb_page() ) {
			return;
		}

		wp_enqueue_script(
			'itr-kb-frontend',
			ITR_KB_URL . 'assets/js/itr-kb-frontend.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			'itr-kb-frontend',
			'itrKbFrontend',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'restUrl'          => rest_url( 'itr-kb/v1/' ),
				'nonce'            => wp_create_nonce( 'itr_kb_frontend_nonce' ),
				'restNonce'        => wp_create_nonce( 'wp_rest' ),
				'searchEnabled'    => (bool) get_option( 'itr_kb_search_enabled', true ),
				'searchHighlight'  => (bool) get_option( 'itr_kb_search_highlight', true ),
				'tocEnabled'       => (bool) get_option( 'itr_kb_toc_enabled', true ),
				'backToTopEnabled' => (bool) get_option( 'itr_kb_back_to_top_enabled', true ),
				'siteName'         => get_bloginfo( 'name' ),
				'logoUrl'          => function_exists( 'get_custom_logo' ) ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'medium' ) : '',
				'strings'          => array(
					'searchPlaceholder' => esc_html__( 'Search articles...', 'itr-knowledgebase' ),
					'noResults'         => esc_html__( 'No articles found.', 'itr-knowledgebase' ),
					'searching'         => esc_html__( 'Searching...', 'itr-knowledgebase' ),
					'viewAll'           => esc_html__( 'View all results', 'itr-knowledgebase' ),
				),
			)
		);
	}

	/**
	 * Load plugin templates instead of theme templates for KB pages.
	 *
	 * Skipped when Elementor (free or Pro) is controlling the template so
	 * Theme Builder single/archive templates are not blocked.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function load_templates( $template ) {
		if ( is_singular( 'itr_kb_article' ) ) {
			if ( $this->is_elementor_controlled( 'single' ) ) {
				return $template;
			}
			$plugin_template = ITR_KB_Utils::get_template_path( 'single-itr-kb.php' );
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		if ( is_post_type_archive( 'itr_kb_article' ) || is_tax( 'itr_kb_category' ) || is_tax( 'itr_kb_tag' ) ) {
			if ( $this->is_elementor_controlled( 'archive' ) ) {
				return $template;
			}
			$plugin_template = ITR_KB_Utils::get_template_path( 'archive-itr-kb.php' );
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	/**
	 * Check whether Elementor (free or Pro) should control the current template.
	 *
	 * Returns true in two situations:
	 *  1. The current post was built with the Elementor editor (free or Pro).
	 *  2. Elementor Pro Theme Builder has a registered template for the given
	 *     location ('single' or 'archive').
	 *
	 * @param string $location 'single' or 'archive'.
	 * @return bool
	 */
	private function is_elementor_controlled( $location = 'single' ) {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return false;
		}

		// Post/page was edited with Elementor editor.
		$post_id = get_queried_object_id();
		if ( $post_id && 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
			return true;
		}

		// Elementor Pro Theme Builder has a template for this location.
		if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
			try {
				$locations_manager = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_locations_manager();
				if ( $locations_manager && method_exists( $locations_manager, 'get_document_for_location' ) ) {
					if ( $locations_manager->get_document_for_location( $location ) ) {
						return true;
					}
				}
			} catch ( \Throwable $e ) {
				// Theme Builder API unavailable — fall through to plugin template.
			}
		}

		return false;
	}

	/**
	 * Inject CSS variables from plugin settings into <head>.
	 *
	 * @return void
	 */
	private function inject_dynamic_css() {
		$o = function( $key, $default ) {
			return sanitize_hex_color( get_option( $key, $default ) ) ?: $default;
		};

		$heading_font = sanitize_text_field( get_option( 'itr_kb_font_heading', '' ) );
		$body_font    = sanitize_text_field( get_option( 'itr_kb_font_body', '' ) );

		$css = '
		:root {
			/* Typography */
			--itr-kb-font-heading: ' . ( $heading_font ? "'" . $heading_font . "', " : '' ) . 'inherit;
			--itr-kb-font-body:    ' . ( $body_font ? "'" . $body_font . "', " : '' ) . 'inherit;

			/* Global */
			--itr-kb-primary:        ' . $o( 'itr_kb_color_primary',      '#0073aa' ) . ';
			--itr-kb-primary-dark:   ' . $o( 'itr_kb_color_primary_dark', '#005a87' ) . ';
			--itr-kb-heading:        ' . $o( 'itr_kb_color_heading',       '#23282d' ) . ';
			--itr-kb-text:           ' . $o( 'itr_kb_color_text',          '#444444' ) . ';
			--itr-kb-text-light:     ' . $o( 'itr_kb_color_text_light',    '#767676' ) . ';
			--itr-kb-link:           ' . $o( 'itr_kb_color_link',          '#0073aa' ) . ';
			--itr-kb-border:         ' . $o( 'itr_kb_color_border',        '#e2e4e7' ) . ';
			--itr-kb-bg:             ' . $o( 'itr_kb_color_bg',            '#f8f9fa' ) . ';
			--itr-kb-card-bg:        ' . $o( 'itr_kb_color_card_bg',       '#ffffff' ) . ';

			/* Buttons */
			--itr-kb-btn-bg:         ' . $o( 'itr_kb_btn_bg',             '#0073aa' ) . ';
			--itr-kb-btn-text:       ' . $o( 'itr_kb_btn_text',           '#ffffff' ) . ';
			--itr-kb-btn-hover:      ' . $o( 'itr_kb_btn_bg_hover',       '#005a87' ) . ';
			--itr-kb-btn-radius:     ' . absint( get_option( 'itr_kb_btn_radius', 6 ) ) . 'px;

			/* Search */
			--itr-kb-search-input-bg:   ' . $o( 'itr_kb_search_input_bg',   '#ffffff' ) . ';
			--itr-kb-search-input-text: ' . $o( 'itr_kb_search_input_text', '#444444' ) . ';
			--itr-kb-search-border:     ' . $o( 'itr_kb_search_border',     '#e2e4e7' ) . ';
			--itr-kb-search-btn-bg:     ' . $o( 'itr_kb_search_btn_bg',     '#0073aa' ) . ';
			--itr-kb-search-btn-icon:   ' . $o( 'itr_kb_search_btn_icon',   '#ffffff' ) . ';
			--itr-kb-search-radius:     ' . absint( get_option( 'itr_kb_search_radius', 6 ) ) . 'px;

			/* Sidebar */
			--itr-kb-sidebar-bg:          ' . $o( 'itr_kb_sidebar_bg',          '#ffffff' ) . ';
			--itr-kb-sidebar-link:        ' . $o( 'itr_kb_sidebar_link',         '#23282d' ) . ';
			--itr-kb-sidebar-link-active: ' . $o( 'itr_kb_sidebar_link_active',  '#0073aa' ) . ';
			--itr-kb-sidebar-border:      ' . $o( 'itr_kb_sidebar_border',       '#e2e4e7' ) . ';

			/* Article */
			--itr-kb-article-title:     ' . $o( 'itr_kb_article_title',     '#23282d' ) . ';
			--itr-kb-article-body:      ' . $o( 'itr_kb_article_body',      '#444444' ) . ';
			--itr-kb-article-link:      ' . $o( 'itr_kb_article_link',      '#0073aa' ) . ';
			--itr-kb-article-meta:      ' . $o( 'itr_kb_article_meta',      '#767676' ) . ';
			--itr-kb-article-font-size: ' . absint( get_option( 'itr_kb_article_font_size', 16 ) ) . 'px;

			/* TOC */
			--itr-kb-toc-bg:           ' . $o( 'itr_kb_toc_bg',           '#f8f9fa' ) . ';
			--itr-kb-toc-border:       ' . $o( 'itr_kb_toc_border',       '#0073aa' ) . ';
			--itr-kb-toc-title:        ' . $o( 'itr_kb_toc_title',        '#23282d' ) . ';
			--itr-kb-toc-link:         ' . $o( 'itr_kb_toc_link',         '#444444' ) . ';
			--itr-kb-toc-link-active:  ' . $o( 'itr_kb_toc_link_active',  '#0073aa' ) . ';

			/* Category Cards */
			--itr-kb-cat-card-bg:      ' . $o( 'itr_kb_cat_card_bg',      '#ffffff' ) . ';
			--itr-kb-cat-card-title:   ' . $o( 'itr_kb_cat_card_title',   '#23282d' ) . ';
			--itr-kb-cat-card-border:  ' . $o( 'itr_kb_cat_card_border',  '#e2e4e7' ) . ';
			--itr-kb-cat-card-link:    ' . $o( 'itr_kb_cat_card_link',    '#0073aa' ) . ';
			--itr-kb-cat-card-radius:  ' . absint( get_option( 'itr_kb_cat_card_radius', 10 ) ) . 'px;

			/* Author Box */
			--itr-kb-author-box-bg:     ' . $o( 'itr_kb_author_box_bg',     '#f8f9fa' ) . ';
			--itr-kb-author-box-name:   ' . $o( 'itr_kb_author_box_name',   '#23282d' ) . ';
			--itr-kb-author-box-bio:    ' . $o( 'itr_kb_author_box_bio',    '#767676' ) . ';
			--itr-kb-author-box-border: ' . $o( 'itr_kb_author_box_border', '#e2e4e7' ) . ';

			/* Navigation */
			--itr-kb-nav-bg:     ' . $o( 'itr_kb_nav_bg',     '#f8f9fa' ) . ';
			--itr-kb-nav-border: ' . $o( 'itr_kb_nav_border', '#e2e4e7' ) . ';
			--itr-kb-nav-link:   ' . $o( 'itr_kb_nav_link',   '#23282d' ) . ';
			--itr-kb-nav-label:  ' . $o( 'itr_kb_nav_label',  '#767676' ) . ';

			/* Banner */
			--itr-kb-banner-from:  ' . $o( 'itr_kb_banner_bg_from', '#1a237e' ) . ';
			--itr-kb-banner-to:    ' . $o( 'itr_kb_banner_bg_to',   '#3949ab' ) . ';
			--itr-kb-banner-title: ' . $o( 'itr_kb_banner_title',   '#ffffff' ) . ';
		}';

		// Apply font families.
		if ( $heading_font ) {
			$css .= '
			.itr-kb-single-article__title,
			.itr-kb-single-article__body h2,
			.itr-kb-single-article__body h3,
			.itr-kb-single-article__body h4,
			.itr-kb-archive-banner__title,
			.itr-kb-archive-section__title,
			.itr-kb-cat-card__title,
			.itr-kb-cat-acc-simple__name,
			.itr-kb-cat-acc-card__title {
				font-family: var(--itr-kb-font-heading);
			}';
		}

		if ( $body_font ) {
			$css .= '
			.itr-kb-single-article__body,
			.itr-kb-archive-wrap,
			.itr-kb-toc,
			.itr-kb-author-box,
			.itr-kb-article-nav {
				font-family: var(--itr-kb-font-body);
			}';
		}

		// Apply button variables to actual selectors.
		$css .= '
		.itr-kb-search-bar__submit,
		.itr-kb-archive-banner__submit,
		.itr-kb-back-to-top {
			background-color: var(--itr-kb-btn-bg) !important;
			color: var(--itr-kb-btn-text) !important;
		}
		.itr-kb-search-bar__submit:hover,
		.itr-kb-back-to-top:hover {
			background-color: var(--itr-kb-btn-hover) !important;
		}
		.itr-kb-search-bar__input-wrap,
		.itr-kb-archive-banner__input-wrap {
			border-radius: var(--itr-kb-search-radius) !important;
		}
		.itr-kb-search-bar__input {
			background-color: var(--itr-kb-search-input-bg) !important;
			color: var(--itr-kb-search-input-text) !important;
		}
		.itr-kb-search-bar__input-wrap { border-color: var(--itr-kb-search-border) !important; }
		.itr-kb-search-bar__submit { background-color: var(--itr-kb-search-btn-bg) !important; }
		.itr-kb-search-bar__submit .dashicons { color: var(--itr-kb-search-btn-icon) !important; }
		.itr-kb-archive-banner {
			background: linear-gradient( 135deg, var(--itr-kb-banner-from) 0%, var(--itr-kb-banner-to) 100% ) !important;
		}
		.itr-kb-archive-banner__title { color: var(--itr-kb-banner-title) !important; }
		.itr-kb-single-sidebar,
		.itr-kb-archive-sidebar { background-color: var(--itr-kb-sidebar-bg); border-color: var(--itr-kb-sidebar-border); }
		.itr-kb-single-sidebar__link,
		.itr-kb-archive-sidebar__link { color: var(--itr-kb-sidebar-link); }
		.itr-kb-single-sidebar__link--active,
		.itr-kb-archive-sidebar__link--active { color: var(--itr-kb-sidebar-link-active); }
		.itr-kb-single-article__title { color: var(--itr-kb-article-title); }
		.itr-kb-single-article__body { font-size: var(--itr-kb-article-font-size); color: var(--itr-kb-article-body); }
		.itr-kb-single-article__body a { color: var(--itr-kb-article-link); }
		.itr-kb-single-article__meta { color: var(--itr-kb-article-meta); }
		.itr-kb-toc { background-color: var(--itr-kb-toc-bg); border-left-color: var(--itr-kb-toc-border); }
		.itr-kb-toc__title { color: var(--itr-kb-toc-title); }
		.itr-kb-toc__link { color: var(--itr-kb-toc-link); }
		.itr-kb-toc__link--active { color: var(--itr-kb-toc-link-active); }
		.itr-kb-cat-card { background-color: var(--itr-kb-cat-card-bg); border-color: var(--itr-kb-cat-card-border); border-radius: var(--itr-kb-cat-card-radius); }
		.itr-kb-cat-card__title a { color: var(--itr-kb-cat-card-title); }
		.itr-kb-cat-card__item-link { color: var(--itr-kb-cat-card-link); }
		.itr-kb-author-box { background-color: var(--itr-kb-author-box-bg); border-color: var(--itr-kb-author-box-border); }
		.itr-kb-author-box__name { color: var(--itr-kb-author-box-name); }
		.itr-kb-author-box__bio { color: var(--itr-kb-author-box-bio); }
		.itr-kb-article-nav__item { background-color: var(--itr-kb-nav-bg); border-color: var(--itr-kb-nav-border); }
		.itr-kb-article-nav__link { color: var(--itr-kb-nav-link); }
		.itr-kb-article-nav__label { color: var(--itr-kb-nav-label); }
		';

		// Custom CSS.
		$custom_css = wp_strip_all_tags( get_option( 'itr_kb_custom_css', '' ) );
		if ( ! empty( $custom_css ) ) {
			$css .= "\n/* ITR KB Custom CSS */\n" . $custom_css;
		}

		wp_add_inline_style( 'itr-kb-frontend', $css );
	}
}