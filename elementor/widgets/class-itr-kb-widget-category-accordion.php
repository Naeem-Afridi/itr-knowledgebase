<?php
/**
 * Elementor Widget: KB Category Accordion
 *
 * Layout 1 — Simple: Icon + Category name + count, expand to show subcategory list
 * Layout 2 — Card with Image: Category image as background, expand replaces image with subcategory pills
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor/widgets
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ITR_Knowledgebase\Admin\ITR_KB_Category_Order;
use ITR_Knowledgebase\Taxonomies\ITR_KB_Category;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ITR_Knowledgebase\\Elementor\\Widgets\\ITR_KB_Widget_Category_Accordion' ) ) {
	return;
}

/**
 * Class ITR_KB_Widget_Category_Accordion
 */
class ITR_KB_Widget_Category_Accordion extends Widget_Base {

	public function get_name()       { return 'itr-kb-category-accordion'; }
	public function get_title()      { return esc_html__( 'KB Category Accordion', 'itr-knowledgebase' ); }
	public function get_icon()       { return 'eicon-accordion'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords()   { return array( 'category', 'accordion', 'kb', 'subcategory' ); }

	// =========================================================================
	// Helper — top-level categories for SELECT2.
	// =========================================================================

	private function get_parent_category_options() {
		$terms = get_terms( array(
			'taxonomy'   => 'itr_kb_category',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		$options = array();
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}
		return $options;
	}

	// =========================================================================
	// Controls
	// =========================================================================

	protected function register_controls() {

		// ── Content ──────────────────────────────────────────────────────────
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Category Accordion', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'layout', array(
			'label'   => esc_html__( 'Layout', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'simple',
			'options' => array(
				'simple' => esc_html__( 'Layout 1 — Simple Accordion', 'itr-knowledgebase' ),
				'card'   => esc_html__( 'Layout 2 — Card with Image', 'itr-knowledgebase' ),
			),
		) );

		// ── Source ───────────────────────────────────────────────────────────
		$this->add_control( 'source', array(
			'label'   => esc_html__( 'Source', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'auto',
			'options' => array(
				'auto'   => esc_html__( 'Auto — based on parent category ID', 'itr-knowledgebase' ),
				'manual' => esc_html__( 'Manual — select specific categories', 'itr-knowledgebase' ),
			),
		) );

		// ── Manual: repeater with drag-and-drop order ────────────────────────
		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'category_id', array(
			'label'       => esc_html__( 'Category', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::SELECT,
			'label_block' => true,
			'options'     => array( '' => esc_html__( '— Select a category —', 'itr-knowledgebase' ) ) + $this->get_parent_category_options(),
			'default'     => '',
		) );

		$repeater->add_control( 'custom_icon', array(
			'label'       => esc_html__( 'Custom Icon', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Override the icon for this card. Leave empty to use the category icon or default.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::ICONS,
			'default'     => array(),
		) );

		$repeater->add_control( 'custom_icon_color', array(
			'label'     => esc_html__( 'Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '',
		) );

		$cat_options_js = wp_json_encode( $this->get_parent_category_options() );

		$this->add_control( 'manual_categories_list', array(
			'label'       => esc_html__( 'Categories', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Add categories and drag the rows to set display order. Only top-level (parent) categories are listed.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => array(),
			'title_field' => '<# var opts = ' . $cat_options_js . '; print( opts[category_id] || "— select a category —" ); #>',
			'condition'   => array( 'source' => 'manual' ),
		) );

		// ── Auto: parent ID ───────────────────────────────────────────────────
		$this->add_control( 'parent_id', array(
			'label'       => esc_html__( 'Parent Category ID', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Leave 0 to show top-level categories.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
			'condition'   => array( 'source' => 'auto' ),
		) );

		$this->add_control( 'columns', array(
			'label'     => esc_html__( 'Columns', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '3',
			'options'   => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'condition' => array( 'layout' => 'card' ),
		) );

		$this->add_control( 'open_first', array(
			'label'        => esc_html__( 'Open First Category', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		) );

		$this->add_control( 'show_cta_button', array(
			'label'        => esc_html__( 'Show CTA Button', 'itr-knowledgebase' ),
			'description'  => esc_html__( 'Show a button at the bottom of each expanded panel.', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
			'condition'    => array( 'layout' => 'simple' ),
		) );

		$this->add_control( 'cta_label', array(
			'label'     => esc_html__( 'Button Label', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => esc_html__( 'View All Categories', 'itr-knowledgebase' ),
			'condition' => array( 'layout' => 'simple', 'show_cta_button' => 'yes' ),
		) );

		$this->add_control( 'cta_url', array(
			'label'       => esc_html__( 'Button URL', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Leave empty to link to the category page.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::URL,
			'placeholder' => 'https://',
			'condition'   => array( 'layout' => 'simple', 'show_cta_button' => 'yes' ),
		) );

		$this->add_control( 'cta_icon', array(
			'label'     => esc_html__( 'Button Icon', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::ICONS,
			'default'   => array( 'value' => 'dashicons dashicons-arrow-right-alt', 'library' => 'dashicons' ),
			'condition' => array( 'layout' => 'simple', 'show_cta_button' => 'yes' ),
		) );

		$this->add_control( 'cta_icon_position', array(
			'label'     => esc_html__( 'Icon Position', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'after',
			'options'   => array(
				'before' => esc_html__( 'Before text', 'itr-knowledgebase' ),
				'after'  => esc_html__( 'After text', 'itr-knowledgebase' ),
				'none'   => esc_html__( 'No icon', 'itr-knowledgebase' ),
			),
			'condition' => array( 'layout' => 'simple', 'show_cta_button' => 'yes' ),
		) );

		$this->add_control( 'show_count', array(
			'label'        => esc_html__( 'Show Article Count', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'layout' => 'simple' ),
		) );

		$this->add_control( 'browse_all_text', array(
			'label'       => esc_html__( '"Browse All" Button Text', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Shown when a category has no subcategories.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => esc_html__( 'Browse all articles', 'itr-knowledgebase' ),
			'condition'   => array( 'layout' => 'simple' ),
			'separator'   => 'before',
		) );

		$this->add_control( 'show_browse_all', array(
			'label'        => esc_html__( 'Show "Browse All" Button', 'itr-knowledgebase' ),
			'description'  => esc_html__( 'Toggle the browse-all button that appears when a category has no subcategories.', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'layout' => 'simple' ),
		) );

		$this->add_control( 'browse_all_icon', array(
			'label'     => esc_html__( '"Browse All" Button Icon', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::ICONS,
			'default'   => array( 'value' => 'dashicons dashicons-arrow-right-alt2', 'library' => 'dashicons' ),
			'condition' => array( 'layout' => 'simple', 'show_browse_all' => 'yes' ),
		) );

		$this->add_control( 'browse_all_icon_position', array(
			'label'     => esc_html__( 'Icon Position', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'after',
			'options'   => array(
				'before' => esc_html__( 'Before text', 'itr-knowledgebase' ),
				'after'  => esc_html__( 'After text', 'itr-knowledgebase' ),
				'none'   => esc_html__( 'No icon', 'itr-knowledgebase' ),
			),
			'condition' => array( 'layout' => 'simple', 'show_browse_all' => 'yes' ),
		) );

		$this->add_control( 'simple_columns', array(
			'label'     => esc_html__( 'Columns', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '1',
			'options'   => array(
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
			),
			'condition' => array( 'layout' => 'simple' ),
		) );

		$this->add_control( 'show_icon', array(
			'label'        => esc_html__( 'Show Category Icon', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'layout' => 'simple' ),
		) );

		$this->add_control( 'card_image_height', array(
			'label'     => esc_html__( 'Card Image Height (px)', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 160,
			'min'       => 80,
			'max'       => 400,
			'condition' => array( 'layout' => 'card' ),
		) );

		$this->end_controls_section();

		// ── Style — Simple Layout ──────────────────────────────────────────────
		$this->start_controls_section( 'section_style_simple', array(
			'label'     => esc_html__( 'Item Panel', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple' ),
		) );

		$this->start_controls_tabs( 'tabs_simple_panel' );

		$this->start_controls_tab( 'tab_simple_panel_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );

		$this->add_control( 'simple_card_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'simple_panel_border',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple',
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_simple_panel_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );

		$this->add_control( 'simple_card_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple:hover' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'simple_card_hover_border', array(
			'label'     => esc_html__( 'Border Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple:hover' => 'border-color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'simple_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'simple_gap', array(
			'label'      => esc_html__( 'Gap Between Items', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'simple_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple',
		) );

		$this->end_controls_section();

		// ── Header ────────────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_header', array(
			'label'     => esc_html__( 'Header', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple' ),
		) );

		$this->add_control( 'simple_header_bg', array(
			'label'     => esc_html__( 'Header Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__header' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'simple_header_padding', array(
			'label'      => esc_html__( 'Header Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'simple_toggle_color', array(
			'label'     => esc_html__( 'Toggle Arrow Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__toggle-icon' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'simple_name_heading', array(
			'label'     => esc_html__( 'Category Name', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_responsive_control( 'simple_name_max_width', array(
			'label'       => esc_html__( 'Name Max Width', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Limit the name width so long names wrap to 2 lines. e.g. 160px', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px', '%' ),
			'range'       => array( 'px' => array( 'min' => 80, 'max' => 500 ) ),
			'selectors'   => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__name' => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Icon (Simple Layout) ───────────────────────────────────────────────
		$this->start_controls_section( 'section_style_simple_icon', array(
			'label'     => esc_html__( 'Icon Box', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple', 'show_icon' => 'yes' ),
		) );

		$this->add_control( 'simple_icon_bg', array(
			'label'     => esc_html__( 'Icon Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__icon' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'simple_icon_color', array(
			'label'     => esc_html__( 'Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__icon .dashicons' => 'color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'simple_icon_size', array(
			'label'      => esc_html__( 'Icon Box Size', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 24, 'max' => 80 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'simple_icon_radius', array(
			'label'      => esc_html__( 'Icon Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Category Name (Simple) ─────────────────────────────────────────────
		$this->start_controls_section( 'section_style_simple_name', array(
			'label'     => esc_html__( 'Category Name', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'simple_name_typography',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple__name',
		) );

		$this->add_control( 'simple_title_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__name' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'simple_title_hover_color', array(
			'label'     => esc_html__( 'Hover Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple--open .itr-kb-cat-acc-simple__name' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Article Count (Simple) ─────────────────────────────────────────────
		$this->start_controls_section( 'section_style_simple_count', array(
			'label'     => esc_html__( 'Article Count', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple', 'show_count' => 'yes' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'simple_count_typography',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple__count',
		) );

		$this->add_control( 'simple_count_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__count' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Panel Content (Simple) ─────────────────────────────────────────────
		$this->start_controls_section( 'section_style_simple_panel', array(
			'label'     => esc_html__( 'Panel Content', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple' ),
		) );

		$this->add_control( 'simple_panel_bg', array(
			'label'     => esc_html__( 'Panel Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__panel' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'simple_panel_padding', array(
			'label'      => esc_html__( 'Panel Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		// ── Subcategory list items ────────────────────────────────────────────
		$this->add_control( 'simple_subcat_heading', array(
			'label'     => esc_html__( 'Subcategory List Items', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'simple_subcat_typography',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-link',
		) );

		$this->start_controls_tabs( 'tabs_subcat' );

		$this->start_controls_tab( 'tab_subcat_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );
		$this->add_control( 'simple_subcat_bg', array(
			'label'     => esc_html__( 'Item Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-item' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'simple_subcat_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-link' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_subcat_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );
		$this->add_control( 'simple_subcat_hover_bg', array(
			'label'     => esc_html__( 'Item Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-item:hover' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'simple_subcat_hover_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-link:hover' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control( 'simple_subcat_item_padding', array(
			'label'      => esc_html__( 'Item Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'simple_subcat_divider_color', array(
			'label'     => esc_html__( 'Divider Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-item' => 'border-bottom-color: {{VALUE}};' ),
			'separator' => 'before',
		) );

		$this->add_control( 'simple_subcat_arrow_color', array(
			'label'     => esc_html__( 'Arrow Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-link .dashicons' => 'color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'simple_subcat_arrow_size', array(
			'label'      => esc_html__( 'Arrow Icon Size', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 30 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-link .dashicons' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();

		// ── Browse All Button (Simple) ──────────────────────────────────────────
		$this->start_controls_section( 'section_style_browse_all', array(
			'label'     => esc_html__( '"Browse All" Button', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple' ),
		) );

		$this->start_controls_tabs( 'tabs_browse_all' );

		$this->start_controls_tab( 'tab_browse_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );
		$this->add_control( 'browse_all_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'browse_all_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'browse_all_border',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all',
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_browse_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );
		$this->add_control( 'browse_all_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all:hover' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'browse_all_hover_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all:hover' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'browse_all_hover_border_color', array(
			'label'     => esc_html__( 'Border Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all:hover' => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'browse_all_typography',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all',
		) );

		$this->add_responsive_control( 'browse_all_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'browse_all_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__browse-all' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── CTA Button (Simple) ────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_simple_cta', array(
			'label'     => esc_html__( 'CTA Button', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple', 'show_cta_button' => 'yes' ),
		) );

		$this->start_controls_tabs( 'tabs_simple_cta' );

		$this->start_controls_tab( 'tab_cta_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );

		$this->add_control( 'simple_cta_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__cta' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'simple_cta_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__cta' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_cta_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );

		$this->add_control( 'simple_cta_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__cta:hover' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'simple_cta_hover_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__cta:hover' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'simple_cta_typography',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple__cta',
		) );

		$this->add_responsive_control( 'simple_cta_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__cta' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'simple_cta_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-simple__cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Style — Card Layout ────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_card', array(
			'label'     => esc_html__( 'Card', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'card' ),
		) );

		$this->add_control( 'card_bg_color', array(
			'label'       => esc_html__( 'Card Background Color', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Used when no image is set or when expanded.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::COLOR,
			'default'     => '#26a69a',
			'selectors'   => array( '{{WRAPPER}} .itr-kb-cat-acc-card' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'card_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'default'    => array( 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'unit' => 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-card',
		) );

		$this->add_control( 'card_title_heading', array(
			'label'     => esc_html__( 'Card Title', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'card_title_typography',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-card__title',
		) );

		$this->add_control( 'card_title_color', array(
			'label'     => esc_html__( 'Title Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-card__title' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Card Pills ─────────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_card_pills', array(
			'label'     => esc_html__( 'Card: Subcategory Pills', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'card' ),
		) );

		$this->start_controls_tabs( 'tabs_card_pills' );

		$this->start_controls_tab( 'tab_pills_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );

		$this->add_control( 'card_pill_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(255,255,255,0.15)',
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-card__pill' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_pill_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-card__pill' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_pill_border_color', array(
			'label'     => esc_html__( 'Border Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-card__pill' => 'border-color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_pills_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );

		$this->add_control( 'card_pill_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-card__pill:hover' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_pill_hover_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-cat-acc-card__pill:hover' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'card_pill_typography',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-card__pill',
		) );

		$this->add_responsive_control( 'card_pill_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-card__pill' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'card_pill_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-cat-acc-card__pill' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	// =========================================================================
	// Render

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$layout     = $settings['layout'];
		$source     = $settings['source'] ?? 'auto';
		$open_first = 'yes' === $settings['open_first'];
		$columns    = absint( $settings['columns'] ?? 3 );
		$is_editor  = \Elementor\Plugin::$instance->editor->is_edit_mode();

		// ── Resolve category list ────────────────────────────────────────────
		$icon_map = array(); // term_id => [ 'icon' => array, 'color' => string ]

		if ( 'manual' === $source ) {
			$repeater_items = (array) ( $settings['manual_categories_list'] ?? array() );

			if ( empty( $repeater_items ) ) {
				echo '<p class="itr-kb-cat-acc-empty">' . esc_html__( 'No categories added. Edit this widget and add categories under "Categories".', 'itr-knowledgebase' ) . '</p>';
				return;
			}

			// Build ordered list from repeater rows, skipping empty rows.
			$categories = array();
			foreach ( $repeater_items as $item ) {
				$tid = absint( $item['category_id'] ?? 0 );
				if ( ! $tid ) continue;
				$term = get_term( $tid, 'itr_kb_category' );
				if ( $term && ! is_wp_error( $term ) ) {
					$categories[] = $term;
					// Store repeater icon if set.
					$icon_val = $item['custom_icon'] ?? array();
					if ( ! empty( $icon_val['value'] ) ) {
						$icon_map[ $tid ] = array(
							'icon'  => $icon_val,
							'color' => $item['custom_icon_color'] ?? '',
						);
					}
				}
			}
		} else {
			// Auto — use parent_id from settings.
			$parent_id  = absint( $settings['parent_id'] );
			$categories = ITR_KB_Category_Order::get_ordered_categories( $parent_id );
		}

		if ( empty( $categories ) ) {
			echo '<p class="itr-kb-cat-acc-empty">' . esc_html__( 'No categories found.', 'itr-knowledgebase' ) . '</p>';
			return;
		}

		if ( 'simple' === $layout ) {
			$this->render_simple( $categories, $settings, $open_first, $is_editor, $icon_map );
		} else {
			$this->render_card( $categories, $settings, $open_first, $columns, $is_editor, $icon_map );
		}
	}

	// =========================================================================
	// Layout 1 — Simple accordion
	// =========================================================================

	private function render_simple( $categories, $settings, $open_first, $is_editor, $icon_map = array() ) {
		$show_count  = 'yes' === $settings['show_count'];
		$show_icon   = 'yes' === $settings['show_icon'];
		$show_cta        = 'yes' === ( $settings['show_cta_button'] ?? 'no' );
		$show_browse_all = 'yes' === ( $settings['show_browse_all'] ?? 'yes' );
		$cta_label       = trim( $settings['cta_label'] ?? '' ) ?: esc_html__( 'View All Categories', 'itr-knowledgebase' );
		$cta_url_raw     = $settings['cta_url']['url'] ?? '';
		$browse_all_text = trim( $settings['browse_all_text'] ?? '' ) ?: esc_html__( 'Browse all articles', 'itr-knowledgebase' );
		$browse_icon     = $settings['browse_all_icon'] ?? array();
		$browse_icon_pos = $settings['browse_all_icon_position'] ?? 'after';
		$cols            = max( 1, min( 5, absint( $settings['simple_columns'] ?? 1 ) ) );

		// Ensure icon font libraries are loaded on the frontend (needed for repeater icons).
		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			\Elementor\Icons_Manager::enqueue_shim();
		}
		?>
		<div class="itr-kb-cat-acc itr-kb-cat-acc--simple itr-kb-cat-acc--cols-<?php echo esc_attr( $cols ); ?>">
			<?php foreach ( $categories as $index => $cat ) : ?>
				<?php
				$is_open  = $is_editor || ( $open_first && 0 === $index );
				$icon     = ITR_KB_Category::get_icon( $cat->term_id );
				$img_url  = ITR_KB_Category::get_image_url( $cat->term_id, 'thumbnail' );
				$children = array_slice( ITR_KB_Category_Order::get_ordered_categories( $cat->term_id ), 0, 4 );
				$panel_id = 'itr-kb-cat-acc-s-' . $cat->term_id;
				?>
				<div class="itr-kb-cat-acc-simple <?php echo $is_open ? 'itr-kb-cat-acc-simple--open' : ''; ?>">

					<button
						class="itr-kb-cat-acc-simple__header"
						aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					>
						<?php if ( $show_icon ) : ?>
							<span class="itr-kb-cat-acc-simple__icon">
								<?php
								$_tid = $cat->term_id;
								if ( ! empty( $icon_map[ $_tid ] ) ) :
									$_ic    = $icon_map[ $_tid ];
									$_style = ! empty( $_ic['color'] ) ? ' style="color:' . esc_attr( $_ic['color'] ) . '"' : '';
									echo '<span' . $_style . '>';
									\Elementor\Icons_Manager::render_icon( $_ic['icon'], array( 'aria-hidden' => 'true' ) );
									echo '</span>';
								elseif ( $img_url ) : ?>
									<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" width="32" height="32" />
								<?php elseif ( $icon ) : ?>
									<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
								<?php else : ?>
									<span class="dashicons dashicons-category" aria-hidden="true"></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>

						<span class="itr-kb-cat-acc-simple__info">
							<span class="itr-kb-cat-acc-simple__name"><?php echo esc_html( $cat->name ); ?></span>
							<?php if ( $show_count ) : ?>
								<span class="itr-kb-cat-acc-simple__count">
									<?php printf( esc_html( _n( '%d article', '%d articles', $cat->count, 'itr-knowledgebase' ) ), absint( $cat->count ) ); ?>
								</span>
							<?php endif; ?>
						</span>

						<span class="itr-kb-cat-acc-simple__toggle-icon dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
					</button>

					<div
						id="<?php echo esc_attr( $panel_id ); ?>"
						class="itr-kb-cat-acc-simple__panel"
						style="<?php echo $is_open ? '' : 'display:none;'; ?>"
					>
						<?php if ( ! empty( $children ) ) : ?>
							<ul class="itr-kb-cat-acc-simple__subcat-list">
								<?php foreach ( $children as $child ) : ?>
									<li class="itr-kb-cat-acc-simple__subcat-item">
										<a href="<?php echo esc_url( get_term_link( $child ) ); ?>" class="itr-kb-cat-acc-simple__subcat-link">
											<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
											<?php echo esc_html( $child->name ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( $show_browse_all ) : ?>
						<div class="itr-kb-cat-acc-simple__no-sub">
							<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="itr-kb-cat-acc-simple__browse-all">
								<?php
								$_ba_icon_html = '';
								if ( 'none' !== $browse_icon_pos && ! empty( $browse_icon['value'] ) ) {
									ob_start();
									\Elementor\Icons_Manager::render_icon( $browse_icon, array( 'aria-hidden' => 'true' ) );
									$_ba_icon_html = ob_get_clean();
								}
								if ( 'before' === $browse_icon_pos ) echo wp_kses_post( $_ba_icon_html );
								echo esc_html( $browse_all_text );
								if ( 'after' === $browse_icon_pos ) echo wp_kses_post( $_ba_icon_html );
								?>
							</a>
						</div>
						<?php endif; ?>
					</div>

					<?php if ( $show_cta ) :
						$btn_href = $cta_url_raw ?: get_term_link( $cat );
					?>
					<a href="<?php echo esc_url( $btn_href ); ?>" class="itr-kb-cat-acc-simple__cta">
						<?php
						$_cta_icon     = $settings['cta_icon'] ?? array();
						$_cta_icon_pos = $settings['cta_icon_position'] ?? 'after';
						$_icon_html    = '';
						if ( 'none' !== $_cta_icon_pos && ! empty( $_cta_icon['value'] ) ) {
							ob_start();
							\Elementor\Icons_Manager::render_icon( $_cta_icon, array( 'aria-hidden' => 'true' ) );
							$_icon_html = ob_get_clean();
						}
						if ( 'before' === $_cta_icon_pos ) echo wp_kses_post( $_icon_html );
						echo esc_html( $cta_label );
						if ( 'after' === $_cta_icon_pos ) echo wp_kses_post( $_icon_html );
						?>
					</a>
					<?php endif; ?>

				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Layout 2 — Card with image accordion
	// =========================================================================

	private function render_card( $categories, $settings, $open_first, $columns, $is_editor, $icon_map = array() ) {
		$image_height = absint( $settings['card_image_height'] ?? 160 );
		$grid_style   = $is_editor ? 'display:grid;grid-template-columns:repeat(' . $columns . ',1fr);gap:16px;' : '';
		?>
		<div class="itr-kb-cat-acc itr-kb-cat-acc--card itr-kb-cat-acc--cols-<?php echo esc_attr( $columns ); ?>" style="<?php echo esc_attr( $grid_style ); ?>">
			<?php foreach ( $categories as $index => $cat ) : ?>
				<?php
				$is_open    = $is_editor || ( $open_first && 0 === $index );
				$icon       = ITR_KB_Category::get_icon( $cat->term_id );
				$img_url    = ITR_KB_Category::get_image_url( $cat->term_id, 'large' );
				$children   = array_slice( ITR_KB_Category_Order::get_ordered_categories( $cat->term_id ), 0, 4 );
				$panel_id   = 'itr-kb-cat-acc-c-' . $cat->term_id;
				$card_style = $is_editor ? 'background-color:#26a69a;border-radius:12px;overflow:hidden;display:flex;flex-direction:column;' : '';
				?>
				<div class="itr-kb-cat-acc-card <?php echo $is_open ? 'itr-kb-cat-acc-card--open' : ''; ?>" style="<?php echo esc_attr( $card_style ); ?>">

					<div class="itr-kb-cat-acc-card__header" style="<?php echo $is_editor ? 'display:flex;align-items:flex-start;justify-content:space-between;gap:8px;padding:18px 16px 12px;' : ''; ?>">
						<h3 class="itr-kb-cat-acc-card__title" style="<?php echo $is_editor ? 'margin:0;font-size:17px;font-weight:700;color:#fff;' : ''; ?>"><?php echo esc_html( $cat->name ); ?></h3>
						<button
							class="itr-kb-cat-acc-card__toggle"
							aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>"
							aria-label="<?php echo esc_attr( sprintf( __( 'Toggle %s', 'itr-knowledgebase' ), $cat->name ) ); ?>"
							style="<?php echo $is_editor ? 'background:rgba(255,255,255,0.25);border:none;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;' : ''; ?>"
						>
							<span class="dashicons <?php echo $is_open ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'; ?>" style="<?php echo $is_editor ? 'color:#fff;' : ''; ?>" aria-hidden="true"></span>
						</button>
					</div>

					<div
						class="itr-kb-cat-acc-card__image"
						style="<?php echo $img_url ? "background-image:url('" . esc_url( $img_url ) . "');" : ''; ?><?php echo ( $is_open && ! $is_editor ) ? 'display:none;' : ''; ?>"
						aria-hidden="true"
					>
						<?php if ( ! $img_url ) : ?>
							<span class="itr-kb-cat-acc-card__image-placeholder">
								<span class="dashicons <?php echo $icon ? esc_attr( $icon ) : 'dashicons-category'; ?>"></span>
							</span>
						<?php endif; ?>
					</div>

					<div
						id="<?php echo esc_attr( $panel_id ); ?>"
						class="itr-kb-cat-acc-card__panel"
						style="<?php echo $is_open ? 'padding:4px 14px 16px;' : 'display:none;'; ?>"
					>
						<div class="itr-kb-cat-acc-card__pills" style="<?php echo $is_editor ? 'display:flex;flex-wrap:wrap;gap:8px;' : ''; ?>">
							<?php if ( ! empty( $children ) ) : ?>
								<?php foreach ( $children as $child ) : ?>
									<a href="<?php echo esc_url( get_term_link( $child ) ); ?>" class="itr-kb-cat-acc-card__pill" style="<?php echo $is_editor ? 'display:inline-block;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:5px 14px;font-size:13px;color:#fff;text-decoration:none;' : ''; ?>">
										<?php echo esc_html( $child->name ); ?>
									</a>
								<?php endforeach; ?>
							<?php else : ?>
								<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="itr-kb-cat-acc-card__pill" style="<?php echo $is_editor ? 'display:inline-block;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);border-radius:20px;padding:5px 14px;font-size:13px;color:#fff;text-decoration:none;' : ''; ?>">
									<?php esc_html_e( 'Browse all articles', 'itr-knowledgebase' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}