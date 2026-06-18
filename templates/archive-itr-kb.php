<?php
/**
 * Template: KB Archive / Category Page
 *
 * Main Archive  → Banner + Sidebar + Category sections (subcats only, no articles)
 * Category Page → Sidebar + Subcategory cards with articles inside
 * Tag Page      → Sidebar + Flat article list
 *
 * @package ITR_Knowledgebase
 */

use ITR_Knowledgebase\Admin\ITR_KB_Category_Order;
use ITR_Knowledgebase\Taxonomies\ITR_KB_Category;
use ITR_Knowledgebase\Helpers\ITR_KB_Query;
use ITR_Knowledgebase\Helpers\ITR_KB_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// If Elementor Pro Theme Builder has an active archive template, let it render.
if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'archive' ) ) {
	get_footer();
	return;
}

$is_archive  = is_post_type_archive( 'itr_kb_article' );
$is_category = is_tax( 'itr_kb_category' );
$is_tag      = is_tax( 'itr_kb_tag' );
$queried     = get_queried_object();

$current_term_id = $is_category ? get_queried_object_id() : 0;
$category_tree   = ITR_KB_Category_Order::get_category_tree( 0 );
$kb_archive      = get_post_type_archive_link( 'itr_kb_article' );
?>

<div class="itr-kb-archive-wrap">

	<?php if ( $is_archive ) : ?>
	<!-- Banner (main archive only) -->
	<div class="itr-kb-archive-banner">
		<div class="itr-kb-archive-banner__inner">
			<h1 class="itr-kb-archive-banner__title">
				<?php echo esc_html( get_option( 'itr_kb_banner_title_text', __( 'Hi, How can we help?', 'itr-knowledgebase' ) ) ); ?>
			</h1>
			<div class="itr-kb-archive-banner__search">
				<form class="itr-kb-archive-banner__form" action="<?php echo esc_url( $kb_archive ); ?>" method="get" role="search">
					<div class="itr-kb-search-bar__input-wrap itr-kb-archive-banner__input-wrap">
						<label for="itr-kb-banner-search" class="screen-reader-text"><?php esc_html_e( 'Search articles', 'itr-knowledgebase' ); ?></label>
						<input
							type="search"
							id="itr-kb-banner-search"
							class="itr-kb-search-bar__input itr-kb-archive-banner__input"
							name="itr_kb_search"
							placeholder="<?php esc_attr_e( 'Search Our Articles', 'itr-knowledgebase' ); ?>"
							autocomplete="off"
							aria-autocomplete="list"
							aria-controls="itr-kb-banner-results"
							aria-expanded="false"
						/>
						<button type="submit" class="itr-kb-search-bar__submit itr-kb-archive-banner__submit" aria-label="<?php esc_attr_e( 'Search', 'itr-knowledgebase' ); ?>">
							<span class="dashicons dashicons-search" aria-hidden="true"></span>
						</button>
					</div>
					<div id="itr-kb-banner-results" class="itr-kb-search-bar__results" role="listbox" hidden>
						<ul class="itr-kb-search-bar__results-list" aria-live="polite"></ul>
						<div class="itr-kb-search-bar__results-footer" hidden>
							<a href="#" class="itr-kb-search-bar__view-all"><?php esc_html_e( 'View all results', 'itr-knowledgebase' ); ?></a>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Body: Sidebar + Main -->
	<div class="itr-kb-archive-body">

		<!-- LEFT SIDEBAR -->
		<aside class="itr-kb-archive-sidebar">
			<div class="itr-kb-archive-sidebar__all">
				<a href="<?php echo esc_url( $kb_archive ); ?>" class="itr-kb-archive-sidebar__all-link <?php echo $is_archive ? 'itr-kb-archive-sidebar__all-link--active' : ''; ?>">
					<?php esc_html_e( 'All Topics', 'itr-knowledgebase' ); ?>
				</a>
			</div>
			<nav class="itr-kb-archive-sidebar__nav" aria-label="<?php esc_attr_e( 'Categories', 'itr-knowledgebase' ); ?>">
				<ul class="itr-kb-archive-sidebar__list">
					<?php foreach ( $category_tree as $cat ) : ?>
						<?php
						$is_active   = (int) $current_term_id === (int) $cat->term_id;
						$anc_ids     = get_ancestors( $current_term_id, 'itr_kb_category', 'taxonomy' );
						$is_ancestor = in_array( (int) $cat->term_id, array_map( 'intval', $anc_ids ), true );
						$has_children = ! empty( $cat->children );
						?>
						<li class="itr-kb-archive-sidebar__item">
							<div class="itr-kb-archive-sidebar__row">
								<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="itr-kb-archive-sidebar__link <?php echo ( $is_active || $is_ancestor ) ? 'itr-kb-archive-sidebar__link--active' : ''; ?>">
									<?php echo esc_html( $cat->name ); ?>
								</a>
								<?php if ( $has_children ) : ?>
									<button class="itr-kb-archive-sidebar__toggle" aria-expanded="<?php echo ( $is_active || $is_ancestor ) ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Toggle %s submenu', 'itr-knowledgebase' ), $cat->name ) ); ?>">
										<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
									</button>
								<?php endif; ?>
							</div>
							<?php if ( $has_children ) : ?>
								<ul class="itr-kb-archive-sidebar__sublist" <?php echo ( ! $is_active && ! $is_ancestor ) ? 'hidden' : ''; ?>>
									<?php foreach ( $cat->children as $child ) : ?>
										<?php $child_active = (int) $current_term_id === (int) $child->term_id; ?>
										<li class="itr-kb-archive-sidebar__item">
											<a href="<?php echo esc_url( get_term_link( $child ) ); ?>" class="itr-kb-archive-sidebar__link itr-kb-archive-sidebar__link--child <?php echo $child_active ? 'itr-kb-archive-sidebar__link--active' : ''; ?>">
												<?php echo esc_html( $child->name ); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</aside>

		<!-- MAIN -->
		<main class="itr-kb-archive-main" id="itr-kb-main" role="main">

			<?php if ( $is_archive ) : ?>
			<!-- ================================================================
			     MAIN ARCHIVE — Each top-level category with subcategories only
			     ================================================================ -->
			<?php foreach ( $category_tree as $cat ) : ?>
				<?php
				$icon      = ITR_KB_Category::get_icon( $cat->term_id );
				$image_url = ITR_KB_Category::get_image_url( $cat->term_id );
				$children  = $cat->children;
				$section_id = 'itr-kb-section-' . $cat->term_id;
				?>
				<div class="itr-kb-archive-section">

					<div class="itr-kb-archive-section__header">
						<div class="itr-kb-archive-section__header-left">
							<div class="itr-kb-archive-section__icon-wrap">
								<?php if ( $image_url ) : ?>
									<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" width="22" height="22" />
								<?php elseif ( $icon ) : ?>
									<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
								<?php else : ?>
									<span class="dashicons dashicons-category" aria-hidden="true"></span>
								<?php endif; ?>
							</div>
							<h2 class="itr-kb-archive-section__title">
								<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
							</h2>
						</div>
						<button class="itr-kb-archive-section__toggle" aria-expanded="true" aria-controls="<?php echo esc_attr( $section_id ); ?>">
							<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
						</button>
					</div>

					<div id="<?php echo esc_attr( $section_id ); ?>" class="itr-kb-archive-section__body">
						<?php if ( ! empty( $children ) ) : ?>
							<ul class="itr-kb-archive-section__subcat-list">
								<?php foreach ( $children as $child ) : ?>
									<li class="itr-kb-archive-section__subcat-item">
										<a href="<?php echo esc_url( get_term_link( $child ) ); ?>" class="itr-kb-archive-section__subcat-link">
											<?php echo esc_html( $child->name ); ?>
										</a>
										<span class="itr-kb-archive-section__subcat-arrow dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="itr-kb-archive-section__empty">
								<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php printf( esc_html__( 'Browse all articles in %s', 'itr-knowledgebase' ), esc_html( $cat->name ) ); ?></a>
							</p>
						<?php endif; ?>
					</div>

				</div>
			<?php endforeach; ?>

			<?php elseif ( $is_category ) : ?>
			<!-- ================================================================
			     CATEGORY PAGE — Header + subcategory cards with articles
			     ================================================================ -->
			<?php
			$cat_icon  = ITR_KB_Category::get_icon( $queried->term_id );
			$cat_image = ITR_KB_Category::get_image_url( $queried->term_id );
			$sub_cats  = ITR_KB_Category_Order::get_ordered_categories( $queried->term_id );
			?>

			<div class="itr-kb-cat-page-header">
				<div class="itr-kb-cat-page-header__icon">
					<?php if ( $cat_image ) : ?>
						<img src="<?php echo esc_url( $cat_image ); ?>" alt="<?php echo esc_attr( $queried->name ); ?>" width="32" height="32" />
					<?php elseif ( $cat_icon ) : ?>
						<span class="dashicons <?php echo esc_attr( $cat_icon ); ?>" aria-hidden="true"></span>
					<?php else : ?>
						<span class="dashicons dashicons-category" aria-hidden="true"></span>
					<?php endif; ?>
				</div>
				<h1 class="itr-kb-cat-page-header__title">
					<?php printf( esc_html__( 'Category - %s', 'itr-knowledgebase' ), esc_html( $queried->name ) ); ?>
				</h1>
			</div>

			<?php if ( ! empty( $sub_cats ) ) : ?>

				<!-- Subcategory cards in 2-column grid -->
				<div class="itr-kb-cat-cards">
					<?php foreach ( $sub_cats as $sub ) : ?>
						<?php $sub_articles = ITR_KB_Query::get_by_category( $sub->term_id, 3, 1 ); ?>
						<div class="itr-kb-cat-card">

							<div class="itr-kb-cat-card__header">
								<span class="itr-kb-cat-card__title">
									<a href="<?php echo esc_url( get_term_link( $sub ) ); ?>"><?php echo esc_html( $sub->name ); ?></a>
								</span>
							</div>

							<div class="itr-kb-cat-card__body">
								<?php if ( $sub_articles->have_posts() ) : ?>
									<ul class="itr-kb-cat-card__list">
										<?php while ( $sub_articles->have_posts() ) : $sub_articles->the_post(); ?>
											<li class="itr-kb-cat-card__item">
												<span class="dashicons dashicons-media-text itr-kb-cat-card__item-icon" aria-hidden="true"></span>
												<a href="<?php echo esc_url( ITR_KB_Utils::get_contextual_article_url( get_the_ID(), $sub->term_id ) ); ?>" class="itr-kb-cat-card__item-link"><?php the_title(); ?></a>
												<span class="dashicons dashicons-arrow-right-alt2 itr-kb-cat-card__item-arrow" aria-hidden="true"></span>
											</li>
										<?php endwhile; ?>
										<?php wp_reset_postdata(); ?>
									</ul>
									<?php if ( $sub_articles->found_posts > 3 ) : ?>
										<div class="itr-kb-cat-card__footer">
											<span class="itr-kb-cat-card__more">+ <?php echo absint( $sub_articles->found_posts - 3 ); ?> <?php esc_html_e( 'Articles', 'itr-knowledgebase' ); ?></span>
											<a href="<?php echo esc_url( get_term_link( $sub ) ); ?>" class="itr-kb-cat-card__view-all"><?php esc_html_e( 'Show Remaining Articles', 'itr-knowledgebase' ); ?></a>
										</div>
									<?php endif; ?>
								<?php else : ?>
									<p class="itr-kb-cat-card__empty"><?php esc_html_e( 'No articles yet.', 'itr-knowledgebase' ); ?></p>
								<?php endif; ?>
							</div>

						</div>
					<?php endforeach; ?>
				</div>

			<?php else : ?>

				<!-- No subcategories — flat article list with Load More -->
				<?php if ( have_posts() ) : ?>
					<div class="itr-kb-article-list" id="itr-kb-article-list">
						<?php while ( have_posts() ) : the_post(); ?>
							<article class="itr-kb-article-list__item">
								<h3 class="itr-kb-article-list__title">
									<a href="<?php echo esc_url( ITR_KB_Utils::get_contextual_article_url( get_the_ID(), isset( $queried->term_id ) ? $queried->term_id : 0 ) ); ?>" class="itr-kb-article-list__link"><?php the_title(); ?></a>
								</h3>
								<?php if ( has_excerpt() ) : ?>
									<div class="itr-kb-article-list__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></div>
								<?php endif; ?>
								<div class="itr-kb-article-list__meta">
									<time class="itr-kb-article-list__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
								</div>
							</article>
						<?php endwhile; ?>
					</div>
					<?php if ( $GLOBALS['wp_query']->max_num_pages > 1 ) : ?>
						<div class="itr-kb-load-more-wrap" id="itr-kb-load-more-wrap">
							<button
								class="itr-kb-load-more-btn"
								id="itr-kb-load-more"
								data-page="1"
								data-max="<?php echo esc_attr( $GLOBALS['wp_query']->max_num_pages ); ?>"
								data-term="<?php echo esc_attr( absint( $queried->term_id ) ); ?>"
								data-taxonomy="itr_kb_category"
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'itr_kb_load_more' ) ); ?>"
							>
								<?php esc_html_e( 'Load More Articles', 'itr-knowledgebase' ); ?>
								<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
							</button>
							<span class="itr-kb-load-more-status"></span>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<div class="itr-kb-no-results"><p><?php esc_html_e( 'No articles found.', 'itr-knowledgebase' ); ?></p></div>
				<?php endif; ?>

			<?php endif; ?>

			<?php elseif ( $is_tag ) : ?>
			<!-- ================================================================
			     TAG PAGE — Flat article list
			     ================================================================ -->
			<div class="itr-kb-cat-page-header">
				<h1 class="itr-kb-cat-page-header__title">
					<?php printf( esc_html__( 'Tag: %s', 'itr-knowledgebase' ), esc_html( $queried->name ) ); ?>
				</h1>
			</div>
			<?php if ( have_posts() ) : ?>
				<div class="itr-kb-article-list">
					<?php while ( have_posts() ) : the_post(); ?>
						<article class="itr-kb-article-list__item">
							<h3 class="itr-kb-article-list__title">
								<a href="<?php echo esc_url( ITR_KB_Utils::get_contextual_article_url( get_the_ID(), isset( $queried->term_id ) ? $queried->term_id : 0 ) ); ?>" class="itr-kb-article-list__link"><?php the_title(); ?></a>
							</h3>
							<time class="itr-kb-article-list__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</article>
					<?php endwhile; ?>
				</div>
				<div class="itr-kb-pagination">
					<?php echo paginate_links( array( 'prev_text' => esc_html__( '&laquo; Previous', 'itr-knowledgebase' ), 'next_text' => esc_html__( 'Next &raquo;', 'itr-knowledgebase' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			<?php else : ?>
				<div class="itr-kb-no-results"><p><?php esc_html_e( 'No articles found.', 'itr-knowledgebase' ); ?></p></div>
			<?php endif; ?>

			<?php endif; ?>

		</main>

	</div><!-- .itr-kb-archive-body -->

</div><!-- .itr-kb-archive-wrap -->

<?php if ( get_option( 'itr_kb_back_to_top_enabled', true ) ) : ?>
	<button id="itr-kb-back-to-top" class="itr-kb-back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'itr-knowledgebase' ); ?>" hidden>
		<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
	</button>
<?php endif; ?>

<?php get_footer(); ?>