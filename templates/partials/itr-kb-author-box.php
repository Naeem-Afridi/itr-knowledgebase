<?php
/**
 * Partial: Author & Reviewer Box
 *
 * Variables available:
 * @var int   $author_id    Author post ID (0 if none).
 * @var array $reviewer_ids Array of reviewer post IDs.
 *
 * @package ITR_Knowledgebase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $author_id && empty( $reviewer_ids ) ) {
	return;
}

/**
 * Get person data from itr_kb_author post ID.
 *
 * @param int $person_id Post ID.
 * @return array|null
 */
function itr_kb_get_person_data( $person_id ) {
	$person_id = absint( $person_id );
	$post      = get_post( $person_id );

	if ( ! $post || 'itr_kb_author' !== $post->post_type || 'publish' !== $post->post_status ) {
		return null;
	}

	return array(
		'id'    => $post->ID,
		'name'  => esc_html( $post->post_title ),
		'bio'   => wp_kses_post( $post->post_content ),
		'photo' => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
		'url'   => esc_url( get_permalink( $post->ID ) ),
	);
}
?>

<div class="itr-kb-author-box" itemscope itemtype="https://schema.org/Person">

	<?php if ( $author_id ) : ?>
		<?php $author = itr_kb_get_person_data( $author_id ); ?>
		<?php if ( $author ) : ?>

			<div class="itr-kb-author-box__section itr-kb-author-box__section--author">
				<h4 class="itr-kb-author-box__heading">
					<?php esc_html_e( 'Written by', 'itr-knowledgebase' ); ?>
				</h4>

				<div class="itr-kb-author-box__person">
					<?php if ( $author['photo'] ) : ?>
						<div class="itr-kb-author-box__avatar">
							<img
								src="<?php echo esc_url( $author['photo'] ); ?>"
								alt="<?php echo esc_attr( $author['name'] ); ?>"
								width="80"
								height="80"
								loading="lazy"
								itemprop="image"
							/>
						</div>
					<?php else : ?>
						<div class="itr-kb-author-box__avatar itr-kb-author-box__avatar--placeholder">
							<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
						</div>
					<?php endif; ?>

					<div class="itr-kb-author-box__info">
						<span class="itr-kb-author-box__name" itemprop="name">
							<?php echo esc_html( $author['name'] ); ?>
						</span>
						<?php if ( ! empty( $author['bio'] ) ) : ?>
							<div class="itr-kb-author-box__bio" itemprop="description">
								<?php echo wp_kses_post( $author['bio'] ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

		<?php endif; ?>
	<?php endif; ?>

	<?php if ( ! empty( $reviewer_ids ) ) : ?>

		<div class="itr-kb-author-box__section itr-kb-author-box__section--reviewers">
			<h4 class="itr-kb-author-box__heading">
				<?php
				echo esc_html(
					_n(
						'Reviewed by',
						'Reviewed by',
						count( $reviewer_ids ),
						'itr-knowledgebase'
					)
				);
				?>
			</h4>

			<div class="itr-kb-author-box__reviewers">
				<?php foreach ( $reviewer_ids as $reviewer_id ) : ?>
					<?php $reviewer = itr_kb_get_person_data( $reviewer_id ); ?>
					<?php if ( ! $reviewer ) : continue; endif; ?>

					<div class="itr-kb-author-box__person itr-kb-author-box__person--reviewer">
						<?php if ( $reviewer['photo'] ) : ?>
							<div class="itr-kb-author-box__avatar itr-kb-author-box__avatar--sm">
								<img
									src="<?php echo esc_url( $reviewer['photo'] ); ?>"
									alt="<?php echo esc_attr( $reviewer['name'] ); ?>"
									width="50"
									height="50"
									loading="lazy"
								/>
							</div>
						<?php else : ?>
							<div class="itr-kb-author-box__avatar itr-kb-author-box__avatar--sm itr-kb-author-box__avatar--placeholder">
								<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
							</div>
						<?php endif; ?>

						<div class="itr-kb-author-box__info">
							<span class="itr-kb-author-box__name">
								<?php echo esc_html( $reviewer['name'] ); ?>
							</span>
							<?php if ( ! empty( $reviewer['bio'] ) ) : ?>
								<div class="itr-kb-author-box__bio">
									<?php echo wp_kses_post( $reviewer['bio'] ); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

				<?php endforeach; ?>
			</div>
		</div>

	<?php endif; ?>

</div><!-- .itr-kb-author-box -->