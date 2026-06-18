( function () {
	'use strict';

	function setActiveTab( tabId ) {
		document.querySelectorAll( '.forwp-notifications-tab' ).forEach( function ( btn ) {
			var active = btn.getAttribute( 'data-tab' ) === tabId;
			btn.classList.toggle( 'is-active', active );
			btn.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			btn.tabIndex = active ? 0 : -1;
		} );

		document.querySelectorAll( '.forwp-notifications-tab-panel [role="tabpanel"]' ).forEach( function ( panel ) {
			var show = panel.id === 'forwp-notifications-panel-' + tabId;
			panel.hidden = ! show;
		} );

		if ( window.history && window.history.replaceState ) {
			var url = new URL( window.location.href );
			url.searchParams.set( 'tab', tabId );
			window.history.replaceState( {}, '', url.toString() );
		}
	}

	function initTabs() {
		var shell = document.querySelector( '.forwp-notifications-tab-panel' );
		if ( ! shell ) {
			return;
		}

		shell.querySelectorAll( '.forwp-notifications-tab' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var tab = btn.getAttribute( 'data-tab' );
				if ( tab ) {
					setActiveTab( tab );
				}
			} );
		} );
	}

	function initRecipients() {
		var form = document.querySelector( '.forwp-notifications-direct-form' );
		if ( ! form ) {
			return;
		}

		var checkboxes = form.querySelectorAll( '.forwp-notif-user-cb' );

		form.querySelector( '[data-forwp-select-all]' )?.addEventListener( 'click', function () {
			checkboxes.forEach( function ( cb ) {
				cb.checked = true;
			} );
		} );

		form.querySelector( '[data-forwp-select-none]' )?.addEventListener( 'click', function () {
			checkboxes.forEach( function ( cb ) {
				cb.checked = false;
			} );
		} );

		form.querySelectorAll( '[data-forwp-select-role]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var role = btn.getAttribute( 'data-forwp-select-role' );
				checkboxes.forEach( function ( cb ) {
					if ( cb.getAttribute( 'data-role' ) === role ) {
						cb.checked = true;
					}
				} );
			} );
		} );

		form.querySelectorAll( '.forwp-notif-role-cb' ).forEach( function ( roleCb ) {
			roleCb.addEventListener( 'change', function () {
				var role = roleCb.getAttribute( 'data-role' );
				var group = form.querySelector(
					'.forwp-notifications-recipient-group[data-role-group="' + role + '"]'
				);
				if ( group ) {
					group.classList.toggle( 'is-role-selected', roleCb.checked );
				}
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initTabs();
		initRecipients();
	} );
} )();
