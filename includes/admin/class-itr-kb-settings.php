<?php
/**
 * Plugin settings registration.
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
 * Class ITR_KB_Settings
 *
 * Registers all plugin settings using the WordPress Settings API.
 * All inputs are sanitized and validated before saving.
 */
class ITR_KB_Settings {

	/**
	 * All registered option keys with their sanitize callbacks.
	 * Used for bulk sanitization and uninstall cleanup.
	 *
	 * @var array
	 */
	public static $option_keys = array();

	/**
	 * Register all settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		$this->register_general_settings();
		$this->register_permalink_settings();
		$this->register_search_settings();
		$this->register_styling_settings();
	}

	// =========================================================================
	// General Settings
	// =========================================================================

	private function register_general_settings() {
		$group   = 'itr_kb_settings_general';
		$page    = 'itr_kb_settings_general';
		$section = 'itr_kb_general_section';

		$this->register_option( $group, 'itr_kb_toc_enabled',           'boolean', true );
		$this->register_option( $group, 'itr_kb_breadcrumb_enabled',     'boolean', true );
		$this->register_option( $group, 'itr_kb_print_enabled',          'boolean', true );
		$this->register_option( $group, 'itr_kb_back_to_top_enabled',    'boolean', true );
		$this->register_option( $group, 'itr_kb_articles_per_page',      'integer', 10 );

		add_settings_section( $section, '', '__return_false', $page );

		add_settings_field( 'itr_kb_toc_enabled', esc_html__( 'Table of Contents', 'itr-knowledgebase' ),
			array( $this, 'render_checkbox' ), $page, $section,
			array( 'name' => 'itr_kb_toc_enabled', 'label' => esc_html__( 'Enable Table of Contents on articles', 'itr-knowledgebase' ) ) );

		add_settings_field( 'itr_kb_breadcrumb_enabled', esc_html__( 'Breadcrumbs', 'itr-knowledgebase' ),
			array( $this, 'render_checkbox' ), $page, $section,
			array( 'name' => 'itr_kb_breadcrumb_enabled', 'label' => esc_html__( 'Enable breadcrumb navigation', 'itr-knowledgebase' ) ) );

		add_settings_field( 'itr_kb_print_enabled', esc_html__( 'Print Support', 'itr-knowledgebase' ),
			array( $this, 'render_checkbox' ), $page, $section,
			array( 'name' => 'itr_kb_print_enabled', 'label' => esc_html__( 'Show Print / PDF button on articles', 'itr-knowledgebase' ) ) );

		add_settings_field( 'itr_kb_back_to_top_enabled', esc_html__( 'Back to Top', 'itr-knowledgebase' ),
			array( $this, 'render_checkbox' ), $page, $section,
			array( 'name' => 'itr_kb_back_to_top_enabled', 'label' => esc_html__( 'Show Back to Top button', 'itr-knowledgebase' ) ) );

		add_settings_field( 'itr_kb_articles_per_page', esc_html__( 'Articles Per Page', 'itr-knowledgebase' ),
			array( $this, 'render_number' ), $page, $section,
			array( 'name' => 'itr_kb_articles_per_page', 'min' => 1, 'max' => 100,
				'desc' => esc_html__( 'Number of articles shown per page on category/archive pages.', 'itr-knowledgebase' ) ) );
	}

	// =========================================================================
	// Permalink Settings
	// =========================================================================

	private function register_permalink_settings() {
		$group   = 'itr_kb_settings_permalink';
		$page    = 'itr_kb_settings_permalink';
		$section = 'itr_kb_permalink_section';

		$this->register_option( $group, 'itr_kb_slug',              'slug',    'knowledgebase' );
		$this->register_option( $group, 'itr_kb_category_slug',     'slug',    'kb-category' );
		$this->register_option( $group, 'itr_kb_category_in_url',   'boolean', false );

		add_settings_section( $section, '', function() {
			echo '<div class="itr-kb-settings-notice itr-kb-settings-notice--info">';
			echo '<span class="dashicons dashicons-info"></span> ';
			echo esc_html__( 'After changing slugs, go to Settings → Permalinks and click Save Changes to flush rewrite rules.', 'itr-knowledgebase' );
			echo '</div>';
		}, $page );

		add_settings_field( 'itr_kb_slug', esc_html__( 'Knowledge Base Slug', 'itr-knowledgebase' ),
			array( $this, 'render_text' ), $page, $section,
			array( 'name' => 'itr_kb_slug', 'desc' => esc_html__( 'URL slug for KB articles. Default: knowledgebase', 'itr-knowledgebase' ),
				'placeholder' => 'knowledgebase' ) );

		add_settings_field( 'itr_kb_category_slug', esc_html__( 'Category Slug', 'itr-knowledgebase' ),
			array( $this, 'render_text' ), $page, $section,
			array( 'name' => 'itr_kb_category_slug', 'desc' => esc_html__( 'URL slug for KB categories. Default: kb-category', 'itr-knowledgebase' ),
				'placeholder' => 'kb-category' ) );

		add_settings_field( 'itr_kb_category_in_url', esc_html__( 'Category in Article URL', 'itr-knowledgebase' ),
			array( $this, 'render_checkbox' ), $page, $section,
			array(
				'name'  => 'itr_kb_category_in_url',
				'label' => esc_html__( 'Include full category hierarchy in article URLs', 'itr-knowledgebase' ),
				'desc'  => esc_html__( 'When enabled, article links generated from category pages include the full category path (e.g. /kb/parent/child/article/). The canonical URL used in admin and sitemaps is unchanged. After toggling, go to Settings → Permalinks and Save Changes.', 'itr-knowledgebase' ),
			) );
	}

	// =========================================================================
	// Search Settings
	// =========================================================================

	private function register_search_settings() {
		$group   = 'itr_kb_settings_search';
		$page    = 'itr_kb_settings_search';
		$section = 'itr_kb_search_section';

		$this->register_option( $group, 'itr_kb_search_enabled',       'boolean', true );
		$this->register_option( $group, 'itr_kb_search_results_count', 'integer', 5 );
		$this->register_option( $group, 'itr_kb_search_highlight',     'boolean', true );

		add_settings_section( $section, '', '__return_false', $page );

		add_settings_field( 'itr_kb_search_enabled', esc_html__( 'Enable Live Search', 'itr-knowledgebase' ),
			array( $this, 'render_checkbox' ), $page, $section,
			array( 'name' => 'itr_kb_search_enabled', 'label' => esc_html__( 'Enable AJAX live search with autocomplete', 'itr-knowledgebase' ) ) );

		add_settings_field( 'itr_kb_search_results_count', esc_html__( 'Live Results Count', 'itr-knowledgebase' ),
			array( $this, 'render_number' ), $page, $section,
			array( 'name' => 'itr_kb_search_results_count', 'min' => 1, 'max' => 20,
				'desc' => esc_html__( 'Number of results to show in live search dropdown.', 'itr-knowledgebase' ) ) );

		add_settings_field( 'itr_kb_search_highlight', esc_html__( 'Highlight Terms', 'itr-knowledgebase' ),
			array( $this, 'render_checkbox' ), $page, $section,
			array( 'name' => 'itr_kb_search_highlight', 'label' => esc_html__( 'Highlight matched search terms in results', 'itr-knowledgebase' ) ) );
	}

	// =========================================================================
	// Styling Settings
	// =========================================================================

	private function register_styling_settings() {
		$group = 'itr_kb_settings_styling';
		$page  = 'itr_kb_settings_styling';

		// ── Typography ───────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_font_heading', 'text', '' );
		$this->register_option( $group, 'itr_kb_font_body',    'text', '' );

		// ── Global Colors ────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_color_primary',     'color', '#0073aa' );
		$this->register_option( $group, 'itr_kb_color_primary_dark','color', '#005a87' );
		$this->register_option( $group, 'itr_kb_color_heading',     'color', '#23282d' );
		$this->register_option( $group, 'itr_kb_color_text',        'color', '#444444' );
		$this->register_option( $group, 'itr_kb_color_text_light',  'color', '#767676' );
		$this->register_option( $group, 'itr_kb_color_link',        'color', '#0073aa' );
		$this->register_option( $group, 'itr_kb_color_border',      'color', '#e2e4e7' );
		$this->register_option( $group, 'itr_kb_color_bg',          'color', '#f8f9fa' );
		$this->register_option( $group, 'itr_kb_color_card_bg',     'color', '#ffffff' );

		// ── Banner ───────────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_banner_bg_from',    'color', '#1a237e' );
		$this->register_option( $group, 'itr_kb_banner_bg_to',      'color', '#3949ab' );
		$this->register_option( $group, 'itr_kb_banner_title',      'color', '#ffffff' );
		$this->register_option( $group, 'itr_kb_banner_title_text', 'text',  'Hi, How can we help?' );

		// ── Buttons ──────────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_btn_bg',            'color', '#0073aa' );
		$this->register_option( $group, 'itr_kb_btn_text',          'color', '#ffffff' );
		$this->register_option( $group, 'itr_kb_btn_bg_hover',      'color', '#005a87' );
		$this->register_option( $group, 'itr_kb_btn_radius',        'integer', 6 );

		// ── Sidebar ──────────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_sidebar_bg',        'color', '#ffffff' );
		$this->register_option( $group, 'itr_kb_sidebar_link',      'color', '#23282d' );
		$this->register_option( $group, 'itr_kb_sidebar_link_active','color', '#0073aa' );
		$this->register_option( $group, 'itr_kb_sidebar_border',    'color', '#e2e4e7' );

		// ── Article ──────────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_article_title',     'color', '#23282d' );
		$this->register_option( $group, 'itr_kb_article_body',      'color', '#444444' );
		$this->register_option( $group, 'itr_kb_article_link',      'color', '#0073aa' );
		$this->register_option( $group, 'itr_kb_article_meta',      'color', '#767676' );
		$this->register_option( $group, 'itr_kb_article_font_size', 'integer', 16 );

		// ── TOC ──────────────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_toc_bg',            'color', '#f8f9fa' );
		$this->register_option( $group, 'itr_kb_toc_border',        'color', '#0073aa' );
		$this->register_option( $group, 'itr_kb_toc_title',         'color', '#23282d' );
		$this->register_option( $group, 'itr_kb_toc_link',          'color', '#444444' );
		$this->register_option( $group, 'itr_kb_toc_link_active',   'color', '#0073aa' );

		// ── Search Bar ───────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_search_input_bg',   'color', '#ffffff' );
		$this->register_option( $group, 'itr_kb_search_input_text', 'color', '#444444' );
		$this->register_option( $group, 'itr_kb_search_btn_bg',     'color', '#0073aa' );
		$this->register_option( $group, 'itr_kb_search_btn_icon',   'color', '#ffffff' );
		$this->register_option( $group, 'itr_kb_search_border',     'color', '#e2e4e7' );
		$this->register_option( $group, 'itr_kb_search_radius',     'integer', 6 );

		// ── Category Cards ───────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_cat_card_bg',       'color', '#ffffff' );
		$this->register_option( $group, 'itr_kb_cat_card_title',    'color', '#23282d' );
		$this->register_option( $group, 'itr_kb_cat_card_border',   'color', '#e2e4e7' );
		$this->register_option( $group, 'itr_kb_cat_card_link',     'color', '#0073aa' );
		$this->register_option( $group, 'itr_kb_cat_card_radius',   'integer', 10 );

		// ── Author Box ───────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_author_box_bg',     'color', '#f8f9fa' );
		$this->register_option( $group, 'itr_kb_author_box_name',   'color', '#23282d' );
		$this->register_option( $group, 'itr_kb_author_box_bio',    'color', '#767676' );
		$this->register_option( $group, 'itr_kb_author_box_border', 'color', '#e2e4e7' );

		// ── Navigation ───────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_nav_bg',            'color', '#f8f9fa' );
		$this->register_option( $group, 'itr_kb_nav_border',        'color', '#e2e4e7' );
		$this->register_option( $group, 'itr_kb_nav_link',          'color', '#23282d' );
		$this->register_option( $group, 'itr_kb_nav_label',         'color', '#767676' );

		// ── Custom CSS ───────────────────────────────────────────────
		$this->register_option( $group, 'itr_kb_custom_css', 'css', '' );

		// Sections are registered but rendered via menu page callback.
		// No add_settings_section/field needed — custom render handles it.
		add_settings_section( 'itr_kb_styling_section', '', '__return_false', $page );
	}

	// =========================================================================
	// Helper: register a single option with proper sanitization
	// =========================================================================

	/**
	 * Register a single option with proper type-based sanitization.
	 *
	 * @param string $group   Settings group.
	 * @param string $key     Option key.
	 * @param string $type    Type: color|boolean|integer|text|slug|css.
	 * @param mixed  $default Default value.
	 */
	private function register_option( $group, $key, $type, $default ) {
		self::$option_keys[ $key ] = $type;

		$sanitize_cb = array( $this, 'sanitize_' . $type );

		register_setting( $group, $key, array(
			'sanitize_callback' => is_callable( $sanitize_cb ) ? $sanitize_cb : 'sanitize_text_field',
			'default'           => $default,
		));
	}

	// =========================================================================
	// Sanitize callbacks (called by WordPress Settings API on save)
	// =========================================================================

	public function sanitize_color( $value ) {
		$value = sanitize_text_field( wp_unslash( $value ) );
		if ( '' === $value ) return '';
		// Allow hex colors only.
		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value ) ) {
			return $value;
		}
		return '';
	}

	public function sanitize_boolean( $value ) {
		return rest_sanitize_boolean( $value );
	}

	public function sanitize_integer( $value ) {
		return absint( $value );
	}

	public function sanitize_text( $value ) {
		return sanitize_text_field( wp_unslash( $value ) );
	}

	public function sanitize_slug( $value ) {
		return sanitize_title( wp_unslash( $value ) );
	}

	public function sanitize_css( $value ) {
		// Strip any PHP tags and allow only CSS content.
		$value = wp_unslash( $value );
		$value = wp_strip_all_tags( $value );
		// Remove any attempts at JS injection inside CSS.
		$value = preg_replace( '/(javascript|expression|behavior|vbscript)\s*:/i', '', $value );
		return $value;
	}

	// =========================================================================
	// Styling page — custom render (not using WordPress add_settings_field
	// for styling tab because we need a grouped, visual UI)
	// =========================================================================

	/**
	 * Render the full styling settings page content.
	 * Called from ITR_KB_Menu::render_settings_page() for the styling tab.
	 *
	 * @return void
	 */
	public function render_styling_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle save.
		if ( isset( $_POST['itr_kb_styling_save'] ) ) {
			$this->handle_styling_save();
		}

		$this->output_styling_form();
	}

	/**
	 * Handle styling form submission with full security checks.
	 *
	 * @return void
	 */
	private function handle_styling_save() {
		// Verify nonce.
		if (
			! isset( $_POST['itr_kb_styling_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['itr_kb_styling_nonce'] ) ), 'itr_kb_styling_save' )
		) {
			add_settings_error( 'itr_kb_styling', 'nonce_failed', esc_html__( 'Security check failed. Please try again.', 'itr-knowledgebase' ), 'error' );
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error( 'itr_kb_styling', 'no_permission', esc_html__( 'You do not have permission to save settings.', 'itr-knowledgebase' ), 'error' );
			return;
		}

		$saved = 0;

		// Save each option by its type.
		foreach ( self::$option_keys as $key => $type ) {
			// Only process styling options.
			if ( strpos( $key, 'itr_kb_color_' ) === false
				&& strpos( $key, 'itr_kb_banner_' ) === false
				&& strpos( $key, 'itr_kb_btn_' ) === false
				&& strpos( $key, 'itr_kb_sidebar_' ) === false
				&& strpos( $key, 'itr_kb_article_' ) === false
				&& strpos( $key, 'itr_kb_toc_' ) === false
				&& strpos( $key, 'itr_kb_search_input' ) === false
				&& strpos( $key, 'itr_kb_search_btn' ) === false
				&& strpos( $key, 'itr_kb_search_border' ) === false
				&& strpos( $key, 'itr_kb_search_radius' ) === false
				&& strpos( $key, 'itr_kb_cat_card' ) === false
				&& strpos( $key, 'itr_kb_author_box' ) === false
				&& strpos( $key, 'itr_kb_nav_' ) === false
				&& $key !== 'itr_kb_custom_css'
			) {
				continue;
			}

			if ( ! isset( $_POST[ $key ] ) ) {
				// Checkboxes not set = false.
				if ( 'boolean' === $type ) {
					update_option( $key, false );
					$saved++;
				}
				continue;
			}

			$raw   = $_POST[ $key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$clean = $this->sanitize_by_type( $raw, $type );
			update_option( $key, $clean );
			$saved++;
		}

		if ( $saved > 0 ) {
			add_settings_error( 'itr_kb_styling', 'saved', esc_html__( 'Styling settings saved.', 'itr-knowledgebase' ), 'updated' );
		}
	}

	/**
	 * Sanitize a value by type.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $type  Type key.
	 * @return mixed
	 */
	private function sanitize_by_type( $value, $type ) {
		switch ( $type ) {
			case 'color':   return $this->sanitize_color( $value );
			case 'boolean': return $this->sanitize_boolean( $value );
			case 'integer': return $this->sanitize_integer( $value );
			case 'text':    return $this->sanitize_text( $value );
			case 'slug':    return $this->sanitize_slug( $value );
			case 'css':     return $this->sanitize_css( $value );
			default:        return sanitize_text_field( wp_unslash( $value ) );
		}
	}

	/**
	 * Output the full styling settings form.
	 *
	 * @return void
	 */
	private function output_styling_form() {
		settings_errors( 'itr_kb_styling' );
		?>
		<form method="post" action="" class="itr-kb-styling-form">
			<?php wp_nonce_field( 'itr_kb_styling_save', 'itr_kb_styling_nonce' ); ?>
			<input type="hidden" name="itr_kb_styling_save" value="1" />

			<div class="itr-kb-styling-grid">

				<!-- ── Typography ─────────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-editor-textcolor"></span>
						<?php esc_html_e( 'Typography', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc">
						<?php esc_html_e( 'Font families for headings and body text. Leave empty to inherit from your theme.', 'itr-knowledgebase' ); ?>
					</p>
					<div class="itr-kb-styling-fields">
						<?php $this->font_row( 'itr_kb_font_heading', esc_html__( 'Heading Font', 'itr-knowledgebase' ) ); ?>
						<?php $this->font_row( 'itr_kb_font_body',    esc_html__( 'Body Text Font', 'itr-knowledgebase' ) ); ?>
					</div>
					<p style="margin-top:10px;font-size:12px;color:#646970;">
						<?php esc_html_e( 'Enter any Google Fonts name (e.g. Roboto, Open Sans, Lato) or a system font (e.g. Arial, Georgia). The font will be loaded from Google Fonts automatically.', 'itr-knowledgebase' ); ?>
					</p>
				</div>

				<!-- ── Global Colors ──────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-art"></span>
						<?php esc_html_e( 'Global Colors', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Base colors used throughout the plugin.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_color_primary',      esc_html__( 'Primary Color', 'itr-knowledgebase' ),        '#0073aa' ); ?>
						<?php $this->color_row( 'itr_kb_color_primary_dark', esc_html__( 'Primary Hover Color', 'itr-knowledgebase' ),   '#005a87' ); ?>
						<?php $this->color_row( 'itr_kb_color_heading',      esc_html__( 'Heading Color', 'itr-knowledgebase' ),         '#23282d' ); ?>
						<?php $this->color_row( 'itr_kb_color_text',         esc_html__( 'Body Text Color', 'itr-knowledgebase' ),       '#444444' ); ?>
						<?php $this->color_row( 'itr_kb_color_text_light',   esc_html__( 'Muted Text Color', 'itr-knowledgebase' ),      '#767676' ); ?>
						<?php $this->color_row( 'itr_kb_color_link',         esc_html__( 'Link Color', 'itr-knowledgebase' ),            '#0073aa' ); ?>
						<?php $this->color_row( 'itr_kb_color_border',       esc_html__( 'Border Color', 'itr-knowledgebase' ),          '#e2e4e7' ); ?>
						<?php $this->color_row( 'itr_kb_color_bg',           esc_html__( 'Background Color', 'itr-knowledgebase' ),      '#f8f9fa' ); ?>
						<?php $this->color_row( 'itr_kb_color_card_bg',      esc_html__( 'Card Background', 'itr-knowledgebase' ),       '#ffffff' ); ?>
					</div>
				</div>

				<!-- ── Banner ────────────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-cover-image"></span>
						<?php esc_html_e( 'Banner', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Main Knowledge Base archive banner with search.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_banner_bg_from',  esc_html__( 'Gradient From Color', 'itr-knowledgebase' ), '#1a237e' ); ?>
						<?php $this->color_row( 'itr_kb_banner_bg_to',    esc_html__( 'Gradient To Color', 'itr-knowledgebase' ),   '#3949ab' ); ?>
						<?php $this->color_row( 'itr_kb_banner_title',    esc_html__( 'Title Color', 'itr-knowledgebase' ),         '#ffffff' ); ?>
						<?php $this->text_row( 'itr_kb_banner_title_text', esc_html__( 'Banner Title Text', 'itr-knowledgebase' ),
							esc_html__( 'Hi, How can we help?', 'itr-knowledgebase' ),
							esc_html__( 'The heading text shown in the banner.', 'itr-knowledgebase' ) ); ?>
					</div>
				</div>

				<!-- ── Buttons ───────────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-button"></span>
						<?php esc_html_e( 'Buttons', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Search button, print button, back to top etc.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_btn_bg',       esc_html__( 'Button Background', 'itr-knowledgebase' ),       '#0073aa' ); ?>
						<?php $this->color_row( 'itr_kb_btn_text',     esc_html__( 'Button Text Color', 'itr-knowledgebase' ),       '#ffffff' ); ?>
						<?php $this->color_row( 'itr_kb_btn_bg_hover', esc_html__( 'Button Hover Background', 'itr-knowledgebase' ), '#005a87' ); ?>
						<?php $this->number_row( 'itr_kb_btn_radius',  esc_html__( 'Border Radius (px)', 'itr-knowledgebase' ),      1, 50, esc_html__( 'Rounded corners for buttons.', 'itr-knowledgebase' ) ); ?>
					</div>
				</div>

				<!-- ── Search Bar ────────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Search Bar', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Search input and button styling.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_search_input_bg',   esc_html__( 'Input Background', 'itr-knowledgebase' ),    '#ffffff' ); ?>
						<?php $this->color_row( 'itr_kb_search_input_text', esc_html__( 'Input Text Color', 'itr-knowledgebase' ),    '#444444' ); ?>
						<?php $this->color_row( 'itr_kb_search_border',     esc_html__( 'Input Border Color', 'itr-knowledgebase' ),  '#e2e4e7' ); ?>
						<?php $this->color_row( 'itr_kb_search_btn_bg',     esc_html__( 'Button Background', 'itr-knowledgebase' ),   '#0073aa' ); ?>
						<?php $this->color_row( 'itr_kb_search_btn_icon',   esc_html__( 'Button Icon Color', 'itr-knowledgebase' ),   '#ffffff' ); ?>
						<?php $this->number_row( 'itr_kb_search_radius',    esc_html__( 'Border Radius (px)', 'itr-knowledgebase' ),  0, 50, esc_html__( 'Rounded corners for search bar.', 'itr-knowledgebase' ) ); ?>
					</div>
				</div>

				<!-- ── Sidebar ───────────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-layout"></span>
						<?php esc_html_e( 'Sidebar', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Category tree sidebar on archive and single pages.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_sidebar_bg',          esc_html__( 'Background', 'itr-knowledgebase' ),          '#ffffff' ); ?>
						<?php $this->color_row( 'itr_kb_sidebar_link',        esc_html__( 'Link Color', 'itr-knowledgebase' ),           '#23282d' ); ?>
						<?php $this->color_row( 'itr_kb_sidebar_link_active', esc_html__( 'Active Link Color', 'itr-knowledgebase' ),    '#0073aa' ); ?>
						<?php $this->color_row( 'itr_kb_sidebar_border',      esc_html__( 'Border Color', 'itr-knowledgebase' ),         '#e2e4e7' ); ?>
					</div>
				</div>

				<!-- ── Article ───────────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-media-document"></span>
						<?php esc_html_e( 'Article', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Single article page typography and colors.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_article_title', esc_html__( 'Title Color', 'itr-knowledgebase' ),       '#23282d' ); ?>
						<?php $this->color_row( 'itr_kb_article_body',  esc_html__( 'Body Text Color', 'itr-knowledgebase' ),   '#444444' ); ?>
						<?php $this->color_row( 'itr_kb_article_link',  esc_html__( 'Link Color', 'itr-knowledgebase' ),        '#0073aa' ); ?>
						<?php $this->color_row( 'itr_kb_article_meta',  esc_html__( 'Meta Text Color', 'itr-knowledgebase' ),   '#767676' ); ?>
						<?php $this->number_row( 'itr_kb_article_font_size', esc_html__( 'Body Font Size (px)', 'itr-knowledgebase' ), 12, 24,
							esc_html__( 'Font size for article body text.', 'itr-knowledgebase' ) ); ?>
					</div>
				</div>

				<!-- ── Table of Contents ─────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-list-view"></span>
						<?php esc_html_e( 'Table of Contents', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'TOC box on single article pages.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_toc_bg',          esc_html__( 'Background', 'itr-knowledgebase' ),          '#f8f9fa' ); ?>
						<?php $this->color_row( 'itr_kb_toc_border',      esc_html__( 'Left Border Color', 'itr-knowledgebase' ),   '#0073aa' ); ?>
						<?php $this->color_row( 'itr_kb_toc_title',       esc_html__( 'Title Color', 'itr-knowledgebase' ),         '#23282d' ); ?>
						<?php $this->color_row( 'itr_kb_toc_link',        esc_html__( 'Link Color', 'itr-knowledgebase' ),          '#444444' ); ?>
						<?php $this->color_row( 'itr_kb_toc_link_active', esc_html__( 'Active Link Color', 'itr-knowledgebase' ),   '#0073aa' ); ?>
					</div>
				</div>

				<!-- ── Category Cards ────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-grid-view"></span>
						<?php esc_html_e( 'Category Cards', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Subcategory cards on category pages.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_cat_card_bg',     esc_html__( 'Card Background', 'itr-knowledgebase' ),    '#ffffff' ); ?>
						<?php $this->color_row( 'itr_kb_cat_card_title',  esc_html__( 'Title Color', 'itr-knowledgebase' ),        '#23282d' ); ?>
						<?php $this->color_row( 'itr_kb_cat_card_border', esc_html__( 'Border Color', 'itr-knowledgebase' ),       '#e2e4e7' ); ?>
						<?php $this->color_row( 'itr_kb_cat_card_link',   esc_html__( 'Article Link Color', 'itr-knowledgebase' ), '#0073aa' ); ?>
						<?php $this->number_row( 'itr_kb_cat_card_radius', esc_html__( 'Border Radius (px)', 'itr-knowledgebase' ), 0, 30,
							esc_html__( 'Rounded corners for category cards.', 'itr-knowledgebase' ) ); ?>
					</div>
				</div>

				<!-- ── Author Box ────────────────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-admin-users"></span>
						<?php esc_html_e( 'Author Box', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Author and reviewer box at bottom of articles.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_author_box_bg',     esc_html__( 'Background', 'itr-knowledgebase' ),     '#f8f9fa' ); ?>
						<?php $this->color_row( 'itr_kb_author_box_name',   esc_html__( 'Name Color', 'itr-knowledgebase' ),     '#23282d' ); ?>
						<?php $this->color_row( 'itr_kb_author_box_bio',    esc_html__( 'Bio Text Color', 'itr-knowledgebase' ), '#767676' ); ?>
						<?php $this->color_row( 'itr_kb_author_box_border', esc_html__( 'Border Color', 'itr-knowledgebase' ),   '#e2e4e7' ); ?>
					</div>
				</div>

				<!-- ── Prev/Next Navigation ──────────────── -->
				<div class="itr-kb-styling-group">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<?php esc_html_e( 'Article Navigation', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc"><?php esc_html_e( 'Previous / Next article navigation at bottom of articles.', 'itr-knowledgebase' ); ?></p>
					<div class="itr-kb-styling-fields">
						<?php $this->color_row( 'itr_kb_nav_bg',     esc_html__( 'Card Background', 'itr-knowledgebase' ), '#f8f9fa' ); ?>
						<?php $this->color_row( 'itr_kb_nav_border', esc_html__( 'Border Color', 'itr-knowledgebase' ),    '#e2e4e7' ); ?>
						<?php $this->color_row( 'itr_kb_nav_link',   esc_html__( 'Link Color', 'itr-knowledgebase' ),      '#23282d' ); ?>
						<?php $this->color_row( 'itr_kb_nav_label',  esc_html__( 'Label Color', 'itr-knowledgebase' ),     '#767676' ); ?>
					</div>
				</div>

				<!-- ── Custom CSS ────────────────────────── (full width) -->
				<div class="itr-kb-styling-group itr-kb-styling-group--full">
					<h3 class="itr-kb-styling-group__title">
						<span class="dashicons dashicons-editor-code"></span>
						<?php esc_html_e( 'Custom CSS', 'itr-knowledgebase' ); ?>
					</h3>
					<p class="itr-kb-styling-group__desc">
						<?php esc_html_e( 'Add your own CSS. Applied to the frontend only. Use', 'itr-knowledgebase' ); ?>
						<code>.itr-kb-*</code>
						<?php esc_html_e( 'selectors to target plugin elements.', 'itr-knowledgebase' ); ?>
					</p>
					<textarea
						name="itr_kb_custom_css"
						id="itr_kb_custom_css"
						rows="10"
						class="itr-kb-styling-textarea"
						placeholder="/* Your custom CSS here */&#10;.itr-kb-single-article__title { font-size: 32px; }"
						spellcheck="false"
					><?php echo esc_textarea( get_option( 'itr_kb_custom_css', '' ) ); ?></textarea>
				</div>

			</div><!-- .itr-kb-styling-grid -->

			<div class="itr-kb-styling-submit">
				<button type="submit" class="button button-primary button-large">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Styling Settings', 'itr-knowledgebase' ); ?>
				</button>
				<button type="button" class="button itr-kb-reset-btn" data-confirm="<?php esc_attr_e( 'Reset all styling to defaults? This cannot be undone.', 'itr-knowledgebase' ); ?>">
					<?php esc_html_e( 'Reset to Defaults', 'itr-knowledgebase' ); ?>
				</button>
			</div>

		</form>
		<?php
	}

	// =========================================================================
	// Field output helpers
	// =========================================================================

	/**
	 * Output a font family selector row.
	 */
	private function font_row( $name, $label ) {
		$value = sanitize_text_field( get_option( $name, '' ) );

		$popular_fonts = array(
			''              => esc_html__( '— Theme Default —', 'itr-knowledgebase' ),
			'Roboto'        => 'Roboto',
			'Open Sans'     => 'Open Sans',
			'Lato'          => 'Lato',
			'Montserrat'    => 'Montserrat',
			'Raleway'       => 'Raleway',
			'Poppins'       => 'Poppins',
			'Nunito'        => 'Nunito',
			'Inter'         => 'Inter',
			'Playfair Display' => 'Playfair Display',
			'Merriweather'  => 'Merriweather',
			'Georgia'       => 'Georgia (System)',
			'Arial'         => 'Arial (System)',
			'custom'        => esc_html__( 'Custom...', 'itr-knowledgebase' ),
		);

		$is_custom = $value && ! array_key_exists( $value, $popular_fonts );
		?>
		<div class="itr-kb-styling-field itr-kb-styling-field--font">
			<label for="<?php echo esc_attr( $name ); ?>_select" class="itr-kb-styling-field__label">
				<?php echo esc_html( $label ); ?>
			</label>
			<div class="itr-kb-font-wrap">
				<select
					id="<?php echo esc_attr( $name ); ?>_select"
					class="itr-kb-font-select"
					data-target="<?php echo esc_attr( $name ); ?>"
				>
					<?php foreach ( $popular_fonts as $font_val => $font_label ) : ?>
						<option
							value="<?php echo esc_attr( $font_val ); ?>"
							<?php selected( $is_custom ? 'custom' : $value, $font_val ); ?>
						>
							<?php echo esc_html( $font_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<input
					type="text"
					id="<?php echo esc_attr( $name ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. Open Sans', 'itr-knowledgebase' ); ?>"
					class="itr-kb-font-custom"
					style="<?php echo $is_custom ? '' : 'display:none;'; ?>"
				/>
			</div>
		</div>
		<?php
	}

	/**
	 * Output a color picker row.
	 */
	private function color_row( $name, $label, $default ) {
		$value = sanitize_hex_color( get_option( $name, $default ) ) ?: $default;
		?>
		<div class="itr-kb-styling-field">
			<label for="<?php echo esc_attr( $name ); ?>" class="itr-kb-styling-field__label">
				<?php echo esc_html( $label ); ?>
			</label>
			<div class="itr-kb-styling-field__color-wrap">
				<input
					type="color"
					id="<?php echo esc_attr( $name ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					class="itr-kb-color-input"
				/>
				<input
					type="text"
					class="itr-kb-color-hex"
					value="<?php echo esc_attr( $value ); ?>"
					maxlength="7"
					placeholder="#000000"
					aria-label="<?php echo esc_attr( $label . ' ' . __( 'hex value', 'itr-knowledgebase' ) ); ?>"
				/>
				<button type="button" class="itr-kb-color-reset" data-default="<?php echo esc_attr( $default ); ?>" title="<?php esc_attr_e( 'Reset to default', 'itr-knowledgebase' ); ?>">
					<span class="dashicons dashicons-image-rotate"></span>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Output a text field row.
	 */
	private function text_row( $name, $label, $placeholder = '', $desc = '' ) {
		$value = sanitize_text_field( get_option( $name, $placeholder ) );
		?>
		<div class="itr-kb-styling-field">
			<label for="<?php echo esc_attr( $name ); ?>" class="itr-kb-styling-field__label">
				<?php echo esc_html( $label ); ?>
			</label>
			<input
				type="text"
				id="<?php echo esc_attr( $name ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				class="regular-text"
			/>
			<?php if ( $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Output a number field row.
	 */
	private function number_row( $name, $label, $min, $max, $desc = '' ) {
		$value = absint( get_option( $name, $min ) );
		?>
		<div class="itr-kb-styling-field">
			<label for="<?php echo esc_attr( $name ); ?>" class="itr-kb-styling-field__label">
				<?php echo esc_html( $label ); ?>
			</label>
			<input
				type="number"
				id="<?php echo esc_attr( $name ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				min="<?php echo esc_attr( $min ); ?>"
				max="<?php echo esc_attr( $max ); ?>"
				class="small-text"
			/>
			<?php if ( $desc ) : ?>
				<p class="description"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Standard WP Settings API field renderers (for non-styling tabs)
	// =========================================================================

	public function render_checkbox( $args ) {
		$value = get_option( $args['name'] );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $args['name'] ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php echo esc_html( $args['label'] ?? '' ); ?>
		</label>
		<?php
	}

	public function render_text( $args ) {
		$value = sanitize_text_field( get_option( $args['name'], '' ) );
		?>
		<input
			type="text"
			name="<?php echo esc_attr( $args['name'] ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>"
			class="regular-text"
		/>
		<?php if ( ! empty( $args['desc'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['desc'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_number( $args ) {
		$value = absint( get_option( $args['name'], $args['min'] ?? 0 ) );
		?>
		<input
			type="number"
			name="<?php echo esc_attr( $args['name'] ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="<?php echo esc_attr( $args['min'] ?? 0 ); ?>"
			max="<?php echo esc_attr( $args['max'] ?? 100 ); ?>"
			class="small-text"
		/>
		<?php if ( ! empty( $args['desc'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['desc'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	// Legacy renderers (kept for backward compat).
	public function render_checkbox_field( $args ) { $args['name'] = $args['option_name']; $this->render_checkbox( $args ); }
	public function render_text_field( $args )     { $args['name'] = $args['option_name']; $this->render_text( $args ); }
	public function render_number_field( $args )   { $args['name'] = $args['option_name']; $this->render_number( $args ); }
	public function render_color_field( $args )    {
		$value = sanitize_hex_color( get_option( $args['option_name'], '#0073aa' ) ) ?: '#0073aa';
		echo '<input type="color" name="' . esc_attr( $args['option_name'] ) . '" value="' . esc_attr( $value ) . '" />';
	}
	public function render_textarea_field( $args ) {
		$value = wp_strip_all_tags( get_option( $args['option_name'], '' ) );
		echo '<textarea name="' . esc_attr( $args['option_name'] ) . '" rows="' . absint( $args['rows'] ?? 5 ) . '" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
		if ( ! empty( $args['description'] ) ) echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
	}
}