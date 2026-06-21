( function () {
	'use strict';

	var globalI18n = window.forwpFavoritesMenuI18n || {};

	function getI18n( wrapper ) {
		if ( ! wrapper ) {
			return globalI18n;
		}
		var raw = wrapper.getAttribute( 'data-forwp-i18n' );
		if ( ! raw ) {
			return globalI18n;
		}
		try {
			return Object.assign( {}, globalI18n, JSON.parse( raw ) );
		} catch ( e ) {
			return globalI18n;
		}
	}

	var LIST_SEL = '.forwp-favorites-menu__list';
	var LIST_EMPTY_SEL = '.forwp-favorites-menu__list-empty';
	var BADGE_SEL = '.forwp-favorites-menu__badge';
	var DROPDOWN_ACTIVE_CLASS = 'forwp-favorites-menu__dropdown--active';
	var POLL_INTERVAL_MS = 30000;

	function escapeHtml( str ) {
		if ( ! str ) {
			return '';
		}
		var div = document.createElement( 'div' );
		div.textContent = str;
		return div.innerHTML;
	}

	function formatTimeAgo( createdAt, i18n ) {
		i18n = i18n || globalI18n;
		if ( ! createdAt ) {
			return '';
		}
		try {
			var d = new Date( createdAt.replace( /\s/, 'T' ) );
			if ( isNaN( d.getTime() ) ) {
				return createdAt;
			}
			var now = new Date();
			var s = Math.floor( ( now - d ) / 1000 );
			if ( s < 60 ) {
				return i18n.justNow || 'just now';
			}
			if ( s < 3600 ) {
				return Math.floor( s / 60 ) + ' ' + ( i18n.minAgo || 'min ago' );
			}
			if ( s < 86400 ) {
				return Math.floor( s / 3600 ) + ' ' + ( i18n.hrAgo || 'hr ago' );
			}
			return Math.floor( s / 86400 ) + ' ' + ( i18n.dAgo || 'd ago' );
		} catch ( e ) {
			return createdAt;
		}
	}

	function getItemIconClass( type ) {
		if ( type === 'post_type' ) {
			return 'dashicons-archive';
		}
		if ( type === 'term' ) {
			return 'dashicons-category';
		}
		if ( type === 'post' ) {
			return 'dashicons-admin-post';
		}
		return 'dashicons-heart';
	}

	function renderItem( item, i18n ) {
		i18n = i18n || globalI18n;
		var title = item.title || '';
		var subtitle = item.subtitle || '';
		var url = item.url || '#';
		var type = item.type || '';
		var timeAgo = formatTimeAgo( item.created_at, i18n );
		var iconClass = getItemIconClass( type );
		var hasLink = url && url !== '#';
		var subtitleHtml = subtitle ? '<p class="forwp-favorites-menu__item-text">' + escapeHtml( subtitle ) + '</p>' : '';
		var timeHtml = timeAgo ? '<time class="forwp-favorites-menu__item-time">' + escapeHtml( timeAgo ) + '</time>' : '';
		var goToPageLabel = i18n.goToPage || 'Go to page';
		var linkIconHtml = hasLink ? '<span class="forwp-favorites-menu__item-link-icon" aria-label="' + escapeHtml( goToPageLabel ) + '"><span class="dashicons dashicons-external"></span></span>' : '';

		return (
			'<a href="' + escapeHtml( url ) + '" class="forwp-favorites-menu__item" data-id="' + item.id + '">' +
			'<span class="forwp-favorites-menu__item-icon"><span class="dashicons ' + iconClass + '" style="font-size:20px;width:20px;height:20px;" aria-hidden="true"></span></span>' +
			'<div class="forwp-favorites-menu__item-content">' +
			'<h4 class="forwp-favorites-menu__item-title">' + escapeHtml( title ) + '</h4>' + subtitleHtml + timeHtml + linkIconHtml +
			'</div></a>'
		);
	}

	function renderList( items, i18n ) {
		if ( ! items || ! items.length ) {
			return '';
		}
		var html = '';
		for ( var i = 0; i < items.length; i++ ) {
			html += renderItem( items[ i ], i18n );
		}
		return html;
	}

	function setBadge( wrapper, count ) {
		var btn = wrapper.querySelector( '.forwp-favorites-menu__button' );
		if ( ! btn ) {
			return;
		}
		var badge = btn.querySelector( BADGE_SEL );
		if ( ! badge ) {
			return;
		}
		if ( count > 0 ) {
			badge.textContent = count > 99 ? '99+' : count;
			badge.style.display = 'flex';
		} else {
			badge.style.display = 'none';
		}
	}

	function getEmptyHtml( wrapper, i18n ) {
		i18n = i18n || ( wrapper ? getI18n( wrapper ) : globalI18n );
		var text = i18n.empty || 'No favorites yet';
		return '<div class="forwp-favorites-menu__list-empty"><p>' + escapeHtml( text ) + '</p></div>';
	}

	function setListAndEmpty( wrapper, items ) {
		var listEl = wrapper.querySelector( LIST_SEL );
		if ( ! listEl ) {
			return;
		}
		var i18n = getI18n( wrapper );
		var listHtml = renderList( items, i18n );
		var emptyHtml = getEmptyHtml( wrapper, i18n );
		if ( listHtml ) {
			listEl.innerHTML = listHtml + emptyHtml;
			var emptyEl = listEl.querySelector( LIST_EMPTY_SEL );
			if ( emptyEl ) {
				emptyEl.style.display = 'none';
			}
		} else {
			listEl.innerHTML = emptyHtml;
		}
	}

	function fetchFavorites( wrapper, restUrl, nonce ) {
		var limit = wrapper.getAttribute( 'data-forwp-limit' ) || '5';
		fetch( restUrl + '/favorites?view=recent&limit=' + encodeURIComponent( limit ), {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nonce },
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( data ) {
				if ( data.items && Array.isArray( data.items ) ) {
					setListAndEmpty( wrapper, data.items );
					setBadge( wrapper, data.total != null ? data.total : data.items.length );
				}
			} )
			.catch( function () {} );
	}

	function bindDropdownToggle( wrapper, restUrl, nonce ) {
		var btn = wrapper.querySelector( '.forwp-favorites-menu__button' );
		var dropdown = wrapper.querySelector( '.forwp-favorites-menu__dropdown' );
		if ( ! btn || ! dropdown ) {
			return;
		}

		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			var isOpen = dropdown.classList.contains( DROPDOWN_ACTIVE_CLASS );
			if ( isOpen ) {
				dropdown.classList.remove( DROPDOWN_ACTIVE_CLASS );
				btn.setAttribute( 'aria-expanded', 'false' );
			} else {
				dropdown.classList.add( DROPDOWN_ACTIVE_CLASS );
				btn.setAttribute( 'aria-expanded', 'true' );
				fetchFavorites( wrapper, restUrl, nonce );
			}
		} );

		dropdown.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! wrapper.contains( e.target ) ) {
				dropdown.classList.remove( DROPDOWN_ACTIVE_CLASS );
				btn.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	}

	function initWidget( wrapper ) {
		if ( wrapper.getAttribute( 'data-forwp-favorites-menu-inited' ) === '1' ) {
			return;
		}
		wrapper.setAttribute( 'data-forwp-favorites-menu-inited', '1' );
		var restUrl = wrapper.getAttribute( 'data-forwp-rest-url' );
		var nonce = wrapper.getAttribute( 'data-forwp-nonce' );
		if ( ! restUrl || ! nonce ) {
			return;
		}
		bindDropdownToggle( wrapper, restUrl, nonce );
		fetchFavorites( wrapper, restUrl, nonce );
		setInterval( function () {
			fetchFavorites( wrapper, restUrl, nonce );
		}, POLL_INTERVAL_MS );
	}

	function init() {
		document.querySelectorAll( '.forwp-favorites-menu[data-forwp-favorites-menu-guest="1"]' ).forEach( function ( wrapper ) {
			var loginUrl = wrapper.getAttribute( 'data-forwp-login-url' );
			var btn = wrapper.querySelector( '.forwp-favorites-menu__button' );
			if ( ! btn || ! loginUrl ) {
				return;
			}
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				window.location.href = loginUrl;
			} );
		} );

		document.querySelectorAll( '.forwp-favorites-menu[data-forwp-favorites-menu="1"]' ).forEach( initWidget );
	}

	document.addEventListener( 'forwp-favorites-updated', function () {
		document.querySelectorAll( '.forwp-favorites-menu[data-forwp-favorites-menu="1"]' ).forEach( function ( wrapper ) {
			var restUrl = wrapper.getAttribute( 'data-forwp-rest-url' );
			var nonce = wrapper.getAttribute( 'data-forwp-nonce' );
			if ( restUrl && nonce ) {
				fetchFavorites( wrapper, restUrl, nonce );
			}
		} );
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
	window.addEventListener( 'load', init );
} )();
