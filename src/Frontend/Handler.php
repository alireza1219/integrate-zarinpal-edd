<?php

namespace EDD_ZarinPal\Frontend;

use EDD_ZarinPal\Gateway\Gateway;
use EDD_ZarinPal\Plugin;

/**
 * Payment verification handler in the front-end.
 *
 * @since 1.0.0
 */
class Handler {

	/**
	 * Private constructor to prevent direct instantiation.
	 * Hooks into the payment verification action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {

		$this->hooks();
	}

	/**
	 * Register the hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hooks() {

		add_action( 'edd_zarinpal_payment_verification_completed', [ $this, 'handle_payment_verification' ], 10, 2 );
	}

	/**
	 * Handles the final phase of payment verification process based on the order status.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $order_id The ID of the order.
	 * @param object $order    The order object containing relevant details.
	 *
	 * @return void
	 */
	public function handle_payment_verification( $order_id, $order ) {

		switch ( $order->status ) {

			case Gateway::EDD_COMPLETED_STATUS_KEY:
				edd_send_to_success_page();
				break;

			case Gateway::EDD_FAILED_STATUS_KEY:
				edd_send_back_to_checkout( [ 'payment-mode' => $payment_data['post_data']['edd-gateway'] ?? Plugin::SLUG ] );
				break;
		}
	}
}
