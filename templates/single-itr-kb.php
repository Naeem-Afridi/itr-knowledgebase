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

// If Elementor Pro Theme Builder has an active single template for this page,
// let it render instead of our custom layout.
if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'single' ) ) {
	get_footer();
	return;
}

while ( have_posts() ) :
	the_post();

	// Track view count.
	ITR_KB_Sections::track_view( get_the_ID() );

	$author_id    = ITR_KB_Utils::get_meta( get_the_ID(), '_itr_kb_author_id', 0 );
	$reviewer_ids = ITR_KB_Utils::get_meta( get_the_ID(), '_itr_kb_reviewer_ids', array() );
	$has_author_box = $author_id || ! empty( $reviewer_ids );
	$toc_disabled = ITR_KB_Utils::get_meta( get_the_ID(), '_itr_kb_toc_disabled', '' );
	$toc_disabled = ( '1' === (string) $toc_disabled );

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
	// Article layout: 'classic' (category sidebar) or 'modern' (ad banners column).
	$article_layout = get_option( 'itr_kb_article_layout', 'classic' );
	?>

	<div class="itr-kb-single-wrap itr-kb-single-wrap--<?php echo esc_attr( $article_layout ); ?>">
		<?php
		// Resolve TOC banner now so we can show the left column even when TOC has no headings.
		$_toc_banner = \ITR_Knowledgebase\Includes\ITR_KB_Banner::resolve( get_the_ID(), 'desktop_toc' );
		$show_toc_column = $show_toc || ! empty( $_toc_banner['image_url'] );
		?>
		<div class="itr-kb-single-inner <?php echo $show_toc_column ? 'itr-kb-single-inner--has-toc' : ''; ?>">

			<!-- LEFT: TOC column — shown when TOC has headings OR a TOC banner is set -->
			<?php if ( $show_toc_column ) : ?>
				<aside class="itr-kb-single-toc" aria-label="<?php esc_attr_e( 'Table of Contents', 'itr-knowledgebase' ); ?>">
					<div class="itr-kb-single-toc__sticky">
						<?php if ( $show_toc ) : // TOC list — only when article has headings ?>
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
						<?php endif; // end $show_toc ?>

						<?php if ( 'classic' === $article_layout ) :
						// Classic: TOC banner stays in the TOC column as before.
						\ITR_Knowledgebase\Includes\ITR_KB_Banner::render( get_the_ID(), 'desktop_toc' );
						endif; // Modern: TOC banner moves to right column, shown there instead.
						?>
					</div><!-- /.itr-kb-single-toc__sticky -->
				</aside>
			<?php endif; // end $show_toc_column ?>

			<!-- CENTER: Article -->
			<main class="itr-kb-single-main" id="itr-kb-main" role="main">

				<?php
				// Mobile Top Banner — above article content, mobile only.
				\ITR_Knowledgebase\Includes\ITR_KB_Banner::render( get_the_ID(), 'mobile_top' );
				?>
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

						<!-- Actions toolbar: Print, PDF, Share -->
						<?php
						$_article_url   = esc_url( get_permalink() );
						$_article_title = esc_attr( get_the_title() );
						?>
						<div class="itr-kb-single-article__actions">

							<?php if ( get_option( 'itr_kb_print_enabled', true ) ) : ?>
								<button class="itr-kb-single-article__action-btn itr-kb-single-article__print" onclick="itrKbPrintArticleButton()" aria-label="<?php esc_attr_e( 'Print this article', 'itr-knowledgebase' ); ?>">
									<span class="dashicons dashicons-printer" aria-hidden="true"></span>
									<?php esc_html_e( 'Print', 'itr-knowledgebase' ); ?>
								</button>
								<button class="itr-kb-single-article__action-btn itr-kb-single-article__download" onclick="itrKbDownloadPDF()" aria-label="<?php esc_attr_e( 'Download as PDF', 'itr-knowledgebase' ); ?>">
									<span class="dashicons dashicons-download" aria-hidden="true"></span>
									<?php esc_html_e( 'PDF', 'itr-knowledgebase' ); ?>
								</button>
							<?php endif; ?>

							<div class="itr-kb-share" data-url="<?php echo esc_attr( $_article_url ); ?>" data-title="<?php echo esc_attr( $_article_title ); ?>">
								<button class="itr-kb-single-article__action-btn itr-kb-share__toggle" aria-expanded="false" aria-controls="itr-kb-share-dropdown-<?php the_ID(); ?>">
									<span class="dashicons dashicons-share" aria-hidden="true"></span>
									<?php esc_html_e( 'Share', 'itr-knowledgebase' ); ?>
								</button>
								<div class="itr-kb-share__dropdown" id="itr-kb-share-dropdown-<?php the_ID(); ?>" hidden>
									<a class="itr-kb-share__option" href="#" data-platform="twitter" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg> Twitter / X</a>
									<a class="itr-kb-share__option" href="#" data-platform="facebook" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> Facebook</a>
									<a class="itr-kb-share__option" href="#" data-platform="linkedin" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg> LinkedIn</a>
									<a class="itr-kb-share__option" href="#" data-platform="whatsapp" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg> WhatsApp</a>
									<a class="itr-kb-share__option" href="#" data-platform="email"><span class="dashicons dashicons-email-alt" aria-hidden="true"></span> <?php esc_html_e( 'Email', 'itr-knowledgebase' ); ?></a>
									<button class="itr-kb-share__option itr-kb-share__copy" data-platform="copy"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span> <span class="itr-kb-share__copy-label"><?php esc_html_e( 'Copy Link', 'itr-knowledgebase' ); ?></span></button>
								</div>
							</div>

						</div><!-- .itr-kb-single-article__actions -->

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

					<!-- Mobile Bottom Banner — between article body and author box (mobile only) -->
					<?php \ITR_Knowledgebase\Includes\ITR_KB_Banner::render( get_the_ID(), 'mobile_bottom' ); ?>

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

			<?php if ( 'modern' === $article_layout ) : ?>
			<!-- RIGHT: Modern layout — sticky ad banners column (no category tree).
			     Both desktop_toc and desktop_categories banners stack here.
			     Category inheritance still applies — images come from category settings. -->
			<?php
			$_toc_banner_m = \ITR_Knowledgebase\Includes\ITR_KB_Banner::resolve( get_the_ID(), 'desktop_toc' );
			$_cat_banner_m = \ITR_Knowledgebase\Includes\ITR_KB_Banner::resolve( get_the_ID(), 'desktop_categories' );
			$_has_right_col = ! empty( $_toc_banner_m['image_url'] ) || ! empty( $_cat_banner_m['image_url'] );
			if ( $_has_right_col ) :
			?>
			<aside class="itr-kb-single-sidebar itr-kb-single-sidebar--modern">
				<div class="itr-kb-single-sidebar__sticky">
					<?php
					\ITR_Knowledgebase\Includes\ITR_KB_Banner::render( get_the_ID(), 'desktop_toc' );
					\ITR_Knowledgebase\Includes\ITR_KB_Banner::render( get_the_ID(), 'desktop_categories' );
					?>
				</div>
			</aside>
			<?php endif; ?>

		<?php else : ?>
			<!-- RIGHT: Classic layout — Category Sidebar + Categories Banner -->
			<aside class="itr-kb-single-sidebar">
				<div class="itr-kb-single-sidebar__header">
					<span class="itr-kb-single-sidebar__title"><?php esc_html_e( 'Categories', 'itr-knowledgebase' ); ?></span>
				</div>

				<?php
				$_sidebar_ancestors = $current_term_id
					? array_map( 'intval', get_ancestors( $current_term_id, 'itr_kb_category', 'taxonomy' ) )
					: array();

				$_render_sidebar = function( $terms, $active_term_id, $ancestor_ids ) use ( &$_render_sidebar ) {
					foreach ( $terms as $cat ) :
						$is_active    = (int) $active_term_id === (int) $cat->term_id;
						$is_ancestor  = in_array( (int) $cat->term_id, $ancestor_ids, true );
						$is_open      = $is_active || $is_ancestor;
						$has_children = ! empty( $cat->children );
						?>
						<li class="itr-kb-archive-sidebar__item">
							<div class="itr-kb-archive-sidebar__row">
								<a
									href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
									class="itr-kb-archive-sidebar__link<?php echo $is_open ? ' itr-kb-archive-sidebar__link--active' : ''; ?>"
									<?php echo $is_active ? 'aria-current="page"' : ''; ?>
								>
									<?php echo esc_html( $cat->name ); ?>
								</a>
								<?php if ( $has_children ) : ?>
									<button
										class="itr-kb-archive-sidebar__toggle"
										aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
										aria-label="<?php echo esc_attr( sprintf( __( 'Toggle %s submenu', 'itr-knowledgebase' ), $cat->name ) ); ?>"
									>
										<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
									</button>
								<?php endif; ?>
							</div>

							<?php if ( $has_children ) : ?>
								<ul class="itr-kb-archive-sidebar__sublist" <?php echo ! $is_open ? 'hidden' : ''; ?>>
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
				<?php
				\ITR_Knowledgebase\Includes\ITR_KB_Banner::render( get_the_ID(), 'desktop_categories' );
				?>
			</aside>
		<?php endif; ?>

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