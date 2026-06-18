<?php
/**
 * Admin menu registration.
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
 * Class ITR_KB_Menu
 *
 * Registers all admin menu pages for the plugin.
 */
class ITR_KB_Menu {

	/**
	 * Register all admin menus.
	 *
	 * @return void
	 */
	public function register_menus() {
		// Import / Export page under KB Articles.
		add_submenu_page(
			'edit.php?post_type=itr_kb_article',
			esc_html__( 'Import / Export', 'itr-knowledgebase' ),
			esc_html__( 'Import / Export', 'itr-knowledgebase' ),
			'manage_itr_kb_categories',
			'itr-kb-import-export',
			array( $this, 'render_import_export_page' )
		);

		// Settings page under KB Articles.
		add_submenu_page(
			'edit.php?post_type=itr_kb_article',
			esc_html__( 'Settings', 'itr-knowledgebase' ),
			esc_html__( 'Settings', 'itr-knowledgebase' ),
			'manage_options',
			'itr-kb-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the Import / Export page.
	 *
	 * @return void
	 */
	public function render_import_export_page() {
		if ( ! current_user_can( 'manage_itr_kb_categories' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'itr-knowledgebase' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'export'; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="wrap itr-kb-wrap">
			<h1><?php esc_html_e( 'Import / Export', 'itr-knowledgebase' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a
					href="<?php echo esc_url( add_query_arg( 'tab', 'export', admin_url( 'edit.php?post_type=itr_kb_article&page=itr-kb-import-export' ) ) ); ?>"
					class="nav-tab <?php echo 'export' === $active_tab ? 'nav-tab-active' : ''; ?>"
				>
					<?php esc_html_e( 'Export', 'itr-knowledgebase' ); ?>
				</a>
				<a
					href="<?php echo esc_url( add_query_arg( 'tab', 'import', admin_url( 'edit.php?post_type=itr_kb_article&page=itr-kb-import-export' ) ) ); ?>"
					class="nav-tab <?php echo 'import' === $active_tab ? 'nav-tab-active' : ''; ?>"
				>
					<?php esc_html_e( 'Import', 'itr-knowledgebase' ); ?>
				</a>
			</nav>

			<div class="itr-kb-tab-content">
				<?php if ( 'export' === $active_tab ) : ?>
					<?php $this->render_export_tab(); ?>
				<?php else : ?>
					<?php $this->render_import_tab(); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Export tab content.
	 *
	 * @return void
	 */
	private function render_export_tab() {
		?>
		<div class="itr-kb-card">
			<h2><?php esc_html_e( 'Export Knowledge Base', 'itr-knowledgebase' ); ?></h2>
			<p><?php esc_html_e( 'Export all KB articles, categories, tags, and author data as a JSON file.', 'itr-knowledgebase' ); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field( 'itr_kb_export', 'itr_kb_export_nonce' ); ?>
				<input type="hidden" name="itr_kb_action" value="export" />

				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Include', 'itr-knowledgebase' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="export_articles" value="1" checked />
								<?php esc_html_e( 'Articles', 'itr-knowledgebase' ); ?>
							</label><br />
							<label>
								<input type="checkbox" name="export_categories" value="1" checked />
								<?php esc_html_e( 'Categories', 'itr-knowledgebase' ); ?>
							</label><br />
							<label>
								<input type="checkbox" name="export_tags" value="1" checked />
								<?php esc_html_e( 'Tags', 'itr-knowledgebase' ); ?>
							</label><br />
							<label>
								<input type="checkbox" name="export_authors" value="1" checked />
								<?php esc_html_e( 'Authors & Reviewers', 'itr-knowledgebase' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Download Export File', 'itr-knowledgebase' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the Import tab content.
	 *
	 * @return void
	/**
	 * Render the Import tab.
	 *
	 * The file upload is handled entirely by JavaScript (chunked upload).
	 * No server-side redirect is needed for the upload step — the full flow
	 * (upload → prepare → import) runs in the browser without page reloads.
	 */
	private function render_import_tab() {
		$chunk_nonce  = wp_create_nonce( 'itr_kb_import_chunk' );
		$upload_nonce = wp_create_nonce( 'itr_kb_upload_chunk' );
		?>
		<div class="itr-kb-card">
			<h2><?php esc_html_e( 'Import Knowledge Base', 'itr-knowledgebase' ); ?></h2>
			<p><?php esc_html_e( 'Import articles, categories, tags, and authors from a JSON file. Files up to 200 MB are supported — the file is uploaded in 5 MB chunks and imported in batches of 50.', 'itr-knowledgebase' ); ?></p>

			<!-- FILE PICKER -->
			<table class="form-table" id="itr-kb-upload-form-table">
				<tr>
					<th>
						<label for="itr_kb_import_file_js"><?php esc_html_e( 'Import File (.json)', 'itr-knowledgebase' ); ?></label>
					</th>
					<td>
						<input type="file" id="itr_kb_import_file_js" accept=".json" />
						<p class="description" id="itr-kb-file-info" style="margin-top:4px;color:#444;"></p>
						<p class="description">
							<?php esc_html_e( 'Only .json files exported from ITR Knowledgebase or the Echo KB Exporter plugin are supported.', 'itr-knowledgebase' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Options', 'itr-knowledgebase' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="itr_kb_import_overwrite" value="1" />
							<?php esc_html_e( 'Overwrite existing articles with the same title', 'itr-knowledgebase' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<!-- ERROR NOTICE (shown on validation failure) -->
			<div id="itr-kb-upload-error" style="display:none;" class="notice notice-error inline">
				<p id="itr-kb-upload-error-msg"></p>
			</div>

			<!-- PHASE LABELS -->
			<div style="display:flex;gap:8px;margin:16px 0;flex-wrap:wrap;" id="itr-kb-phase-labels">
				<span id="itr-kb-phase-upload"  style="padding:4px 12px;background:#f0f0f1;border-radius:20px;font-size:12px;font-weight:600;color:#767676;"><?php esc_html_e( 'Upload', 'itr-knowledgebase' ); ?></span>
				<span id="itr-kb-phase-prepare" style="padding:4px 12px;background:#f0f0f1;border-radius:20px;font-size:12px;font-weight:600;color:#767676;"><?php esc_html_e( 'Prepare', 'itr-knowledgebase' ); ?></span>
				<span id="itr-kb-stage-categories" style="padding:4px 12px;background:#f0f0f1;border-radius:20px;font-size:12px;font-weight:600;color:#767676;"><?php esc_html_e( 'Categories', 'itr-knowledgebase' ); ?></span>
				<span id="itr-kb-stage-tags"       style="padding:4px 12px;background:#f0f0f1;border-radius:20px;font-size:12px;font-weight:600;color:#767676;"><?php esc_html_e( 'Tags', 'itr-knowledgebase' ); ?></span>
				<span id="itr-kb-stage-authors"    style="padding:4px 12px;background:#f0f0f1;border-radius:20px;font-size:12px;font-weight:600;color:#767676;"><?php esc_html_e( 'Authors', 'itr-knowledgebase' ); ?></span>
				<span id="itr-kb-stage-articles"   style="padding:4px 12px;background:#f0f0f1;border-radius:20px;font-size:12px;font-weight:600;color:#767676;"><?php esc_html_e( 'Articles', 'itr-knowledgebase' ); ?></span>
			</div>

			<!-- PROGRESS BAR (hidden until started) -->
			<div id="itr-kb-progress-wrap" style="display:none;margin-bottom:16px;">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
					<span id="itr-kb-progress-stage" style="font-size:13px;font-weight:600;color:#1a237e;"></span>
					<span id="itr-kb-progress-count" style="font-size:13px;color:#444;"></span>
				</div>
				<div style="background:#e0e0e0;border-radius:6px;height:14px;overflow:hidden;">
					<div id="itr-kb-progress-bar" style="background:#1a237e;height:100%;width:0%;transition:width .3s ease;border-radius:6px;"></div>
				</div>
				<div id="itr-kb-overall-count" style="font-size:12px;color:#767676;margin-top:6px;"></div>
			</div>

			<!-- LOG -->
			<div id="itr-kb-import-log" style="display:none;background:#1d2327;color:#f0f0f1;font-family:monospace;font-size:12px;padding:12px;border-radius:6px;max-height:200px;overflow-y:auto;margin-bottom:16px;"></div>

			<!-- BUTTONS -->
			<button id="itr-kb-import-start-btn" class="button button-primary" style="font-size:14px;height:38px;line-height:36px;padding:0 24px;" disabled>
				<?php esc_html_e( '↑ Upload &amp; Import', 'itr-knowledgebase' ); ?>
			</button>
			<button id="itr-kb-import-cancel-btn" class="button" style="margin-left:8px;display:none;">
				<?php esc_html_e( 'Cancel', 'itr-knowledgebase' ); ?>
			</button>
			<span id="itr-kb-import-done-msg" style="display:none;margin-left:12px;color:#2e7d32;font-weight:600;font-size:14px;">
				&#10003; <?php esc_html_e( 'Import complete!', 'itr-knowledgebase' ); ?>
			</span>
		</div>

		<script>
		( function () {
			'use strict';

			var UPLOAD_CHUNK_SIZE = 5 * 1024 * 1024; // 5 MB per upload chunk
			var ajaxUrl           = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var chunkNonce        = <?php echo wp_json_encode( $chunk_nonce ); ?>;
			var uploadNonce       = <?php echo wp_json_encode( $upload_nonce ); ?>;
			var stages            = [ 'categories', 'tags', 'authors', 'articles' ];
			var stageLabels       = { categories: '<?php esc_html_e( 'Categories', 'itr-knowledgebase' ); ?>', tags: '<?php esc_html_e( 'Tags', 'itr-knowledgebase' ); ?>', authors: '<?php esc_html_e( 'Authors', 'itr-knowledgebase' ); ?>', articles: '<?php esc_html_e( 'Articles', 'itr-knowledgebase' ); ?>' };

			var fileInput    = document.getElementById( 'itr_kb_import_file_js' );
			var startBtn     = document.getElementById( 'itr-kb-import-start-btn' );
			var cancelBtn    = document.getElementById( 'itr-kb-import-cancel-btn' );
			var fileInfoEl   = document.getElementById( 'itr-kb-file-info' );
			var errorWrap    = document.getElementById( 'itr-kb-upload-error' );
			var errorMsg     = document.getElementById( 'itr-kb-upload-error-msg' );

			var running    = false;
			var sessionId  = '';

			// ── File selected ────────────────────────────────────────────────
			fileInput.addEventListener( 'change', function () {
				var file = fileInput.files[0];
				if ( ! file ) {
					startBtn.disabled = true;
					fileInfoEl.textContent = '';
					return;
				}
				var sizeMB = ( file.size / ( 1024 * 1024 ) ).toFixed( 1 );
				fileInfoEl.textContent = file.name + '  (' + sizeMB + ' MB)';
				startBtn.disabled = false;
				errorWrap.style.display = 'none';
			} );

			// ── Start button ─────────────────────────────────────────────────
			startBtn.addEventListener( 'click', function () {
				if ( running ) return;
				var file = fileInput.files[0];
				if ( ! file ) return;

				running = true;
				startBtn.disabled = true;
				cancelBtn.style.display = 'inline-block';
				document.getElementById( 'itr-kb-progress-wrap' ).style.display = 'block';
				document.getElementById( 'itr-kb-import-log' ).style.display    = 'block';

				// ── PHASE 1: Upload file in chunks ───────────────────────────
				setPhaseActive( 'upload' );
				log( '⏳ Uploading file (' + ( file.size / ( 1024 * 1024 ) ).toFixed( 1 ) + ' MB) in ' + Math.ceil( file.size / UPLOAD_CHUNK_SIZE ) + ' chunks...' );
				uploadFile( file, 0, '', function ( sid ) {
					sessionId = sid;
					setPhaseComplete( 'upload' );
					log( '✓ Upload complete. Starting import preparation...' );

					// ── PHASE 2: Prepare (parse + split into stage files) ────
					setPhaseActive( 'prepare' );
					setProgress( 'Preparing...', 0, 0 );
					post( 'itr_kb_import_prepare', { session_id: sessionId, nonce: chunkNonce }, function ( data ) {
						if ( ! data.success ) {
							log( '❌ Prepare failed: ' + ( data.data ? data.data.message : 'Unknown error' ) );
							running = false;
							return;
						}
						var totals = data.data.totals;
						setPhaseComplete( 'prepare' );
						log( '✓ File parsed — Categories: ' + ( totals.categories || 0 ) + ', Tags: ' + ( totals.tags || 0 ) + ', Authors: ' + ( totals.authors || 0 ) + ', Articles: ' + ( totals.articles || 0 ) );
						log( 'Starting chunked import...' );

						// ── PHASE 3: Import stages ───────────────────────────
						runStage( 'categories', 0, totals, 0 );
					}, function () {
						log( '❌ Network error during prepare. Please try again.' );
						running = false;
					} );
				} );
			} );

			// ── Cancel button ────────────────────────────────────────────────
			cancelBtn.addEventListener( 'click', function () {
				if ( ! confirm( '<?php esc_html_e( 'Cancel the import? Partially imported data will remain.', 'itr-knowledgebase' ); ?>' ) ) return;
				running = false;
				if ( sessionId ) {
					post( 'itr_kb_import_cancel', { session_id: sessionId, nonce: chunkNonce }, function () {
						log( '⚠ Import cancelled.' );
						cancelBtn.style.display = 'none';
					} );
				} else {
					log( '⚠ Upload cancelled.' );
					cancelBtn.style.display = 'none';
				}
			} );

			// ── Upload file in 5 MB chunks ───────────────────────────────────
			function uploadFile( file, chunkIndex, sid, onComplete ) {
				if ( ! running ) return;

				var totalChunks = Math.ceil( file.size / UPLOAD_CHUNK_SIZE );
				var start       = chunkIndex * UPLOAD_CHUNK_SIZE;
				var end         = Math.min( start + UPLOAD_CHUNK_SIZE, file.size );
				var chunk       = file.slice( start, end );
				var overwrite   = document.getElementById( 'itr_kb_import_overwrite' ).checked ? '1' : '0';

				var pct = Math.round( ( chunkIndex / totalChunks ) * 100 );
				setProgress(
					'<?php esc_html_e( 'Uploading', 'itr-knowledgebase' ); ?>',
					pct,
					( start / ( 1024 * 1024 ) ).toFixed( 1 ) + ' MB / ' + ( file.size / ( 1024 * 1024 ) ).toFixed( 1 ) + ' MB'
				);

				var formData = new FormData();
				formData.append( 'action',       'itr_kb_upload_chunk' );
				formData.append( 'nonce',        uploadNonce );
				formData.append( 'chunk',        chunk, file.name );
				formData.append( 'chunk_index',  chunkIndex );
				formData.append( 'total_chunks', totalChunks );
				formData.append( 'session_id',   sid );
				formData.append( 'filename',     file.name );
				formData.append( 'overwrite',    overwrite );

				fetch( ajaxUrl, { method: 'POST', body: formData } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( data ) {
						if ( ! data.success ) {
							log( '❌ Upload error on chunk ' + ( chunkIndex + 1 ) + ': ' + ( data.data ? data.data.message : 'Unknown' ) );
							running = false;
							return;
						}
						var newSid = data.data.session_id || sid;
						if ( 'ready' === data.data.status ) {
							// All chunks uploaded successfully.
							setProgress( '<?php esc_html_e( 'Uploading', 'itr-knowledgebase' ); ?>', 100, file.size / ( 1024 * 1024 ) + ' MB / ' + file.size / ( 1024 * 1024 ) + ' MB' );
							onComplete( newSid );
						} else {
							// Upload next chunk.
							uploadFile( file, chunkIndex + 1, newSid, onComplete );
						}
					} )
					.catch( function () {
						// Retry this chunk once on network error.
						log( '⚠ Network error on chunk ' + ( chunkIndex + 1 ) + ' — retrying...' );
						setTimeout( function () { uploadFile( file, chunkIndex, sid, onComplete ); }, 2000 );
					} );
			}

			// ── Run one import stage ─────────────────────────────────────────
			function runStage( stage, offset, totals, chunkIndex ) {
				if ( ! running ) return;
				chunkIndex = chunkIndex || 0;

				setStageActive( stage );

				var params = {
					session_id:  sessionId,
					stage:       stage,
					offset:      offset,
					chunk_index: chunkIndex,
					nonce:       chunkNonce,
				};

				post( 'itr_kb_import_chunk', params, function ( data ) {
					if ( ! data.success ) {
						log( '❌ Error on ' + stage + ': ' + ( data.data ? data.data.message : 'Unknown' ) );
						setTimeout( function () { runStage( stage, offset, totals, chunkIndex ); }, 3000 );
						return;
					}

					var r   = data.data;
					var pct = r.total > 0 ? Math.round( ( r.done / r.total ) * 100 ) : 100;
					setProgress( stageLabels[ stage ], pct, r.done.toLocaleString() + ' / ' + r.total.toLocaleString() );

					if ( ! r.finished ) {
						var nextChunk = r.chunk_index !== undefined ? r.chunk_index : chunkIndex;
						setTimeout( function () { runStage( stage, r.offset, totals, nextChunk ); }, 200 );
						return;
					}

					// Stage finished.
					setStageComplete( stage );
					if ( r.total > 0 ) {
						log( '✓ ' + stageLabels[ stage ] + ': ' + r.done.toLocaleString() + ' processed' );
					}

					var idx  = stages.indexOf( stage );
					var next = stages[ idx + 1 ] || null;

					if ( next && running ) {
						document.getElementById( 'itr-kb-progress-bar' ).style.width = '0%';
						setTimeout( function () { runStage( next, 0, totals, 0 ); }, 300 );
					} else {
						document.getElementById( 'itr-kb-progress-bar' ).style.width     = '100%';
						document.getElementById( 'itr-kb-progress-bar' ).style.background = '#2e7d32';
						document.getElementById( 'itr-kb-progress-count' ).textContent    = '<?php esc_html_e( 'Complete!', 'itr-knowledgebase' ); ?>';
						document.getElementById( 'itr-kb-overall-count' ).textContent     = '';
						document.getElementById( 'itr-kb-import-done-msg' ).style.display  = 'inline';
						cancelBtn.style.display = 'none';
						log( '✅ Import complete! All data has been imported successfully.' );
						running = false;
					}
				}, function () {
					log( '⚠ Network error — retrying in 3s...' );
					setTimeout( function () { runStage( stage, offset, totals, chunkIndex ); }, 3000 );
				} );
			}

			// ── Helpers ──────────────────────────────────────────────────────
			function post( action, params, onSuccess, onError ) {
				var body = new URLSearchParams( Object.assign( { action: action }, params ) );
				fetch( ajaxUrl, {
					method:  'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body:    body.toString(),
				} )
					.then( function ( r ) { return r.json(); } )
					.then( onSuccess )
					.catch( onError || function () {} );
			}

			function log( msg ) {
				var el = document.getElementById( 'itr-kb-import-log' );
				el.innerHTML += msg + '\n';
				el.scrollTop  = el.scrollHeight;
			}

			function setProgress( label, pct, countText ) {
				document.getElementById( 'itr-kb-progress-stage' ).textContent = label;
				document.getElementById( 'itr-kb-progress-bar' ).style.width   = pct + '%';
				document.getElementById( 'itr-kb-progress-count' ).textContent = countText || '';
			}

			function setPhaseActive( phase ) {
				var el = document.getElementById( 'itr-kb-phase-' + phase );
				if ( el ) { el.style.background = '#e8eaf6'; el.style.color = '#1a237e'; }
			}

			function setPhaseComplete( phase ) {
				var label = phase.charAt(0).toUpperCase() + phase.slice(1);
				var el = document.getElementById( 'itr-kb-phase-' + phase );
				if ( el ) { el.style.background = '#e8f5e9'; el.style.color = '#2e7d32'; el.textContent = '✓ ' + label; }
			}

			function setStageActive( stage ) {
				var el = document.getElementById( 'itr-kb-stage-' + stage );
				if ( el ) { el.style.background = '#e8eaf6'; el.style.color = '#1a237e'; }
			}

			function setStageComplete( stage ) {
				var el = document.getElementById( 'itr-kb-stage-' + stage );
				if ( el ) { el.style.background = '#e8f5e9'; el.style.color = '#2e7d32'; el.textContent = '✓ ' + stageLabels[ stage ]; }
			}
		} )();
		</script>
		<?php
	}

	/**
	 * Render the Settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'itr-knowledgebase' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification

		$tabs = array(
			'general'   => __( 'General', 'itr-knowledgebase' ),
			'permalink' => __( 'Permalink', 'itr-knowledgebase' ),
			'search'    => __( 'Search', 'itr-knowledgebase' ),
			'styling'   => __( 'Styling', 'itr-knowledgebase' ),
			'shortcodes' => __( 'Shortcodes', 'itr-knowledgebase' ),
		);
		?>
		<div class="wrap itr-kb-wrap">
			<h1><?php esc_html_e( 'ITR Knowledgebase Settings', 'itr-knowledgebase' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<a
						href="<?php echo esc_url( add_query_arg( array( 'page' => 'itr-kb-settings', 'tab' => $tab_key ), admin_url( 'edit.php?post_type=itr_kb_article' ) ) ); ?>"
						class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
					>
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="itr-kb-tab-content">
				<?php if ( 'styling' === $active_tab ) : ?>
					<?php
					$settings = new \ITR_Knowledgebase\Admin\ITR_KB_Settings();
					$settings->render_styling_page();
					?>
				<?php elseif ( 'shortcodes' === $active_tab ) : ?>
					<?php \ITR_Knowledgebase\Admin\ITR_KB_Menu::render_shortcodes_tab(); ?>
				<?php else : ?>
					<?php
					$settings = new \ITR_Knowledgebase\Admin\ITR_KB_Settings();
					$settings->maybe_handle_tab_save( $active_tab );
					settings_errors( 'itr_kb_' . $active_tab );
					?>
					<form method="post" action="">
						<?php wp_nonce_field( 'itr_kb_save_' . $active_tab, 'itr_kb_tab_nonce' ); ?>
						<input type="hidden" name="itr_kb_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
						<?php
						do_settings_sections( 'itr_kb_settings_' . $active_tab );
						submit_button( __( 'Save Changes', 'itr-knowledgebase' ) );
						?>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	/**
	 * Render the Shortcodes reference tab.
	 */
	public static function render_shortcodes_tab() {
		$shortcodes = array(
			array(
				'code'       => '[itr_kb_meta]',
				'desc'       => 'Displays the article meta row: Posted date, Updated date, Author name, Reviewer name(s). Each field is individually toggleable via attributes.',
				'attributes' => array(
					'show_posted="yes|no"'   => 'Show or hide the posted date. Default: yes',
					'show_updated="yes|no"'  => 'Show or hide the last updated date. Default: yes',
					'show_author="yes|no"'   => 'Show or hide the author name. Default: yes',
					'show_reviewer="yes|no"' => 'Show or hide reviewer name(s). Default: yes',
				),
				'examples' => array( '[itr_kb_meta]', '[itr_kb_meta show_updated="no"]', '[itr_kb_meta show_reviewer="no" show_updated="no"]' ),
			),
			array(
				'code'       => '[itr_kb_share]',
				'desc'       => 'Displays the Share dropdown button only (Twitter/X, Facebook, LinkedIn, WhatsApp, Email, Copy Link). Use separately from Print and PDF.',
				'attributes' => array(),
				'examples' => array( '[itr_kb_share]' ),
			),
			array(
				'code'       => '[itr_kb_print]',
				'desc'       => 'Displays the Print button only. Opens the browser print dialog. Supports icon-only mode and custom image icons.',
				'attributes' => array(
					'hide_text="yes"'        => 'Hide the button label, show icon only. Default: no',
					'label="text"'           => 'Custom button label. Default: Print',
					'custom_icon_id="123"'   => 'Media library image ID to use as the icon instead of the default dashicon.',
				),
				'examples' => array( '[itr_kb_print]', '[itr_kb_print hide_text="yes"]', '[itr_kb_print hide_text="yes" custom_icon_id="42"]' ),
			),
			array(
				'code'       => '[itr_kb_pdf]',
				'desc'       => 'Displays the PDF Download button only. Supports icon-only mode and custom image icons.',
				'attributes' => array(
					'hide_text="yes"'        => 'Hide the button label, show icon only. Default: no',
					'label="text"'           => 'Custom button label. Default: PDF',
					'custom_icon_id="123"'   => 'Media library image ID to use as the icon instead of the default dashicon.',
				),
				'examples' => array( '[itr_kb_pdf]', '[itr_kb_pdf hide_text="yes"]', '[itr_kb_pdf hide_text="yes" custom_icon_id="42"]' ),
			),
			array(
				'code'       => '[itr_kb_author_name]',
				'desc'       => 'Outputs the KB author name as plain text. Useful inside your own HTML in an Elementor HTML widget.',
				'attributes' => array(
					'before="text"' => 'Text to prepend before the name. Default: empty',
					'after="text"'  => 'Text to append after the name. Default: empty',
				),
				'examples' => array( '[itr_kb_author_name]', '[itr_kb_author_name before="Written by: "]' ),
			),
			array(
				'code'       => '[itr_kb_reviewer_names]',
				'desc'       => 'Outputs reviewer name(s) as plain text, comma-separated when multiple.',
				'attributes' => array(
					'separator="text"' => 'Separator between multiple names. Default: ", "',
					'before="text"'    => 'Text to prepend. Default: empty',
					'after="text"'     => 'Text to append. Default: empty',
				),
				'examples' => array( '[itr_kb_reviewer_names]', '[itr_kb_reviewer_names before="Reviewed by: "]' ),
			),
			array(
				'code'       => '[itr_kb_nav]',
				'desc'       => 'Renders the full Previous / Next article navigation bar (both directions at once). Place at the bottom of your single article Elementor template.',
				'attributes' => array(),
				'examples'   => array( '[itr_kb_nav]' ),
			),
			array(
				'code'       => '[itr_kb_prev]',
				'desc'       => 'Renders only the Previous article link. Use when you want to place Prev and Next in separate Elementor columns.',
				'attributes' => array(
					'label="text"' => 'Label prefix shown above the article title. Default: &#8592; Previous Article',
				),
				'examples'   => array( '[itr_kb_prev]', '[itr_kb_prev label="&larr; Back"]' ),
			),
			array(
				'code'       => '[itr_kb_next]',
				'desc'       => 'Renders only the Next article link. Use when you want to place Prev and Next in separate Elementor columns.',
				'attributes' => array(
					'label="text"' => 'Label suffix shown above the article title. Default: Next Article &#8594;',
				),
				'examples'   => array( '[itr_kb_next]', '[itr_kb_next label="Keep Reading &rarr;"]' ),
			),
		);
		?>
		<div style="max-width:820px;padding-top:16px;">
			<p style="color:#50575e;font-size:14px;margin-bottom:24px;">
				<?php esc_html_e( 'Use these shortcodes in Elementor&#39;s Shortcode widget to build your single article template. Click any shortcode or example to copy it to your clipboard.', 'itr-knowledgebase' ); ?>
			</p>
			<?php foreach ( $shortcodes as $sc ) : ?>
			<div style="background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:20px 24px;margin-bottom:20px;">
				<div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
					<code class="itr-kb-sc-copy" style="font-size:14px;font-weight:600;background:#f0f4ff;color:#185fa5;padding:5px 12px;border-radius:4px;cursor:pointer;border:1px solid #c9d8f7;" title="Click to copy"><?php echo esc_html( $sc['code'] ); ?></code>
					<span class="itr-kb-sc-copied" style="font-size:12px;color:#0f6e56;display:none;">&#10003; Copied!</span>
				</div>
				<p style="margin:0 0 14px;color:#3c434a;font-size:13px;"><?php echo esc_html( $sc['desc'] ); ?></p>
				<?php if ( ! empty( $sc['attributes'] ) ) : ?>
				<details style="margin-bottom:12px;">
					<summary style="cursor:pointer;font-size:12px;font-weight:500;color:#50575e;"><?php esc_html_e( 'Available attributes', 'itr-knowledgebase' ); ?></summary>
					<table style="width:100%;border-collapse:collapse;margin-top:8px;font-size:12px;">
						<thead><tr style="background:#f6f7f7;">
							<th style="text-align:left;padding:7px 10px;border:1px solid #dcdcde;"><?php esc_html_e( 'Attribute', 'itr-knowledgebase' ); ?></th>
							<th style="text-align:left;padding:7px 10px;border:1px solid #dcdcde;"><?php esc_html_e( 'Description', 'itr-knowledgebase' ); ?></th>
						</tr></thead>
						<tbody>
						<?php foreach ( $sc['attributes'] as $attr => $desc ) : ?>
						<tr>
							<td style="padding:7px 10px;border:1px solid #dcdcde;font-family:monospace;color:#185fa5;white-space:nowrap;"><?php echo esc_html( $attr ); ?></td>
							<td style="padding:7px 10px;border:1px solid #dcdcde;color:#50575e;"><?php echo esc_html( $desc ); ?></td>
						</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</details>
				<?php endif; ?>
				<?php if ( ! empty( $sc['examples'] ) ) : ?>
				<p style="font-size:12px;font-weight:500;color:#50575e;margin:0 0 6px;"><?php esc_html_e( 'Examples — click to copy:', 'itr-knowledgebase' ); ?></p>
				<?php foreach ( $sc['examples'] as $ex ) : ?>
					<code class="itr-kb-sc-copy" style="display:block;margin-bottom:5px;font-size:12px;background:#f6f7f7;color:#3c434a;padding:5px 10px;border-radius:3px;cursor:pointer;border:1px solid #dcdcde;"><?php echo esc_html( $ex ); ?></code>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<script>
		document.querySelectorAll('.itr-kb-sc-copy').forEach(function(el){
			el.addEventListener('click',function(){
				var text=el.textContent.trim();
				navigator.clipboard.writeText(text).then(function(){
					var c=el.parentNode.querySelector('.itr-kb-sc-copied');
					if(c){c.style.display='inline';setTimeout(function(){c.style.display='none';},2000);}
				}).catch(function(){
					var r=document.createRange();r.selectNodeContents(el);
					window.getSelection().removeAllRanges();window.getSelection().addRange(r);
				});
			});
		});
		</script>
		<?php
	}

}