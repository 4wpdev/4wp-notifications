import { store, getContext } from '@wordpress/interactivity';

store( 'forwp/notifications', {
	actions: {
		async markRead() {
			const ctx = getContext();
			if ( ctx.is_read ) return;
			const st = store( 'forwp/notifications' ).state;
			const url = `${ st.restUrl }/notifications/${ ctx.id }`;
			const res = await fetch( url, {
				method: 'PATCH',
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': st.nonce,
					'Content-Type': 'application/json',
				},
			} );
			if ( res.ok ) {
				ctx.is_read = 1;
				st.unreadCount = Math.max( 0, ( st.unreadCount || 0 ) - 1 );
			}
		},
	},
	callbacks: {
		startPolling() {
			const st = store( 'forwp/notifications' ).state;
			const interval = st.pollInterval || 30000;
			st._pollTimer = setInterval( () => {
				const { restUrl, nonce } = store( 'forwp/notifications' ).state;
				fetch( `${ restUrl }/notifications?per_page=20&page=1`, {
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': nonce },
				} )
					.then( ( r ) => r.json() )
					.then( ( data ) => {
						const state = store( 'forwp/notifications' ).state;
						if ( data.items && Array.isArray( data.items ) ) {
							state.items = data.items;
							state.unreadCount = data.unread_count ?? 0;
						}
					} )
					.catch( () => {} );
			}, interval );
		},
	},
} );
