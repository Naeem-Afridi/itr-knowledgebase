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

		$this->end_controls_section();

		// Style tab.
		$this->start_controls_section( 'section_style', array(
			'label' => esc_html__( 'Style', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		));

		$this->add_control( 'input_bg', array(
			'label'     => esc_html__( 'Input Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-search-bar__input' => 'background-color: {{VALUE}};',
			),
		));

		$this->add_control( 'button_bg', array(
			'label'     => esc_html__( 'Button Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-search-bar__submit' => 'background-color: {{VALUE}};',
			),
		));

		$this->add_responsive_control( 'input_padding', array(
			'label'      => esc_html__( 'Input Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-search-bar__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		));

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'input_border',
			'label'    => esc_html__( 'Input Border', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-search-bar__input',
		));

		$this->add_control( 'input_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-search-bar__input-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
			),
		));

		$this->end_controls_section();
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$placeholder = ! empty( $settings['placeholder'] ) ? esc_attr( $settings['placeholder'] ) : esc_attr__( 'Search articles...', 'itr-knowledgebase' );
		$count       = absint( $settings['show_results_count'] ?? 5 );
		?>
		<div class="itr-kb-search-bar" role="search" data-results-count="<?php echo esc_attr( $count ); ?>">
			<form
				class="itr-kb-search-bar__form"
				action="<?php echo esc_url( get_post_type_archive_link( 'itr_kb_article' ) ); ?>"
				method="get"
				aria-label="<?php esc_attr_e( 'Search Knowledge Base', 'itr-knowledgebase' ); ?>"
			>
				<div class="itr-kb-search-bar__input-wrap">
					<label for="itr-kb-search-input-<?php echo esc_attr( $this->get_id() ); ?>" class="screen-reader-text">
						<?php esc_html_e( 'Search articles', 'itr-knowledgebase' ); ?>
					</label>
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
					<button type="submit" class="itr-kb-search-bar__submit" aria-label="<?php esc_attr_e( 'Search', 'itr-knowledgebase' ); ?>">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
					</button>
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