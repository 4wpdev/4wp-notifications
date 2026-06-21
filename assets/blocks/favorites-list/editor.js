( function ( wp ) {
	'use strict';

	var blocks = wp.blocks;
	var blockEditor = wp.blockEditor;
	var element = wp.element;
	var i18n = wp.i18n;

	if ( ! blocks || ! blockEditor || ! element ) {
		return;
	}

	var registerBlockType = blocks.registerBlockType;
	var useBlockProps = blockEditor.useBlockProps;
	var createElement = element.createElement;
	var __ = i18n.__;

	registerBlockType( 'forwp/favorites-list', {
		edit: function Edit() {
			var blockProps = useBlockProps( {
				className: 'forwp-favorites-list forwp-favorites-list--editor',
			} );

			return createElement(
				'div',
				blockProps,
				createElement( 'strong', null, __( '4WP Favorites List', '4wp-notifications' ) ),
				createElement(
					'p',
					null,
					__( 'Shows the signed-in user favorites grouped by content type.', '4wp-notifications' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
