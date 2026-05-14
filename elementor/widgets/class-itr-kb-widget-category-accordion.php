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

		$this->add_control( 'show_count', array(
			'label'        => esc_html__( 'Show Article Count', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'layout' => 'simple' ),
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

		// ── Style — Simple ────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_simple', array(
			'label'     => esc_html__( 'Simple Layout Style', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'simple' ),
		) );

		$this->add_control( 'simple_icon_color', array(
			'label'     => esc_html__( 'Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple__icon .dashicons' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'simple_icon_bg', array(
			'label'     => esc_html__( 'Icon Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple__icon' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'simple_title_color', array(
			'label'     => esc_html__( 'Category Name Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple__name' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'simple_count_color', array(
			'label'     => esc_html__( 'Count Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple__count' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'simple_card_bg', array(
			'label'     => esc_html__( 'Card Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'simple_subcat_color', array(
			'label'     => esc_html__( 'Subcategory Link Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple__subcat-link' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'simple_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-cat-acc-simple' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'simple_shadow',
			'selector' => '{{WRAPPER}} .itr-kb-cat-acc-simple',
		) );

		$this->end_controls_section();

		// ── Style — Card ──────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_card', array(
			'label'     => esc_html__( 'Card Layout Style', 'itr-knowledgebase' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'card' ),
		) );

		$this->add_control( 'card_bg_color', array(
			'label'       => esc_html__( 'Card Background Color', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Used when no image is set or when expanded.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::COLOR,
			'default'     => '#26a69a',
			'selectors'   => array(
				'{{WRAPPER}} .itr-kb-cat-acc-card' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_title_color', array(
			'label'     => esc_html__( 'Title Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-card__title' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_pill_bg', array(
			'label'     => esc_html__( 'Pill Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => 'rgba(255,255,255,0.15)',
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-card__pill' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_pill_color', array(
			'label'     => esc_html__( 'Pill Text Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-cat-acc-card__pill' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'default'    => array( 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'unit' => 'px' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-cat-acc-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
			),
		) );

		$this->end_controls_section();
	}

	// =========================================================================
	// Render
	// =========================================================================

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$layout     = $settings['layout'];
		$source     = $settings['source'] ?? 'auto';
		$open_first = 'yes' === $settings['open_first'];
		$columns    = absint( $settings['columns'] ?? 3 );
		$is_editor  = \Elementor\Plugin::$instance->editor->is_edit_mode();

		// ── Resolve category list ────────────────────────────────────────────
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
			$this->render_simple( $categories, $settings, $open_first, $is_editor );
		} else {
			$this->render_card( $categories, $settings, $open_first, $columns, $is_editor );
		}
	}

	// =========================================================================
	// Layout 1 — Simple accordion
	// =========================================================================

	private function render_simple( $categories, $settings, $open_first, $is_editor ) {
		$show_count = 'yes' === $settings['show_count'];
		$show_icon  = 'yes' === $settings['show_icon'];
		?>
		<div class="itr-kb-cat-acc itr-kb-cat-acc--simple">
			<?php foreach ( $categories as $index => $cat ) : ?>
				<?php
				$is_open  = $is_editor || ( $open_first && 0 === $index );
				$icon     = ITR_KB_Category::get_icon( $cat->term_id );
				$img_url  = ITR_KB_Category::get_image_url( $cat->term_id, 'thumbnail' );
				$children = ITR_KB_Category_Order::get_ordered_categories( $cat->term_id );
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
								<?php if ( $img_url ) : ?>
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
						<?php else : ?>
							<div class="itr-kb-cat-acc-simple__no-sub">
								<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="itr-kb-cat-acc-simple__subcat-link">
									<?php esc_html_e( 'Browse all articles →', 'itr-knowledgebase' ); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>

				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	// =========================================================================
	// Layout 2 — Card with image accordion
	// =========================================================================

	private function render_card( $categories, $settings, $open_first, $columns, $is_editor ) {
		$image_height = absint( $settings['card_image_height'] ?? 160 );
		$grid_style   = $is_editor ? 'display:grid;grid-template-columns:repeat(' . $columns . ',1fr);gap:16px;' : '';
		?>
		<div class="itr-kb-cat-acc itr-kb-cat-acc--card itr-kb-cat-acc--cols-<?php echo esc_attr( $columns ); ?>" style="<?php echo esc_attr( $grid_style ); ?>">
			<?php foreach ( $categories as $index => $cat ) : ?>
				<?php
				$is_open    = $is_editor || ( $open_first && 0 === $index );
				$icon       = ITR_KB_Category::get_icon( $cat->term_id );
				$img_url    = ITR_KB_Category::get_image_url( $cat->term_id, 'large' );
				$children   = ITR_KB_Category_Order::get_ordered_categories( $cat->term_id );
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