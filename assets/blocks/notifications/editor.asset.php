<?php
/**
 * Editor script dependencies for the notifications list block.
 *
 * @package ForWP_Notifications
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-i18n',
		'wp-server-side-render',
	),
	'version'      => FORWP_NOTIFICATIONS_VERSION,
);
