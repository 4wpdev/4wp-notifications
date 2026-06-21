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

	registerBlockType( 'forwp/favorites-menu', {
		edit: function Edit() {
			var blockProps = useBlockProps( {
				className: 'forwp-favorites-menu forwp-favorites-menu--editor',
			} );

			return createElement(
				'div',
				blockProps,
				createElement(
					'span',
					{
						className: 'forwp-favorites-menu__button forwp-favorites-menu__button--editor',
						'aria-hidden': true,
					},
					createElement(
						'span',
						{ className: 'forwp-favorites-menu__icon', 'aria-hidden': true },
						'♥'
					),
					createElement(
						'span',
						{
							className: 'forwp-favorites-menu__badge forwp-favorites-menu__badge--editor',
							title: __( 'Favorites count on frontend', '4wp-notifications' ),
						},
						'*'
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
