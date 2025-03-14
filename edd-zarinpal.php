<?php
/**
 * Plugin Name:       EDD ZarinPal
 * Plugin URI:        https://github.com/alireza1219/edd-zarinpal/issues
 * Description:       Zarinpal integration with Easy Digital Downloads.
 * Author:            Alireza Barani
 * Author URI:        https://alireza1219.ir
 * License:           GPL v2 or later
 * Version:           1.0.0
 * Text Domain:       edd-zarinpal
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 *
 * @package           edd-zarinpal
 */

use EDD_ZarinPal\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
define( 'EDD_ZARINPAL_VERSION', '1.0.0' );

/**
 * Plugin file.
 *
 * @since 1.0.0
 */
define( 'EDD_ZARINPAL_FILE', __FILE__ );

/**
 * Plugin path.
 *
 * @since 1.0.0
 */
define( 'EDD_ZARINPAL_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL.
 *
 * @since 1.0.0
 */
define( 'EDD_ZARINPAL_URL', plugin_dir_url( __FILE__ ) );

// Require composer's autoload.
require_once EDD_ZARINPAL_PATH . 'vendor/autoload.php';

/**
 * Loads the plugin textdomain.
 *
 * @since 1.0.0
 *
 * @return void
 */
function load_edd_zarinpal_textdomain() {

	load_plugin_textdomain(
		'edd-zarinpal',
		false,
		dirname( plugin_basename( EDD_ZARINPAL_FILE ) ) . '/languages/'
	);
}

add_action( 'init', 'load_edd_zarinpal_textdomain' );

// Hello, friend!
Plugin::get_instance();
