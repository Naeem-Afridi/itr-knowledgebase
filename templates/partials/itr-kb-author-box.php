<?php
/**
 * Partial: Author & Reviewer Box
 *
 * Variables available:
 * @var int    $author_id     Author post ID (0 if none).
 * @var array  $reviewer_ids  Array of reviewer post IDs.
 * @var string $author_layout 'standard' (default) or 'compact'.
 *
 * @package ITR_Knowledgebase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $author_id && empty( $reviewer_ids ) ) {
	return;
}

$_layout = $author_layout ?? 'standard';

if ( ! function_exists( 'itr_kb_get_person_data' ) ) {
	function itr_kb_get_person_data( $person_id ) {
		$person_id = absint( $person_id );
		$post      = get_post( $person_id );
		if ( ! $post || 'itr_kb_author' !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		return array(
			'id'    => $post->ID,
			'name'  => esc_html( $post->post_title ),
			'role'  => esc_html( get_post_meta( $post->ID, '_itr_kb_role', true ) ),
			'bio'   => wp_kses_post( $post->post_content ),
			'photo' => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
			'url'   => esc_url( get_permalink( $post->ID ) ),
		);
	}
}

$_author   = $author_id ? itr_kb_get_person_data( $author_id ) : null;
$_reviewer = ! empty( $reviewer_ids ) ? itr_kb_get_person_data( $reviewer_ids[0] ) : null;
?>

<?php if ( 'compact' === $_layout ) : ?>

	<?php /* ── COMPACT LAYOUT ─────────────────────────────────────────── */ ?>
	<div class="itr-kb-author-box itr-kb-author-box--compact" itemscope itemtype="https://schema.org/Person">

		<?php if ( $_author ) : ?>
		<div class="itr-kb-author-box__compact-row itr-kb-author-box__compact-row--author">
    <div class="itr-kb-author-box__compact-avatar">
        <?php if ( $_author['photo'] ) : ?>
            <img src="<?php echo esc_url( $_author['photo'] ); ?>" alt="<?php echo esc_attr( $_author['name'] ); ?>" width="44" height="44" loading="lazy" />
        <?php else : ?>
            <span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
        <?php endif; ?>
    </div>
    <div class="itr-kb-author-box__compact-info">
        <span class="itr-kb-author-box__compact-label"><?php esc_html_e( 'Written By', 'itr-knowledgebase' ); ?></span>
        <span class="itr-kb-author-box__compact-name" itemprop="name"><?php echo esc_html( $_author['name'] ); ?></span>
        <?php if ( $_author['role'] ) : ?>
            <span class="itr-kb-author-box__compact-role"><?php echo esc_html( $_author['role'] ); ?></span>
        <?php endif; ?>
    </div>
</div>
<?php if ( $_author['bio'] ) : ?>
    <div class="itr-kb-author-box__compact-bio" itemprop="description"><?php echo wp_kses_post( $_author['bio'] ); ?></div>
<?php endif; ?>
		<?php endif; ?>

		<?php if ( $_reviewer ) : ?>
			<div class="itr-kb-author-box__compact-row itr-kb-author-box__compact-row--reviewer">
				<div class="itr-kb-author-box__compact-avatar">
					<?php if ( $_reviewer['photo'] ) : ?>
						<img src="<?php echo esc_url( $_reviewer['photo'] ); ?>" alt="<?php echo esc_attr( $_reviewer['name'] ); ?>" width="44" height="44" loading="lazy" />
					<?php else : ?>
						<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
					<?php endif; ?>
				</div>
				<div class="itr-kb-author-box__compact-info">
					<span class="itr-kb-author-box__compact-label"><?php esc_html_e( 'Reviewed By', 'itr-knowledgebase' ); ?></span>
					<span class="itr-kb-author-box__compact-name"><?php echo esc_html( $_reviewer['name'] ); ?></span>
					<?php if ( $_reviewer['role'] ) : ?>
						<span class="itr-kb-author-box__compact-role"><?php echo esc_html( $_reviewer['role'] ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $_reviewer['bio'] ) : ?>
				<div class="itr-kb-author-box__compact-bio"><?php echo wp_kses_post( $_reviewer['bio'] ); ?></div>
			<?php endif; ?>
		<?php endif; ?>

	</div>

<?php else : ?>

	<?php /* ── STANDARD LAYOUT ─────────────────────────────────────────── */ ?>
	<div class="itr-kb-author-box itr-kb-author-box--standard" itemscope itemtype="https://schema.org/Person">

		<?php if ( $_author ) : ?>
			<div class="itr-kb-author-box__section itr-kb-author-box__section--author">
				<h4 class="itr-kb-author-box__heading"><?php esc_html_e( 'Written by', 'itr-knowledgebase' ); ?></h4>
				<div class="itr-kb-author-box__person">
					<?php if ( $_author['photo'] ) : ?>
						<div class="itr-kb-author-box__avatar">
							<img src="<?php echo esc_url( $_author['photo'] ); ?>" alt="<?php echo esc_attr( $_author['name'] ); ?>" width="80" height="80" loading="lazy" itemprop="image" />
						</div>
					<?php else : ?>
						<div class="itr-kb-author-box__avatar itr-kb-author-box__avatar--placeholder">
							<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
						</div>
					<?php endif; ?>
					<div class="itr-kb-author-box__info">
						<span class="itr-kb-author-box__name" itemprop="name"><?php echo esc_html( $_author['name'] ); ?></span>
						<?php if ( ! empty( $_author['bio'] ) ) : ?>
							<div class="itr-kb-author-box__bio" itemprop="description"><?php echo wp_kses_post( $_author['bio'] ); ?></div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $reviewer_ids ) ) : ?>
			<div class="itr-kb-author-box__section itr-kb-author-box__section--reviewers">
				<h4 class="itr-kb-author-box__heading"><?php esc_html_e( 'Reviewed by', 'itr-knowledgebase' ); ?></h4>
				<div class="itr-kb-author-box__reviewers">
					<?php foreach ( $reviewer_ids as $reviewer_id ) :
						$reviewer = itr_kb_get_person_data( $reviewer_id );
						if ( ! $reviewer ) continue; ?>
						<div class="itr-kb-author-box__person itr-kb-author-box__person--reviewer">
							<?php if ( $reviewer['photo'] ) : ?>
								<div class="itr-kb-author-box__avatar itr-kb-author-box__avatar--sm">
									<img src="<?php echo esc_url( $reviewer['photo'] ); ?>" alt="<?php echo esc_attr( $reviewer['name'] ); ?>" width="50" height="50" loading="lazy" />
								</div>
							<?php else : ?>
								<div class="itr-kb-author-box__avatar itr-kb-author-box__avatar--sm itr-kb-author-box__avatar--placeholder">
									<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
								</div>
							<?php endif; ?>
							<div class="itr-kb-author-box__info">
								<span class="itr-kb-author-box__name"><?php echo esc_html( $reviewer['name'] ); ?></span>
								<?php if ( ! empty( $reviewer['bio'] ) ) : ?>
									<div class="itr-kb-author-box__bio"><?php echo wp_kses_post( $reviewer['bio'] ); ?></div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

	</div>

<?php endif; ?>
