<?php
/**
 * Elementor Widget: KB Category Grid
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

/**
 * Class ITR_KB_Widget_Category_Grid
 */
class ITR_KB_Widget_Category_Grid extends Widget_Base {

	public function get_name()       { return 'itr-kb-category-grid'; }
	public function get_title()      { return esc_html__( 'KB Category Grid', 'itr-knowledgebase' ); }
	public function get_icon()       { return 'eicon-gallery-grid'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords()   { return array( 'category', 'grid', 'kb' ); }

	// =========================================================================
	// Helper — build term_id => name options for top-level categories only.
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

		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Category Grid', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		// ── Source ──────────────────────────────────────────────────────────
		$this->add_control( 'source', array(
			'label'   => esc_html__( 'Source', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'auto',
			'options' => array(
				'auto'   => esc_html__( 'Auto — based on current page context', 'itr-knowledgebase' ),
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

		// ── Auto: parent ID fallback ─────────────────────────────────────────
		$this->add_control( 'parent_id', array(
			'label'       => esc_html__( 'Parent Category ID', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Leave 0 to show top-level categories.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
			'condition'   => array( 'source' => 'auto' ),
		) );

		// ── Layout & display ─────────────────────────────────────────────────
		$this->add_control( 'layout', array(
			'label'   => esc_html__( 'Layout', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'grid',
			'options' => array(
				'grid'           => esc_html__( 'Default Grid', 'itr-knowledgebase' ),
				'card'           => esc_html__( 'Subcategory Card', 'itr-knowledgebase' ),
				'featured_topics'=> esc_html__( 'Featured Topics — image cards with overlay', 'itr-knowledgebase' ),
			),
		) );

		$this->add_control( 'columns', array(
			'label'   => esc_html__( 'Columns', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '4',
			'options' => array(
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
				'6' => '6',
			),
		) );

		$this->add_control( 'show_count', array(
			'label'        => esc_html__( 'Show Article Count', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_children', array(
			'label'        => esc_html__( 'Show Sub-categories', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_description', array(
			'label'        => esc_html__( 'Show Description', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		) );

		$this->end_controls_section();

		// ── Style ─────────────────────────────────────────────────────────────

		/* ── 1. Layout ──────────────────────────────────────────────────────── */
		$this->start_controls_section( 'section_style_layout', array(
			'label' => esc_html__( 'Layout', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'grid_gap', array(
			'label'      => esc_html__( 'Gap Between Items', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default'    => array( 'size' => 16, 'unit' => 'px' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-category-grid'  => 'gap: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .itr-kb-subcat-grid'    => 'gap: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .itr-kb-featured-topics'=> 'gap: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();

		/* ── 2. Card — Default Grid ──────────────────────────────────────────── */
		$this->start_controls_section( 'section_style_card', array(
			'label'     => esc_html__( 'Card (Default Grid)', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'grid' ),
		) );

		$this->start_controls_tabs( 'tabs_card' );

		$this->start_controls_tab( 'tab_card_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );

		$this->add_control( 'card_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-grid__item' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .itr-kb-category-grid__item',
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_card_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );

		$this->add_control( 'card_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-grid__item:hover' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_hover_border_color', array(
			'label'     => esc_html__( 'Border Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-grid__item:hover' => 'border-color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'card_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-grid__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'card_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-grid__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-category-grid__item',
		) );

		$this->end_controls_section();

		/* ── 3. Icon — Default Grid ──────────────────────────────────────────── */
		$this->start_controls_section( 'section_style_icon', array(
			'label'     => esc_html__( 'Icon (Default Grid)', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'grid' ),
		) );

		$this->add_control( 'icon_bg', array(
			'label'     => esc_html__( 'Icon Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-grid__icon' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'icon_color', array(
			'label'     => esc_html__( 'Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-grid__icon .dashicons' => 'color: {{VALUE}};',
				'{{WRAPPER}} .itr-kb-category-grid__icon img'        => 'filter: none;',
			),
		) );

		$this->add_responsive_control( 'icon_size', array(
			'label'      => esc_html__( 'Icon Size', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 16, 'max' => 80 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-category-grid__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .itr-kb-category-grid__icon .dashicons' => 'font-size: calc({{SIZE}}{{UNIT}} * 0.55); width: calc({{SIZE}}{{UNIT}} * 0.55); height: calc({{SIZE}}{{UNIT}} * 0.55);',
			),
		) );

		$this->add_responsive_control( 'icon_radius', array(
			'label'      => esc_html__( 'Icon Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-grid__icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		/* ── 4. Category Name ───────────────────────────────────────────────── */
		$this->start_controls_section( 'section_style_name', array(
			'label' => esc_html__( 'Category Name', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'name_typography',
			'selector' => '{{WRAPPER}} .itr-kb-category-grid__title, {{WRAPPER}} .itr-kb-subcat-card__title, {{WRAPPER}} .itr-kb-featured-topic__name, {{WRAPPER}} .itr-kb-featured-topic__hover-name',
		) );

		$this->add_control( 'name_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-grid__title' => 'color: {{VALUE}};',
				'{{WRAPPER}} .itr-kb-subcat-card__title'   => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'name_hover_color', array(
			'label'     => esc_html__( 'Hover Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-grid__item:hover .itr-kb-category-grid__title' => 'color: {{VALUE}};',
				'{{WRAPPER}} .itr-kb-subcat-card:hover .itr-kb-subcat-card__title'           => 'color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();

		/* ── 5. Count ───────────────────────────────────────────────────────── */
		$this->start_controls_section( 'section_style_count', array(
			'label'     => esc_html__( 'Article Count', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout!' => 'featured_topics', 'show_count' => 'yes' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'count_typography',
			'selector' => '{{WRAPPER}} .itr-kb-category-grid__count, {{WRAPPER}} .itr-kb-subcat-card__count',
		) );

		$this->add_control( 'count_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-grid__count' => 'color: {{VALUE}};',
				'{{WRAPPER}} .itr-kb-subcat-card__count'   => 'color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();

		/* ── 6. Featured Topics: Card ────────────────────────────────────────── */
		$this->start_controls_section( 'section_style_ft_card', array(
			'label'     => esc_html__( 'Featured Topics: Card', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'featured_topics' ),
		) );

		$this->add_responsive_control( 'ft_card_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-featured-topic' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'ft_card_placeholder_bg', array(
			'label'     => esc_html__( 'Placeholder Background (no image)', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic' => 'background-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		/* ── 7. Featured Topics: Name Overlay ───────────────────────────────── */
		$this->start_controls_section( 'section_style_ft_name', array(
			'label'     => esc_html__( 'Featured Topics: Name Label', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'featured_topics' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'ft_name_typography',
			'selector' => '{{WRAPPER}} .itr-kb-featured-topic__name',
		) );

		$this->add_control( 'ft_name_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic__name' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		/* ── 8. Featured Topics: Hover Overlay ─────────────────────────────── */
		$this->start_controls_section( 'section_style_ft_hover', array(
			'label'     => esc_html__( 'Featured Topics: Hover Overlay', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'featured_topics' ),
		) );

		$this->add_control( 'ft_overlay_bg', array(
			'label'     => esc_html__( 'Overlay Background Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic__hover-layer' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'ft_hover_name_typography',
			'label'    => esc_html__( 'Category Name Typography', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-featured-topic__hover-name',
		) );

		$this->add_control( 'ft_hover_name_color', array(
			'label'     => esc_html__( 'Category Name Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic__hover-name' => 'color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'ft_hover_padding', array(
			'label'      => esc_html__( 'Overlay Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-featured-topic__hover-layer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		/* ── 9. Featured Topics: Subcategory Chips ──────────────────────────── */
		$this->start_controls_section( 'section_style_ft_chips', array(
			'label'     => esc_html__( 'Featured Topics: Subcategory Chips', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'featured_topics' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'ft_chip_typography',
			'selector' => '{{WRAPPER}} .itr-kb-featured-topic__chip',
		) );

		$this->add_control( 'ft_chip_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic__chip' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'ft_chip_border_color', array(
			'label'     => esc_html__( 'Border Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic__chip' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'ft_chip_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-featured-topic__chip' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		/* ── 10. Featured Topics: CTA Button ────────────────────────────────── */
		$this->start_controls_section( 'section_style_ft_cta', array(
			'label'     => esc_html__( 'Featured Topics: CTA Button', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'featured_topics' ),
		) );

		$this->start_controls_tabs( 'tabs_ft_cta' );

		$this->start_controls_tab( 'tab_ft_cta_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );

		$this->add_control( 'ft_cta_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic__cta' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'ft_cta_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic__cta' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_ft_cta_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );

		$this->add_control( 'ft_cta_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic:hover .itr-kb-featured-topic__cta' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'ft_cta_hover_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-featured-topic:hover .itr-kb-featured-topic__cta' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'ft_cta_typography',
			'selector' => '{{WRAPPER}} .itr-kb-featured-topic__cta',
		) );

		$this->add_responsive_control( 'ft_cta_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-featured-topic__cta' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'ft_cta_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-featured-topic__cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		/* ── 11. Article Card ───────────────────────────────────────────────── */
		$this->start_controls_section( 'section_style_article', array(
			'label' => esc_html__( 'Article Card', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'article_card_bg', array(
			'label'     => esc_html__( 'Card Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'article_card_border',
			'selector' => '{{WRAPPER}} .itr-kb-article-card',
		) );

		$this->add_responsive_control( 'article_card_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-article-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'article_card_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-article-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'article_badge_heading', array(
			'label'     => esc_html__( 'Category Badge', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_control( 'article_badge_bg', array(
			'label'     => esc_html__( 'Badge Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card__badge' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'article_badge_color', array(
			'label'     => esc_html__( 'Badge Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card__badge' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'article_title_heading', array(
			'label'     => esc_html__( 'Title', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'article_title_typography',
			'selector' => '{{WRAPPER}} .itr-kb-article-card__title',
		) );

		$this->add_control( 'article_title_color', array(
			'label'     => esc_html__( 'Title Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card__link' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'article_title_hover_color', array(
			'label'     => esc_html__( 'Title Hover Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card__link:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'article_excerpt_heading', array(
			'label'     => esc_html__( 'Excerpt', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'article_excerpt_typography',
			'selector' => '{{WRAPPER}} .itr-kb-article-card__excerpt',
		) );

		$this->add_control( 'article_excerpt_color', array(
			'label'     => esc_html__( 'Excerpt Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card__excerpt' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'article_readmore_heading', array(
			'label'     => esc_html__( 'Read More Link', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_control( 'article_readmore_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card__read-more' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'article_readmore_hover_color', array(
			'label'     => esc_html__( 'Hover Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card__read-more:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'article_meta_heading', array(
			'label'     => esc_html__( 'Meta (Date / Author)', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'article_meta_typography',
			'selector' => '{{WRAPPER}} .itr-kb-article-card__meta',
		) );

		$this->add_control( 'article_meta_color', array(
			'label'     => esc_html__( 'Meta Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-article-card__meta' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── 12. Load More Button ─────────────────────────────────────────────
		$this->start_controls_section( 'section_style_load_more', array(
			'label' => esc_html__( 'Load More Button', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->start_controls_tabs( 'tabs_load_more' );

		$this->start_controls_tab( 'tab_load_more_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );

		$this->add_control( 'load_more_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-load-more-btn' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'load_more_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-load-more-btn' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'load_more_border',
			'selector' => '{{WRAPPER}} .itr-kb-load-more-btn',
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_load_more_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );

		$this->add_control( 'load_more_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-load-more-btn:hover' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'load_more_hover_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-load-more-btn:hover' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'load_more_typography',
			'selector' => '{{WRAPPER}} .itr-kb-load-more-btn',
		) );

		$this->add_responsive_control( 'load_more_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-load-more-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'load_more_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-load-more-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'load_more_icon', array(
			'label'     => esc_html__( 'Icon', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::ICONS,
			'default'   => array( 'value' => 'dashicons dashicons-arrow-down-alt2', 'library' => 'dashicons' ),
			'separator' => 'before',
		) );

		$this->end_controls_section();

	}

	// =========================================================================
	// Render

	protected function render() {
		$settings = $this->get_settings_for_display();
		$source   = $settings['source'] ?? 'auto';
		$layout   = $settings['layout'] ?? 'grid';
		$columns  = absint( $settings['columns'] ?? 4 );

		// Ensure icon font libraries are loaded on the frontend (needed for repeater icons).
		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			\Elementor\Icons_Manager::enqueue_shim();
		}

		// ── Resolve category list ────────────────────────────────────────────
		$icon_map = array(); // term_id => [ 'icon' => Elementor Icons array, 'color' => string ]

		if ( 'manual' === $source ) {
			$repeater_items = (array) ( $settings['manual_categories_list'] ?? array() );

			if ( empty( $repeater_items ) ) {
				echo '<p class="itr-kb-widget-empty">' . esc_html__( 'No categories added. Edit this widget and add categories under "Categories".', 'itr-knowledgebase' ) . '</p>';
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
					// Store custom icon if set in repeater.
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
			// Auto — detect current term context.
			$parent_id    = absint( $settings['parent_id'] ?? 0 );
			$current_term = get_queried_object();

			if ( $current_term && isset( $current_term->term_id ) ) {
				$parent_id = $current_term->term_id;
			}

			$categories = get_terms( array(
				'taxonomy'   => 'itr_kb_category',
				'parent'     => $parent_id,
				'hide_empty' => false,
			) );
		}

		// ── Nothing to show ──────────────────────────────────────────────────
		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			// Auto mode with no sub-categories — show articles directly.
			$current_term = get_queried_object();
			if ( 'auto' === $source && $current_term && isset( $current_term->term_id ) ) {
				$this->render_article_fallback( $current_term );
			} else {
				echo '<p>' . esc_html__( 'No categories found.', 'itr-knowledgebase' ) . '</p>';
			}
			return;
		}

		// ── Render ───────────────────────────────────────────────────────────
		if ( 'card' === $layout ) {
			$this->render_card_layout( $categories );
		} elseif ( 'featured_topics' === $layout ) {
			$this->render_featured_topics_layout( $categories, $columns, $icon_map );
		} else {
			$this->render_grid_layout( $categories, $columns, $icon_map );
		}
	}

	// ─── Grid layout ─────────────────────────────────────────────────────────

	private function render_grid_layout( $categories, $columns, $icon_map = array() ) {
		echo '<div class="itr-kb-category-grid itr-kb-category-grid--cols-' . esc_attr( $columns ) . '">';
		foreach ( $categories as $cat ) {
			$link    = get_term_link( $cat );
			$term_id = $cat->term_id;
			echo '<div class="itr-kb-category-grid__item">';
			echo '<a href="' . esc_url( $link ) . '" class="itr-kb-category-grid__link">';

			// Icon priority: 1) repeater custom icon  2) term meta image  3) default dashicon.
			if ( ! empty( $icon_map[ $term_id ] ) ) {
				$ic    = $icon_map[ $term_id ];
				$style = ! empty( $ic['color'] ) ? ' style="color:' . esc_attr( $ic['color'] ) . '"' : '';
				echo '<div class="itr-kb-category-grid__icon"' . $style . '>';
				\Elementor\Icons_Manager::render_icon( $ic['icon'], array( 'aria-hidden' => 'true' ) );
				echo '</div>';
			} else {
				$icon_url = \ITR_Knowledgebase\Admin\ITR_KB_Term_Icon::get_icon_url( $term_id, 'thumbnail' );
				if ( $icon_url ) {
					echo '<div class="itr-kb-category-grid__icon itr-kb-category-grid__icon--custom"><img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $cat->name ) . '" loading="lazy" /></div>';
				} else {
					echo '<div class="itr-kb-category-grid__icon"><span class="dashicons dashicons-category"></span></div>';
				}
			}

			echo '<h3 class="itr-kb-category-grid__title">' . esc_html( $cat->name ) . '</h3>';
			echo '<span class="itr-kb-category-grid__count">' . absint( $cat->count ) . ' Topics</span>';
			echo '</a>';
			echo '</div>';
		}
		echo '</div>';
	}

	// ─── Card layout ─────────────────────────────────────────────────────────

	private function render_card_layout( $categories ) {
		echo '<div class="itr-kb-subcat-grid">';
		foreach ( $categories as $cat ) {
			$link = get_term_link( $cat );
			?>
			<a href="<?php echo esc_url( $link ); ?>" class="itr-kb-subcat-card">
				<div class="itr-kb-subcat-card__icon">
					<span class="dashicons dashicons-category"></span>
				</div>
				<div class="itr-kb-subcat-card__content">
					<h3 class="itr-kb-subcat-card__title"><?php echo esc_html( $cat->name ); ?></h3>
					<span class="itr-kb-subcat-card__count"><?php echo absint( $cat->count ); ?> Topics</span>
				</div>
			</a>
			<?php
		}
		echo '</div>';
	}

	// ─── Featured Topics layout ──────────────────────────────────────────────

	private function render_featured_topics_layout( $categories, $columns ) {
		echo '<div class="itr-kb-featured-topics itr-kb-featured-topics--cols-' . esc_attr( $columns ) . '">';
		foreach ( $categories as $cat ) {
			$link      = get_term_link( $cat );
			$image_id  = get_term_meta( $cat->term_id, 'itr_kb_category_image', true );
			$image_url = $image_id ? wp_get_attachment_image_url( (int) $image_id, 'large' ) : '';
			$children  = array_slice(
				\ITR_Knowledgebase\Admin\ITR_KB_Category_Order::get_ordered_categories( $cat->term_id ),
				0,
				4
			);
			?>
			<a href="<?php echo esc_url( $link ); ?>" class="itr-kb-featured-topic">
				<!-- Background image layer -->
				<div class="itr-kb-featured-topic__bg">
					<?php if ( $image_url ) : ?>
						<img
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $cat->name ); ?>"
							class="itr-kb-featured-topic__img"
							loading="lazy"
						/>
					<?php else : ?>
						<div class="itr-kb-featured-topic__img-placeholder">
							<?php
							$icon = get_term_meta( $cat->term_id, 'itr_kb_category_icon', true );
							echo '<span class="dashicons ' . esc_attr( $icon ?: 'dashicons-category' ) . '"></span>';
							?>
						</div>
					<?php endif; ?>
				</div>

				<!-- Default name overlay (always visible) -->
				<div class="itr-kb-featured-topic__name-layer">
					<span class="itr-kb-featured-topic__name"><?php echo esc_html( $cat->name ); ?></span>
				</div>

				<!-- Hover overlay: slides up from bottom -->
				<div class="itr-kb-featured-topic__hover-layer" aria-hidden="true">
					<span class="itr-kb-featured-topic__hover-name"><?php echo esc_html( $cat->name ); ?></span>
					<?php if ( ! empty( $children ) ) : ?>
						<div class="itr-kb-featured-topic__chips">
							<?php foreach ( $children as $child ) : ?>
								<span class="itr-kb-featured-topic__chip"><?php echo esc_html( $child->name ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<span class="itr-kb-featured-topic__cta">
						<?php esc_html_e( 'View All Categories', 'itr-knowledgebase' ); ?>
						<span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
					</span>
				</div>

			</a>
			<?php
		}
		echo '</div>';
	}

	// ─── Article fallback (auto mode, leaf category) ─────────────────────────

	private function render_article_fallback( $current_term ) {
		// Guard: $current_term must be a valid term object (PHP 8 fatal if null).
		if ( ! $current_term || ! is_object( $current_term ) || empty( $current_term->term_id ) ) {
			return;
		}

		$articles_per_page = absint( get_option( 'itr_kb_articles_per_page', 10 ) );
		$article_query = new \WP_Query( array(
			'post_type'      => 'itr_kb_article',
			'post_status'    => 'publish',
			'posts_per_page' => $articles_per_page,
			'paged'          => get_query_var( 'paged' ) ?: 1,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'itr_kb_category',
					'field'    => 'term_id',
					'terms'    => $current_term->term_id,
				),
			),
		) );

		if ( ! $article_query->have_posts() ) {
			echo '<p class="itr-kb-no-articles">' . esc_html__( 'No articles found in this category.', 'itr-knowledgebase' ) . '</p>';
			return;
		}

		$_cat_badge = isset( $current_term->name ) ? $current_term->name : '';
		?>
		<div class="itr-kb-article-list" id="itr-kb-article-list">
			<?php while ( $article_query->have_posts() ) : $article_query->the_post();
				$_post_id        = get_the_ID();
				$_author_id      = get_post_meta( $_post_id, '_itr_kb_author_id', true );
				$_reviewer_ids   = get_post_meta( $_post_id, '_itr_kb_reviewer_ids', true );
				$_author_name    = '';
				$_reviewer_names = array();

				if ( $_author_id ) {
					$_ap = get_post( absint( $_author_id ) );
					if ( $_ap && 'itr_kb_author' === $_ap->post_type ) {
						$_author_name = $_ap->post_title;
					}
				}

				if ( ! empty( $_reviewer_ids ) && is_array( $_reviewer_ids ) ) {
					foreach ( $_reviewer_ids as $_rid ) {
						$_rp = get_post( absint( $_rid ) );
						if ( $_rp && 'itr_kb_author' === $_rp->post_type ) {
							$_reviewer_names[] = $_rp->post_title;
						}
					}
				}

				$_article_url = \ITR_Knowledgebase\Helpers\ITR_KB_Utils::get_contextual_article_url( $_post_id, $current_term->term_id );
			?>
				<article class="itr-kb-article-card">
					<?php if ( $_cat_badge ) : ?>
						<span class="itr-kb-article-card__badge"><?php echo esc_html( $_cat_badge ); ?></span>
					<?php endif; ?>

					<h3 class="itr-kb-article-card__title">
						<a href="<?php echo esc_url( $_article_url ); ?>" class="itr-kb-article-card__link">
							<?php the_title(); ?>
						</a>
					</h3>

					<?php $excerpt = get_the_excerpt(); if ( $excerpt ) : ?>
						<div class="itr-kb-article-card__excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 20 ) ); ?></div>
					<?php endif; ?>

					<div class="itr-kb-article-card__footer">
						<a href="<?php echo esc_url( $_article_url ); ?>" class="itr-kb-article-card__read-more">
							<?php esc_html_e( 'Read More', 'itr-knowledgebase' ); ?>
						</a>
						<div class="itr-kb-article-card__meta">
							<span><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php printf( esc_html__( 'Posted: %s', 'itr-knowledgebase' ), '<span class=itr-meta-posted>' . esc_html( get_the_date() ) . '</span>' ); ?></time></span>
							<?php if ( $_author_name ) : ?>
								<span><?php printf( esc_html__( 'Author: %s', 'itr-knowledgebase' ), '<strong>' . esc_html( $_author_name ) . '</strong>' ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $_reviewer_names ) ) : ?>
								<span><?php printf( esc_html__( 'Reviewed by: %s', 'itr-knowledgebase' ), '<strong>' . esc_html( implode( ', ', $_reviewer_names ) ) . '</strong>' ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		</div>
		<?php if ( $article_query->max_num_pages > 1 ) : ?>
			<div class="itr-kb-load-more-wrap" id="itr-kb-load-more-wrap">
				<button
					class="itr-kb-load-more-btn"
					id="itr-kb-load-more"
					data-page="1"
					data-max="<?php echo esc_attr( $article_query->max_num_pages ); ?>"
					data-term="<?php echo esc_attr( $current_term->term_id ); ?>"
					data-taxonomy="itr_kb_category"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'itr_kb_load_more' ) ); ?>"
				>
					<?php esc_html_e( 'Load More Articles', 'itr-knowledgebase' ); ?>
					<?php
					$_lm_icon = $this->get_settings_for_display()['load_more_icon'] ?? array();
					if ( ! empty( $_lm_icon['value'] ) ) {
						\Elementor\Icons_Manager::render_icon( $_lm_icon, array( 'aria-hidden' => 'true' ) );
					} else {
						echo '<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>';
					}
					?>
				</button>
				<span class="itr-kb-load-more-status"></span>
			</div>
		<?php endif;
	}
}