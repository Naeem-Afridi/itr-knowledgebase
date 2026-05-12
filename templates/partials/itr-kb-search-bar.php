<?php
/**
 * Partial: Search Bar
 *
 * @package ITR_Knowledgebase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! get_option( 'itr_kb_search_enabled', true ) ) {
	return;
}
?>
<div class="itr-kb-search-bar" role="search">
	<form
		class="itr-kb-search-bar__form"
		action="<?php echo esc_url( get_post_type_archive_link( 'itr_kb_article' ) ); ?>"
		method="get"
		role="search"
		aria-label="<?php esc_attr_e( 'Search Knowledge Base', 'itr-knowledgebase' ); ?>"
	>
		<div class="itr-kb-search-bar__input-wrap">
			<label for="itr-kb-search-input" class="screen-reader-text">
				<?php esc_html_e( 'Search articles', 'itr-knowledgebase' ); ?>
			</label>
			<input
				type="search"
				id="itr-kb-search-input"
				class="itr-kb-search-bar__input"
				name="itr_kb_search"
				placeholder="<?php esc_attr_e( 'Search articles...', 'itr-knowledgebase' ); ?>"
				value="<?php echo esc_attr( isset( $_GET['itr_kb_search'] ) ? sanitize_text_field( wp_unslash( $_GET['itr_kb_search'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification ?>"
				autocomplete="off"
				aria-autocomplete="list"
				aria-controls="itr-kb-search-results"
				aria-expanded="false"
			/>
			<button type="submit" class="itr-kb-search-bar__submit" aria-label="<?php esc_attr_e( 'Search', 'itr-knowledgebase' ); ?>">
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
			</button>
		</div>

		<!-- Live search results dropdown -->
		<div
			id="itr-kb-search-results"
			class="itr-kb-search-bar__results"
			role="listbox"
			aria-label="<?php esc_attr_e( 'Search results', 'itr-knowledgebase' ); ?>"
			hidden
		>
			<ul class="itr-kb-search-bar__results-list" aria-live="polite"></ul>
			<div class="itr-kb-search-bar__results-footer" hidden>
				<a href="#" class="itr-kb-search-bar__view-all">
					<?php esc_html_e( 'View all results', 'itr-knowledgebase' ); ?>
				</a>
			</div>
		</div>

	</form>
</div>