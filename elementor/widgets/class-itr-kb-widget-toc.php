<?php
/**
 * Elementor Widget: KB Table of Contents
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor/widgets
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ITR_Knowledgebase\Frontend\ITR_KB_TOC;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Widget_TOC
 */
class ITR_KB_Widget_TOC extends Widget_Base {

	public function get_name() { return 'itr-kb-toc'; }
	public function get_title() { return esc_html__( 'KB Table of Contents', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-table-of-contents'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'toc', 'table', 'contents', 'headings', 'kb' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Table of Contents', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		));

		$this->add_control( 'toc_title', array(
			'label'   => esc_html__( 'Title', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Table of Contents', 'itr-knowledgebase' ),
		));

		$this->add_control( 'min_headings', array(
			'label'       => esc_html__( 'Minimum Headings to Show', 'itr-knowledgebase' ),
			'description' => esc_html__( 'TOC only renders if article has at least this many headings.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 2,
			'min'         => 1,
		));

		$this->end_controls_section();

		// ── TOC: Container ───────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_toc_container', array(
			'label' => esc_html__( 'Container', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'bg_color', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-toc' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'toc_border',
			'selector' => '{{WRAPPER}} .itr-kb-toc',
		) );

		$this->add_responsive_control( 'toc_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-toc' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'toc_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-toc' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'toc_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-toc',
		) );

		$this->end_controls_section();

		// ── TOC: Header / Title ───────────────────────────────────────────────
		$this->start_controls_section( 'section_style_toc_header', array(
			'label' => esc_html__( 'Header', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'toc_header_bg', array(
			'label'     => esc_html__( 'Header Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-toc__header' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'toc_title_typography',
			'label'    => esc_html__( 'Title Typography', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-toc__title',
		) );

		$this->add_control( 'toc_title_color', array(
			'label'     => esc_html__( 'Title Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-toc__title' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'toc_toggle_color', array(
			'label'     => esc_html__( 'Toggle Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-toc__toggle-icon' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── TOC: Links ────────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_toc_links', array(
			'label' => esc_html__( 'Links', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'toc_link_typography',
			'selector' => '{{WRAPPER}} .itr-kb-toc__link',
		) );

		$this->start_controls_tabs( 'tabs_toc_link' );

		$this->start_controls_tab( 'tab_toc_link_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );
		$this->add_control( 'link_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-toc__link' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_toc_link_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );
		$this->add_control( 'link_hover_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-toc__link:hover' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_toc_link_active', array( 'label' => esc_html__( 'Active', 'itr-knowledgebase' ) ) );
		$this->add_control( 'link_active_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-toc__link--active' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control( 'toc_item_spacing', array(
			'label'      => esc_html__( 'Item Spacing', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-toc__item' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'toc_indent', array(
			'label'      => esc_html__( 'Sub-level Indent', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-toc__sublist' => 'padding-left: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$min         = absint( $settings['min_headings'] ?? 2 );
		$post_id     = get_the_ID();

		$toc = ITR_KB_TOC::get_toc_for_post( $post_id );

		if ( empty( $toc ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="itr-kb-toc"><p style="padding:1em;color:#999;">' . esc_html__( 'TOC will appear here when article has headings.', 'itr-knowledgebase' ) . '</p></div>';
			}
			return;
		}

		echo $toc; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}


/**
 * Elementor Widget: KB Author Box
 */
class ITR_KB_Widget_Author_Box extends Widget_Base {

	public function get_name() { return 'itr-kb-author-box'; }
	public function get_title() { return esc_html__( 'KB Author Box', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-person'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'author', 'reviewer', 'box', 'kb' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Author Box', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		));

		$this->add_control( 'author_layout', array(
			'label'   => esc_html__( 'Layout', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'standard',
			'options' => array(
				'standard' => esc_html__( 'Standard', 'itr-knowledgebase' ),
				'compact'  => esc_html__( 'Compact (inline name + role)', 'itr-knowledgebase' ),
			),
		));

		$this->add_control( 'show_bio', array(
			'label'        => esc_html__( 'Show Bio', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_reviewers', array(
			'label'        => esc_html__( 'Show Reviewers', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'avatar_size', array(
			'label'      => esc_html__( 'Avatar Size', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 40, 'max' => 150 ) ),
			'default'    => array( 'unit' => 'px', 'size' => 80 ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-author-box__avatar img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		));

		$this->end_controls_section();

		// ── Author Box: Container ────────────────────────────────────────────
		$this->start_controls_section( 'section_style_author_container', array(
			'label' => esc_html__( 'Container', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'box_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-author-box' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'box_border',
			'selector' => '{{WRAPPER}} .itr-kb-author-box',
		) );

		$this->add_responsive_control( 'box_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-author-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'box_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-author-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'box_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-author-box',
		) );

		$this->end_controls_section();

		// ── Author Box: Avatar ────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_author_avatar', array(
			'label' => esc_html__( 'Avatar', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'avatar_size', array(
			'label'      => esc_html__( 'Avatar Size', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 40, 'max' => 160 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-author-box__avatar img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'avatar_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-author-box__avatar img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Author Box: Name ──────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_author_name', array(
			'label' => esc_html__( 'Name', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'name_typography',
			'selector' => '{{WRAPPER}} .itr-kb-author-box__name',
		) );

		$this->add_control( 'name_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-author-box__name' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Author Box: Heading Label (Written by / Reviewed by) ──────────────
		$this->start_controls_section( 'section_style_author_heading', array(
			'label' => esc_html__( 'Role Label', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'heading_typography',
			'selector' => '{{WRAPPER}} .itr-kb-author-box__heading',
		) );

		$this->add_control( 'heading_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-author-box__heading' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Author Box: Bio ───────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_author_bio', array(
			'label' => esc_html__( 'Bio / Description', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'bio_typography',
			'selector' => '{{WRAPPER}} .itr-kb-author-box__bio',
		) );

		$this->add_control( 'bio_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-author-box__bio' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings       = $this->get_settings_for_display();
		$show_reviewers = 'yes' === $settings['show_reviewers'];
		$author_layout  = $settings['author_layout'] ?? 'standard';
		$post_id        = get_the_ID();

		$author_id    = absint( get_post_meta( $post_id, '_itr_kb_author_id', true ) );
		$reviewer_ids = get_post_meta( $post_id, '_itr_kb_reviewer_ids', true );
		$reviewer_ids = is_array( $reviewer_ids ) ? $reviewer_ids : array();

		if ( ! $show_reviewers ) {
			$reviewer_ids = array();
		}

		if ( ! $author_id && empty( $reviewer_ids ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="itr-kb-author-box"><p style="padding:1em;color:#999;">' . esc_html__( 'Author box will appear here when article has an author assigned.', 'itr-knowledgebase' ) . '</p></div>';
			}
			return;
		}

		\ITR_Knowledgebase\Helpers\ITR_KB_Utils::load_template(
			'partials/itr-kb-author-box.php',
			array(
				'author_id'     => $author_id,
				'reviewer_ids'  => $reviewer_ids,
				'author_layout' => $author_layout,
			)
		);
	}
}


/**
 * Elementor Widget: KB Content Sections
 */
class ITR_KB_Widget_Content_Sections extends Widget_Base {

	public function get_name() { return 'itr-kb-content-sections'; }
	public function get_title() { return esc_html__( 'KB Content Sections', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-post-content'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'latest', 'popular', 'trending', 'featured', 'recommended', 'kb' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Content Section', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		));

		$this->add_control( 'section_type', array(
			'label'   => esc_html__( 'Section Type', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'latest',
			'options' => array(
				'latest'           => esc_html__( 'Latest Articles', 'itr-knowledgebase' ),
				'recently_updated' => esc_html__( 'Recently Updated', 'itr-knowledgebase' ),
				'popular'          => esc_html__( 'Popular Articles', 'itr-knowledgebase' ),
				'trending'         => esc_html__( 'Trending Articles', 'itr-knowledgebase' ),
				'featured'         => esc_html__( 'Featured Articles', 'itr-knowledgebase' ),
				'recommended'      => esc_html__( 'Recommended Articles', 'itr-knowledgebase' ),
			),
		));

		$this->add_control( 'count', array(
			'label'   => esc_html__( 'Number of Articles', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 5,
			'min'     => 1,
			'max'     => 50,
		));

		$this->add_control( 'section_title', array(
			'label'       => esc_html__( 'Section Title', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => esc_html__( 'e.g. Popular Articles', 'itr-knowledgebase' ),
		));

		$this->add_control( 'layout', array(
			'label'   => esc_html__( 'Layout', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'list',
			'options' => array(
				'list' => esc_html__( 'List', 'itr-knowledgebase' ),
				'grid' => esc_html__( 'Grid', 'itr-knowledgebase' ),
			),
		));

		$this->add_control( 'show_date', array(
			'label'        => esc_html__( 'Show Date', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_excerpt', array(
			'label'        => esc_html__( 'Show Excerpt', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		));

		$this->add_control( 'show_thumbnail', array(
			'label'        => esc_html__( 'Show Thumbnail', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		));

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', array(
			'label' => esc_html__( 'Style', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		));

		$this->add_control( 'section_title_color', array(
			'label'     => esc_html__( 'Section Title Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-section__title' => 'color: {{VALUE}};',
			),
		));

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'section_title_typography',
			'label'    => esc_html__( 'Section Title Typography', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-section__title',
		));

		$this->add_control( 'article_link_color', array(
			'label'     => esc_html__( 'Article Link Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-section__link' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'article_link_hover', array(
			'label'     => esc_html__( 'Article Link Hover', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-section__link:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'article_link_typography',
			'label'    => esc_html__( 'Article Link Typography', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-section__link',
		) );

		$this->end_controls_section();

		// ── Content Sections: Section Header ──────────────────────────────────
		$this->start_controls_section( 'section_style_cs_header', array(
			'label' => esc_html__( 'Section Header', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'cs_header_bg', array(
			'label'     => esc_html__( 'Header Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-section__header' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'cs_header_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-section__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'cs_header_border',
			'selector' => '{{WRAPPER}} .itr-kb-section__header',
		) );

		$this->end_controls_section();

		// ── Content Sections: Meta ─────────────────────────────────────────────
		$this->start_controls_section( 'section_style_cs_meta', array(
			'label' => esc_html__( 'Meta (Date / Category)', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'cs_meta_typography',
			'selector' => '{{WRAPPER}} .itr-kb-section__date, {{WRAPPER}} .itr-kb-section__category',
		) );

		$this->add_control( 'cs_meta_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-section__date, {{WRAPPER}} .itr-kb-section__category' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Content Sections: Item ─────────────────────────────────────────────
		$this->start_controls_section( 'section_style_cs_item', array(
			'label' => esc_html__( 'List Item', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'cs_item_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-section__item' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'cs_item_border',
			'selector' => '{{WRAPPER}} .itr-kb-section__item',
		) );

		$this->add_responsive_control( 'cs_item_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-section__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'cs_item_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-section__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$html = \ITR_Knowledgebase\Frontend\ITR_KB_Sections::render(
			$settings['section_type'],
			array(
				'count'         => absint( $settings['count'] ),
				'post_id'       => get_the_ID(),
				'title'         => esc_html( $settings['section_title'] ?? '' ),
				'layout'        => sanitize_key( $settings['layout'] ?? 'list' ),
				'show_date'     => 'yes' === $settings['show_date'],
				'show_excerpt'  => 'yes' === $settings['show_excerpt'],
				'show_thumb'    => 'yes' === $settings['show_thumbnail'],
				'show_category' => true,
			)
		);

		if ( $html ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p style="color:#999;padding:1em;">' . esc_html__( 'No articles found for this section type.', 'itr-knowledgebase' ) . '</p>';
			}
		}
	}
}