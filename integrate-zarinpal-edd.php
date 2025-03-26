<?php
/**
 * Plugin Name:       Integrate ZarinPal for EDD
 * Plugin URI:        https://wordpress.org/plugins/integrate-zarinpal-edd
 * Description:       Zarinpal integration for Easy Digital Downloads.
 * Author:            Alireza Barani
 * Author URI:        https://alireza1219.ir
 * License:           GPL v2 or later
 * Version:           1.0.0
 * Text Domain:       integrate-zarinpal-edd
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 *
 * @package           integrate-zarinpal-edd
 */

use Integrate_ZarinPal_EDD\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
define( 'INTEGRATE_ZARINPAL_EDD_VERSION', '1.0.0' );

/**
 * Plugin file.
 *
 * @since 1.0.0
 */
define( 'INTEGRATE_ZARINPAL_EDD_FILE', __FILE__ );

/**
 * Plugin path.
 *
 * @since 1.0.0
 */
define( 'INTEGRATE_ZARINPAL_EDD_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL.
 *
 * @since 1.0.0
 */
define( 'INTEGRATE_ZARINPAL_EDD_URL', plugin_dir_url( __FILE__ ) );

// Require composer's autoload.
require_once INTEGRATE_ZARINPAL_EDD_PATH . 'vendor/autoload.php';

/**
 * Loads the plugin textdomain.
 *
 * @since 1.0.0
 *
 * @return void
 */
function load_integrate_zarinpal_edd_textdomain() {

	load_plugin_textdomain(
		'integrate-zarinpal-edd',
		false,
		dirname( plugin_basename( INTEGRATE_ZARINPAL_EDD_FILE ) ) . '/languages/'
	);
}

add_action( 'init', 'load_integrate_zarinpal_edd_textdomain' );

// Hello, friend!
Plugin::get_instance();
