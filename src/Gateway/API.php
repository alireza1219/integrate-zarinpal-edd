<?php

namespace Alireza1219\Integrate_ZarinPal_EDD\Gateway;

use Alireza1219\Integrate_ZarinPal_EDD\Admin\Settings;
use Alireza1219\Integrate_ZarinPal_EDD\Helpers;

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
	 * @since 1.2.0 Updated to v4 API.
	 *
	 * @var string
	 */
	const LIVE_ENDPOINT = 'https://payment.zarinpal.com/pg/v4/payment/';

	/**
	 * The API endpoint for the sandbox environment.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Use ZarinPal's official sandbox environment.
	 *
	 * @var string
	 */
	const TEST_ENDPOINT = 'https://sandbox.zarinpal.com/pg/v4/payment/';

	/**
	 * The payment page for the live environment.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Updated to the latest endpoint.
	 *
	 * @var string
	 */
	const LIVE_PAYMENT_PAGE = 'https://payment.zarinpal.com/pg/StartPay/';

	/**
	 * The payment page for the sandbox environment.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Use ZarinPal's official sandbox environment.
	 *
	 * @var string
	 */
	const TEST_PAYMENT_PAGE = 'https://sandbox.zarinpal.com/pg/StartPay/';

	/**
	 * Valid API actions.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Adapted actions to v4 API changes.
	 *
	 * @var array
	 */
	const VALID_ACTIONS = [ 'verify', 'request' ];

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
	 * @since 1.2.0 Adapted to v4 API changes.
	 *
	 * @param string $action  The API action (request|verify).
	 * @param array  $params  Request parameters.
	 * @param int    $timeout Request timeout in seconds.
	 *
	 * @return array|false The API response or false on failure.
	 */
	public function send_request( $action, $params, $timeout = 15 ) {

		// Validate the action.
		if ( ! in_array( $action, self::VALID_ACTIONS, true ) ) {

			Helpers::log_error(
				esc_html__( 'Invalid ZarinPal API action provided!', 'integrate-zarinpal-edd' ),
				esc_html__( 'ZarinPal API Action Error', 'integrate-zarinpal-edd' ),
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
				'merchant_id' => $this->merchant_id,
			]
		);

		// Setup request arguments.
		$args = [
			'method'     => 'POST',
			'body'       => wp_json_encode( $params ),
			'user-agent' => $this->get_user_agent(),
			'timeout'    => $timeout,
			'headers'    => [
				'content-type' => 'application/json',
			],
		];

		// Log the request.
		Helpers::log_info(
			sprintf(
				/* translators: %s The current endpoint action. */
				esc_html__( 'Making API request to %s endpoint', 'integrate-zarinpal-edd' ),
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
	 * @since 1.2.0 Return parsed response data for non-200 HTTP responses when available.
	 *
	 * @param WP_Error|array $response The response from wp_remote_request.
	 * @param string         $action   The API action that was performed.
	 *
	 * @return array|false Decoded response data or false on error.
	 */
	private function handle_response( $response, $action ) {

		// Check for WP_Error.
		if ( is_wp_error( $response ) ) {

			Helpers::log_error(
				$response->get_error_message(),
				esc_html__( 'ZarinPal API Request Failure', 'integrate-zarinpal-edd' ),
				false,
				[ 'action' => $action ],
				$this->order_id
			);

			return false;
		}

		// Get and decode response body.
		$body       = wp_remote_retrieve_body( $response );
		$data       = json_decode( $body, true );
		$json_error = json_last_error();

		// Check status code.
		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code !== 200 ) {

			Helpers::log_error(
				sprintf(
					/* translators: %d API HTTP response code. */
					esc_html__( 'ZarinPal payment gateway returned HTTP status code %d', 'integrate-zarinpal-edd' ),
					$status_code
				),
				esc_html__( 'ZarinPal API Status Error', 'integrate-zarinpal-edd' ),
				false,
				[
					'action' => $action,
					'status' => $status_code,
					'body'   => $body,
				],
				$this->order_id
			);

			return JSON_ERROR_NONE === $json_error && is_array( $data ) ? $data : false;
		}

		// Check for JSON decode errors.
		if ( JSON_ERROR_NONE !== $json_error ) {

			Helpers::log_error(
				esc_html__( 'Failed to decode ZarinPal API response', 'integrate-zarinpal-edd' ),
				esc_html__( 'JSON Decode Error', 'integrate-zarinpal-edd' ),
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
				esc_html__( 'Request to %s ZarinPal endpoint succeeded', 'integrate-zarinpal-edd' ),
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
	 * @since 1.2.0 Adapted to v4 API changes.
	 *
	 * @param array $payment_data Payment data.
	 *
	 * @return array|false
	 */
	public function create_payment( $payment_data ) {

		return $this->send_request( 'request', $payment_data );
	}

	/**
	 * Verify a payment.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Adapted to v4 API changes.
	 *
	 * @param array $verification_data Verification data.
	 *
	 * @return array|false
	 */
	public function verify_payment( $verification_data ) {

		return $this->send_request( 'verify', $verification_data );
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

	/**
	 * Generate a user agent based on the current configuration.
	 *
	 * @since 1.2.0
	 *
	 * @return string The generated user agent.
	 */
	private function get_user_agent() {

		// Collect versions.
		$plugin_version    = defined( 'INTEGRATE_ZARINPAL_EDD_VERSION' ) ? INTEGRATE_ZARINPAL_EDD_VERSION : 'N/A';
		$edd_version       = defined( 'EDD_VERSION' ) ? EDD_VERSION : 'N/A';
		$wordpress_version = get_bloginfo( 'version' );
		$php_version       = defined( 'PHP_VERSION' ) ? PHP_VERSION : 'N/A';

		return "Integrate ZarinPal EDD $plugin_version (EDD $edd_version; WordPress $wordpress_version; PHP $php_version)";
	}
}
