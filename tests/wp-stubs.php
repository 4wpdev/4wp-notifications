<?php
/**
 * Minimal WordPress stubs for unit tests.
 *
 * phpcs:ignoreFile
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

$GLOBALS['forwp_notifications_test_users'] = array();
$GLOBALS['forwp_notifications_test_caps']  = array();

/**
 * Reset stub state between tests.
 */
function forwp_notifications_tests_reset(): void {
	$GLOBALS['forwp_notifications_test_users'] = array();
	$GLOBALS['forwp_notifications_test_caps']  = array();
}

forwp_notifications_tests_reset();

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		unset( $hook );
		$args = func_get_args();
		return $args[1];
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = strip_tags( (string) $string );
		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', '', $string );
		}
		return trim( $string );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( wp_strip_all_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( wp_strip_all_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		$url = trim( (string) $url );
		return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return ! empty( $GLOBALS['forwp_notifications_test_caps'][ $capability ] );
	}
}

if ( ! function_exists( 'get_userdata' ) ) {
	function get_userdata( $user_id ) {
		$user_id = (int) $user_id;
		return $GLOBALS['forwp_notifications_test_users'][ $user_id ] ?? false;
	}
}

if ( ! function_exists( 'has_block' ) ) {
	function has_block( $block_name, $post = null ) {
		unset( $block_name, $post );
		return false;
	}
}

if ( ! function_exists( 'has_shortcode' ) ) {
	function has_shortcode( $content, $tag ) {
		return false !== strpos( (string) $content, '[' . $tag );
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		/** @var string */
		public $post_type = 'page';
		/** @var string */
		public $post_content = '';
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		/** @var string */
		private $code;
		/** @var string */
		private $message;
		/** @var mixed */
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook ) {
		unset( $hook );
	}
}
