( function () {
	'use strict';

	var cfg = window.forwpFavorites || {};
	var restUrl = cfg.restUrl || '';
	var nonce = cfg.nonce || '';
	var loginUrl = cfg.loginUrl || '';
	var i18n = cfg.i18n || {};

	function request( method, path, body ) {
		var options = {
			method: method,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce,
			},
			credentials: 'same-origin',
		};

		if ( body ) {
			options.body = JSON.stringify( body );
		}

		return fetch( restUrl.replace( /\/$/, '' ) + path, options ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'request_failed' );
			}
			return response.json();
		} );
	}

	function setButtonState( button, active ) {
		var labelAdd = button.getAttribute( 'data-forwp-label-add' ) || i18n.add || 'Add to favorites';
		var labelRemove = button.getAttribute( 'data-forwp-label-remove' ) || i18n.remove || 'Remove from favorites';
		var label = button.querySelector( '.forwp-favorite-button__label' );

		button.classList.toggle( 'is-active', !! active );
		button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );

		if ( label ) {
			label.textContent = active ? labelRemove : labelAdd;
		}
	}

	function onFavoriteClick( event ) {
		var button = event.currentTarget;

		if ( button.getAttribute( 'data-forwp-login' ) === '1' ) {
			if ( loginUrl ) {
				window.location.href = loginUrl;
			}
			return;
		}

		if ( button.getAttribute( 'data-forwp-busy' ) === '1' ) {
			return;
		}

		button.setAttribute( 'data-forwp-busy', '1' );

		request( 'POST', '/favorites/toggle', {
			type: button.getAttribute( 'data-forwp-fav-type' ),
			ref_id: parseInt( button.getAttribute( 'data-forwp-fav-ref-id' ) || '0', 10 ),
			ref_key: button.getAttribute( 'data-forwp-fav-ref-key' ) || '',
		} )
			.then( function ( data ) {
				setButtonState( button, !! data.active );
				document.dispatchEvent( new CustomEvent( 'forwp-favorites-updated' ) );
			} )
			.catch( function () {
				window.alert( i18n.error || 'Could not update favorites.' );
			} )
			.finally( function () {
				button.removeAttribute( 'data-forwp-busy' );
			} );
	}

	function onRemoveClick( event ) {
		var button = event.currentTarget;
		var id = button.getAttribute( 'data-forwp-fav-remove' );
		var item = button.closest( '.forwp-favorites__item' );
		var list = button.closest( '.forwp-favorites' );

		if ( ! id || button.getAttribute( 'data-forwp-busy' ) === '1' ) {
			return;
		}

		button.setAttribute( 'data-forwp-busy', '1' );

		request( 'DELETE', '/favorites/' + encodeURIComponent( id ) )
			.then( function () {
				if ( item ) {
					item.remove();
				}

				if ( list && ! list.querySelector( '.forwp-favorites__item' ) ) {
					var empty = document.createElement( 'p' );
					empty.className = 'forwp-favorites__empty';
					empty.textContent = i18n.empty || 'No favorites yet.';
					list.appendChild( empty );
				}
				document.dispatchEvent( new CustomEvent( 'forwp-favorites-updated' ) );
			} )
			.catch( function () {
				window.alert( i18n.error || 'Could not update favorites.' );
			} )
			.finally( function () {
				button.removeAttribute( 'data-forwp-busy' );
			} );
	}

	function init() {
		document.querySelectorAll( '[data-forwp-favorite-button="1"]' ).forEach( function ( button ) {
			button.addEventListener( 'click', onFavoriteClick );
		} );

		document.querySelectorAll( '[data-forwp-fav-remove]' ).forEach( function ( button ) {
			button.addEventListener( 'click', onRemoveClick );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
