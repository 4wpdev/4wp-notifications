<?php
/**
 * Editor script dependencies for the favorite button block.
 *
 * @package ForWP_Notifications
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
	),
	'version'      => FORWP_NOTIFICATIONS_VERSION,
);
