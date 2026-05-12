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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Widget_Category_Grid
 */
class ITR_KB_Widget_Category_Grid extends Widget_Base {

	public function get_name() { return 'itr-kb-category-grid'; }
	public function get_title() { return esc_html__( 'KB Category Grid', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'category', 'grid', 'kb' ); }

	protected function register_controls() {
		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Category Grid', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		));

		$this->add_control( 'parent_id', array(
			'label'       => esc_html__( 'Parent Category ID', 'itr-knowledgebase' ),
			'description' => esc_html__( 'Leave 0 to show top-level categories.', 'itr-knowledgebase' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
		));

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
		));

		$this->add_control( 'show_count', array(
			'label'        => esc_html__( 'Show Article Count', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_children', array(
			'label'        => esc_html__( 'Show Sub-categories', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		));

		$this->add_control( 'show_description', array(
			'label'        => esc_html__( 'Show Description', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		));

		$this->end_controls_section();

		// Style.
		$this->start_controls_section( 'section_style', array(
			'label' => esc_html__( 'Card Style', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		));

		$this->add_control( 'card_bg', array(
			'label'     => esc_html__( 'Card Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-grid__item' => 'background-color: {{VALUE}};',
			),
		));

		$this->add_control( 'card_hover_bg', array(
			'label'     => esc_html__( 'Card Hover Background', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-grid__item:hover' => 'background-color: {{VALUE}};',
			),
		));

		$this->add_control( 'title_color', array(
			'label'     => esc_html__( 'Title Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-grid__title' => 'color: {{VALUE}};',
			),
		));

		$this->add_control( 'icon_color', array(
			'label'     => esc_html__( 'Icon Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-category-grid__icon .dashicons' => 'color: {{VALUE}};',
			),
		));

		$this->add_responsive_control( 'card_padding', array(
			'label'      => esc_html__( 'Card Padding', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-category-grid__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		));

		$this->add_control( 'card_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-category-grid__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		));

		$this->add_control( 'layout', [
	'label'   => 'Layout',
	'type'    => \Elementor\Controls_Manager::SELECT,
	'default' => 'grid',
	'options' => [
		'grid' => 'Default Grid',
		'card' => 'Subcategory Card',
	],
] );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'label'    => esc_html__( 'Card Shadow', 'itr-knowledgebase' ),
			'selector' => '{{WRAPPER}} .itr-kb-category-grid__item',
		));

		$this->end_controls_section();
	}

	protected function render() {
	$settings = $this->get_settings_for_display();

	// Layout control (default fallback)
	$layout = $settings['layout'] ?? 'grid';

	// Default parent (from Elementor control if exists)
	$parent_id = isset( $settings['parent_id'] ) ? absint( $settings['parent_id'] ) : 0;

	// ✅ Detect current category (THIS FIXES YOUR ISSUE)
	$current_term = get_queried_object();

	if ( $current_term && isset( $current_term->term_id ) && isset( $current_term->taxonomy ) ) {
		$parent_id = $current_term->term_id;
	}

	// Get subcategories of current category
	$categories = get_terms( [
		'taxonomy'   => $current_term->taxonomy ?? 'itr_kb_category', // replace if needed
		'parent'     => $parent_id,
		'hide_empty' => false,
	] );

	if ( empty( $categories ) || is_wp_error( $categories ) ) {

		// No subcategories — show articles that belong directly to this category.
		if ( ! $current_term || ! isset( $current_term->term_id ) ) {
			echo '<p>' . esc_html__( 'No subcategories found.', 'itr-knowledgebase' ) . '</p>';
			return;
		}

		$articles_per_page = absint( get_option( 'itr_kb_articles_per_page', 10 ) );

		$article_query = new \WP_Query( array(
			'post_type'      => 'itr_kb_article',
			'post_status'    => 'publish',
			'posts_per_page' => $articles_per_page,
			'paged'          => get_query_var( 'paged' ) ?: 1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'itr_kb_category',
					'field'    => 'term_id',
					'terms'    => $current_term->term_id,
				),
			),
		) );

		if ( ! $article_query->have_posts() ) {
			echo '<p>' . esc_html__( 'No articles found in this category.', 'itr-knowledgebase' ) . '</p>';
			return;
		}
		?>
		<div class="itr-kb-article-list" id="itr-kb-article-list">
			<?php while ( $article_query->have_posts() ) : $article_query->the_post(); ?>
				<article class="itr-kb-article-list__item">
					<h3 class="itr-kb-article-list__title">
						<a href="<?php echo esc_url( \ITR_Knowledgebase\Helpers\ITR_KB_Utils::get_contextual_article_url( get_the_ID(), isset( $current_term->term_id ) ? $current_term->term_id : 0 ) ); ?>" class="itr-kb-article-list__link"><?php the_title(); ?></a>
					</h3>
					<?php if ( has_excerpt() ) : ?>
						<div class="itr-kb-article-list__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></div>
					<?php endif; ?>
					<div class="itr-kb-article-list__meta">
						<time class="itr-kb-article-list__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
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
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
				</button>
				<span class="itr-kb-load-more-status"></span>
			</div>
		<?php endif; ?>
		<?php
		return;
	}

	// =========================
	// 🔹 LAYOUT: DEFAULT GRID
	// =========================
	if ( $layout === 'grid' ) {

		// echo '<div class="itr-kb-category-grid">';

		$columns = isset( $settings['columns'] ) ? intval( $settings['columns'] ) : 4;

echo '<div class="itr-kb-category-grid itr-kb-category-grid--cols-' . esc_attr( $columns ) . '">';

		foreach ( $categories as $cat ) {
			$link = get_term_link( $cat );

			echo '<div class="itr-kb-category-grid__item">';
			echo '<a href="' . esc_url( $link ) . '" class="itr-kb-category-grid__link">';

			echo '<div class="itr-kb-category-grid__icon">';
			echo '<span class="dashicons dashicons-category"></span>';
			echo '</div>';

			echo '<h3 class="itr-kb-category-grid__title">' . esc_html( $cat->name ) . '</h3>';

			echo '<span class="itr-kb-category-grid__count">' . absint( $cat->count ) . ' Topics</span>';

			echo '</a>';
			echo '</div>';
		}

		echo '</div>';
	}

	// =========================
	// 🔹 LAYOUT: CARD (NEW)
	// =========================
	elseif ( $layout === 'card' ) {

		echo '<div class="itr-kb-subcat-grid">';

		foreach ( $categories as $cat ) {
			$link = get_term_link( $cat );
			?>

			<a href="<?php echo esc_url( $link ); ?>" class="itr-kb-subcat-card">

				<div class="itr-kb-subcat-card__icon">
					<span class="dashicons dashicons-category"></span>
				</div>

				<div class="itr-kb-subcat-card__content">
					<h3 class="itr-kb-subcat-card__title">
						<?php echo esc_html( $cat->name ); ?>
					</h3>

					<span class="itr-kb-subcat-card__count">
						<?php echo absint( $cat->count ); ?> Topics
					</span>
				</div>

			</a>

			<?php
		}

		echo '</div>';
	}
}
	
}