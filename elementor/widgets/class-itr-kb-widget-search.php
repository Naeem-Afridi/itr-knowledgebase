<?php
/**
 * Elementor Widget: KB Search Bar
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor/widgets
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ITR_Knowledgebase\Helpers\ITR_KB_Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Widget_Search
 */
class ITR_KB_Widget_Search extends Widget_Base {

	public function get_name() { return 'itr-kb-search'; }
	public function get_title() { return esc_html__( 'KB Search Bar', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-search'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'search', 'kb', 'knowledgebase' ); }

	protected function register_controls() {
		// Content tab.
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Search Bar', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		));

		$this->add_control( 'placeholder', array(
			'label'   => esc_html__( 'Placeholder Text', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Search articles...', 'itr-knowledgebase' ),
		));

		$this->add_control( 'show_results_count', array(
			'label'        => esc_html__( 'Live Results Count', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::NUMBER,
			'default'      => 5,
			'min'          => 1,
			'max'          => 20,
		));

		$this->add_control( 'search_design', array(
			'label'     => esc_html__( 'Design', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'standard',
			'separator' => 'before',
			'options'   => array(
				'standard' => esc_html__( 'Standard', 'itr-knowledgebase' ),
				'pill'     => esc_html__( 'Pill (integrated icon)', 'itr-knowledgebase' ),
			),
		) );

		$this->add_control( 'pill_icon', array(
			'label'     => esc_html__( 'Pill Icon', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::ICONS,
			'default'   => array( 'value' => 'dashicons dashicons-search', 'library' => 'dashicons' ),
			'condition' => array( 'search_design' => 'pill' ),
		) );

		$this->add_control( 'pill_icon_color', array(
			'label'     => esc_html__( 'Pill Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#999999',
			'condition' => array( 'search_design' => 'pill' ),
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__pill-icon' => 'color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'pill_icon_size', array(
			'label'      => esc_html__( 'Pill Icon Size', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 12, 'max' => 36 ) ),
			'default'    => array( 'size' => 18, 'unit' => 'px' ),
			'condition'  => array( 'search_design' => 'pill' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-search-bar__pill-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .itr-kb-search-bar__pill-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_control( 'show_button', array(
			'label'        => esc_html__( 'Show Submit Button', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'search_design' => 'standard' ),
		) );

		$this->add_control( 'button_text', array(
			'label'     => esc_html__( 'Button Text', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => '',
			'placeholder' => esc_html__( 'Search', 'itr-knowledgebase' ),
			'condition' => array( 'search_design' => 'standard', 'show_button' => 'yes' ),
		) );

		$this->add_control( 'button_icon', array(
			'label'     => esc_html__( 'Button Icon', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::ICONS,
			'default'   => array( 'value' => 'dashicons dashicons-search', 'library' => 'dashicons' ),
			'condition' => array( 'search_design' => 'standard', 'show_button' => 'yes' ),
		) );

		$this->end_controls_section();

		// Style tab.
		// ── Search: Input ────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_input', array(
			'label' => esc_html__( 'Input', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'input_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__input' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'input_text_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__input' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'placeholder_color', array(
			'label'     => esc_html__( 'Placeholder Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__input::placeholder' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'input_typography',
			'selector' => '{{WRAPPER}} .itr-kb-search-bar__input',
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'input_border',
			'selector' => '{{WRAPPER}} .itr-kb-search-bar__input-wrap',
		) );

		$this->add_responsive_control( 'input_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'default'    => array( 'top' => 999, 'right' => 999, 'bottom' => 999, 'left' => 999, 'unit' => 'px', 'isLinked' => true ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-search-bar__input-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important; overflow: hidden;',
			),
		) );

		$this->add_responsive_control( 'input_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-search-bar__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'input_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-search-bar__input-wrap',
		) );

		$this->end_controls_section();

		// ── Search: Submit Button ─────────────────────────────────────────────
		$this->start_controls_section( 'section_style_button', array(
			'label'     => esc_html__( 'Submit Button', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'search_design' => 'standard' ),
		) );

		$this->start_controls_tabs( 'tabs_button' );
		$this->start_controls_tab( 'tab_btn_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );
		$this->add_control( 'button_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__submit' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'button_color', array(
			'label'     => esc_html__( 'Icon / Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__submit' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_btn_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );
		$this->add_control( 'button_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__submit:hover' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'button_hover_color', array(
			'label'     => esc_html__( 'Icon / Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__submit:hover' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'button_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-search-bar__submit' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'button_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-search-bar__submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Search: Results Dropdown ──────────────────────────────────────────
		$this->start_controls_section( 'section_style_results', array(
			'label' => esc_html__( 'Results Dropdown', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'results_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__results' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'results_border',
			'selector' => '{{WRAPPER}} .itr-kb-search-bar__results',
		) );

		$this->add_responsive_control( 'results_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-search-bar__results' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'results_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-search-bar__results',
		) );

		$this->add_control( 'results_link_color', array(
			'label'     => esc_html__( 'Result Link Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__results-list a' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'results_link_hover_color', array(
			'label'     => esc_html__( 'Result Link Hover Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__results-list a:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'view_all_color', array(
			'label'     => esc_html__( 'View All Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-search-bar__view-all' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$placeholder = ! empty( $settings['placeholder'] ) ? esc_attr( $settings['placeholder'] ) : esc_attr__( 'Search articles...', 'itr-knowledgebase' );
		$count       = absint( $settings['show_results_count'] ?? 5 );
		$design      = $settings['search_design'] ?? 'standard';
		$show_button = 'yes' === ( $settings['show_button'] ?? 'yes' );
		$btn_text    = trim( $settings['button_text'] ?? '' );
		$wrap_class  = 'itr-kb-search-bar' . ( 'pill' === $design ? ' itr-kb-search-bar--pill' : '' );

		// Form action is just a sane no-JS fallback target — real search is
		// AJAX/JS-driven with Enter-key submission already prevented.
		$results_url = get_post_type_archive_link( 'itr_kb_article' ) ?: home_url( '/' );
		$results_url = esc_url( $results_url );
		?>
		<div class="<?php echo esc_attr( $wrap_class ); ?>"
			role="search"
			data-results-count="<?php echo esc_attr( $count ); ?>"
			data-search-url="<?php echo esc_attr( $results_url ); ?>"
		>
			<form
				class="itr-kb-search-bar__form"
				action="<?php echo esc_url( $results_url ); ?>"
				method="get"
				aria-label="<?php esc_attr_e( 'Search Knowledge Base', 'itr-knowledgebase' ); ?>"
			>
				<div class="itr-kb-search-bar__input-wrap">
					<label for="itr-kb-search-input-<?php echo esc_attr( $this->get_id() ); ?>" class="screen-reader-text">
						<?php esc_html_e( 'Search articles', 'itr-knowledgebase' ); ?>
					</label>
					<?php if ( 'pill' === $design ) : ?>
						<span class="itr-kb-search-bar__pill-icon" aria-hidden="true">
							<?php
							$pill_icon = $settings['pill_icon'] ?? array();
							if ( ! empty( $pill_icon['value'] ) ) {
								\Elementor\Icons_Manager::render_icon( $pill_icon, array( 'aria-hidden' => 'true' ) );
							} else {
								echo '<span class="dashicons dashicons-search"></span>';
							}
							?>
						</span>
					<?php endif; ?>
					<input
						type="search"
						id="itr-kb-search-input-<?php echo esc_attr( $this->get_id() ); ?>"
						class="itr-kb-search-bar__input"
						name="itr_kb_search"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						autocomplete="off"
						aria-autocomplete="list"
						aria-controls="itr-kb-search-results-<?php echo esc_attr( $this->get_id() ); ?>"
						aria-expanded="false"
					/>
					<?php if ( $show_button && 'standard' === $design ) : ?>
					<button type="submit" class="itr-kb-search-bar__submit" aria-label="<?php esc_attr_e( 'Search', 'itr-knowledgebase' ); ?>">
						<?php
						$icon_val = $settings['button_icon'] ?? array();
						if ( ! empty( $icon_val['value'] ) ) {
							\Elementor\Icons_Manager::render_icon( $icon_val, array( 'aria-hidden' => 'true' ) );
						} else {
							echo '<span class="dashicons dashicons-search" aria-hidden="true"></span>';
						}
						if ( $btn_text ) {
							echo '<span class="itr-kb-search-bar__btn-text">' . esc_html( $btn_text ) . '</span>';
						}
						?>
					</button>
					<?php endif; ?>
				</div>
				<div
					id="itr-kb-search-results-<?php echo esc_attr( $this->get_id() ); ?>"
					class="itr-kb-search-bar__results"
					role="listbox"
					hidden
				>
					<ul class="itr-kb-search-bar__results-list" aria-live="polite"></ul>
					<div class="itr-kb-search-bar__results-footer" hidden>
						<a href="#" class="itr-kb-search-bar__view-all">
							<?php esc_html_e( 'View all results', 'itr-knowledgebase' ); ?>
						</a>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
}