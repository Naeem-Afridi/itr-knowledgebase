<?php
/**
 * Elementor Widget: KB Breadcrumb
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/elementor/widgets
 */

namespace ITR_Knowledgebase\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Widget_Breadcrumb
 */
class ITR_KB_Widget_Breadcrumb extends Widget_Base {

	public function get_name() { return 'itr-kb-breadcrumb'; }
	public function get_title() { return esc_html__( 'KB Breadcrumb', 'itr-knowledgebase' ); }
	public function get_icon() { return 'eicon-post-navigation'; }
	public function get_categories() { return array( 'itr-knowledgebase' ); }
	public function get_keywords() { return array( 'breadcrumb', 'navigation', 'trail', 'kb', 'knowledgebase' ); }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', array(
			'label' => esc_html__( 'Breadcrumb', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'home_label', array(
			'label'   => esc_html__( 'Home Label', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Home', 'itr-knowledgebase' ),
		) );

		$this->add_control( 'show_all_topics', array(
			'label'        => esc_html__( 'Show "All Topics" Button', 'itr-knowledgebase' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'all_topics_label', array(
			'label'     => esc_html__( '"All Topics" Label', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => esc_html__( 'All Topics', 'itr-knowledgebase' ),
			'condition' => array( 'show_all_topics' => 'yes' ),
		) );

		$this->add_control( 'separator', array(
			'label'   => esc_html__( 'Separator', 'itr-knowledgebase' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '›',
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'section_style', array(
			'label' => esc_html__( 'Style', 'itr-knowledgebase' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'breadcrumb_typography',
			'selector' => '{{WRAPPER}} .itr-kb-single-breadcrumb',
		) );

		$this->add_control( 'link_color', array(
			'label'     => esc_html__( 'Link Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-single-breadcrumb__trail a'   => 'color: {{VALUE}} !important;',
				'{{WRAPPER}} .itr-kb-single-breadcrumb__all'       => 'color: {{VALUE}} !important;',
			),
		) );

		$this->add_control( 'current_color', array(
			'label'     => esc_html__( 'Current Page Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-single-breadcrumb__trail span:not([aria-hidden="true"])' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'separator_color', array(
			'label'     => esc_html__( 'Separator Color', 'itr-knowledgebase' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .itr-kb-single-breadcrumb__trail span[aria-hidden="true"]' => 'color: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'gap', array(
			'label'      => esc_html__( 'Gap Between Items', 'itr-knowledgebase' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .itr-kb-single-breadcrumb__trail' => 'gap: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings   = $this->get_settings_for_display();
		$post_id    = get_the_ID();
		$is_edit    = \Elementor\Plugin::$instance->editor->is_edit_mode();
		$kb_archive = get_post_type_archive_link( 'itr_kb_article' );
		$home_label = sanitize_text_field( $settings['home_label'] ) ?: __( 'Home', 'itr-knowledgebase' );
		$separator  = esc_html( $settings['separator'] ?: '›' );

		$items = $this->build_breadcrumb_items( $post_id, $is_edit );

		if ( empty( $items ) && ! $is_edit ) {
			return;
		}
		?>
		<div class="itr-kb-single-breadcrumb">

			<?php if ( 'yes' === $settings['show_all_topics'] && $kb_archive ) : ?>
				<a href="<?php echo esc_url( $kb_archive ); ?>" class="itr-kb-single-breadcrumb__all">
					&#8592; <?php echo esc_html( sanitize_text_field( $settings['all_topics_label'] ) ?: __( 'All Topics', 'itr-knowledgebase' ) ); ?>
				</a>
			<?php endif; ?>

			<nav class="itr-kb-single-breadcrumb__trail" aria-label="<?php esc_attr_e( 'Breadcrumb', 'itr-knowledgebase' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $home_label ); ?></a>
				<?php foreach ( $items as $item ) : ?>
					<span aria-hidden="true"><?php echo ' ' . $separator . ' '; ?></span>
					<?php if ( ! empty( $item['url'] ) ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $item['label'] ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>

		</div>
		<?php
	}

	/**
	 * Build breadcrumb items based on current page context.
	 *
	 * @param int  $post_id Current post ID.
	 * @param bool $is_edit Whether in Elementor editor.
	 * @return array
	 */
	private function build_breadcrumb_items( $post_id, $is_edit ) {
		$items = array();

		if ( is_singular( 'itr_kb_article' ) ) {
			$article_cats = wp_get_post_terms( $post_id, 'itr_kb_category', array( 'orderby' => 'parent' ) );
			if ( ! is_wp_error( $article_cats ) && ! empty( $article_cats ) ) {
				$primary_cat  = $article_cats[0];
				$ancestor_ids = array_reverse( get_ancestors( $primary_cat->term_id, 'itr_kb_category', 'taxonomy' ) );
				foreach ( $ancestor_ids as $anc_id ) {
					$anc = get_term( $anc_id, 'itr_kb_category' );
					if ( $anc && ! is_wp_error( $anc ) ) {
						$items[] = array( 'label' => $anc->name, 'url' => get_term_link( $anc ) );
					}
				}
				$items[] = array( 'label' => $primary_cat->name, 'url' => get_term_link( $primary_cat ) );
			}
			$items[] = array( 'label' => get_the_title( $post_id ), 'url' => '' );

		} elseif ( is_tax( 'itr_kb_category' ) ) {
			$term = get_queried_object();
			if ( $term ) {
				$ancestor_ids = array_reverse( get_ancestors( $term->term_id, 'itr_kb_category', 'taxonomy' ) );
				foreach ( $ancestor_ids as $anc_id ) {
					$anc = get_term( $anc_id, 'itr_kb_category' );
					if ( $anc && ! is_wp_error( $anc ) ) {
						$items[] = array( 'label' => $anc->name, 'url' => get_term_link( $anc ) );
					}
				}
				$items[] = array( 'label' => $term->name, 'url' => '' );
			}

		} elseif ( is_tax( 'itr_kb_tag' ) ) {
			$term    = get_queried_object();
			$items[] = array( 'label' => __( 'Tags', 'itr-knowledgebase' ), 'url' => '' );
			if ( $term ) {
				$items[] = array( 'label' => $term->name, 'url' => '' );
			}

		} elseif ( is_post_type_archive( 'itr_kb_article' ) ) {
			$items[] = array( 'label' => __( 'Knowledge Base', 'itr-knowledgebase' ), 'url' => '' );

		} elseif ( $is_edit ) {
			$items[] = array( 'label' => __( 'Category Name', 'itr-knowledgebase' ), 'url' => '#' );
			$items[] = array( 'label' => __( 'Article Title', 'itr-knowledgebase' ),  'url' => '' );
		}

		return $items;
	}
}
