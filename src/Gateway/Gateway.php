<?php

namespace Alireza1219\Integrate_ZarinPal_EDD\Gateway;

use Alireza1219\Integrate_ZarinPal_EDD\Admin\Settings;
use Alireza1219\Integrate_ZarinPal_EDD\Helpers;
use Alireza1219\Integrate_ZarinPal_EDD\Plugin;

/**
 * Gateway class.
 *
 * @since 1.0.0
 */
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
	 * The meta key for storing payment authority.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const REF_ID_META_KEY = 'zarinpal_order_reference_id';

	/**
	 * The key for the pending orders in EDD.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const EDD_PENDING_STATUS_KEY = 'pending';

	/**
	 * The key for the completed orders in EDD.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const EDD_COMPLETED_STATUS_KEY = 'complete';

	/**
	 * The key for the failed orders in EDD.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const EDD_FAILED_STATUS_KEY = 'failed';

	/**
	 * The maximum allowed length of the description field.
	 *
	 * @since 1.2.0
	 *
	 * @var int
	 */
	const DESCRIPTION_LENGTH = 500;

	/**
	 * ZarinPal's default gateway currency.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	const DEFAULT_GATEWAY_CURRENCY = 'IRR';

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
		add_action( sprintf( 'edd_gateway_%s', Plugin::SLUG ), [ $this, 'process_payment' ] );
		add_action( 'init', [ $this, 'process_verification' ] );
		add_filter( 'allowed_redirect_hosts', [ $this, 'allow_redirect' ], 10 );
	}

	/**
	 * Process the incoming payment request from EDD before sending to ZarinPal gateway.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Checks for recurring items in the cart.
	 * @since 1.2.0 Adapted to v4 API changes.
	 *
	 * @param array $payment_data All the payment data.
	 *
	 * @return void
	 */
	public function process_payment( $payment_data ) {

		Helpers::log_info( esc_html__( 'ZarinPal payment process has begun.', 'integrate-zarinpal-edd' ) );

		// Make PHP interpreter happy in rare situations.
		$payment_data = is_array( $payment_data ) ? $payment_data : [];

		// Start with the nonce verification.
		$nonce = $payment_data['gateway_nonce'] ?? '';

		// Nonce verification.
		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {

			Helpers::log_info( esc_html__( 'ZarinPal payment process was stopped due to a nonce verification problem.', 'integrate-zarinpal-edd' ) );

			wp_die(
				esc_html__( 'Nonce verification has failed', 'integrate-zarinpal-edd' ),
				esc_html__( 'Error', 'integrate-zarinpal-edd' ),
				[ 'response' => 403 ]
			);
		}

		// ZarinPal does not support recurring payments, check cart items before proceeding.
		if ( is_array( $payment_data['cart_details'] ) && ! empty( $payment_data['cart_details'] ) ) {

			foreach ( $payment_data['cart_details'] as $item ) {

				if ( isset( $item['item_number']['options']['recurring'] ) ) {

					Helpers::log_error(
						esc_html__( 'Integrate ZarinPal for EDD does not support recurring items in cart.', 'integrate-zarinpal-edd' ),
						esc_html__( 'ZarinPal Gateway Error', 'integrate-zarinpal-edd' ),
						true
					);

					edd_send_back_to_checkout( [ 'payment-mode' => $payment_data['post_data']['edd-gateway'] ?? Plugin::SLUG ] );
				}
			}
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
			'status'       => self::EDD_PENDING_STATUS_KEY,
			'gateway'      => Plugin::SLUG,
		];

		$order_id = edd_insert_payment( $order_data );

		// Something went wrong when creating the order.
		if ( ! $order_id ) {

			Helpers::log_error(
				esc_html__( 'Order creation failed before ZarinPal payment request initialization.', 'integrate-zarinpal-edd' ),
				esc_html__( 'ZarinPal Gateway Error', 'integrate-zarinpal-edd' ),
				false,
				$order_data
			);

			edd_send_back_to_checkout( [ 'payment-mode' => $payment_data['post_data']['edd-gateway'] ?? Plugin::SLUG ] );
		}

		// Ensure $order_id is an integer. In rare cases, such as payment restoration, it may be a string.
		$order_id = (int) $order_id;

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

		// Prepare the payment amount. The final amount is always in Rial.
		$amount = ! Helpers::is_rial( $order_data['currency'] ) ? $order_data['price'] * 10 : $order_data['price'];

		// Prepare the payment description.
		$description = sprintf(
			/* translators: %1$s Order ID, %2$s: Customer name. */
			esc_html__( 'Order ID: %1$s, Customer Name: %2$s', 'integrate-zarinpal-edd' ),
			$order_id,
			Helpers::get_customer_name( $order_data['user_info'] )
		);

		// Additional length check since description field cannot be more than 500 characters.
		if ( mb_strlen( $description ) > self::DESCRIPTION_LENGTH ) {

			// Use a non-localized description without the customer name to make it shorter.
			$description = 'Order ID: ' . $order_id;
		}

		$request_body = [
			'currency'     => self::DEFAULT_GATEWAY_CURRENCY, // The request amount is always in Rial. So, enforce Rial as the default currency.
			'amount'       => $amount,
			'callback_url' => $callback_url,
			'description'  => $description,
		];

		// Request a payment from ZarinPal.
		$request_result = $zarinpal_api->create_payment( $request_body );

		// Something went wrong.
		if ( ! $request_result || ! isset( $request_result['data']['code'] ) ) {

			$error_code    = $request_result['errors']['code'] ?? '';
			$error_message = '';
			$error_details = null;

			// Try to get a more specific error message.
			if ( ! empty( $error_code ) ) {

				// Build the error details.
				$error_message = Helpers::parse_error_message( $error_code );
				$error_details = sprintf(
					/* translators: %1$s Error Code, %2$s: Parsed error message. */
					esc_html__( 'Error Code %1$s, %2$s', 'integrate-zarinpal-edd' ),
					$error_code,
					$error_message
				);
			}

			Helpers::log_error(
				esc_html__( 'There was an error while trying to connect to ZarinPal\'s API.', 'integrate-zarinpal-edd' ),
				esc_html__( 'ZarinPal Gateway Error', 'integrate-zarinpal-edd' ),
				true,
				$error_details,
				$order_id
			);

			edd_send_back_to_checkout( [ 'payment-mode' => $payment_data['post_data']['edd-gateway'] ?? Plugin::SLUG ] );
		}

		// Handle statues other than 100.
		if ( $request_result['data']['code'] !== 100 ) {

			Helpers::log_error(
				esc_html__( 'There was an error while trying to connect to ZarinPal\'s API.', 'integrate-zarinpal-edd' ),
				esc_html__( 'ZarinPal Gateway Error', 'integrate-zarinpal-edd' ),
				true,
				sprintf(
					/* translators: %1$s Error Code, %2$s: Parsed error message. */
					esc_html__( 'Error Code %1$s, %2$s', 'integrate-zarinpal-edd' ),
					$request_result['data']['code'],
					Helpers::parse_error_message( $request_result['data']['code'] )
				),
				$order_id
			);

			edd_send_back_to_checkout( [ 'payment-mode' => $payment_data['post_data']['edd-gateway'] ?? Plugin::SLUG ] );
		}

		Helpers::log_info( esc_html__( 'ZarinPal payment process has been completed successfully.', 'integrate-zarinpal-edd' ) );

		// Redirect the user to payment page.
		$authority    = $request_result['data']['authority'];
		$redirect_url = API::get_payment_page( $authority );
		edd_update_payment_meta( $order_id, self::AUTHORITY_META_KEY, $authority );
		edd_redirect( $redirect_url );
		edd_die();
	}

	/**
	 * Processes the ZarinPal payment verification requests.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Adapted to v4 API changes.
	 *
	 * @return void
	 */
	public function process_verification() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated

		if ( ! isset( $_GET['edd-listener'] ) || $_GET['edd-listener'] !== 'ZARINPAL' ) {
			return;
		}

		Helpers::log_info( esc_html__( 'ZarinPal payment verification has begun.', 'integrate-zarinpal-edd' ) );

		// All required arguments must be set and not empty.
		$required_args = [ 'Order', 'Verification', 'Authority' ];
		foreach ( $required_args as $key ) {
			if ( ! isset( $_GET[ $key ] ) || empty( $_GET[ $key ] ) ) {

				Helpers::log_info( esc_html__( 'ZarinPal payment verification was stopped due to the missing required parameters.', 'integrate-zarinpal-edd' ) );

				return;
			}
		}

		// Retrieve, sanitize and store the GET parameters for further use.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$order_id          = (int) edd_sanitize_key( wp_unslash( $_GET['Order'] ) );
		$verification_hash = edd_sanitize_key( wp_unslash( $_GET['Verification'] ) );
		$authority         = edd_sanitize_key( wp_unslash( $_GET['Authority'] ) );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated

		// Do nothing when the hash computed from the order ID differs from the received hash.
		if ( ! Helpers::verify_hash( $order_id, $verification_hash ) ) {

			Helpers::log_info( esc_html__( 'ZarinPal payment verification was stopped due to a mismatch in the verification hash.', 'integrate-zarinpal-edd' ) );

			return;
		}

		$order = edd_get_order( $order_id );

		if ( ! $order ) {

			Helpers::log_info( esc_html__( 'ZarinPal payment verification was stopped due to a missing order record.', 'integrate-zarinpal-edd' ) );

			return;
		}

		if ( $order->status !== self::EDD_PENDING_STATUS_KEY ) {

			Helpers::log_info( esc_html__( 'ZarinPal payment verification was stopped because the order is undergoing re-verification.', 'integrate-zarinpal-edd' ) );

			return;
		}

		$amount = (int) $order->total;
		// Ensure the amount is in Rial since ZarinPal v4 API requires the verification amount to be in Rial.
		if ( ! Helpers::is_rial( $order->currency ) ) {
			$amount = $amount * 10;
		}

		$request_body = [
			'authority' => $authority,
			'amount'    => $amount,
		];

		$zarinpal_api = new API( edd_get_option( Settings::MERCHANT_SETTINGS_KEY ) );
		$zarinpal_api->set_order_id( $order_id );

		// Verify the payment via the ZarinPal API.
		$request_result = $zarinpal_api->verify_payment( $request_body );

		// The payment verification was unsuccessful.
		if (
			! $request_result ||
			! isset( $request_result['data']['code'] ) ||
			! in_array( (int) $request_result['data']['code'], [ 100, 101 ], true ) ||
			empty( $request_result['data']['ref_id'] )
		) {

			// Try to parse the error code from the verification result.
			$status = $request_result['errors']['code'] ?? $request_result['data']['code'] ?? -1219;

			$failure_message = esc_html__( 'ZarinPal payment verification failed!', 'integrate-zarinpal-edd' );
			$failure_reason  = sprintf(
				/* translators: %1$s Error Code, %2$s: Parsed error message. */
				esc_html__( 'Error Code %1$s, %2$s', 'integrate-zarinpal-edd' ),
				$status,
				Helpers::parse_error_message( $status )
			);

			Helpers::log_error(
				$failure_message,
				esc_html__( 'ZarinPal Gateway Error', 'integrate-zarinpal-edd' ),
				true,
				$failure_reason,
				$order_id
			);

			// Mark this order as failed.
			edd_update_payment_status( $order_id, self::EDD_FAILED_STATUS_KEY );

			// Include a note explaining the reason for the payment failure.
			edd_insert_payment_note( $order_id, sprintf( '%s %s', $failure_message, $failure_reason ) );
		} else {

			Helpers::log_info( esc_html__( 'ZarinPal payment verification has been completed successfully.', 'integrate-zarinpal-edd' ) );

			$reference_id = $request_result['data']['ref_id'];

			// Mark this order as completed.
			edd_update_payment_status( $order_id, self::EDD_COMPLETED_STATUS_KEY );

			edd_update_payment_meta( $order_id, self::REF_ID_META_KEY, $reference_id );

			// Include a note with reference ID.
			edd_insert_payment_note(
				$order_id,
				sprintf(
					/* translators: %1$s Reference ID, %2$s: Authority. */
					esc_html__( 'ZarinPal payment was successful. Reference ID: %1$s, Authority: %2$s.', 'integrate-zarinpal-edd' ),
					$reference_id,
					$authority
				)
			);
		}

		/**
		 * Fires when a Zarinpal payment verification process completes.
		 *
		 * This action allows developers to handle custom actions,
		 * such as logging errors, notifying the customer, or triggering custom workflows.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $order_id The ID of the order.
		 * @param object $order    The order object containing relevant details.
		 */
		do_action( 'integrate_zarinpal_edd_payment_verification_completed', $order_id, edd_get_order( $order_id ) );
	}

	/**
	 * Allow wp_safe_redirect to redirect to ZarinPal.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Updated ZarinPal endpoints.
	 *
	 * @param array $redirects The list of urls that wp_safe_redirect can redirect to.
	 *
	 * @return array
	 */
	public function allow_redirect( $redirects ) {

		$redirects[] = 'payment.zarinpal.com';
		$redirects[] = 'sandbox.zarinpal.com';

		return $redirects;
	}
}
