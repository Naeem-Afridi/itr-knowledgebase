<?php
/**
 * Partial: Category Tree (Sidebar)
 *
 * @package ITR_Knowledgebase
 */

use ITR_Knowledgebase\Admin\ITR_KB_Category_Order;
use ITR_Knowledgebase\Taxonomies\ITR_KB_Category;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tree = ITR_KB_Category_Order::get_category_tree( 0 );

if ( empty( $tree ) ) {
	return;
}

// Get current term/post for active state highlighting.
$current_term_id = 0;
if ( is_tax( 'itr_kb_category' ) ) {
	$current_term_id = get_queried_object_id();
} elseif ( is_singular( 'itr_kb_article' ) ) {
	$terms = wp_get_post_terms( get_the_ID(), 'itr_kb_category', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$current_term_id = $terms[0];
	}
}

/**
 * Recursively render category tree items.
 *
 * @param array $items            Category tree nodes.
 * @param int   $current_term_id  Current active term ID.
 * @param int   $depth            Current nesting depth.
 * @return void
 */
function itr_kb_render_category_tree( $items, $current_term_id, $depth = 0 ) {
	foreach ( $items as $term ) {
		$has_children = ! empty( $term->children );
		$is_active    = ( (int) $current_term_id === (int) $term->term_id );
		$is_ancestor  = $has_children && in_array( $current_term_id, wp_list_pluck( $term->children, 'term_id' ), false );
		$icon         = ITR_KB_Category::get_icon( $term->term_id );

		$classes = array( 'itr-kb-category-tree__item' );
		if ( $is_active ) {
			$classes[] = 'itr-kb-category-tree__item--active';
		}
		if ( $has_children ) {
			$classes[] = 'itr-kb-category-tree__item--has-children';
		}
		?>
		<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

			<div class="itr-kb-category-tree__row">

				<?php if ( $icon ) : ?>
					<span class="itr-kb-category-tree__icon dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
				<?php endif; ?>

				<a
					href="<?php echo esc_url( get_term_link( $term ) ); ?>"
					class="itr-kb-category-tree__link"
					<?php echo $is_active ? 'aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $term->name ); ?>
					<span class="itr-kb-category-tree__count">(<?php echo absint( $term->count ); ?>)</span>
				</a>

				<?php if ( $has_children ) : ?>
					<button
						class="itr-kb-category-tree__toggle"
						aria-expanded="<?php echo ( $is_active || $is_ancestor ) ? 'true' : 'false'; ?>"
						aria-label="<?php echo esc_attr( sprintf( __( 'Toggle %s submenu', 'itr-knowledgebase' ), $term->name ) ); ?>"
					>
						<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
					</button>
				<?php endif; ?>

			</div>

			<?php if ( $has_children ) : ?>
				<ul
					class="itr-kb-category-tree__children"
					<?php echo ( ! $is_active && ! $is_ancestor ) ? 'hidden' : ''; ?>
				>
					<?php itr_kb_render_category_tree( $term->children, $current_term_id, $depth + 1 ); ?>
				</ul>
			<?php endif; ?>

		</li>
		<?php
	}
}
?>
<nav class="itr-kb-category-tree" aria-label="<?php esc_attr_e( 'Knowledge Base Categories', 'itr-knowledgebase' ); ?>">
	<div class="itr-kb-category-tree__header">
		<span class="itr-kb-category-tree__title">
			<?php esc_html_e( 'Categories', 'itr-knowledgebase' ); ?>
		</span>
	</div>
	<ul class="itr-kb-category-tree__list">
		<?php itr_kb_render_category_tree( $tree, $current_term_id ); ?>
	</ul>
</nav>