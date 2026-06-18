( function ( wp ) {
	'use strict';

	var blocks = wp.blocks;
	var blockEditor = wp.blockEditor;
	var element = wp.element;
	var serverSideRender = wp.serverSideRender;
	var i18n = wp.i18n;

	if ( ! blocks || ! blockEditor || ! element || ! serverSideRender ) {
		return;
	}

	var registerBlockType = blocks.registerBlockType;
	var useBlockProps = blockEditor.useBlockProps;
	var createElement = element.createElement;
	var __ = i18n.__;

	registerBlockType( 'forwp/notifications', {
		edit: function Edit( props ) {
			var blockProps = useBlockProps();

			return createElement(
				'div',
				blockProps,
				createElement( serverSideRender, {
					block: 'forwp/notifications',
					attributes: props.attributes,
					EmptyResponsePlaceholder: function () {
						return createElement(
							'p',
							{ className: 'forwp-notifications-block-editor__empty' },
							__( '4WP Notifications List (log in to preview)', '4wp-notifications' )
						);
					},
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
