<?php

namespace EDD_ZarinPal\Gateway;

use EDD_ZarinPal\Admin\Settings;
use EDD_ZarinPal\Helpers;
use EDD_ZarinPal\Plugin;

class Gateway {

	/**
	 * The EDD gateway nonce key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const NONCE_KEY = 'edd-gateway';

	/**
	 * The meta key for storing payment authority.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AUTHORITY_META_KEY = 'zarinpal_order_authority';

	/**
	 * The default Rial currency symbol, provided by EDD.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const IRR_SYMBOL = 'RIAL';

	/**
	 * Gateway class constructor.
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

		add_action( sprintf( 'edd_%s_cc_form', Plugin::SLUG ), '__return_false' ); // ZarinPal does not require a CC form.
		add_action( 'edd_gateway_' . Plugin::SLUG, [ $this, 'process_payment' ] );
	}

	/**
	 * Process the incoming payment request from EDD before sending to ZarinPal gateway.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payment_date All the payment data.
	 *
	 * @return void
	 */
	public function process_payment( $payment_data ) {

		Helpers::log_info( esc_html__( 'ZarinPal payment process has begun.', 'edd-zarinpal' ) );

		// Make PHP interpreter happy in rare situations.
		$payment_data = is_array( $payment_data ) ? $payment_data : [];

		// Start with the nonce verification.
		$nonce = $payment_data['gateway_nonce'] ?? '';

		// Nonce verification.
		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {

			Helpers::log_info( esc_html__( 'ZarinPal payment process was stopped due to a nonce verification problem.', 'edd-zarinpal' ) );

			wp_die(
				esc_html__( 'Nonce verification has failed', 'edd-zarinpal' ),
				esc_html__( 'Error', 'edd-zarinpal' ),
				[ 'response' => 403 ]
			);
		}

		$order_data = [
			'price'        => $payment_data['price'],
			'date'         => $payment_data['date'],
			'user_email'   => $payment_data['user_email'],
			'purchase_key' => $payment_data['purchase_key'],
			'currency'     => edd_get_currency(),
			'downloads'    => $payment_data['downloads'],
			'cart_details' => $payment_data['cart_details'],
			'user_info'    => $payment_data['user_info'],
			'status'       => 'pending',
			'gateway'      => Plugin::SLUG,
		];

		$order_id = edd_insert_payment( $order_data );

		// Something went wrong when creating the order.
		if ( ! $order_id ) {

			Helpers::log_error(
				esc_html__( 'Order creation failed before ZarinPal payment request initialization.', 'edd-zarinpal' ),
				esc_html__( 'ZarinPal Gateway Error', 'edd-zarinpal' ),
				false,
				$order_data
			);

			edd_send_back_to_checkout(
				[
					'payment-mode' => $payment_data['post_data']['edd-gateway'] ?? Plugin::SLUG,
				]
			);
		}

		// Prepare the ZarinPal API.
		$merchant_id  = edd_get_option( Settings::MERCHANT_SETTINGS_KEY );
		$zarinpal_api = new API( $merchant_id );
		$zarinpal_api->set_order_id( $order_id ); // Logging purposes.

		// Prepare the return URL.
		$callback_url = home_url( 'index.php' );
		$callback_url = add_query_arg(
			[
				'edd-listener' => 'ZARINPAL',
				'Order'        => $order_id,
				'Verification' => Helpers::generate_hash( $order_id ),
			],
			$callback_url
		);

		// Prepare the payment amount.
		// FIXME: EDD does not provide IRT currency by default, and various implementations can lead to complexity.
		// The best solution for now is to contact the EDD development department and request that they add IRT
		// to the list of core currencies.
		$amount = $order_data['currency'] === self::IRR_SYMBOL ? $order_data['price'] / 10 : $order_data['price'];

		// Prepare the payment description.
		// TODO: Check for description max length.
		$description = sprintf(
			/* translators: %1$s Order ID, %2$s: Customer name. */
			esc_html__( 'Order ID: %1$s, Customer Name: %2$s', 'edd-zarinpal' ),
			$order_id,
			Helpers::get_customer_name( $order_data['user_info'] )
		);

		$request_body = [
			'Amount'      => $amount,
			'CallbackURL' => $callback_url,
			'Description' => $description,
		];

		// Request a payment from ZarinPal.
		$request_result = $zarinpal_api->create_payment( $request_body );

		// Something went wrong.
		if ( ! $request_result || ! isset( $request_result['Status'] ) ) {

			Helpers::log_error(
				esc_html__( 'There was an error while trying to connect to ZarinPal\'s API.', 'edd-zarinpal' ),
				esc_html__( 'ZarinPal Gateway Error', 'edd-zarinpal' ),
				true,
				null,
				$order_id
			);

			edd_send_back_to_checkout(
				[
					'payment-mode' => $payment_data['post_data']['edd-gateway'] ?? Plugin::SLUG,
				]
			);
		}

		// Handle statues other than 100.
		if ( $request_result['Status'] !== 100 ) {

			Helpers::log_error(
				esc_html__( 'There was an error while trying to connect to ZarinPal\'s API.', 'edd-zarinpal' ),
				esc_html__( 'ZarinPal Gateway Error', 'edd-zarinpal' ),
				true,
				sprintf(
					/* translators: %1$s Error Code, %2$s: Parsed error message. */
					esc_html__( 'Error Code %1$s, %2$s', 'edd-zarinpal' ),
					$request_result['Status'],
					Helpers::parse_error_message( $request_result['Status'] )
				),
				$order_id
			);

			edd_send_back_to_checkout(
				[
					'payment-mode' => $payment_data['post_data']['edd-gateway'] ?? Plugin::SLUG,
				]
			);
		}

		Helpers::log_info( esc_html__( 'ZarinPal payment process has been completed successfully.', 'edd-zarinpal' ) );

		// Redirect the user to payment page.
		$authority    = $request_result['Authority'];
		$redirect_url = API::get_payment_page( $authority );
		edd_update_payment_meta( $order_id, self::AUTHORITY_META_KEY, $authority );
		wp_redirect( $redirect_url );
		edd_die();
	}
}
