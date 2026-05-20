<?php
/**
 * Elementor Widget: KB Banner
 *
 * Resolves and renders a single banner position for the current KB article.
 * Used inside custom Elementor single-article templates.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor/widgets
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ITR_Knowledgebase\Includes\ITR_KB_Banner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Widget_Banner
 */
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
				'desktop_toc'        => esc_html__( 'Desktop — Below TOC Sidebar', 'itr-knowledgebase' ),
				'desktop_categories' => esc_html__( 'Desktop — Below Categories Sidebar', 'itr-knowledgebase' ),
				'mobile_top'         => esc_html__( 'Mobile — Above Article Content', 'itr-knowledgebase' ),
				'mobile_bottom'      => esc_html__( 'Mobile — Below Article Content', 'itr-knowledgebase' ),
			),
		) );

		$this->add_control( 'notice', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'The banner image and URL are set on each category in KB Articles → Categories. The correct banner is resolved automatically based on the article\'s category hierarchy.', 'itr-knowledgebase' ),
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );

		$this->end_controls_section();

		// ── Style ─────────────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style', array(
			'label' => esc_html__( 'Banner Style', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'banner_margin', array(
			'label'      => esc_html__( 'Margin', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-banner' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_control( 'border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-banner__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$position = sanitize_key( $settings['position'] ?? 'desktop_toc' );

		// In Elementor editor show a placeholder so the widget is visible.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$label = array(
				'desktop_toc'        => 'Desktop TOC Banner',
				'desktop_categories' => 'Desktop Categories Banner',
				'mobile_top'         => 'Mobile Top Banner',
				'mobile_bottom'      => 'Mobile Bottom Banner',
			);
			echo '<div style="background:#f0f4ff;border:2px dashed #9ab0e0;border-radius:6px;padding:20px;text-align:center;color:#666;font-size:13px;">';
			echo '<span class="dashicons dashicons-images-alt2" style="font-size:24px;display:block;margin-bottom:8px;color:#9ab0e0;"></span>';
			echo '<strong>' . esc_html( $label[ $position ] ?? $position ) . '</strong><br/>';
			echo '<span style="font-size:11px;">Banner resolved from category inheritance at render time</span>';
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