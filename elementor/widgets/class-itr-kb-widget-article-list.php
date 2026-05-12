<?php
/**
 * Elementor Widget: KB Article List
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor/widgets
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ITR_Knowledgebase\Frontend\ITR_KB_Sections;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Widget_Article_List
 */
class ITR_KB_Widget_Article_List extends Widget_Base {

	public function get_name() { return 'itr-kb-article-list'; }
	public function get_title() { return esc_html__( 'KB Article List', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-post-list'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'article', 'list', 'kb', 'posts' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Article List', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		));

		$this->add_control( 'section_type', array(
			'label'   => esc_html__( 'Article Type', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'latest',
			'options' => array(
				'latest'           => esc_html__( 'Latest', 'itr-knowledgebase' ),
				'recently_updated' => esc_html__( 'Recently Updated', 'itr-knowledgebase' ),
				'popular'          => esc_html__( 'Popular', 'itr-knowledgebase' ),
				'trending'         => esc_html__( 'Trending', 'itr-knowledgebase' ),
				'featured'         => esc_html__( 'Featured', 'itr-knowledgebase' ),
				'recommended'      => esc_html__( 'Recommended', 'itr-knowledgebase' ),
			),
		));

		$this->add_control( 'count', array(
			'label'   => esc_html__( 'Number of Articles', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 5,
			'min'     => 1,
			'max'     => 50,
		));

		$this->add_control( 'show_excerpt', array(
			'label'        => esc_html__( 'Show Excerpt', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		));

		$this->add_control( 'show_date', array(
			'label'        => esc_html__( 'Show Date', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_category', array(
			'label'        => esc_html__( 'Show Category', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_thumbnail', array(
			'label'        => esc_html__( 'Show Thumbnail', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		));

		$this->add_control( 'show_icon', array(
			'label'        => esc_html__( 'Show Icon Before Title', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		));

		$this->add_control( 'item_icon', array(
			'label'      => esc_html__( 'Icon Image', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::MEDIA,
			'default'    => array( 'url' => '' ),
			'description'=> esc_html__( 'Upload an icon to show inline before each article title.', 'itr-knowledgebase' ),
			'condition'  => array( 'show_icon' => 'yes' ),
		));

		$this->add_control( 'icon_size', array(
			'label'      => esc_html__( 'Icon Size', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 12, 'max' => 64 ) ),
			'default'    => array( 'size' => 24, 'unit' => 'px' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-section__item-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
			'condition'  => array( 'show_icon' => 'yes' ),
		));

		$this->add_control( 'section_title', array(
			'label'   => esc_html__( 'Section Title', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
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

		$this->end_controls_section();

		// Style.
		$this->start_controls_section( 'section_style', array(
			'label' => esc_html__( 'Style', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		));

		$this->add_control( 'title_color', array(
			'label'     => esc_html__( 'Title Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-section__link' => 'color: {{VALUE}};',
			),
		));

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'title_typography',
			'label'    => esc_html__( 'Title Typography', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-section__article-title',
		));

		$this->add_control( 'meta_color', array(
			'label'     => esc_html__( 'Meta Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-section__date, {{WRAPPER}} .itr-kb-section__category' => 'color: {{VALUE}};',
			),
		));

		$this->add_responsive_control( 'item_spacing', array(
			'label'      => esc_html__( 'Item Spacing', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-section__item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
			),
		));

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$icon_url = ! empty( $settings['item_icon']['url'] ) ? esc_url( $settings['item_icon']['url'] ) : '';

		$html = ITR_KB_Sections::render(
			$settings['section_type'],
			array(
				'count'         => absint( $settings['count'] ),
				'post_id'       => get_the_ID(),
				'show_excerpt'  => 'yes' === $settings['show_excerpt'],
				'show_date'     => 'yes' === $settings['show_date'],
				'show_category' => 'yes' === $settings['show_category'],
				'show_thumb'    => 'yes' === $settings['show_thumbnail'],
				'show_icon'     => 'yes' === $settings['show_icon'] && ! empty( $icon_url ),
				'icon_url'      => $icon_url,
				'title'         => esc_html( $settings['section_title'] ),
				'layout'        => sanitize_key( $settings['layout'] ),
			)
		);

		if ( $html ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo '<p>' . esc_html__( 'No articles found.', 'itr-knowledgebase' ) . '</p>';
		}
	}
}