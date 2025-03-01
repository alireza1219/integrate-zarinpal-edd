<?php

namespace EDD_ZarinPal;

class Helpers {

	/**
	 * Logs an informational message.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $message Log message.
	 * @param bool       $force   Optional: Whether to force an info log entry. Default false.
	 * @param mixed|null $context Optional: Additional data to log.
	 *
	 * @return void
	 */
	public static function log_info( $message, $force = false, $context = null ) {

		if ( ! empty( $context ) ) {
			$message .= sprintf(
				"\n%s:\n%s",
				esc_html__( 'Context', 'edd-zarinpal' ),
				print_r( $context, true )
			);
		}

		edd_debug_log( $message, $force ); // Logs message to debug log.
	}

	/**
	 * Logs an error message with optional session storage.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $message     Log message.
	 * @param string     $error_title Optional: Title of the error.
	 * @param bool       $store_error Optional: Whether to store the error in the session. Default false.
	 * @param mixed|null $context     Optional: Additional data to log.
	 * @param int        $log_parent  Optional: Parent log entry. Default 0.
	 *
	 * @return void
	 */
	public static function log_error( $message, $error_title = '', $store_error = false, $context = null, $log_parent = 0 ) {

		$error_title = empty( $error_title ) ? esc_html__( 'ZarinPal Gateway Error', 'edd-zarinpal' ) : $error_title;

		if ( ! empty( $context ) ) {
			$message .= sprintf(
				"\n%s:\n%s",
				esc_html__( 'Context', 'edd-zarinpal' ),
				print_r( $context, true )
			);
		}

		$error_id = edd_record_gateway_error( $error_title, $message, $log_parent ); // Logs error.

		if ( $store_error ) {
			edd_set_error( $error_id, $message ); // Stores error in session.
		}
	}

	/**
	 * Hash the given data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $data Data to hash.
	 *
	 * @return string Hashed data.
	 */
	public static function generate_hash( $data ) {

		return wp_hash( (string) $data, wp_salt() );
	}

	/**
	 * Verifies the received data against the received hash.
	 *
	 * @since 1.0.0
	 *
	 * @param string $received_data     Received data to hash.
	 * @param string $received_hash The known hash for the data.
	 *
	 * @return bool True if the received data are valid.
	 */
	public static function verify_hash( $received_data, $received_hash ) {

		$computed_hash = self::generate_hash( $received_data );
		return hash_equals( $computed_hash, $received_hash );
	}

	/**
	 * Return the customer name based on the passed user info.
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_info An associative array with 'first_name' and 'last_name' keys.
	 *
	 * @return string The full name.
	 */
	public static function get_customer_name( $user_info ) {

		$first_name = $user_info['first_name'] ?? '';
		$last_name  = $user_info['last_name'] ?? '';

		if ( empty( $first_name ) && empty( $last_name ) ) {

			return esc_html__( 'Not Available', 'edd-zarinpal' );
		}

		return trim( "$first_name $last_name" );
	}

	/**
	 * Parse ZarinPal error by the given code.
	 *
	 * @param string $code ZarinPal error code.
	 *
	 * @return string A proper message for the given error code.
	 */
	public static function parse_error_message( $code ) {

		$error = [
			'-1'  => esc_attr__( 'Incomplete information provided.', 'edd-zarinpal' ),
			'-2'  => esc_attr__( 'Incorrect IP or merchant code.', 'edd-zarinpal' ),
			'-3'  => esc_attr__( 'Payment with the requested amount is not possible due to Shaparak limitations.', 'edd-zarinpal' ),
			'-4'  => esc_attr__( 'Merchant confirmation level is lower than the Silver level.', 'edd-zarinpal' ),
			'-9'  => esc_attr__( 'There was a validation error.', 'edd-zarinpal' ),
			'-10' => esc_attr__( 'Terminal is not valid. Please check your Merchant ID or IP address.', 'edd-zarinpal' ),
			'-11' => esc_attr__( 'Terminal is not active. Please contact our support team.', 'edd-zarinpal' ),
			'-12' => esc_attr__( 'Too many attempts, please try again later.', 'edd-zarinpal' ),
			'-15' => esc_attr__( 'Terminal user is suspended.', 'edd-zarinpal' ),
			'-16' => esc_attr__( 'Terminal user level is not valid.', 'edd-zarinpal' ),
			'-17' => esc_attr__( 'Terminal user level is not valid.', 'edd-zarinpal' ),
			'-21' => esc_attr__( 'No financial operation found for this transaction.', 'edd-zarinpal' ),
			'-22' => esc_attr__( 'The transaction has been unsuccessful.', 'edd-zarinpal' ),
			'-33' => esc_attr__( 'Transaction amount does not match the paid amount.', 'edd-zarinpal' ),
			'-34' => esc_attr__( 'The transaction has reached the limit for the number or amount of divisions.', 'edd-zarinpal' ),
			'-39' => esc_attr__( 'An unexpected error has occurred. Please get in touch with the ZarinPal\'s customer care.', 'edd-zarinpal' ),
			'-40' => esc_attr__( 'No access permission to the relevant method.', 'edd-zarinpal' ),
			'-41' => esc_attr__( 'The provided data related to AdditionalData is invalid.', 'edd-zarinpal' ),
			'-42' => esc_attr__( 'The valid lifespan of the payment ID must be between 30 minutes to 45 days.', 'edd-zarinpal' ),
			'-50' => esc_attr__( 'The amount paid is different from the amount sent in the verification method.', 'edd-zarinpal' ),
			'-51' => esc_attr__( 'Failed payment.', 'edd-zarinpal' ),
			'-52' => esc_attr__( 'An unexpected error has occurred. Please get in touch with the ZarinPal\'s customer care.', 'edd-zarinpal' ),
			'-53' => esc_attr__( 'The payment does not belong to this merchant code.', 'edd-zarinpal' ),
			'-54' => esc_attr__( 'Invalid authority.', 'edd-zarinpal' ),
			'100' => esc_attr__( 'The operation was successful.', 'edd-zarinpal' ),
			'101' => esc_attr__( 'The payment operation was successful, and the payment verification for the transaction has been done before.', 'edd-zarinpal' ),
		];

		if ( array_key_exists( "$code", $error ) ) {

			return $error[ "$code" ];
		} else {

			return esc_attr__( 'An unknown error occurred while connecting to the ZarinPal gateway.', 'edd-zarinpal' );
		}
	}
}
