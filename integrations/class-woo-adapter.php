<?php
/**
 * WooCommerce adapter: order created and order status changed → in-app notification.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Woo_Adapter {

	public function __construct() {
		$this->register_hooks();
	}

	private function register_hooks() {
		add_action( 'woocommerce_new_order', array( $this, 'on_new_order' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 4 );
	}

	/**
	 * New order created.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order (optional, WC 3.0+).
	 */
	public function on_new_order( $order_id, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}
		$user_id = (int) $order->get_customer_id();
		if ( $user_id <= 0 ) {
			return;
		}
		$view_url = wc_get_account_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) );
		if ( $view_url ) {
			$view_url = add_query_arg( 'view', 'order-' . $order_id, $view_url );
		} else {
			$view_url = $order->get_view_order_url();
		}
		ForWP_Notifications_Queue::push(
			$user_id,
			'order_created',
			'woo',
			array(
				'title'   => __( 'New order received', 'forwp-notifications' ),
				'message' => sprintf( __( 'Order #%d has been created.', 'forwp-notifications' ), $order_id ),
				'url'     => $view_url,
				'actions' => array(
					array( 'type' => 'view', 'label' => __( 'View order', 'forwp-notifications' ), 'url' => $view_url ),
				),
			),
			$order_id
		);
	}

	/**
	 * Order status changed.
	 *
	 * @param int      $order_id   Order ID.
	 * @param string   $from       Previous status.
	 * @param string   $to         New status.
	 * @param WC_Order $order      Order.
	 */
	public function on_order_status_changed( $order_id, $from, $to, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}
		$user_id = (int) $order->get_customer_id();
		if ( $user_id <= 0 ) {
			return;
		}
		$view_url = $order->get_view_order_url();
		$status_label = function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( 'wc-' . $to ) : $to;
		if ( empty( $status_label ) || $status_label === 'wc-' . $to ) {
			$status_label = $to;
		}
		$title = sprintf( __( 'Order #%1$d: %2$s', 'forwp-notifications' ), $order_id, $status_label );
		ForWP_Notifications_Queue::push(
			$user_id,
			'order_status_changed',
			'woo',
			array(
				'title'   => $title,
				'message' => '',
				'url'     => $view_url,
				'actions' => array(
					array( 'type' => 'view', 'label' => __( 'View order', 'forwp-notifications' ), 'url' => $view_url ),
				),
			),
			$order_id
		);
	}
}
