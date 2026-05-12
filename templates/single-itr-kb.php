<?php
/**
 * Template: Single KB Article
 *
 * Layout: Left (Categories) | Center (Article) | Right (TOC)
 *
 * To override: copy to your-theme/itr-knowledgebase/single-itr-kb.php
 *
 * @package ITR_Knowledgebase
 */

use ITR_Knowledgebase\Frontend\ITR_KB_Sections;
use ITR_Knowledgebase\Frontend\ITR_KB_TOC;
use ITR_Knowledgebase\Admin\ITR_KB_Category_Order;
use ITR_Knowledgebase\Helpers\ITR_KB_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	// Track view count.
	ITR_KB_Sections::track_view( get_the_ID() );

	$author_id    = ITR_KB_Utils::get_meta( get_the_ID(), '_itr_kb_author_id', 0 );
	$reviewer_ids = ITR_KB_Utils::get_meta( get_the_ID(), '_itr_kb_reviewer_ids', array() );
	$has_author_box = $author_id || ! empty( $reviewer_ids );
	$toc_disabled = ITR_KB_Utils::get_meta( get_the_ID(), '_itr_kb_toc_disabled', '0' );

	// Build TOC.
	$toc_instance = new \ITR_Knowledgebase\Frontend\ITR_KB_TOC();
	$headings     = $toc_instance->parse_headings( get_the_content() );
	$show_toc     = ! $toc_disabled && get_option( 'itr_kb_toc_enabled', true ) && count( $headings ) >= 2;

	// Add anchor IDs to headings so right sidebar TOC links work.
	add_filter( 'the_content', function( $content ) use ( $toc_instance, $headings, $show_toc ) {
		if ( $show_toc && ! empty( $headings ) ) {
			$content = $toc_instance->add_heading_anchors_public( $content, $headings );
		}
		return $content;
	}, 5 );

	// Category tree for left sidebar.
	$category_tree   = ITR_KB_Category_Order::get_category_tree( 0 );
	$current_term_id = 0;
	$terms = wp_get_post_terms( get_the_ID(), 'itr_kb_category', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$current_term_id = $terms[0];
	}

	// Breadcrumb data.
	$kb_archive   = get_post_type_archive_link( 'itr_kb_article' );
	$breadcrumbs  = array();
	$article_cats = wp_get_post_terms( get_the_ID(), 'itr_kb_category', array( 'orderby' => 'parent' ) );
	if ( ! is_wp_error( $article_cats ) && ! empty( $article_cats ) ) {
		$primary_cat  = $article_cats[0];
		$ancestor_ids = array_reverse( get_ancestors( $primary_cat->term_id, 'itr_kb_category', 'taxonomy' ) );
		foreach ( $ancestor_ids as $anc_id ) {
			$anc = get_term( $anc_id, 'itr_kb_category' );
			if ( $anc && ! is_wp_error( $anc ) ) {
				$breadcrumbs[] = array( 'label' => $anc->name, 'url' => get_term_link( $anc ) );
			}
		}
		$breadcrumbs[] = array( 'label' => $primary_cat->name, 'url' => get_term_link( $primary_cat ) );
	}
	?>

	<div class="itr-kb-single-wrap">
		<div class="itr-kb-single-inner <?php echo $show_toc ? 'itr-kb-single-inner--has-toc' : ''; ?>">

			<!-- LEFT: Table of Contents (only when TOC is enabled) -->
			<?php if ( $show_toc ) : ?>
				<aside class="itr-kb-single-toc" aria-label="<?php esc_attr_e( 'Table of Contents', 'itr-knowledgebase' ); ?>">
					<div class="itr-kb-single-toc__sticky">
						<div class="itr-kb-toc" id="itr-kb-toc" role="navigation">
							<div class="itr-kb-toc__header">
								<span class="itr-kb-toc__title"><?php esc_html_e( 'Table of Contents', 'itr-knowledgebase' ); ?></span>
								<button
									class="itr-kb-toc__toggle"
									aria-expanded="true"
									aria-controls="itr-kb-toc-list"
									aria-label="<?php esc_attr_e( 'Toggle Table of Contents', 'itr-knowledgebase' ); ?>"
								>
									<span class="itr-kb-toc__toggle-icon" aria-hidden="true">&#9660;</span>
								</button>
							</div>
							<ol class="itr-kb-toc__list" id="itr-kb-toc-list">
								<?php
								$prev_level = 2;
								$depth      = 0;
								foreach ( $headings as $heading ) :
									$level = (int) substr( $heading['tag'], 1 );
									if ( $level > $prev_level ) { echo '<ol class="itr-kb-toc__sublist">'; $depth++; }
									elseif ( $level < $prev_level && $depth > 0 ) { echo '</ol>'; $depth--; }
									printf(
										'<li class="itr-kb-toc__item itr-kb-toc__item--%s"><a href="#%s" class="itr-kb-toc__link">%s</a></li>',
										esc_attr( $heading['tag'] ),
										esc_attr( $heading['id'] ),
										esc_html( $heading['text'] )
									);
									$prev_level = $level;
								endforeach;
								for ( $i = 0; $i < $depth; $i++ ) echo '</ol>';
								?>
							</ol>
						</div>
					</div>
				</aside>
			<?php endif; ?>

			<!-- CENTER: Article -->
			<main class="itr-kb-single-main" id="itr-kb-main" role="main">

				<!-- Breadcrumb -->
				<div class="itr-kb-single-breadcrumb">
					<a href="<?php echo esc_url( $kb_archive ); ?>" class="itr-kb-single-breadcrumb__all">
						&#8592; <?php esc_html_e( 'All Topics', 'itr-knowledgebase' ); ?>
					</a>
					<nav class="itr-kb-single-breadcrumb__trail" aria-label="<?php esc_attr_e( 'Breadcrumb', 'itr-knowledgebase' ); ?>">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Main', 'itr-knowledgebase' ); ?></a>
						<?php foreach ( $breadcrumbs as $crumb ) : ?>
							<span aria-hidden="true"> › </span>
							<?php if ( ! empty( $crumb['url'] ) ) : ?>
								<a href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['label'] ); ?></a>
							<?php else : ?>
								<span><?php echo esc_html( $crumb['label'] ); ?></span>
							<?php endif; ?>
						<?php endforeach; ?>
						<span aria-hidden="true"> › </span>
						<span><?php echo esc_html( wp_trim_words( get_the_title(), 8 ) ); ?></span>
					</nav>
				</div>

				<article id="itr-kb-article-<?php the_ID(); ?>" class="itr-kb-single-article" itemscope itemtype="https://schema.org/Article">

					<header class="itr-kb-single-article__header">

						<?php if ( get_option( 'itr_kb_print_enabled', true ) ) : ?>
							<button
								class="itr-kb-single-article__print"
								onclick="itrKbPrintArticle()"
								aria-label="<?php esc_attr_e( 'Print this article', 'itr-knowledgebase' ); ?>"
							>
								<?php esc_html_e( 'Print', 'itr-knowledgebase' ); ?>
								<span class="dashicons dashicons-printer" aria-hidden="true"></span>
							</button>
						<?php endif; ?>

						<h1 class="itr-kb-single-article__title" itemprop="headline"><?php the_title(); ?></h1>

						<div class="itr-kb-single-article__meta">
							<span class="itr-kb-single-article__meta-item">
								<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
								<?php printf( esc_html__( 'Posted %s', 'itr-knowledgebase' ), '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '" itemprop="datePublished">' . esc_html( get_the_date() ) . '</time>' ); ?>
							</span>
							<span class="itr-kb-single-article__meta-item">
								<span class="dashicons dashicons-update" aria-hidden="true"></span>
								<?php printf( esc_html__( 'Updated %s', 'itr-knowledgebase' ), '<time datetime="' . esc_attr( get_the_modified_date( 'c' ) ) . '" itemprop="dateModified">' . esc_html( get_the_modified_date() ) . '</time>' ); ?>
							</span>
							<?php if ( $author_id ) :
								$_meta_author = get_post( absint( $author_id ) );
								if ( $_meta_author && 'itr_kb_author' === $_meta_author->post_type && 'publish' === $_meta_author->post_status ) : ?>
							<span class="itr-kb-single-article__meta-item">
								<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
								<?php printf(
									/* translators: %s: author name */
									esc_html__( 'Author: %s', 'itr-knowledgebase' ),
									'<strong itemprop="author">' . esc_html( $_meta_author->post_title ) . '</strong>'
								); ?>
							</span>
							<?php endif; endif; ?>
							<?php if ( ! empty( $reviewer_ids ) ) :
								$_meta_reviewer_names = array();
								foreach ( (array) $reviewer_ids as $_rid ) {
									$_rp = get_post( absint( $_rid ) );
									if ( $_rp && 'itr_kb_author' === $_rp->post_type && 'publish' === $_rp->post_status ) {
										$_meta_reviewer_names[] = esc_html( $_rp->post_title );
									}
								}
								if ( ! empty( $_meta_reviewer_names ) ) : ?>
							<span class="itr-kb-single-article__meta-item">
								<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
								<?php printf(
									/* translators: %s: reviewer name(s) */
									esc_html__( 'Reviewed by: %s', 'itr-knowledgebase' ),
									'<strong>' . implode( ', ', $_meta_reviewer_names ) . '</strong>'
								); ?>
							</span>
							<?php endif; endif; ?>
						</div>

					</header>

					<!-- Article Body (TOC injected here via filter) -->
					<div class="itr-kb-single-article__body" itemprop="articleBody">
						<?php the_content(); ?>
					</div>

					<!-- Tags -->
					<?php
					$tags = wp_get_post_terms( get_the_ID(), 'itr_kb_tag' );
					if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) :
					?>
					<div class="itr-kb-single-article__tags">
						<span class="itr-kb-single-article__tags-label"><?php esc_html_e( 'Tags:', 'itr-knowledgebase' ); ?></span>
						<?php foreach ( $tags as $tag ) : ?>
							<a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="itr-kb-single-article__tag"><?php echo esc_html( $tag->name ); ?></a>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

					<!-- Author & Reviewer Box -->
					<?php if ( $has_author_box ) : ?>
						<?php ITR_KB_Utils::load_template( 'partials/itr-kb-author-box.php', array( 'author_id' => $author_id, 'reviewer_ids' => is_array( $reviewer_ids ) ? $reviewer_ids : array() ) ); ?>
					<?php endif; ?>

					<!-- Prev / Next Navigation -->
					<?php
					$nav = new \ITR_Knowledgebase\Frontend\ITR_KB_Navigation();
					echo $nav->get_navigation_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>

				</article>

			</main>

			<!-- RIGHT: Category Sidebar (collapsible, all closed by default except active path) -->
			<aside class="itr-kb-single-sidebar">
				<div class="itr-kb-single-sidebar__header">
					<span class="itr-kb-single-sidebar__title"><?php esc_html_e( 'Categories', 'itr-knowledgebase' ); ?></span>
				</div>

				<?php
				// Collect all ancestor IDs of the current article's category so we
				// can keep that branch open while everything else starts closed.
				$_sidebar_ancestors = $current_term_id
					? array_map( 'intval', get_ancestors( $current_term_id, 'itr_kb_category', 'taxonomy' ) )
					: array();

				/**
				 * Recursively render collapsible sidebar items.
				 * Uses itr-kb-archive-sidebar__* classes so the existing JS toggle
				 * handler in itr-kb-frontend.js works without any extra code.
				 *
				 * @param array $terms            Term objects (each may have ->children).
				 * @param int   $active_term_id   Currently active term ID.
				 * @param array $ancestor_ids     Ancestor IDs of active term.
				 */
				$_render_sidebar = function( $terms, $active_term_id, $ancestor_ids ) use ( &$_render_sidebar ) {
					foreach ( $terms as $cat ) :
						$is_active    = (int) $active_term_id === (int) $cat->term_id;
						$is_ancestor  = in_array( (int) $cat->term_id, $ancestor_ids, true );
						$has_children = ! empty( $cat->children );
						?>
						<li class="itr-kb-archive-sidebar__item">
							<div class="itr-kb-archive-sidebar__row">
								<a
									href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
									class="itr-kb-archive-sidebar__link<?php echo $is_active ? ' itr-kb-archive-sidebar__link--active' : ''; ?>"
									<?php echo $is_active ? 'aria-current="page"' : ''; ?>
								>
									<?php echo esc_html( $cat->name ); ?>
								</a>
								<?php if ( $has_children ) : ?>
									<button
										class="itr-kb-archive-sidebar__toggle"
										aria-expanded="false"
										aria-label="<?php echo esc_attr( sprintf( __( 'Toggle %s submenu', 'itr-knowledgebase' ), $cat->name ) ); ?>"
									>
										<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
									</button>
								<?php endif; ?>
							</div>

							<?php if ( $has_children ) : ?>
								<ul class="itr-kb-archive-sidebar__sublist" hidden>
									<?php $_render_sidebar( $cat->children, $active_term_id, $ancestor_ids ); ?>
								</ul>
							<?php endif; ?>
						</li>
					<?php endforeach;
				};
				?>

				<ul class="itr-kb-single-sidebar__list itr-kb-archive-sidebar__list">
					<?php $_render_sidebar( $category_tree, $current_term_id, $_sidebar_ancestors ); ?>
				</ul>
			</aside>

		</div><!-- .itr-kb-single-inner -->
	</div><!-- .itr-kb-single-wrap -->

	<!-- Back to Top -->
	<?php if ( get_option( 'itr_kb_back_to_top_enabled', true ) ) : ?>
		<button id="itr-kb-back-to-top" class="itr-kb-back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'itr-knowledgebase' ); ?>" hidden>
			<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
		</button>
	<?php endif; ?>

<?php endwhile; ?>
<?php get_footer(); ?>