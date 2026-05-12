<?php
/**
 * AJAX search handler.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/frontend
 */

namespace ITR_Knowledgebase\Frontend;

use ITR_Knowledgebase\Helpers\ITR_KB_Security;
use ITR_Knowledgebase\Helpers\ITR_KB_Query;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Search
 *
 * Handles AJAX live search for KB articles.
 *
 * Features:
 * - Searches title, content, categories, tags
 * - Returns JSON results with highlighted terms
 * - Tracks popular searches and failed searches
 * - Provides no-results suggestions
 */
class ITR_KB_Search {

	/**
	 * Option key for popular searches.
	 *
	 * @var string
	 */
	const POPULAR_SEARCHES_KEY = 'itr_kb_popular_searches';

	/**
	 * Option key for failed searches.
	 *
	 * @var string
	 */
	const FAILED_SEARCHES_KEY = 'itr_kb_failed_searches';

	/**
	 * Handle AJAX search request.
	 * Hooked to wp_ajax_itr_kb_search and wp_ajax_nopriv_itr_kb_search.
	 *
	 * @return void
	 */
	public function handle_search() {
		// Verify nonce.
		ITR_KB_Security::verify_ajax_nonce(
			isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '',
			'itr_kb_frontend_nonce'
		);

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

		if ( strlen( $keyword ) < 2 ) {
			wp_send_json_success( array(
				'results'     => array(),
				'total'       => 0,
				'keyword'     => $keyword,
				'suggestions' => array(),
			));
		}

		$results_count = absint( get_option( 'itr_kb_search_results_count', 5 ) );
		$highlight     = (bool) get_option( 'itr_kb_search_highlight', true );

		$query   = ITR_KB_Query::search( $keyword, $results_count );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				$title   = get_the_title();
				$excerpt = $this->get_search_excerpt( get_the_content(), $keyword );
				$url     = get_permalink();

				if ( $highlight ) {
					$title   = $this->highlight_keyword( $title, $keyword );
					$excerpt = $this->highlight_keyword( $excerpt, $keyword );
				}

				$categories = wp_get_post_terms( $post_id, 'itr_kb_category', array( 'fields' => 'names' ) );

				$results[] = array(
					'id'         => $post_id,
					'title'      => $title,
					'excerpt'    => $excerpt,
					'url'        => esc_url( $url ),
					'categories' => ! is_wp_error( $categories ) ? $categories : array(),
					'thumbnail'  => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: '',
				);
			}
			wp_reset_postdata();

			// Track popular search.
			$this->track_search( $keyword, true );
		} else {
			// Track failed search.
			$this->track_search( $keyword, false );
		}

		$total       = $query->found_posts;
		$suggestions = empty( $results ) ? $this->get_suggestions( $keyword ) : array();

		wp_send_json_success( array(
			'results'     => $results,
			'total'       => $total,
			'keyword'     => sanitize_text_field( $keyword ),
			'suggestions' => $suggestions,
		));
	}

	/**
	 * Generate a short excerpt around the keyword.
	 *
	 * @param string $content Full post content.
	 * @param string $keyword Search keyword.
	 * @param int    $length  Excerpt length in characters.
	 * @return string
	 */
	private function get_search_excerpt( $content, $keyword, $length = 150 ) {
		$content  = wp_strip_all_tags( $content );
		$position = stripos( $content, $keyword );

		if ( false === $position ) {
			return wp_trim_words( $content, 20 );
		}

		$start = max( 0, $position - 60 );
		$text  = substr( $content, $start, $length );

		if ( $start > 0 ) {
			$text = '...' . $text;
		}

		if ( ( $start + $length ) < strlen( $content ) ) {
			$text .= '...';
		}

		return esc_html( $text );
	}

	/**
	 * Wrap keyword in highlight span.
	 *
	 * @param string $text    Text to search in.
	 * @param string $keyword Keyword to highlight.
	 * @return string
	 */
	private function highlight_keyword( $text, $keyword ) {
		if ( empty( $keyword ) ) {
			return $text;
		}

		$escaped_keyword = preg_quote( $keyword, '/' );

		return preg_replace(
			'/(' . $escaped_keyword . ')/i',
			'<mark class="itr-kb-search-highlight">$1</mark>',
			$text
		);
	}

	/**
	 * Handle load more AJAX request.
	 * Hooked to wp_ajax_itr_kb_load_more and wp_ajax_nopriv_itr_kb_load_more.
	 *
	 * @return void
	 */
	public function handle_load_more() {
		ITR_KB_Security::verify_ajax_nonce(
			isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '',
			'itr_kb_load_more'
		);

		$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) + 1 : 2;
		$term_id  = isset( $_POST['term'] ) ? absint( $_POST['term'] ) : 0;
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( $_POST['taxonomy'] ) : 'itr_kb_category';
		$per_page = absint( get_option( 'itr_kb_articles_per_page', 10 ) );

		// Validate taxonomy.
		if ( ! in_array( $taxonomy, array( 'itr_kb_category', 'itr_kb_tag' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid taxonomy.' ) );
		}

		$args = array(
			'post_type'      => 'itr_kb_article',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		);

		$query   = new \WP_Query( $args );
		$html    = '';
		$has_more = $page < $query->max_num_pages;

		if ( $query->have_posts() ) {
			ob_start();
			while ( $query->have_posts() ) {
				$query->the_post();
				$excerpt = has_excerpt() ? esc_html( wp_trim_words( get_the_excerpt(), 20 ) ) : '';
				?>
				<article class="itr-kb-article-list__item">
					<h3 class="itr-kb-article-list__title">
						<a href="<?php the_permalink(); ?>" class="itr-kb-article-list__link"><?php the_title(); ?></a>
					</h3>
					<?php if ( $excerpt ) : ?>
						<div class="itr-kb-article-list__excerpt"><?php echo $excerpt; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
					<div class="itr-kb-article-list__meta">
						<time class="itr-kb-article-list__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					</div>
				</article>
				<?php
			}
			wp_reset_postdata();
			$html = ob_get_clean();
		}

		wp_send_json_success( array(
			'html'     => $html,
			'page'     => $page,
			'has_more' => $has_more,
		) );
	}

	/**
	 * Track search keyword (popular or failed).
	 *
	 * @param string $keyword     Search term.
	 * @param bool   $has_results Whether the search returned results.
	 * @return void
	 */
	private function track_search( $keyword, $has_results ) {
		$keyword = strtolower( sanitize_text_field( $keyword ) );
		$option  = $has_results ? self::POPULAR_SEARCHES_KEY : self::FAILED_SEARCHES_KEY;

		$searches = get_option( $option, array() );

		if ( ! is_array( $searches ) ) {
			$searches = array();
		}

		if ( isset( $searches[ $keyword ] ) ) {
			$searches[ $keyword ]++;
		} else {
			$searches[ $keyword ] = 1;
		}

		// Keep only top 200 searches to avoid option bloat.
		if ( count( $searches ) > 200 ) {
			arsort( $searches );
			$searches = array_slice( $searches, 0, 200, true );
		}

		update_option( $option, $searches, false );
	}

	/**
	 * Get popular search suggestions for no-results state.
	 *
	 * @param string $keyword The failed search term.
	 * @return array
	 */
	private function get_suggestions( $keyword ) {
		$popular = get_option( self::POPULAR_SEARCHES_KEY, array() );

		if ( empty( $popular ) ) {
			return array();
		}

		arsort( $popular );
		$top = array_slice( array_keys( $popular ), 0, 5 );

		// Filter out the current failed keyword.
		$top = array_filter( $top, function( $term ) use ( $keyword ) {
			return strtolower( $term ) !== strtolower( $keyword );
		});

		return array_values( $top );
	}

	/**
	 * Get popular searches for admin display.
	 *
	 * @param int $limit Number of top searches to return.
	 * @return array
	 */
	public static function get_popular_searches( $limit = 10 ) {
		$searches = get_option( self::POPULAR_SEARCHES_KEY, array() );

		if ( ! is_array( $searches ) ) {
			return array();
		}

		arsort( $searches );
		return array_slice( $searches, 0, absint( $limit ), true );
	}

	/**
	 * Get failed searches for admin display.
	 *
	 * @param int $limit Number of top failures to return.
	 * @return array
	 */
	public static function get_failed_searches( $limit = 10 ) {
		$searches = get_option( self::FAILED_SEARCHES_KEY, array() );

		if ( ! is_array( $searches ) ) {
			return array();
		}

		arsort( $searches );
		return array_slice( $searches, 0, absint( $limit ), true );
	}
}