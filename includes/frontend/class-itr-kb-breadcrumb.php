<?php
/**
 * Breadcrumb navigation.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/frontend
 */

namespace ITR_Knowledgebase\Frontend;

use ITR_Knowledgebase\Helpers\ITR_KB_Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Breadcrumb
 *
 * Generates breadcrumb navigation for KB pages.
 *
 * Structure:
 * Home → Knowledge Base → [Parent Category] → [Category] → [Article]
 */
class ITR_KB_Breadcrumb {

	/**
	 * Breadcrumb separator.
	 *
	 * @var string
	 */
	private $separator = '›';

	/**
	 * Get breadcrumb HTML.
	 *
	 * @return string
	 */
	public function get_breadcrumb() {
		if ( ! get_option( 'itr_kb_breadcrumb_enabled', true ) ) {
			return '';
		}

		if ( ! ITR_KB_Utils::is_kb_page() ) {
			return '';
		}

		$crumbs = $this->build_crumbs();

		if ( empty( $crumbs ) ) {
			return '';
		}

		return $this->render( $crumbs );
	}

	/**
	 * Build the array of breadcrumb items.
	 *
	 * @return array Array of [ label, url ] pairs. Last item has no url.
	 */
	private function build_crumbs() {
		$crumbs = array();

		// Home.
		$crumbs[] = array(
			'label' => esc_html__( 'Home', 'itr-knowledgebase' ),
			'url'   => home_url( '/' ),
		);

		// KB archive link.
		$kb_archive_url = get_post_type_archive_link( 'itr_kb_article' );
		if ( $kb_archive_url ) {
			$crumbs[] = array(
				'label' => esc_html__( 'Knowledge Base', 'itr-knowledgebase' ),
				'url'   => $kb_archive_url,
			);
		}

		if ( is_singular( 'itr_kb_article' ) ) {
			$crumbs = array_merge( $crumbs, $this->get_article_crumbs() );
		} elseif ( is_tax( 'itr_kb_category' ) ) {
			$crumbs = array_merge( $crumbs, $this->get_category_crumbs() );
		} elseif ( is_tax( 'itr_kb_tag' ) ) {
			$term     = get_queried_object();
			$crumbs[] = array(
				'label' => sprintf(
					/* translators: %s: tag name */
					esc_html__( 'Tag: %s', 'itr-knowledgebase' ),
					esc_html( $term->name )
				),
				'url'   => '',
			);
		} elseif ( is_post_type_archive( 'itr_kb_article' ) ) {
			// Remove the URL from the last KB crumb to mark it as current.
			$last_index = count( $crumbs ) - 1;
			$crumbs[ $last_index ]['url'] = '';
		}

		return $crumbs;
	}

	/**
	 * Get breadcrumb items for a single KB article.
	 *
	 * When the "Category in Article URL" option is ON and the request URL
	 * contains a category path, the breadcrumb reflects the category the
	 * user navigated through rather than the auto-detected primary category.
	 *
	 * @return array
	 */
	private function get_article_crumbs() {
		global $post;
		$crumbs = array();

		// Attempt to detect category context from the URL first.
		$url_term_id = ITR_KB_Utils::get_term_id_from_url( $post->ID );

		if ( $url_term_id ) {
			// Use the category the user came from.
			$url_term  = get_term( $url_term_id, 'itr_kb_category' );
			$ancestors = $this->get_term_ancestors( $url_term );

			foreach ( $ancestors as $ancestor ) {
				$crumbs[] = array(
					'label' => esc_html( $ancestor->name ),
					'url'   => esc_url( get_term_link( $ancestor ) ),
				);
			}

			// Add the term itself (with link, not the current page).
			$crumbs[] = array(
				'label' => esc_html( $url_term->name ),
				'url'   => esc_url( get_term_link( $url_term ) ),
			);
		} else {
			// Default: use the article's primary assigned category.
			$categories = wp_get_post_terms( $post->ID, 'itr_kb_category', array( 'orderby' => 'parent' ) );

			if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
				$primary_cat = $categories[0];
				$ancestors   = $this->get_term_ancestors( $primary_cat );

				foreach ( $ancestors as $ancestor ) {
					$crumbs[] = array(
						'label' => esc_html( $ancestor->name ),
						'url'   => esc_url( get_term_link( $ancestor ) ),
					);
				}
			}
		}

		// Current article (no URL — it's the current page).
		$crumbs[] = array(
			'label' => esc_html( get_the_title( $post->ID ) ),
			'url'   => '',
		);

		return $crumbs;
	}

	/**
	 * Get breadcrumb items for a KB category archive page.
	 *
	 * @return array
	 */
	private function get_category_crumbs() {
		$term   = get_queried_object();
		$crumbs = array();

		// Add ancestor categories.
		$ancestors = $this->get_term_ancestors( $term );
		foreach ( $ancestors as $ancestor ) {
			$crumbs[] = array(
				'label' => esc_html( $ancestor->name ),
				'url'   => esc_url( get_term_link( $ancestor ) ),
			);
		}

		// Current category (no URL).
		$crumbs[] = array(
			'label' => esc_html( $term->name ),
			'url'   => '',
		);

		return $crumbs;
	}

	/**
	 * Get ordered ancestor terms for a given term (excluding the term itself).
	 *
	 * @param \WP_Term $term The term object.
	 * @return array Ordered from root to direct parent.
	 */
	private function get_term_ancestors( $term ) {
		$ancestors = array();

		if ( ! $term->parent ) {
			return $ancestors;
		}

		$ancestor_ids = get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' );
		$ancestor_ids = array_reverse( $ancestor_ids );

		foreach ( $ancestor_ids as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, $term->taxonomy );
			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$ancestors[] = $ancestor;
			}
		}

		return $ancestors;
	}

	/**
	 * Render breadcrumb HTML from crumbs array.
	 *
	 * @param array $crumbs Breadcrumb items.
	 * @return string
	 */
	private function render( $crumbs ) {
		$separator = '<span class="itr-kb-breadcrumb__separator" aria-hidden="true">' . esc_html( $this->separator ) . '</span>';
		$total     = count( $crumbs );

		ob_start();
		?>
		<nav class="itr-kb-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'itr-knowledgebase' ); ?>">
			<ol class="itr-kb-breadcrumb__list" itemscope itemtype="https://schema.org/BreadcrumbList">
				<?php foreach ( $crumbs as $index => $crumb ) : ?>
					<?php
					$is_last     = ( $index === $total - 1 );
					$item_pos    = $index + 1;
					$aria_current = $is_last ? ' aria-current="page"' : '';
					?>
					<li
						class="itr-kb-breadcrumb__item"
						itemprop="itemListElement"
						itemscope
						itemtype="https://schema.org/ListItem"
					>
						<?php if ( ! $is_last && ! empty( $crumb['url'] ) ) : ?>
							<a
								href="<?php echo esc_url( $crumb['url'] ); ?>"
								class="itr-kb-breadcrumb__link"
								itemprop="item"
							>
								<span itemprop="name"><?php echo esc_html( $crumb['label'] ); ?></span>
							</a>
						<?php else : ?>
							<span
								class="itr-kb-breadcrumb__current"
								itemprop="name"
								<?php echo $aria_current; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							>
								<?php echo esc_html( $crumb['label'] ); ?>
							</span>
						<?php endif; ?>

						<meta itemprop="position" content="<?php echo esc_attr( $item_pos ); ?>" />

						<?php if ( ! $is_last ) : ?>
							<?php echo $separator; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * Static helper to output breadcrumb directly.
	 *
	 * @return void
	 */
	public static function render_breadcrumb() {
		$instance = new self();
		echo $instance->get_breadcrumb(); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}