<?php
/**
 * Elementor Widget: KB Category Tree
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor/widgets
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ITR_Knowledgebase\Admin\ITR_KB_Category_Order;
use ITR_Knowledgebase\Taxonomies\ITR_KB_Category;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Widget_Category_Tree
 */
class ITR_KB_Widget_Category_Tree extends Widget_Base {

	public function get_name() { return 'itr-kb-category-tree'; }
	public function get_title() { return esc_html__( 'KB Category Tree', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-bullet-list'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'category', 'tree', 'kb', 'sidebar' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Category Tree', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		));

		$this->add_control( 'show_title', array(
			'label'        => esc_html__( 'Show "Categories" Title', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => esc_html__( 'Show', 'itr-knowledgebase' ),
			'label_off'    => esc_html__( 'Hide', 'itr-knowledgebase' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_count', array(
			'label'        => esc_html__( 'Show Article Count', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_icons', array(
			'label'        => esc_html__( 'Show Category Icons', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'custom_title', array(
			'label'     => esc_html__( 'Custom Title', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => esc_html__( 'Categories', 'itr-knowledgebase' ),
			'condition' => array( 'show_title' => 'yes' ),
		));

		$this->end_controls_section();

		// Style.
		// ── Category Tree: Container ─────────────────────────────────────────
		$this->start_controls_section( 'section_style_container', array(
			'label' => esc_html__( 'Container', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'container_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'container_border',
			'selector' => '{{WRAPPER}} .itr-kb-category-tree',
		) );

		$this->add_responsive_control( 'container_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-tree' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'container_padding', array(
			'label'      => esc_html__( 'Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-tree' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Category Tree: Row ────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_row', array(
			'label' => esc_html__( 'Row', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->start_controls_tabs( 'tabs_row' );
		$this->start_controls_tab( 'tab_row_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );
		$this->add_control( 'row_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__row' => 'background-color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_row_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );
		$this->add_control( 'row_hover_bg', array(
			'label'     => esc_html__( 'Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__row:hover' => 'background-color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'row_padding', array(
			'label'      => esc_html__( 'Row Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-tree__row' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'toggle_color', array(
			'label'     => esc_html__( 'Toggle Arrow Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__toggle' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		// ── Category Tree: Icon ───────────────────────────────────────────────
		$this->start_controls_section( 'section_style_tree_icon', array(
			'label' => esc_html__( 'Category Icon', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'tree_icon_color', array(
			'label'     => esc_html__( 'Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__icon' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'tree_icon_bg', array(
			'label'     => esc_html__( 'Icon Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__icon' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'tree_icon_size', array(
			'label'      => esc_html__( 'Icon Size', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 12, 'max' => 48 ) ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-tree__icon' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'tree_icon_radius', array(
			'label'      => esc_html__( 'Icon Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-tree__icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		// ── Category Tree: Link ───────────────────────────────────────────────
		$this->start_controls_section( 'section_style_link', array(
			'label' => esc_html__( 'Links', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'link_typography',
			'selector' => '{{WRAPPER}} .itr-kb-category-tree__link, {{WRAPPER}} .itr-kb-category-tree__title',
		) );

		$this->start_controls_tabs( 'tabs_link_color' );
		$this->start_controls_tab( 'tab_link_normal', array( 'label' => esc_html__( 'Normal', 'itr-knowledgebase' ) ) );
		$this->add_control( 'link_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__link' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_link_hover', array( 'label' => esc_html__( 'Hover', 'itr-knowledgebase' ) ) );
		$this->add_control( 'link_hover_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__link:hover' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'tab_link_active', array( 'label' => esc_html__( 'Active', 'itr-knowledgebase' ) ) );
		$this->add_control( 'active_color', array(
			'label'     => esc_html__( 'Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__item--active .itr-kb-category-tree__link' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control( 'count_color', array(
			'label'     => esc_html__( 'Count Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .itr-kb-category-tree__count' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'count_typography',
			'label'    => esc_html__( 'Count Typography', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-category-tree__count',
		) );

		$this->add_responsive_control( 'children_indent', array(
			'label'      => esc_html__( 'Children Indent', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'separator'  => 'before',
			'selectors'  => array( '{{WRAPPER}} .itr-kb-category-tree__children' => 'padding-left: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$show_title = 'yes' === $settings['show_title'];
		$show_count = 'yes' === $settings['show_count'];
		$show_icons = 'yes' === $settings['show_icons'];
		$title      = ! empty( $settings['custom_title'] ) ? $settings['custom_title'] : esc_html__( 'Categories', 'itr-knowledgebase' );

		$tree = ITR_KB_Category_Order::get_category_tree( 0 );

		if ( empty( $tree ) ) {
			echo '<p>' . esc_html__( 'No categories found.', 'itr-knowledgebase' ) . '</p>';
			return;
		}

		$current_term_id = 0;
		if ( is_tax( 'itr_kb_category' ) ) {
			$current_term_id = get_queried_object_id();
		} elseif ( is_singular( 'itr_kb_article' ) ) {
			$terms = wp_get_post_terms( get_the_ID(), 'itr_kb_category', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$current_term_id = $terms[0];
			}
		}

		// Ancestor chain of the current term — used so a parent category
		// stays highlighted and expanded whenever one of its descendants
		// is the active page (this is computed fresh on every request,
		// so it survives the full page reload a category link triggers).
		$ancestor_ids = $current_term_id
			? array_map( 'intval', get_ancestors( $current_term_id, 'itr_kb_category', 'taxonomy' ) )
			: array();
		?>
		<nav class="itr-kb-category-tree" aria-label="<?php esc_attr_e( 'Knowledge Base Categories', 'itr-knowledgebase' ); ?>">
			<?php if ( $show_title ) : ?>
				<div class="itr-kb-category-tree__header">
					<span class="itr-kb-category-tree__title"><?php echo esc_html( $title ); ?></span>
				</div>
			<?php endif; ?>

			<ul class="itr-kb-category-tree__list">
				<?php $this->render_tree( $tree, $current_term_id, $show_count, $show_icons, $ancestor_ids ); ?>
			</ul>
		</nav>
		<?php
	}

	private function render_tree( $items, $current_term_id, $show_count, $show_icons, $ancestor_ids = array() ) {
		foreach ( $items as $term ) {
			$has_children = ! empty( $term->children );
			$is_active    = ( (int) $current_term_id === (int) $term->term_id );
			$is_ancestor  = in_array( (int) $term->term_id, $ancestor_ids, true );
			$is_open      = $is_active || $is_ancestor;
			$icon         = $show_icons ? ITR_KB_Category::get_icon( $term->term_id ) : '';

			$classes = array( 'itr-kb-category-tree__item' );
			if ( $is_open ) $classes[] = 'itr-kb-category-tree__item--active';
			if ( $has_children ) $classes[] = 'itr-kb-category-tree__item--has-children';
			?>
			<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<div class="itr-kb-category-tree__row">
					<?php if ( $icon ) : ?>
						<span class="itr-kb-category-tree__icon dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
					<?php endif; ?>
					<a
						href="<?php echo esc_url( get_term_link( $term ) ); ?>"
						class="itr-kb-category-tree__link"
						<?php echo $is_active ? 'aria-current="page"' : ''; ?>
					>
						<?php echo esc_html( $term->name ); ?>
						<?php if ( $show_count ) : ?>
							<span class="itr-kb-category-tree__count">(<?php echo absint( $term->count ); ?>)</span>
						<?php endif; ?>
					</a>
					<?php if ( $has_children ) : ?>
						<button class="itr-kb-category-tree__toggle" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
							<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
						</button>
					<?php endif; ?>
				</div>
				<?php if ( $has_children ) : ?>
					<ul class="itr-kb-category-tree__children" <?php echo ! $is_open ? 'hidden' : ''; ?>>
						<?php $this->render_tree( $term->children, $current_term_id, $show_count, $show_icons, $ancestor_ids ); ?>
					</ul>
				<?php endif; ?>
			</li>
			<?php
		}
	}
}