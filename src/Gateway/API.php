<?php

namespace EDD_ZarinPal\Gateway;

use EDD_ZarinPal\Admin\Settings;
use EDD_ZarinPal\Helpers;

/**
 * ZarinPal API class.
 *
 * @since 1.0.0
 */
class API {

	/**
	 * The API endpoint for the live environment.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const LIVE_ENDPOINT = 'https://www.zarinpal.com/pg/rest/WebGate/';

	/**
	 * The API endpoint for the sandbox environment.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const TEST_ENDPOINT = 'https://sandbox.alireza1219.ir/zarinpal/pg/rest/WebGate/';

	/**
	 * The payment page for the live environment.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const LIVE_PAYMENT_PAGE = 'https://www.zarinpal.com/pg/StartPay/';

	/**
	 * The payment page for the sandbox environment.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const TEST_PAYMENT_PAGE = 'https://sandbox.alireza1219.ir/zarinpal/pg/StartPay/';

	/**
	 * Valid API actions.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	const VALID_ACTIONS = [ 'PaymentVerification', 'PaymentRequest' ];

	/**
	 * ZarinPal payment gateway Merchant ID.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $merchant_id;

	/**
	 * The order ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private $order_id = 0;

	/**
	 * API class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $merchant_id The merchant ID for the request authentication.
	 *
	 * @return void
	 */
	public function __construct( $merchant_id ) {

		$this->merchant_id = $merchant_id;
	}

	/**
	 * Send a request to the API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action  The API action (PaymentRequest|PaymentVerification).
	 * @param array  $params  Request parameters.
	 * @param int    $timeout Request timeout in seconds.
	 *
	 * @return array|false The API response or false on failure.
	 */
	public function send_request( $action, $params, $timeout = 15 ) {

		// Validate the action.
		if ( ! in_array( $action, self::VALID_ACTIONS, true ) ) {

			Helpers::log_error(
				esc_html__( 'Invalid ZarinPal API action provided!', 'edd-zarinpal' ),
				esc_html__( 'ZarinPal API Action Error', 'edd-zarinpal' ),
				false,
				[ 'action' => $action ],
				$this->order_id
			);
			return false;
		}

		// Get the endpoint based on the current payment mode.
		$endpoint = $this->get_endpoint( $action );

		// Add merchant ID to the original params.
		$params = array_merge(
			$params,
			[
				'MerchantID' => $this->merchant_id,
			]
		);

		// Setup request arguments.
		$args = [
			'method'     => 'POST',
			'body'       => wp_json_encode( $params ),
			'user-agent' => 'ZarinPal Rest Api v1',
			'timeout'    => $timeout,
			'headers'    => [
				'content-type' => 'application/json',
			],
		];

		// Log the request.
		Helpers::log_info(
			sprintf(
				/* translators: %s The current endpoint action. */
				esc_html__( 'Making API request to %s endpoint', 'edd-zarinpal' ),
				$action
			),
			false,
			[
				'endpoint' => $endpoint,
				'params'   => $params,
			]
		);

		// Make the request.
		$response = wp_remote_request( $endpoint, $args );

		// Handle response.
		return $this->handle_response( $response, $action );
	}

	/**
	 * Handle the API response.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error|array $response The response from wp_remote_request.
	 * @param string         $action   The API action that was performed.
	 *
	 * @return array|false Decoded response data or false on error
	 */
	private function handle_response( $response, $action ) {

		// Check for WP_Error.
		if ( is_wp_error( $response ) ) {

			Helpers::log_error(
				$response->get_error_message(),
				esc_html__( 'ZarinPal API Request Failure', 'edd-zarinpal' ),
				false,
				[ 'action' => $action ],
				$this->order_id
			);

			return false;
		}

		// Check status code.
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {

			Helpers::log_error(
				sprintf(
					/* translators: %d API HTTP response code. */
					esc_html__( 'ZarinPal payment gateway returned HTTP status code %d', 'edd-zarinpal' ),
					$status_code
				),
				esc_html__( 'ZarinPal API Status Error', 'edd-zarinpal' ),
				false,
				[
					'action' => $action,
					'status' => $status_code,
				],
				$this->order_id
			);
			return false;
		}

		// Get and decode response body.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {

			Helpers::log_error(
				esc_html__( 'Failed to decode ZarinPal API response', 'edd-zarinpal' ),
				esc_html__( 'JSON Decode Error', 'edd-zarinpal' ),
				false,
				[
					'action' => $action,
					'body'   => $body,
				],
				$this->order_id
			);
			return false;
		}

		// Log successful request.
		Helpers::log_info(
			sprintf(
				/* translators: %s Current API action. */
				esc_html__( 'Request to %s ZarinPal endpoint succeeded', 'edd-zarinpal' ),
				$action
			),
			false,
			[ 'response' => $data ]
		);

		return $data;
	}

	/**
	 * Create a payment request.
	 *
	 * @since 1.0.0
	 *
	 * @param array $payment_data Payment data.
	 *
	 * @return array|false
	 */
	public function create_payment( $payment_data ) {

		return $this->send_request( 'PaymentRequest', $payment_data );
	}

	/**
	 * Verify a payment.
	 *
	 * @since 1.0.0
	 *
	 * @param array $verification_data Verification data.
	 *
	 * @return array|false
	 */
	public function verify_payment( $verification_data ) {

		return $this->send_request( 'PaymentVerification', $verification_data );
	}

	/**
	 * Set the order ID. Mostly used with logging purposes.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id The order id.
	 *
	 * @return void
	 */
	public function set_order_id( $order_id ) {

		if ( ! is_int( $order_id ) ) {
			return;
		}

		$this->order_id = $order_id;
	}

	/**
	 * Get the redirect URL to payment page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $authority The payment authority.
	 *
	 * @return string The payment page URL.
	 */
	public static function get_payment_page( $authority ) {

		$base_url = edd_is_test_mode() ? self::TEST_PAYMENT_PAGE : self::LIVE_PAYMENT_PAGE;

		$zaringate_enabled = (bool) edd_get_option( Settings::ZARINGATE_SETTINGS_KEY );

		return $base_url . $authority . ( $zaringate_enabled === true ? '/ZarinGate' : '' );
	}

	/**
	 * Get the appropriate endpoint URL based on test mode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The API action.
	 *
	 * @return string The generated API endpoint URL.
	 */
	private function get_endpoint( $action ) {

		$base_url = edd_is_test_mode() ? self::TEST_ENDPOINT : self::LIVE_ENDPOINT;

		return $base_url . $action . '.json';
	}
}
