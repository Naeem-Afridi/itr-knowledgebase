<?php
/**
 * Elementor Widget: KB Article Accordion
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor/widgets
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ITR_Knowledgebase\Helpers\ITR_KB_Query;
use ITR_Knowledgebase\Admin\ITR_KB_Category_Order;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Widget_Article_Accordion
 *
 * Displays KB categories as accordion sections,
 * each expanded to show its articles.
 */
class ITR_KB_Widget_Article_Accordion extends Widget_Base {

	public function get_name() { return 'itr-kb-article-accordion'; }
	public function get_title() { return esc_html__( 'KB Article Accordion', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-accordion'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'accordion', 'kb', 'article', 'faq' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Accordion', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		));

		$this->add_control( 'parent_id', array(
			'label'       => esc_html__( 'Parent Category ID', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Show categories from this parent. Leave 0 for top-level.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
		));

		$this->add_control( 'articles_per_category', array(
			'label'   => esc_html__( 'Articles per Category', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 5,
			'min'     => 1,
			'max'     => 50,
		));

		$this->add_control( 'open_first', array(
			'label'        => esc_html__( 'Open First Category by Default', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_count', array(
			'label'        => esc_html__( 'Show Article Count', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->end_controls_section();

		// Style.
		// ── Accordion: Section Item ───────────────────────────────────────────
		$this->start_controls_section( 'section_style_acc_item', array(
			'label' => esc_html__( 'Section Item', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'section_border',
			'selector' => '{{WRAPPER}} .itr-kb-accordion__section',
		) );

		$this->add_responsive_control( 'section_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-accordion__section' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'section_gap', array(
			'label'      => esc_html__( 'Gap Between Sections', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-accordion__section' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'section_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-accordion__section',
		) );

		$this->end_controls_section();

		// ── Accordion: Header ─────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_acc_header', array(
			'label' => esc_html__( 'Header', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->start_controls_tabs( 'tabs_acc_header' );

		$this->start_controls_tab( 'tab_acc_header_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );
		$this->add_control( 'header_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__header' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'header_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__header' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_acc_header_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );
		$this->add_control( 'header_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__header:hover' => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'header_hover_color', array(
			'label'     => esc_html__( 'Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__header:hover' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'header_typography',
			'selector' => '{{WRAPPER}} .itr-kb-accordion__cat-name',
		) );

		$this->add_responsive_control( 'header_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-accordion__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'header_toggle_color', array(
			'label'     => esc_html__( 'Toggle Arrow Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__icon' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'header_count_color', array(
			'label'     => esc_html__( 'Article Count Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__cat-count' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Accordion: Article List ────────────────────────────────────────────
		$this->start_controls_section( 'section_style_acc_list', array(
			'label' => esc_html__( 'Article List', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'body_bg', array(
			'label'     => esc_html__( 'Panel Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__body' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'body_padding', array(
			'label'      => esc_html__( 'Panel Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-accordion__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'link_typography',
			'selector' => '{{WRAPPER}} .itr-kb-accordion__article-link',
		) );

		$this->start_controls_tabs( 'tabs_acc_link' );
		$this->start_controls_tab( 'tab_acc_link_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );
		$this->add_control( 'link_color', array(
			'label'     => esc_html__( 'Link Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__article-link' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_acc_link_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );
		$this->add_control( 'link_hover_color', array(
			'label'     => esc_html__( 'Link Hover Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__article-link:hover' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control( 'view_all_color', array(
			'label'     => esc_html__( 'View All Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-accordion__view-all' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings             = $this->get_settings_for_display();
		$parent_id            = absint( $settings['parent_id'] );
		$articles_per_cat     = absint( $settings['articles_per_category'] ?? 5 );
		$open_first           = 'yes' === $settings['open_first'];
		$show_count           = 'yes' === $settings['show_count'];

		// $categories = ITR_KB_Category_Order::get_ordered_categories( $parent_id );

		$current_term = get_queried_object();

if ( $current_term && isset( $current_term->term_id ) && isset( $current_term->taxonomy ) ) {
	// We are on a category page → use current term
	$categories = array( $current_term );
} else {
	// Fallback → use parent_id (Elementor setting)
	$categories = ITR_KB_Category_Order::get_ordered_categories( $parent_id );
}

		if ( empty( $categories ) ) {
			echo '<p>' . esc_html__( 'No categories found.', 'itr-knowledgebase' ) . '</p>';
			return;
		}
		?>
		<div class="itr-kb-accordion">
			<?php foreach ( $categories as $index => $cat ) : ?>
				<?php
				$query      = ITR_KB_Query::get_by_category( $cat->term_id, $articles_per_cat, 1 );
				$is_open    = ( $open_first && 0 === $index );
				$section_id = 'itr-kb-accordion-' . $cat->term_id;
				?>
				<div class="itr-kb-accordion__section <?php echo $is_open ? 'itr-kb-accordion__section--open' : ''; ?>">

					<button
						class="itr-kb-accordion__header"
						aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
						aria-controls="<?php echo esc_attr( $section_id ); ?>"
					>
						<span class="itr-kb-accordion__cat-name"><?php echo esc_html( $cat->name ); ?></span>
						<?php if ( $show_count ) : ?>
							<span class="itr-kb-accordion__cat-count">(<?php echo absint( $cat->count ); ?>)</span>
						<?php endif; ?>
						<span class="itr-kb-accordion__icon dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
					</button>

					<div
						id="<?php echo esc_attr( $section_id ); ?>"
						class="itr-kb-accordion__body"
						<?php echo ! $is_open ? 'hidden' : ''; ?>
					>
						<?php if ( $query->have_posts() ) : ?>
							<ul class="itr-kb-accordion__list">
								<?php while ( $query->have_posts() ) : $query->the_post(); ?>
									<li class="itr-kb-accordion__item">
										<a
											href="<?php echo esc_url( \ITR_Knowledgebase\Helpers\ITR_KB_Utils::get_contextual_article_url( get_the_ID(), $cat->term_id ) ); ?>"
											class="itr-kb-accordion__article-link"
										>
											<span class="dashicons dashicons-arrow-right-alt2 itr-kb-accordion__item-icon" aria-hidden="true"></span>
											<?php the_title(); ?>
										</a>
									</li>
								<?php endwhile; ?>
								<?php wp_reset_postdata(); ?>
							</ul>

							<?php if ( $query->found_posts > $articles_per_cat ) : ?>
								<div class="itr-kb-accordion__view-all">
									<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
										<?php
										printf(
											/* translators: %s: category name */
											esc_html__( 'View all in %s →', 'itr-knowledgebase' ),
											esc_html( $cat->name )
										);
										?>
									</a>
								</div>
							<?php endif; ?>

						<?php else : ?>
							<p class="itr-kb-accordion__empty"><?php esc_html_e( 'No articles in this category.', 'itr-knowledgebase' ); ?></p>
						<?php endif; ?>
					</div>

				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}