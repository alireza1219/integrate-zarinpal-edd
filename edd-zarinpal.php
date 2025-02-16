<?php
/**
 * Plugin Name:       EDD ZarinPal
 * Plugin URI:        https://github.com/alireza1219/edd-zarinpal/issues
 * Description:       Zarinpal integration with Easy Digital Downloads.
 * Author:            Alireza Barani
 * Author URI:        https://alireza1219.ir
 * Version:           1.0.0
 * Text Domain:       edd-zarinpal
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 */

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