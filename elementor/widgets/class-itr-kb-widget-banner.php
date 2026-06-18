<?php
/**
 * Elementor Widget: KB Banner
 *
 * Resolves and renders a single banner position for the current KB article.
 * Banner images are set per-category and resolved via the inheritance engine.
 * Used inside custom Elementor single-article templates.
 *
 * Place this widget anywhere in an Elementor template — in the right column,
 * below the TOC, mid-content, etc. Pick the position and the correct banner
 * for the article's category is shown automatically.
 *
 * @package ITR_Knowledgebase
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ITR_Knowledgebase\Includes\ITR_KB_Banner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ITR_KB_Widget_Banner extends Widget_Base {

	public function get_name()       { return 'itr-kb-banner'; }
	public function get_title()      { return esc_html__( 'KB Banner', 'itr-knowledgebase' ); }
	public function get_icon()       { return 'eicon-image'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords()   { return array( 'banner', 'ad', 'kb', 'knowledgebase' ); }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Banner', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'position', array(
			'label'   => esc_html__( 'Banner Position', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'desktop_toc',
			'options' => array(
				'desktop_toc'        => esc_html__( 'Desktop TOC Banner', 'itr-knowledgebase' ),
				'desktop_categories' => esc_html__( 'Desktop Categories Banner', 'itr-knowledgebase' ),
				'mobile_top'         => esc_html__( 'Mobile Top Banner', 'itr-knowledgebase' ),
				'mobile_bottom'      => esc_html__( 'Mobile Bottom Banner', 'itr-knowledgebase' ),
			),
		) );

		$this->add_control( 'notice', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'Banner images are set per-category in KB Articles → Categories. The correct banner is resolved automatically from the article\'s category hierarchy. Place this widget anywhere in your template — the position label identifies which of the 4 banner slots to use.', 'itr-knowledgebase' ),
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );

		$this->end_controls_section();

		// ── Banner: Layout ───────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_banner_layout', array(
			'label' => esc_html__( 'Layout', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'banner_max_width', array(
			'label'      => esc_html__( 'Max Width', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 100, 'max' => 1200 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-banner__img' => 'max-width: {{SIZE}}{{UNIT}}; width: 100%;' ),
		) );

		$this->add_responsive_control( 'banner_align', array(
			'label'   => esc_html__( 'Alignment', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => array(
				'flex-start' => array( 'title' => esc_html__( 'Left', 'itr-knowledgebase' ), 'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => esc_html__( 'Center', 'itr-knowledgebase' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => esc_html__( 'Right', 'itr-knowledgebase' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .itr-kb-banner' => 'justify-content: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'banner_margin', array(
			'label'      => esc_html__( 'Margin', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-banner' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Banner: Image ─────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_banner_image', array(
			'label' => esc_html__( 'Image', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-banner__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'banner_opacity', array(
			'label'     => esc_html__( 'Opacity', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
			'selectors' => array( '{{WRAPPER}} .itr-kb-banner__img' => 'opacity: {{SIZE}};' ),
		) );

		$this->add_control( 'banner_hover_opacity', array(
			'label'     => esc_html__( 'Hover Opacity', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
			'selectors' => array( '{{WRAPPER}} .itr-kb-banner__img:hover' => 'opacity: {{SIZE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'banner_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-banner__img',
		) );

		$this->add_group_control( \Elementor\Group_Control_Css_Filter::get_type(), array(
			'name'     => 'banner_css_filter',
			'selector' => '{{WRAPPER}} .itr-kb-banner__img',
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$position = sanitize_key( $settings['position'] ?? 'desktop_toc' );

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$labels = array(
				'desktop_toc'        => 'Desktop TOC Banner',
				'desktop_categories' => 'Desktop Categories Banner',
				'mobile_top'         => 'Mobile Top Banner',
				'mobile_bottom'      => 'Mobile Bottom Banner',
			);
			echo '<div style="background:#f0f4ff;border:2px dashed #9ab0e0;border-radius:6px;padding:20px;text-align:center;color:#666;font-size:13px;">';
			echo '<span class="dashicons dashicons-images-alt2" style="font-size:24px;display:block;margin-bottom:8px;color:#9ab0e0;"></span>';
			echo '<strong>' . esc_html__( 'KB Banner', 'itr-knowledgebase' ) . '</strong><br/>';
			echo '<span style="color:#185fa5;font-weight:500;">' . esc_html( $labels[ $position ] ?? $position ) . '</span><br/>';
			echo '<span style="font-size:11px;color:#888;">Resolved from category inheritance at render time</span>';
			echo '</div>';
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		ITR_KB_Banner::render( $post_id, $position );
	}
}
