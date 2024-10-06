<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://semirsmajlovic.com/
 * @since             1.0.0
 * @package           Stackupc
 *
 * @wordpress-plugin
 * Plugin Name:       StackUPC
 * Plugin URI:        https://semirsmajlovic.com/
 * Description:       StackUPC is a plugin that quickly retrieves product details by looking up a barcode using a UPC number, simplifying product identification and inventory management.
 * Version:           1.0.0
 * Author:            Semir Smajlovic
 * Author URI:        https://semirsmajlovic.com//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       stackupc
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'STACKUPC_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-stackupc-activator.php
 */
function activate_stackupc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-stackupc-activator.php';
	Stackupc_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-stackupc-deactivator.php
 */
function deactivate_stackupc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-stackupc-deactivator.php';
	Stackupc_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_stackupc' );
register_deactivation_hook( __FILE__, 'deactivate_stackupc' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-stackupc.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_stackupc() {

	$plugin = new Stackupc();
	$plugin->run();

}
run_stackupc();
