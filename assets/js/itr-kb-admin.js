/**
 * ITR Knowledgebase — Admin Scripts
 *
 * @package ITR_Knowledgebase
 */

/* global itrKbAdmin, wp */

( function ( $ ) {
	'use strict';

	/**
	 * Media Uploader for Category Image.
	 */
	var ITR_KB_MediaUploader = {
		init: function () {
			$( document ).on( 'click', '.itr-kb-upload-image', function ( e ) {
				e.preventDefault();

				var $btn     = $( this );
				var $wrapper = $btn.closest( '.form-field, td' );
				var $input   = $wrapper.find( 'input[name="itr_kb_category_image"]' );
				var $preview = $wrapper.find( '.itr-kb-image-preview' );

				var frame = wp.media( {
					title:    itrKbAdmin.mediaTitle,
					button:   { text: itrKbAdmin.mediaButton },
					multiple: false,
					library:  { type: 'image' },
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					$input.val( attachment.id );
					$preview.html(
						'<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:150px;margin-top:10px;border-radius:4px;" />'
					);
				} );

				frame.open();
			} );
		},
	};

	/**
	 * Category Drag-Drop Ordering.
	 */
	var ITR_KB_CategoryOrder = {
		saveTimeout: null,

		init: function () {
			var self = this;

			if ( ! $( '.itr-kb-sortable' ).length ) {
				return;
			}

			$( '.itr-kb-sortable' ).sortable( {
				handle:      '.itr-kb-sortable__handle',
				placeholder: 'itr-kb-sortable__placeholder',
				tolerance:   'pointer',
				update: function () {
					clearTimeout( self.saveTimeout );
					self.saveTimeout = setTimeout( function () {
						self.saveOrder();
					}, 500 );
				},
			} );
		},

		saveOrder: function () {
			var order = [];

			$( '.itr-kb-sortable > li' ).each( function () {
				order.push( $( this ).data( 'term-id' ) );
			} );

			$.ajax( {
				url:    itrKbAdmin.ajaxUrl,
				method: 'POST',
				data:   {
					action: 'itr_kb_category_order',
					nonce:  itrKbAdmin.categoryOrderNonce,
					order:  JSON.stringify( order ),
				},
				success: function ( response ) {
					if ( response.success ) {
						ITR_KB_CategoryOrder.showSaved();
					}
				},
			} );
		},

		showSaved: function () {
			var $indicator = $( '.itr-kb-order-saved' );

			if ( ! $indicator.length ) {
				$indicator = $(
					'<span class="itr-kb-order-saved"><span class="dashicons dashicons-yes"></span> Saved</span>'
				).appendTo( '.itr-kb-sortable-wrap' );
			}

			$indicator.fadeIn( 200 );

			setTimeout( function () {
				$indicator.fadeOut( 600 );
			}, 2000 );
		},
	};

	/**
	 * Settings — Color Picker (native HTML5 used, no enhancement needed).
	 * Placeholder for future WP color picker integration if needed.
	 */
	var ITR_KB_Settings = {
		init: function () {
			// Tab persistence using URL hash.
			var hash = window.location.hash;
			if ( hash ) {
				$( '.nav-tab[href*="' + hash + '"]' ).addClass( 'nav-tab-active' ).siblings().removeClass( 'nav-tab-active' );
			}
		},
	};

	/**
	 * Import form — validate file type before submit.
	 */
	var ITR_KB_Import = {
		init: function () {
			$( 'form' ).on( 'submit', function () {
				var $fileInput = $( '#itr_kb_import_file' );

				if ( ! $fileInput.length || ! $fileInput.val() ) {
					return true;
				}

				var filename = $fileInput.val();
				var ext      = filename.split( '.' ).pop().toLowerCase();

				if ( 'json' !== ext ) {
					alert( itrKbAdmin.importError );
					return false;
				}

				return true;
			} );
		},
	};

	/**
	 * Meta box — Featured toggle visual feedback.
	 */
	var ITR_KB_MetaBox = {
		init: function () {
			$( '#itr_kb_featured' ).on( 'change', function () {
				var $label = $( this ).closest( 'label' );
				if ( $( this ).is( ':checked' ) ) {
					$label.css( 'color', '#d63638' );
				} else {
					$label.css( 'color', '' );
				}
			} ).trigger( 'change' );
		},
	};

	/**
	 * Styling page color pickers.
	 */
	var ITR_KB_StyleSettings = {
		init: function () {
			$( document ).on( 'input change', '.itr-kb-color-input', function () {
				var $wrap = $( this ).closest( '.itr-kb-styling-field__color-wrap' );
				$wrap.find( '.itr-kb-color-hex' ).val( $( this ).val().toUpperCase() );
			} );
			$( document ).on( 'input', '.itr-kb-color-hex', function () {
				var val = $( this ).val().trim();
				if ( /^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/.test( val ) ) {
					$( this ).closest( '.itr-kb-styling-field__color-wrap' ).find( '.itr-kb-color-input' ).val( val );
				}
			} );
			$( document ).on( 'click', '.itr-kb-color-reset', function () {
				var def  = $( this ).data( 'default' );
				var $wrap = $( this ).closest( '.itr-kb-styling-field__color-wrap' );
				$wrap.find( '.itr-kb-color-input' ).val( def );
				$wrap.find( '.itr-kb-color-hex' ).val( def.toUpperCase() );
			} );
			$( document ).on( 'click', '.itr-kb-reset-btn', function () {
				if ( ! window.confirm( $( this ).data( 'confirm' ) ) ) return;
				$( '.itr-kb-color-reset' ).trigger( 'click' );
			} );
		},
	};

	/**
	 * Inheritance Badge — Author & Reviewer meta box.
	 *
	 * Tracks when the admin changes the author dropdown or reviewer checkboxes
	 * and updates the status badge live. Also handles the "Clear Override" AJAX.
	 */
	var ITR_KB_InheritanceBadge = {

		init: function () {
			if ( ! $( '#itr_kb_author_id' ).length ) {
				return; // Not on an article edit screen.
			}

			this.bindAuthor();
			this.bindReviewer();
			this.bindClearOverride();
		},

		bindAuthor: function () {
			$( '#itr_kb_author_id' ).on( 'change', function () {
				// Tell PHP this field was manually changed.
				$( '#_itr_kb_author_changed' ).val( '1' );

				// Update badge immediately.
				var $badge    = $( '#itr-kb-author-status-badge' );
				var $clearBtn = $( '#itr-kb-author-clear-override' );

				$badge
					.text( itrKbAdmin.strings.manuallySet )
					.attr( 'data-status', 'manual' )
					.removeClass( 'itr-kb-status-badge--inherited itr-kb-status-badge--empty' )
					.addClass( 'itr-kb-status-badge--manual' )
					.show();

				$clearBtn.show();
			} );
		},

		bindReviewer: function () {
			$( document ).on( 'change', 'input[name="itr_kb_reviewer_ids[]"]', function () {
				$( '#_itr_kb_reviewer_changed' ).val( '1' );

				var $badge    = $( '#itr-kb-reviewer-status-badge' );
				var $clearBtn = $( '#itr-kb-reviewer-clear-override' );

				$badge
					.text( itrKbAdmin.strings.manuallySet )
					.attr( 'data-status', 'manual' )
					.removeClass( 'itr-kb-status-badge--inherited itr-kb-status-badge--empty' )
					.addClass( 'itr-kb-status-badge--manual' )
					.show();

				$clearBtn.show();
			} );
		},

		bindClearOverride: function () {
			$( document ).on( 'click', '.itr-kb-clear-override', function ( e ) {
				e.preventDefault();

				var $btn   = $( this );
				var field  = $btn.data( 'field' );
				var postId = $btn.data( 'post-id' );

				$btn.text( itrKbAdmin.strings.clearingOverride );

				$.ajax( {
					url:    itrKbAdmin.ajaxUrl,
					method: 'POST',
					data:   {
						action:  'itr_kb_clear_override',
						nonce:   itrKbAdmin.clearOverrideNonce,
						post_id: postId,
						field:   field,
					},
					success: function ( response ) {
						if ( ! response.success ) {
							$btn.text( 'Error' );
							return;
						}

						var data = response.data;

						if ( 'author' === field ) {
							// Update author dropdown.
							$( '#itr_kb_author_id' ).val( data.author_id || '' );

							// Reset changed flag so a subsequent save doesn't re-mark as manual.
							$( '#_itr_kb_author_changed' ).val( '0' );

							// Update badge.
							ITR_KB_InheritanceBadge.updateBadge(
								'#itr-kb-author-status-badge',
								data.status,
								data.badge_text
							);

							$btn.hide();

						} else {
							// Update reviewer checkboxes.
							$( 'input[name="itr_kb_reviewer_ids[]"]' ).prop( 'checked', false );
							$.each( data.reviewer_ids, function ( i, id ) {
								$( 'input[name="itr_kb_reviewer_ids[]"][value="' + id + '"]' ).prop( 'checked', true );
							} );

							$( '#_itr_kb_reviewer_changed' ).val( '0' );

							ITR_KB_InheritanceBadge.updateBadge(
								'#itr-kb-reviewer-status-badge',
								data.status,
								data.badge_text
							);

							$btn.hide();
						}
					},
					error: function () {
						$btn.text( 'Error — try again' );
					},
				} );
			} );
		},

		/**
		 * Update a status badge element.
		 *
		 * @param {string} selector  jQuery selector for the badge span.
		 * @param {string} status    'manual' | 'inherited' | 'empty'.
		 * @param {string} badgeText Human-readable text to display.
		 */
		updateBadge: function ( selector, status, badgeText ) {
			var $badge = $( selector );
			$badge
				.removeClass( 'itr-kb-status-badge--manual itr-kb-status-badge--inherited itr-kb-status-badge--empty' )
				.addClass( 'itr-kb-status-badge--' + status )
				.attr( 'data-status', status );

			if ( badgeText ) {
				$badge.text( badgeText ).show();
			} else {
				$badge.text( '' ).hide();
			}
		},
	};

	/**
	 * Bootstrap all modules on DOM ready.
	 */
	$( function () {
		ITR_KB_MediaUploader.init();
		ITR_KB_CategoryOrder.init();
		ITR_KB_Settings.init();
		ITR_KB_Import.init();
		ITR_KB_MetaBox.init();
		ITR_KB_InheritanceBadge.init();
		// Font family selector — show/hide custom input.
		$( document ).on( 'change', '.itr-kb-font-select', function () {
			var $wrap  = $( this ).closest( '.itr-kb-font-wrap' );
			var $input = $wrap.find( '.itr-kb-font-custom' );
			var val    = $( this ).val();

			if ( val === 'custom' ) {
				$input.show().focus();
				$input.val( '' );
			} else {
				$input.hide().val( val );
			}
		} );

		ITR_KB_StyleSettings.init();
	} );

} )( jQuery );