<?php
/**
 * Content sections (latest, popular, trending, featured, recommended).
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/frontend
 */

namespace ITR_Knowledgebase\Frontend;

use ITR_Knowledgebase\Helpers\ITR_KB_Query;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Sections
 *
 * Provides reusable HTML output for content sections.
 * Used by Elementor widgets and shortcodes.
 */
class ITR_KB_Sections {

	/**
	 * Supported section types.
	 *
	 * @var array
	 */
	public static $types = array(
		'latest'           => 'Latest Articles',
		'recently_updated' => 'Recently Updated',
		'popular'          => 'Popular Articles',
		'trending'         => 'Trending Articles',
		'featured'         => 'Featured Articles',
		'recommended'      => 'Recommended Articles',
	);

	/**
	 * Render a content section.
	 *
	 * @param string $type    Section type key.
	 * @param array  $args    Display arguments.
	 * @return string HTML output.
	 */
	public static function render( $type, $args = array() ) {
		$defaults = array(
			'count'        => 5,
			'post_id'      => get_the_ID(),
			'show_excerpt' => false,
			'show_date'    => true,
			'show_category'=> true,
			'show_thumb'   => false,
			'show_icon'    => false,
			'icon_url'     => '',
			'title'        => '',
			'layout'       => 'list',
		);

		$args = wp_parse_args( $args, $defaults );

		$query = self::get_query( $type, $args );

		if ( ! $query || ! $query->have_posts() ) {
			return '';
		}

		ob_start();
		?>
		<div class="itr-kb-section itr-kb-section--<?php echo esc_attr( $type ); ?> itr-kb-section--layout-<?php echo esc_attr( $args['layout'] ); ?>">

			<?php if ( ! empty( $args['title'] ) ) : ?>
				<h3 class="itr-kb-section__title"><?php echo esc_html( $args['title'] ); ?></h3>
			<?php endif; ?>

			<ul class="itr-kb-section__list">
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<li class="itr-kb-section__item">
						<article class="itr-kb-section__article <?php echo $args['show_icon'] ? 'itr-kb-section__article--has-icon' : ''; ?>">

							<?php if ( $args['show_thumb'] && has_post_thumbnail() ) : ?>
								<div class="itr-kb-section__thumb">
									<a href="<?php the_permalink(); ?>">
										<?php the_post_thumbnail( 'thumbnail', array( 'alt' => esc_attr( get_the_title() ) ) ); ?>
									</a>
								</div>
							<?php endif; ?>

							<div class="itr-kb-section__content">
								<h4 class="itr-kb-section__article-title">
									<a href="<?php the_permalink(); ?>" class="itr-kb-section__link">
										<?php if ( $args['show_icon'] && ! empty( $args['icon_url'] ) ) : ?>
											<span class="itr-kb-section__item-icon">
												<img src="<?php echo esc_url( $args['icon_url'] ); ?>" alt="" aria-hidden="true" />
											</span>
										<?php endif; ?>
										<span class="itr-kb-section__link-text"><?php the_title(); ?></span>
									</a>
								</h4>

								<?php if ( $args['show_category'] ) : ?>
									<?php
									$cats = wp_get_post_terms( get_the_ID(), 'itr_kb_category', array( 'number' => 1 ) );
									if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) :
										?>
										<div class="itr-kb-section__category">
											<a href="<?php echo esc_url( get_term_link( $cats[0] ) ); ?>">
												<?php echo esc_html( $cats[0]->name ); ?>
											</a>
										</div>
									<?php endif; ?>
								<?php endif; ?>

								<?php if ( $args['show_excerpt'] ) : ?>
									<div class="itr-kb-section__excerpt">
										<?php echo esc_html( wp_trim_words( get_the_excerpt(), 15 ) ); ?>
									</div>
								<?php endif; ?>

								<?php if ( $args['show_date'] ) : ?>
									<div class="itr-kb-section__meta">
										<time
											class="itr-kb-section__date"
											datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"
										>
											<?php echo esc_html( get_the_date() ); ?>
										</time>
									</div>
								<?php endif; ?>

							</div><!-- .itr-kb-section__content -->

						</article>
					</li>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			</ul>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the WP_Query for a given section type.
	 *
	 * @param string $type Section type.
	 * @param array  $args Arguments.
	 * @return \WP_Query|null
	 */
	private static function get_query( $type, $args ) {
		$count   = absint( $args['count'] );
		$post_id = absint( $args['post_id'] );

		switch ( $type ) {
			case 'latest':
				return ITR_KB_Query::get_latest( $count );

			case 'recently_updated':
				return ITR_KB_Query::get_recently_updated( $count );

			case 'popular':
				return ITR_KB_Query::get_popular( $count );

			case 'trending':
				return ITR_KB_Query::get_trending( $count );

			case 'featured':
				return ITR_KB_Query::get_featured( $count );

			case 'recommended':
				return $post_id ? ITR_KB_Query::get_recommended( $post_id, $count ) : null;

			default:
				return null;
		}
	}

	/**
	 * Track article view count.
	 * Called when a KB article is loaded.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function track_view( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || 'itr_kb_article' !== get_post_type( $post_id ) ) {
			return;
		}

		// Don't count admin or bot views.
		if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		$count = absint( get_post_meta( $post_id, '_itr_kb_view_count', true ) );
		update_post_meta( $post_id, '_itr_kb_view_count', $count + 1 );

		// Update trending score: weighted by recency (views in last 7 days).
		self::update_trending_score( $post_id );
	}

	/**
	 * Update the trending score for an article.
	 * Trending score = views in the last 7 days.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private static function update_trending_score( $post_id ) {
		$trending_data = get_post_meta( $post_id, '_itr_kb_trending_data', true );

		if ( ! is_array( $trending_data ) ) {
			$trending_data = array();
		}

		$today = date( 'Y-m-d' );
		$trending_data[ $today ] = isset( $trending_data[ $today ] ) ? $trending_data[ $today ] + 1 : 1;

		// Remove entries older than 7 days.
		$cutoff = date( 'Y-m-d', strtotime( '-7 days' ) );
		foreach ( array_keys( $trending_data ) as $date ) {
			if ( $date < $cutoff ) {
				unset( $trending_data[ $date ] );
			}
		}

		update_post_meta( $post_id, '_itr_kb_trending_data', $trending_data );

		// Store total score as separate meta for fast query ordering.
		$score = array_sum( $trending_data );
		update_post_meta( $post_id, '_itr_kb_trending_score', $score );
	}
}