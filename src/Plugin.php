<?php

namespace EDD_ZarinPal;

use EDD_ZarinPal\Admin\Settings;
use EDD_ZarinPal\Frontend\Handler;
use EDD_ZarinPal\Gateway\Gateway;

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
