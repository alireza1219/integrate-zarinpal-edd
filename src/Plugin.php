<?php

namespace Alireza1219\Integrate_ZarinPal_EDD;

use Alireza1219\Integrate_ZarinPal_EDD\Admin\Settings;
use Alireza1219\Integrate_ZarinPal_EDD\Frontend\Handler;
use Alireza1219\Integrate_ZarinPal_EDD\Gateway\Gateway;

/**
 * Plugin class.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * The ZarinPal payment gateway slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const SLUG = 'zarinpal';

	/**
	 * Plugin constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public function setup() {

		new Settings();
		new Gateway();
		new Handler();

		return $this;
	}

	/**
	 * Retrieve a single instance of the Plugin class.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function get_instance() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();

			$instance->setup();
		}

		return $instance;
	}
}
