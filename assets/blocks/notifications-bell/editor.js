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

	var bellIcon = createElement(
		'svg',
		{
			xmlns: 'http://www.w3.org/2000/svg',
			viewBox: '0 0 24 24',
			width: 20,
			height: 20,
			'aria-hidden': true,
			focusable: 'false',
		},
		createElement( 'path', {
			fill: 'currentColor',
			d: 'M18 8c0-3.3-2.7-6-6-6S6 4.7 6 8c0 2.2-1.3 4.1-3.2 5.1-.2.1-.4.3-.4.5V14h16v-.4c0-.2-.2-.4-.4-.5C19.3 12.1 18 10.2 18 8z',
		} ),
		createElement( 'path', {
			fill: 'currentColor',
			d: 'M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2z',
		} )
	);

	registerBlockType( 'forwp/notifications-bell', {
		edit: function Edit() {
			var blockProps = useBlockProps( {
				className: 'forwp-notifications-bell forwp-notifications-bell--editor',
			} );

			return createElement(
				'div',
				blockProps,
				createElement(
					'span',
					{
						className: 'forwp-notifications-bell__button forwp-notifications-bell__button--editor',
						'aria-hidden': true,
					},
					createElement(
						'span',
						{ className: 'forwp-notifications-bell__icon', 'aria-hidden': true },
						bellIcon
					),
					createElement(
						'span',
						{
							className: 'forwp-notifications-bell__badge forwp-notifications-bell__badge--editor',
							title: __( 'Unread notifications (count on frontend)', '4wp-notifications' ),
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
