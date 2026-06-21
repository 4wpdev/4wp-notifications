( function ( wp ) {
	'use strict';

	var blocks = wp.blocks;
	var blockEditor = wp.blockEditor;
	var components = wp.components;
	var element = wp.element;
	var i18n = wp.i18n;

	if ( ! blocks || ! blockEditor || ! components || ! element ) {
		return;
	}

	var registerBlockType = blocks.registerBlockType;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var TextControl = components.TextControl;
	var createElement = element.createElement;
	var __ = i18n.__;

	registerBlockType( 'forwp/favorite-button', {
		edit: function Edit( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'forwp-favorite-button forwp-favorite-button--editor',
			} );

			return createElement(
				element.Fragment,
				null,
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: __( 'Favorite target', '4wp-notifications' ), initialOpen: true },
						createElement( SelectControl, {
							label: __( 'Target mode', '4wp-notifications' ),
							value: attributes.targetMode,
							options: [
								{ label: __( 'Auto (context)', '4wp-notifications' ), value: 'auto' },
								{ label: __( 'Single post', '4wp-notifications' ), value: 'post' },
								{ label: __( 'Post type archive', '4wp-notifications' ), value: 'post_type' },
								{ label: __( 'Taxonomy term', '4wp-notifications' ), value: 'term' },
							],
							onChange: function ( value ) {
								setAttributes( { targetMode: value } );
							},
						} ),
						attributes.targetMode === 'post_type'
							? createElement( TextControl, {
									label: __( 'Post type slug', '4wp-notifications' ),
									value: attributes.postTypeSlug,
									onChange: function ( value ) {
										setAttributes( { postTypeSlug: value } );
									},
							  } )
							: null,
						attributes.targetMode === 'term'
							? createElement( TextControl, {
									label: __( 'Taxonomy slug', '4wp-notifications' ),
									value: attributes.taxonomy,
									onChange: function ( value ) {
										setAttributes( { taxonomy: value } );
									},
							  } )
							: null,
						createElement( ToggleControl, {
							label: __( 'Show text label', '4wp-notifications' ),
							checked: !! attributes.showLabel,
							onChange: function ( value ) {
								setAttributes( { showLabel: value } );
							},
						} )
					)
				),
				createElement(
					'button',
					blockProps,
					createElement(
						'span',
						{ className: 'forwp-favorite-button__icon', 'aria-hidden': true },
						'♥'
					),
					attributes.showLabel
						? createElement(
								'span',
								{ className: 'forwp-favorite-button__label' },
								__( 'Add to favorites', '4wp-notifications' )
						  )
						: null
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
