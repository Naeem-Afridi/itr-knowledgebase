<?php
/**
 * Previous / Next article navigation.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/frontend
 */

namespace ITR_Knowledgebase\Frontend;

use ITR_Knowledgebase\Helpers\ITR_KB_Utils;
use ITR_Knowledgebase\Helpers\ITR_KB_Query;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Navigation
 *
 * Appends Previous / Next article navigation to KB article content.
 * Navigation stays within the same KB category when possible.
 */
class ITR_KB_Navigation {

	/**
	 * Inject prev/next navigation after article content.
	 * Disabled — the single article template handles this directly.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function inject_navigation( $content ) {
		return $content;
	}

	/**
	 * Build navigation HTML for a given post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function get_navigation_html( $post_id ) {
		$post_id = absint( $post_id );

		$prev = ITR_KB_Query::get_previous_article( $post_id );
		$next = ITR_KB_Query::get_next_article( $post_id );

		if ( ! $prev && ! $next ) {
			return '';
		}

		ob_start();
		?>
		<nav class="itr-kb-article-nav" aria-label="<?php esc_attr_e( 'Article Navigation', 'itr-knowledgebase' ); ?>">
	<div class="itr-kb-article-nav__inner">

		<!-- PREV -->
		<div class="itr-kb-article-nav__item itr-kb-article-nav__item--prev <?php echo ! $prev ? 'itr-kb-article-nav__item--empty' : ''; ?>">
			<?php if ( $prev ) : ?>
				<span class="itr-kb-article-nav__label">
					<span class="itr-kb-article-nav__arrow" aria-hidden="true">&#8592;</span>
					<?php esc_html_e( 'Previous', 'itr-knowledgebase' ); ?>
				</span>
				<a href="<?php echo esc_url( get_permalink( $prev->ID ) ); ?>" class="itr-kb-article-nav__link" rel="prev">
					<?php echo esc_html( get_the_title( $prev->ID ) ); ?>
				</a>
			<?php endif; ?>
		</div>

		<!-- NEXT -->
		<div class="itr-kb-article-nav__item itr-kb-article-nav__item--next <?php echo ! $next ? 'itr-kb-article-nav__item--empty' : ''; ?>">
			<?php if ( $next ) : ?>
				<span class="itr-kb-article-nav__label">
					<?php esc_html_e( 'Next', 'itr-knowledgebase' ); ?>
					<span class="itr-kb-article-nav__arrow" aria-hidden="true">&#8594;</span>
				</span>
				<a href="<?php echo esc_url( get_permalink( $next->ID ) ); ?>" class="itr-kb-article-nav__link" rel="next">
					<?php echo esc_html( get_the_title( $next->ID ) ); ?>
				</a>
			<?php endif; ?>
		</div>

	</div>
</nav>
		<?php
		return ob_get_clean();
	}
}