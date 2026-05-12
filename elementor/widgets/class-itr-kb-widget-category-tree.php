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
		$this->start_controls_section( 'section_style', array(
			'label' => esc_html__( 'Style', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		));

		$this->add_control( 'link_color', array(
			'label'     => esc_html__( 'Link Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-tree__link' => 'color: {{VALUE}};',
			),
		));

		$this->add_control( 'active_color', array(
			'label'     => esc_html__( 'Active Link Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-tree__item--active .itr-kb-category-tree__link' => 'color: {{VALUE}};',
			),
		));

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'link_typography',
			'label'    => esc_html__( 'Link Typography', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-category-tree__link',
		));

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
		?>
		<nav class="itr-kb-category-tree" aria-label="<?php esc_attr_e( 'Knowledge Base Categories', 'itr-knowledgebase' ); ?>">
			<?php if ( $show_title ) : ?>
				<div class="itr-kb-category-tree__header">
					<span class="itr-kb-category-tree__title"><?php echo esc_html( $title ); ?></span>
				</div>
			<?php endif; ?>

			<ul class="itr-kb-category-tree__list">
				<?php $this->render_tree( $tree, $current_term_id, $show_count, $show_icons ); ?>
			</ul>
		</nav>
		<?php
	}

	private function render_tree( $items, $current_term_id, $show_count, $show_icons ) {
		foreach ( $items as $term ) {
			$has_children = ! empty( $term->children );
			$is_active    = ( (int) $current_term_id === (int) $term->term_id );
			$icon         = $show_icons ? ITR_KB_Category::get_icon( $term->term_id ) : '';

			$classes = array( 'itr-kb-category-tree__item' );
			if ( $is_active ) $classes[] = 'itr-kb-category-tree__item--active';
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
						<button class="itr-kb-category-tree__toggle" aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>">
							<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
						</button>
					<?php endif; ?>
				</div>
				<?php if ( $has_children ) : ?>
					<ul class="itr-kb-category-tree__children" <?php echo ! $is_active ? 'hidden' : ''; ?>>
						<?php $this->render_tree( $term->children, $current_term_id, $show_count, $show_icons ); ?>
					</ul>
				<?php endif; ?>
			</li>
			<?php
		}
	}
}