<?php
/**
 * Editor script dependencies for the notifications bell block.
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
	),
	'version'      => FORWP_NOTIFICATIONS_VERSION,
);
