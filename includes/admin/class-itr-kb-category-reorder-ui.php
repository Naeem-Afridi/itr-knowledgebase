<?php
/**
 * Category reorder admin page.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes/admin
 */

namespace ITR_Knowledgebase\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Category_Reorder_UI
 *
 * Registers a proper WordPress admin page for drag-and-drop category reordering.
 * Accessible via Categories → Reorder Categories.
 */
class ITR_KB_Category_Reorder_UI {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the reorder submenu page under KB Articles.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'edit.php?post_type=itr_kb_article',
			esc_html__( 'Reorder Categories', 'itr-knowledgebase' ),
			esc_html__( 'Reorder Categories', 'itr-knowledgebase' ),
			'manage_itr_kb_categories',
			'itr-kb-reorder-categories',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts on our page only.
	 *
	 * @param string $hook Current page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'itr_kb_article_page_itr-kb-reorder-categories' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	/**
	 * Render the reorder page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'itr-knowledgebase' ) );
		}

		$nonce      = wp_create_nonce( 'itr_kb_category_order' );
		$top_level  = ITR_KB_Category_Order::get_ordered_categories( 0 );
		$categories_url = admin_url( 'edit-tags.php?taxonomy=itr_kb_category&post_type=itr_kb_article' );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Reorder Categories', 'itr-knowledgebase' ); ?>
			</h1>
			<a href="<?php echo esc_url( $categories_url ); ?>" class="page-title-action">
				<?php esc_html_e( '← Back to Categories', 'itr-knowledgebase' ); ?>
			</a>

			<hr class="wp-header-end">

			<div class="itr-kb-card" style="max-width:800px;margin-top:20px;">

				<p style="color:#646970;margin-bottom:20px;">
					<?php esc_html_e( 'Drag and drop categories to set their display order. Order saves automatically.', 'itr-knowledgebase' ); ?>
				</p>

				<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
					<span id="itr-kb-order-status" style="font-size:13px;font-weight:500;"></span>
				</div>

				<?php if ( empty( $top_level ) ) : ?>
					<p><?php esc_html_e( 'No categories found.', 'itr-knowledgebase' ); ?></p>
				<?php else : ?>
					<div class="itr-kb-sortable-wrap">
						<?php $this->render_sortable_list( $top_level, 0 ); ?>
					</div>
				<?php endif; ?>

			</div>
		</div>

		<script>
		jQuery( function( $ ) {

			// Initialise sortable on all lists.
			$( '.itr-kb-sortable' ).sortable( {
				handle:      '.itr-kb-sortable__handle',
				placeholder: 'itr-kb-sortable__placeholder',
				tolerance:   'pointer',
				update: function() {
					saveAllOrders();
				},
			} );

			function saveAllOrders() {
				var orders = {};

				$( '.itr-kb-sortable' ).each( function() {
					var $list    = $( this );
					var parentId = parseInt( $list.data( 'parent' ), 10 ) || 0;
					var ids      = [];

					$list.find( '> li[data-term-id]' ).each( function() {
						ids.push( parseInt( $( this ).data( 'term-id' ), 10 ) );
					} );

					if ( ids.length ) {
						orders[ parentId ] = ids;
					}
				} );

				var $status = $( '#itr-kb-order-status' );
				$status.text( '<?php echo esc_js( __( 'Saving...', 'itr-knowledgebase' ) ); ?>' ).css( 'color', '#999' );

				var groups = Object.keys( orders );
				var done   = 0;

				if ( ! groups.length ) return;

				groups.forEach( function( parentId ) {
					$.post( ajaxurl, {
						action: 'itr_kb_category_order',
						nonce:  '<?php echo esc_js( $nonce ); ?>',
						order:  JSON.stringify( orders[ parentId ] ),
					}, function() {
						done++;
						if ( done === groups.length ) {
							$status.text( '<?php echo esc_js( __( 'Order saved!', 'itr-knowledgebase' ) ); ?>' ).css( 'color', '#00a32a' );
							setTimeout( function() { $status.text( '' ); }, 2000 );
						}
					} );
				} );
			}

		} );
		</script>
		<?php
	}

	/**
	 * Render a sortable list of categories recursively.
	 *
	 * @param array $terms  Array of WP_Term objects.
	 * @param int   $parent Parent term ID.
	 * @return void
	 */
	private function render_sortable_list( $terms, $parent ) {
		if ( empty( $terms ) ) {
			return;
		}
		?>
		<ul class="itr-kb-sortable" data-parent="<?php echo absint( $parent ); ?>" style="list-style:none;margin:0;padding:<?php echo $parent ? '0 0 0 28px' : '0'; ?>;">
			<?php foreach ( $terms as $term ) : ?>
				<?php
				$children = ITR_KB_Category_Order::get_ordered_categories( $term->term_id );
				$icon     = get_term_meta( $term->term_id, 'itr_kb_category_icon', true );
				$edit_url = admin_url( 'term.php?taxonomy=itr_kb_category&tag_ID=' . $term->term_id . '&post_type=itr_kb_article' );
				?>
				<li
					data-term-id="<?php echo absint( $term->term_id ); ?>"
					style="list-style:none;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:10px 14px;margin-bottom:6px;display:flex;align-items:center;gap:10px;cursor:default;user-select:none;"
				>
					<span
						class="itr-kb-sortable__handle"
						title="<?php esc_attr_e( 'Drag to reorder', 'itr-knowledgebase' ); ?>"
						style="cursor:grab;color:#c3c4c7;font-size:20px;line-height:1;flex-shrink:0;"
					>⠿</span>

					<?php if ( $icon ) : ?>
						<span class="dashicons <?php echo esc_attr( $icon ); ?>" style="color:#0073aa;flex-shrink:0;"></span>
					<?php else : ?>
						<span class="dashicons dashicons-category" style="color:#c3c4c7;flex-shrink:0;"></span>
					<?php endif; ?>

					<span style="flex:1;font-weight:500;font-size:14px;">
						<?php echo esc_html( $term->name ); ?>
					</span>

					<span style="color:#646970;font-size:12px;flex-shrink:0;">
						<?php printf( esc_html( _n( '%d article', '%d articles', $term->count, 'itr-knowledgebase' ) ), absint( $term->count ) ); ?>
					</span>

					<a href="<?php echo esc_url( $edit_url ); ?>" style="color:#0073aa;font-size:12px;flex-shrink:0;text-decoration:none;">
						<?php esc_html_e( 'Edit', 'itr-knowledgebase' ); ?>
					</a>
				</li>

				<?php if ( ! empty( $children ) ) : ?>
					<li style="list-style:none;margin-bottom:6px;" data-term-id="">
						<?php $this->render_sortable_list( $children, $term->term_id ); ?>
					</li>
				<?php endif; ?>

			<?php endforeach; ?>
		</ul>
		<?php
	}
}