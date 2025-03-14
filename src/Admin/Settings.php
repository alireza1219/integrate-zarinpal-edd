<?php

namespace EDD_ZarinPal\Admin;

use EDD_ZarinPal\Plugin;

/**
 * Settings class.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * The setting key for the Merchant ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const MERCHANT_SETTINGS_KEY = 'zarinpal_merchant_id';

	/**
	 * The setting key for the ZarinGate.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ZARINGATE_SETTINGS_KEY = 'zarinpal_zaringate';

	/**
	 * Settings class constructor.
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

		add_filter( 'edd_payment_gateways', [ $this, 'extend_payment_gateways' ] );
		add_filter( 'edd_settings_gateways', [ $this, 'register_gateway_settings' ] );
		add_filter( 'edd_settings_sections_gateways', [ $this, 'register_gateway_section' ] );
		add_filter( 'edd_is_gateway_setup_' . Plugin::SLUG, [ $this, 'can_setup' ] );
		add_filter( 'edd_gateway_settings_url_' . Plugin::SLUG, [ $this, 'assign_settings_url' ] );
	}

	/**
	 * Extends EDD's available payment gateways.
	 *
	 * @since 1.0.0
	 *
	 * @param array $gateways All the available gateways.
	 *
	 * @return array The extended gateways array.
	 */
	public function extend_payment_gateways( $gateways ) {

		$gateways[ Plugin::SLUG ] = [
			'admin_label'    => __( 'ZarinPal', 'edd-zarinpal' ),
			'checkout_label' => __( 'ZarinPal', 'edd-zarinpal' ),
		];

		return $gateways;
	}

	/**
	 * Registers the ZarinPal settings for the ZarinPal subsection.
	 *
	 * @since 1.0.0
	 *
	 * @param array $gateway_settings Gateway tab settings.
	 *
	 * @return array Gateway tab settings with the ZarinPal settings.
	 */
	public function register_gateway_settings( $gateway_settings ) {

		$zarinpal_settings = [
			self::ZARINGATE_SETTINGS_KEY => [
				'id'   => self::ZARINGATE_SETTINGS_KEY,
				'name' => __( 'Enable ZarinGate', 'edd-zarinpal' ),
				'type' => 'checkbox_toggle',
			],
			self::MERCHANT_SETTINGS_KEY  => [
				'id'   => self::MERCHANT_SETTINGS_KEY,
				'name' => __( 'ZarinPal Merchant ID', 'edd-zarinpal' ),
				'desc' => __( 'Enter your ZarinPal Merchant ID here.', 'edd-zarinpal' ),
				'type' => 'text',
				'size' => 'regular',
			],
		];

		/**
		 * Filters the ZarinPal settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $zarinpal_settings
		 */
		$zarinpal_settings = apply_filters( 'edd_zarinpal_settings', $zarinpal_settings );

		// Include the Zarinpal settings in gateway settings.
		$gateway_settings[ Plugin::SLUG ] = $zarinpal_settings;

		return $gateway_settings;
	}

	/**
	 * Register the ZarinPal gateway subsection.
	 *
	 * @since 1.0.0
	 *
	 * @param array $gateway_sections Current Gateway Tab subsections.
	 *
	 * @return array The extended gateway section that includes ZarinPal tab.
	 */
	public function register_gateway_section( $gateway_sections ) {

		$gateway_sections[ Plugin::SLUG ] = __( 'ZarinPal', 'edd-zarinpal' );

		return $gateway_sections;
	}

	/**
	 * Can the ZarinPal gateway be enabled?
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $is_setup Whether or not the gateway is setup.
	 *
	 * @return boolean True if the gateway's Merchant ID is set and has 36 characters; false otherwise.
	 */
	public function can_setup( $is_setup ) {

		$gateway_merchant = edd_get_option( self::MERCHANT_SETTINGS_KEY, '' );

		$is_setup = ! empty( $gateway_merchant ) && strlen( $gateway_merchant ) === 36;

		return $is_setup;
	}

	/**
	 * Assign a settings URL for the ZarinPal gateway.
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway_settings_url The URL to the gateway settings.
	 *
	 * @return string The settings url.
	 */
	public function assign_settings_url( $gateway_settings_url ) {

		$gateway_settings_url = edd_get_admin_url(
			[
				'page'    => 'edd-settings',
				'tab'     => 'gateways',
				'section' => Plugin::SLUG,
			]
		);

		return $gateway_settings_url;
	}
}
