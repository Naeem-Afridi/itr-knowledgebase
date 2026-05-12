/**
 * ITR Knowledgebase — Frontend Scripts
 *
 * @package ITR_Knowledgebase
 */

/* global itrKbFrontend */

( function () {
	'use strict';

	/* =========================================================================
	   Utility helpers
	   ========================================================================= */

	/**
	 * Return the pixel offset needed to clear any fixed/sticky header at the top
	 * of the viewport, including Elementor Motion Effects sticky sections.
	 *
	 * Uses rect.bottom so headers with a CSS top > 0 are handled correctly.
	 *
	 * @returns {number} Scroll offset in pixels.
	 */
	function getStickyOffset() {
		var offset = 0;

		// Elementor sticky (Motion Effects) — active class is the reliable signal.
		document.querySelectorAll( '.elementor-sticky--active' ).forEach( function ( el ) {
			var rect = el.getBoundingClientRect();
			if ( rect.height > 0 ) {
				offset = Math.max( offset, Math.round( rect.bottom ) );
			}
		} );

		// Generic: common header selectors + direct body children/grandchildren.
		var candidates = Array.from( document.querySelectorAll(
			'header, #masthead, #site-header, .site-header, ' +
			'.sticky-header, .fixed-header, [data-sticky], .is-sticky, ' +
			'body > *, body > * > *'
		) );

		candidates.forEach( function ( el ) {
			var style = window.getComputedStyle( el );
			var pos   = style.position;
			if ( pos === 'fixed' || pos === 'sticky' ) {
				var rect = el.getBoundingClientRect();
				// Only count elements whose top edge is within the top 60 px of the viewport.
				if ( rect.top >= 0 && rect.top <= 60 && rect.height > 0 ) {
					offset = Math.max( offset, Math.round( rect.bottom ) );
				}
			}
		} );

		return offset + 20; // breathing room
	}


	/**
	 * Debounce a function call.
	 *
	 * @param {Function} fn    Function to debounce.
	 * @param {number}   delay Delay in ms.
	 * @returns {Function}
	 */
	function debounce( fn, delay ) {
		var timer;
		return function () {
			var args    = arguments;
			var context = this;
			clearTimeout( timer );
			timer = setTimeout( function () {
				fn.apply( context, args );
			}, delay );
		};
	}

	/**
	 * Make a fetch request to the WP REST API with abort support.
	 *
	 * @param {string} endpoint REST endpoint path.
	 * @param {Object} params   Query parameters.
	 * @param {AbortSignal} signal Abort signal.
	 * @returns {Promise}
	 */
	function restFetch( endpoint, params, signal ) {
		var url = new URL( itrKbFrontend.restUrl + endpoint, window.location.origin );
		Object.keys( params ).forEach( function ( key ) {
			url.searchParams.append( key, params[ key ] );
		} );

		return fetch( url.toString(), {
			headers: { 'X-WP-Nonce': itrKbFrontend.restNonce },
			signal:  signal || null,
		} ).then( function ( res ) {
			if ( ! res.ok ) throw new Error( 'Network error' );
			return res.json();
		} );
	}

	/* =========================================================================
	   Live Search
	   ========================================================================= */

	var ITR_KB_Search = {
		abortController: null,
		lastKeyword:     '',

		init: function () {
			if ( ! itrKbFrontend.searchEnabled ) return;

			var self = this;

			document.querySelectorAll( '.itr-kb-search-bar' ).forEach( function ( bar ) {
				self.bindBar( bar );
			} );
		},

		bindBar: function ( bar ) {
			var self    = this;
			var input   = bar.querySelector( '.itr-kb-search-bar__input' );
			var results = bar.querySelector( '.itr-kb-search-bar__results' );
			var list    = bar.querySelector( '.itr-kb-search-bar__results-list' );
			var footer  = bar.querySelector( '.itr-kb-search-bar__results-footer' );
			var viewAll = bar.querySelector( '.itr-kb-search-bar__view-all' );
			var count   = parseInt( bar.dataset.resultsCount, 10 ) || 5;

			if ( ! input || ! results ) return;

			// Debounced input handler.
			var onInput = debounce( function () {
				var keyword = input.value.trim();

				if ( keyword.length < 2 ) {
					self.hideResults( results, input );
					return;
				}

				if ( keyword === self.lastKeyword ) return;

				self.lastKeyword = keyword;
				self.search( keyword, count, list, footer, viewAll, results, input );
			}, 300 );

			input.addEventListener( 'input', onInput );

			// Close on outside click.
			document.addEventListener( 'click', function ( e ) {
				if ( ! bar.contains( e.target ) ) {
					self.hideResults( results, input );
				}
			} );

			// Keyboard navigation within dropdown.
			input.addEventListener( 'keydown', function ( e ) {
				self.handleKeyboard( e, list, results, input );
			} );

			// Escape closes dropdown.
			input.addEventListener( 'keyup', function ( e ) {
				if ( e.key === 'Escape' ) {
					self.hideResults( results, input );
					input.blur();
				}
			} );

			// Re-open dropdown on focus if has content.
			input.addEventListener( 'focus', function () {
				if ( input.value.trim().length >= 2 && list.children.length > 0 ) {
					results.removeAttribute( 'hidden' );
					input.setAttribute( 'aria-expanded', 'true' );
				}
			} );
		},

		search: function ( keyword, count, list, footer, viewAll, resultsWrap, input ) {
			var self = this;

			// Abort previous request.
			if ( self.abortController ) {
				self.abortController.abort();
			}

			// Create new abort controller.
			self.abortController = window.AbortController ? new AbortController() : null;
			var signal = self.abortController ? self.abortController.signal : null;

			// Show loading state.
			list.innerHTML = '<li class="itr-kb-search-bar__loading" role="status" aria-live="polite">' +
				'<span class="itr-kb-search-bar__spinner"></span>' +
				itrKbFrontend.strings.searching +
				'</li>';
			resultsWrap.removeAttribute( 'hidden' );
			input.setAttribute( 'aria-expanded', 'true' );

			restFetch( 'search', { keyword: keyword, count: count }, signal )
				.then( function ( data ) {
					self.renderResults( data, keyword, list, footer, viewAll, input );
				} )
				.catch( function ( err ) {
					// Ignore aborted requests.
					if ( err && err.name === 'AbortError' ) return;
					list.innerHTML = '<li class="itr-kb-search-bar__no-results">' +
						itrKbFrontend.strings.noResults + '</li>';
				} );
		},

		renderResults: function ( data, keyword, list, footer, viewAll, input ) {
			list.innerHTML = '';

			if ( ! data.results || ! data.results.length ) {
				var noResultsHTML = '<li class="itr-kb-search-bar__no-results">' + itrKbFrontend.strings.noResults + '</li>';

				// Suggestions.
				if ( data.suggestions && data.suggestions.length ) {
					noResultsHTML += '<li class="itr-kb-search-bar__suggestions"><span>' +
						'Try: ' +
						data.suggestions.map( function ( s ) {
							return '<a href="#" data-suggest="' + s + '">' + s + '</a>';
						} ).join( ', ' ) +
						'</span></li>';
				}

				list.innerHTML = noResultsHTML;

				// Bind suggestion clicks.
				list.querySelectorAll( '[data-suggest]' ).forEach( function ( link ) {
					link.addEventListener( 'click', function ( e ) {
						e.preventDefault();
						var suggestInput = link.closest( '.itr-kb-search-bar' ).querySelector( '.itr-kb-search-bar__input' );
						if ( suggestInput ) {
							suggestInput.value = link.dataset.suggest;
							suggestInput.dispatchEvent( new Event( 'input' ) );
						}
					} );
				} );

				if ( footer ) footer.setAttribute( 'hidden', '' );
				return;
			}

			// Render results.
			data.results.forEach( function ( article ) {
				var li = document.createElement( 'li' );
				li.setAttribute( 'role', 'option' );

				var categoryHTML = article.categories && article.categories.length
					? '<span class="itr-kb-search-bar__result-category">' + article.categories[ 0 ] + '</span>'
					: '';

				li.innerHTML =
					'<a href="' + article.url + '">' +
					'<span class="itr-kb-search-bar__result-title">' + article.title + '</span>' +
					( article.excerpt ? '<span class="itr-kb-search-bar__result-excerpt">' + article.excerpt + '</span>' : '' ) +
					categoryHTML +
					'</a>';

				list.appendChild( li );
			} );

			// View all link.
			if ( footer && viewAll && data.total > data.results.length ) {
				var archiveUrl = itrKbFrontend.restUrl.replace( '/wp-json/itr-kb/v1/', '' ) + '?itr_kb_search=' + encodeURIComponent( keyword );
				viewAll.href = archiveUrl;
				footer.removeAttribute( 'hidden' );
			} else if ( footer ) {
				footer.setAttribute( 'hidden', '' );
			}
		},

		handleKeyboard: function ( e, list, resultsWrap, input ) {
			var items   = list.querySelectorAll( 'a' );
			var focused = list.querySelector( 'a:focus' );
			var index   = Array.from( items ).indexOf( focused );

			if ( e.key === 'ArrowDown' ) {
				e.preventDefault();
				if ( index < items.length - 1 ) items[ index + 1 ].focus();
				else if ( items.length ) items[ 0 ].focus();
			} else if ( e.key === 'ArrowUp' ) {
				e.preventDefault();
				if ( index > 0 ) items[ index - 1 ].focus();
				else if ( items.length ) items[ items.length - 1 ].focus();
			} else if ( e.key === 'Enter' && focused ) {
				focused.click();
			}
		},

		hideResults: function ( resultsWrap, input ) {
			if ( resultsWrap ) {
				resultsWrap.setAttribute( 'hidden', '' );
			}
			if ( input ) {
				input.setAttribute( 'aria-expanded', 'false' );
			}
		},
	};

	/* =========================================================================
	   Table of Contents — Toggle + Scroll Spy + Sticky
	   ========================================================================= */

	var ITR_KB_TOC = {
		init: function () {
			if ( ! itrKbFrontend.tocEnabled ) return;

			var toc = document.getElementById( 'itr-kb-toc' );
			if ( ! toc ) return;

			this.bindToggle( toc );
			this.scrollSpy( toc );
		},

		bindToggle: function ( toc ) {
			var toggle = toc.querySelector( '.itr-kb-toc__toggle' );
			var list   = document.getElementById( 'itr-kb-toc-list' );

			if ( ! toggle || ! list ) return;

			toggle.addEventListener( 'click', function () {
				var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
				toggle.setAttribute( 'aria-expanded', String( ! expanded ) );

				if ( expanded ) {
					list.setAttribute( 'hidden', '' );
					toggle.querySelector( '.itr-kb-toc__toggle-icon' ).innerHTML = '&#9654;';
				} else {
					list.removeAttribute( 'hidden' );
					toggle.querySelector( '.itr-kb-toc__toggle-icon' ).innerHTML = '&#9660;';
				}
			} );
		},

		scrollSpy: function ( toc ) {
			var links   = toc.querySelectorAll( '.itr-kb-toc__link' );
			if ( ! links.length ) return;

			var headingIds = [];
			links.forEach( function ( link ) {
				var href = link.getAttribute( 'href' );
				if ( href && href.startsWith( '#' ) ) {
					headingIds.push( href.slice( 1 ) );
				}
			} );

			var onScroll = debounce( function () {
				var active     = null;
				var threshold  = getStickyOffset() + 10;

				headingIds.forEach( function ( id ) {
					var el = document.getElementById( id );
					if ( el && el.getBoundingClientRect().top <= threshold ) {
						active = id;
					}
				} );

				// Force the last heading active when scrolled to the bottom.
				var nearBottom = ( window.scrollY + window.innerHeight ) >= document.documentElement.scrollHeight - 30;
				if ( nearBottom && headingIds.length ) {
					active = headingIds[ headingIds.length - 1 ];
				}

				links.forEach( function ( link ) {
					link.classList.remove( 'itr-kb-toc__link--active' );
					if ( active && link.getAttribute( 'href' ) === '#' + active ) {
						link.classList.add( 'itr-kb-toc__link--active' );
					}
				} );
			}, 80 );

			window.addEventListener( 'scroll', onScroll, { passive: true } );
		},
	};

	/* =========================================================================
	   Back to Top Button
	   ========================================================================= */

	var ITR_KB_BackToTop = {
		init: function () {
			if ( ! itrKbFrontend.backToTopEnabled ) return;

			var btn = document.getElementById( 'itr-kb-back-to-top' );
			if ( ! btn ) return;

			window.addEventListener( 'scroll', debounce( function () {
				if ( window.scrollY > 300 ) {
					btn.removeAttribute( 'hidden' );
				} else {
					btn.setAttribute( 'hidden', '' );
				}
			}, 100 ), { passive: true } );

			btn.addEventListener( 'click', function () {
				window.scrollTo( { top: 0, behavior: 'smooth' } );
			} );
		},
	};

	/* =========================================================================
	   Category Tree — Expand/Collapse
	   ========================================================================= */

	var ITR_KB_CategoryTree = {
		init: function () {
			document.querySelectorAll( '.itr-kb-category-tree__toggle' ).forEach( function ( toggle ) {
				toggle.addEventListener( 'click', function () {
					var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
					var children = document.getElementById(
						toggle.getAttribute( 'aria-controls' )
					) || toggle.closest( '.itr-kb-category-tree__row' ).nextElementSibling;

					toggle.setAttribute( 'aria-expanded', String( ! expanded ) );

					if ( children ) {
						if ( expanded ) {
							children.setAttribute( 'hidden', '' );
						} else {
							children.removeAttribute( 'hidden' );
						}
					}
				} );
			} );
		},
	};

	/* =========================================================================
	   Accordion Widget
	   ========================================================================= */

	var ITR_KB_Accordion = {
		init: function () {
			document.querySelectorAll( '.itr-kb-accordion__header' ).forEach( function ( header ) {
				header.addEventListener( 'click', function () {
					var section  = header.closest( '.itr-kb-accordion__section' );
					var bodyId   = header.getAttribute( 'aria-controls' );
					var body     = document.getElementById( bodyId );
					var expanded = header.getAttribute( 'aria-expanded' ) === 'true';

					header.setAttribute( 'aria-expanded', String( ! expanded ) );
					section.classList.toggle( 'itr-kb-accordion__section--open', ! expanded );

					if ( body ) {
						if ( expanded ) {
							body.setAttribute( 'hidden', '' );
						} else {
							body.removeAttribute( 'hidden' );
						}
					}
				} );
			} );
		},
	};

	/* =========================================================================
	   Smooth Anchor Scroll (TOC links)
	   ========================================================================= */

	var ITR_KB_AnchorScroll = {
		init: function () {
			document.querySelectorAll( '.itr-kb-toc__link' ).forEach( function ( link ) {
				link.addEventListener( 'click', function ( e ) {
					var href = link.getAttribute( 'href' );
					if ( href && href.startsWith( '#' ) ) {
						var target = document.getElementById( href.slice( 1 ) );
						if ( target ) {
							e.preventDefault();
							var offset = getStickyOffset();
							var top    = target.getBoundingClientRect().top + window.scrollY - offset;
							window.scrollTo( { top: top, behavior: 'smooth' } );

							// Update URL hash without jump.
							if ( history.pushState ) {
								history.pushState( null, null, href );
							}
						}
					}
				} );
			} );
		},
	};

	/* =========================================================================
	   Keyboard Navigation (↑ ↓ Enter across article links)
	   ========================================================================= */

	var ITR_KB_KeyboardNav = {
		init: function () {
			var articleBody = document.querySelector( '.itr-kb-article__body' );
			if ( ! articleBody ) return;

			var links = [];
			var refreshLinks = function () {
				links = Array.from( articleBody.querySelectorAll( 'a[href]' ) );
			};

			refreshLinks();

			document.addEventListener( 'keydown', function ( e ) {
				// Only handle when focus is inside article body.
				if ( ! articleBody.contains( document.activeElement ) ) return;

				var index = links.indexOf( document.activeElement );

				if ( e.key === 'ArrowDown' ) {
					e.preventDefault();
					if ( index < links.length - 1 ) links[ index + 1 ].focus();
				} else if ( e.key === 'ArrowUp' ) {
					e.preventDefault();
					if ( index > 0 ) links[ index - 1 ].focus();
				} else if ( e.key === 'Enter' && document.activeElement.tagName === 'A' ) {
					document.activeElement.click();
				}
			} );
		},
	};

	/* =========================================================================
	   View Count Tracking
	   View count is tracked server-side in the PHP template via
	   ITR_KB_Sections::track_view() — no JS call needed.
	   ========================================================================= */

	var ITR_KB_ViewCount = {
		init: function () {
			// Intentionally empty — view tracking is handled in PHP template.
		},
	};

	var ITR_KB_ArchiveSidebar = {
		init: function () {
			document.querySelectorAll( '.itr-kb-archive-sidebar__toggle' ).forEach( function ( toggle ) {
				toggle.addEventListener( 'click', function () {
					var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
					var row      = toggle.closest( '.itr-kb-archive-sidebar__row' );
					var sublist  = row && row.nextElementSibling;
					toggle.setAttribute( 'aria-expanded', String( ! expanded ) );
					if ( sublist ) {
						expanded ? sublist.setAttribute( 'hidden', '' ) : sublist.removeAttribute( 'hidden' );
					}
				} );
			} );
		},
	};

	var ITR_KB_ArchiveSections = {
		init: function () {
			document.querySelectorAll( '.itr-kb-archive-section__toggle' ).forEach( function ( toggle ) {
				toggle.addEventListener( 'click', function () {
					var expanded  = toggle.getAttribute( 'aria-expanded' ) === 'true';
					var body      = document.getElementById( toggle.getAttribute( 'aria-controls' ) );
					toggle.setAttribute( 'aria-expanded', String( ! expanded ) );
					if ( body ) {
						expanded ? body.setAttribute( 'hidden', '' ) : body.removeAttribute( 'hidden' );
					}
				} );
			} );
		},
	};

	var ITR_KB_CatAccordion = {
		init: function () {

			// Layout 1 — Simple accordion.
			document.querySelectorAll( '.itr-kb-cat-acc-simple__header' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					var expanded = btn.getAttribute( 'aria-expanded' ) === 'true';
					var card     = btn.closest( '.itr-kb-cat-acc-simple' );
					var panel    = document.getElementById( btn.getAttribute( 'aria-controls' ) );

					btn.setAttribute( 'aria-expanded', String( ! expanded ) );
					card.classList.toggle( 'itr-kb-cat-acc-simple--open', ! expanded );

					if ( panel ) {
						panel.style.display = expanded ? 'none' : '';
					}
				} );
			} );

			// Layout 2 — Card with image accordion.
			document.querySelectorAll( '.itr-kb-cat-acc-card__toggle' ).forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					var expanded = btn.getAttribute( 'aria-expanded' ) === 'true';
					var card     = btn.closest( '.itr-kb-cat-acc-card' );
					var panel    = document.getElementById( btn.getAttribute( 'aria-controls' ) );
					var image    = card ? card.querySelector( '.itr-kb-cat-acc-card__image' ) : null;
					var icon     = btn.querySelector( '.dashicons' );

					btn.setAttribute( 'aria-expanded', String( ! expanded ) );
					card.classList.toggle( 'itr-kb-cat-acc-card--open', ! expanded );

					if ( panel ) {
						panel.style.display = expanded ? 'none' : '';
					}

					// Image sits below header — show when collapsed, hide when expanded
					if ( image ) {
						image.style.display = expanded ? '' : 'none';
					}

					if ( icon ) {
						icon.classList.toggle( 'dashicons-arrow-up-alt2', ! expanded );
						icon.classList.toggle( 'dashicons-arrow-down-alt2', expanded );
					}
				} );
			} );
		},
	};

	/* =========================================================================
	   Load More Articles
	   ========================================================================= */

	var ITR_KB_LoadMore = {
		init: function () {
			var btn = document.getElementById( 'itr-kb-load-more' );
			if ( ! btn ) return;

			btn.addEventListener( 'click', function () {
				ITR_KB_LoadMore.load( btn );
			} );
		},

		load: function ( btn ) {
			var list    = document.getElementById( 'itr-kb-article-list' );
			var status  = document.querySelector( '.itr-kb-load-more-status' );
			if ( ! list || ! btn ) return;

			var page     = parseInt( btn.dataset.page, 10 );
			var max      = parseInt( btn.dataset.max, 10 );
			var term     = btn.dataset.term;
			var taxonomy = btn.dataset.taxonomy;
			var nonce    = btn.dataset.nonce;

			// Show loading state.
			btn.disabled = true;
			btn.classList.add( 'itr-kb-load-more-btn--loading' );
			if ( status ) status.textContent = itrKbFrontend.strings.searching || 'Loading...';

			var data = new FormData();
			data.append( 'action', 'itr_kb_load_more' );
			data.append( 'nonce', nonce );
			data.append( 'page', page );
			data.append( 'term', term );
			data.append( 'taxonomy', taxonomy );

			fetch( itrKbFrontend.ajaxUrl, { method: 'POST', body: data } )
				.then( function ( res ) { return res.json(); } )
				.then( function ( response ) {
					if ( response.success && response.data.html ) {
						// Append new articles.
						var temp = document.createElement( 'div' );
						temp.innerHTML = response.data.html;
						while ( temp.firstChild ) {
							list.appendChild( temp.firstChild );
						}

						// Update page number.
						btn.dataset.page = response.data.page;

						// Hide button if no more pages.
						if ( ! response.data.has_more ) {
							btn.closest( '.itr-kb-load-more-wrap' ).style.display = 'none';
						} else {
							btn.disabled = false;
							btn.classList.remove( 'itr-kb-load-more-btn--loading' );
							if ( status ) status.textContent = '';
						}
					} else {
						btn.closest( '.itr-kb-load-more-wrap' ).style.display = 'none';
					}
				} )
				.catch( function () {
					btn.disabled = false;
					btn.classList.remove( 'itr-kb-load-more-btn--loading' );
					if ( status ) status.textContent = 'Error loading articles.';
				} );
		},
	};

	/* =========================================================================
	   Bootstrap
	   ========================================================================= */

	document.addEventListener( 'DOMContentLoaded', function () {
		ITR_KB_Search.init();
		ITR_KB_TOC.init();
		ITR_KB_BackToTop.init();
		ITR_KB_CategoryTree.init();
		ITR_KB_Accordion.init();
		ITR_KB_AnchorScroll.init();
		ITR_KB_KeyboardNav.init();
		ITR_KB_ViewCount.init();
		ITR_KB_ArchiveSidebar.init();
		ITR_KB_ArchiveSections.init();
		ITR_KB_CatAccordion.init();
		ITR_KB_LoadMore.init();
	} );

} )();

/**
 * itrKbPrintArticle
 *
 * Opens a clean print window containing only:
 *  - Site logo at the top
 *  - Article title, meta, and body content
 *
 * Called via onclick on the Print button in single-itr-kb.php
 */
function itrKbPrintArticle() {
	var titleEl  = document.querySelector( '.itr-kb-single-article__title' );
	var metaEl   = document.querySelector( '.itr-kb-single-article__meta' );
	var bodyEl   = document.querySelector( '.itr-kb-single-article__body' );

	if ( ! bodyEl ) {
		window.print();
		return;
	}

	var title    = titleEl ? titleEl.innerHTML : document.title;
	var meta     = metaEl  ? metaEl.outerHTML  : '';
	var body     = bodyEl.innerHTML;
	var logoUrl  = ( typeof itrKbFrontend !== 'undefined' && itrKbFrontend.logoUrl ) ? itrKbFrontend.logoUrl : '';
	var siteName = ( typeof itrKbFrontend !== 'undefined' && itrKbFrontend.siteName ) ? itrKbFrontend.siteName : '';

	var logoHtml = logoUrl
		? '<img src="' + logoUrl + '" alt="' + siteName + '" class="itr-print-logo" />'
		: ( siteName ? '<div class="itr-print-site-name">' + siteName + '</div>' : '' );

	var printWindow = window.open( '', '_blank', 'width=900,height=700' );
	if ( ! printWindow ) { window.print(); return; }

	printWindow.document.write( '<!DOCTYPE html><html><head>' );
	printWindow.document.write( '<meta charset="UTF-8">' );
	printWindow.document.write( '<title>' + document.title + '</title>' );
	printWindow.document.write( '<style>' );
	printWindow.document.write( [
		'*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }',
		'body { font-family: Georgia, "Times New Roman", serif; font-size: 13pt; color: #111; background: #fff; padding: 32px 48px; max-width: 800px; margin: 0 auto; }',
		'.itr-print-header { display: flex; align-items: center; padding-bottom: 20px; margin-bottom: 28px; border-bottom: 2px solid #ddd; }',
		'.itr-print-logo { max-height: 60px; max-width: 200px; width: auto; display: block; }',
		'.itr-print-site-name { font-size: 18pt; font-weight: bold; color: #333; }',
		'h1.itr-print-title { font-size: 22pt; font-weight: bold; color: #111; margin-bottom: 12px; line-height: 1.3; }',
		'.itr-kb-single-article__meta { font-size: 10pt; color: #666; margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 12px; }',
		'.itr-kb-single-article__meta .dashicons { display: none; }',
		'.itr-kb-single-article__meta-item { margin-right: 16px; }',
		'.itr-print-body h2 { font-size: 16pt; margin: 28px 0 10px; color: #222; }',
		'.itr-print-body h3 { font-size: 14pt; margin: 22px 0 8px; color: #333; }',
		'.itr-print-body h4 { font-size: 12pt; margin: 18px 0 6px; color: #333; }',
		'.itr-print-body p { margin: 0 0 14px; line-height: 1.7; }',
		'.itr-print-body ul, .itr-print-body ol { padding-left: 24px; margin: 0 0 14px; }',
		'.itr-print-body li { margin-bottom: 6px; line-height: 1.6; }',
		'.itr-print-body a { color: #111; text-decoration: underline; }',
		'.itr-print-body img { max-width: 100%; height: auto; margin: 16px 0; border-radius: 4px; }',
		'.itr-print-body blockquote { border-left: 4px solid #ccc; padding-left: 16px; color: #555; margin: 16px 0; }',
		'.itr-print-body table { width: 100%; border-collapse: collapse; margin: 16px 0; }',
		'.itr-print-body th, .itr-print-body td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }',
		'.itr-print-body th { background: #f5f5f5; font-weight: bold; }',
		/* Hide Elementor widget controls, TOC, and other UI-only elements */
		'.elementor-editor-active, .itr-kb-toc, button, .itr-kb-article-nav { display: none !important; }',
		'@media print {',
		'  body { padding: 0; }',
		'  @page { margin: 20mm 15mm; }',
		'}'
	].join( '\n' ) );
	printWindow.document.write( '</style></head><body>' );
	printWindow.document.write( '<div class="itr-print-header">' + logoHtml + '</div>' );
	printWindow.document.write( '<h1 class="itr-print-title">' + title + '</h1>' );
	printWindow.document.write( meta );
	printWindow.document.write( '<div class="itr-print-body">' + body + '</div>' );
	printWindow.document.write( '</body></html>' );
	printWindow.document.close();

	// setTimeout gives the browser time to fully render the written content
	// before opening the print dialog. onload is unreliable with document.write.
	setTimeout( function () {
		printWindow.focus();
		printWindow.print();
	}, 500 );
}