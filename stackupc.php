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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'STACKUPC_VERSION', '1.0.0' );
define( 'STACKUPC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STACKUPC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The main plugin class
 */
class StackUPC {

	/**
	 * The single instance of the class.
	 *
	 * @var StackUPC
	 */
	protected static $_instance = null;

	/**
	 * Main StackUPC Instance.
	 *
	 * Ensures only one instance of StackUPC is loaded or can be loaded.
	 *
	 * @return StackUPC - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * StackUPC Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files.
	 */
	public function includes() {
		require_once STACKUPC_PLUGIN_DIR . 'includes/class-stackupc-admin.php';
		require_once STACKUPC_PLUGIN_DIR . 'tools/class-stackupc-logger.php';
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		// Initialize admin
		if ( is_admin() ) {
			new StackUPC_Admin();
		}

		// Add activation hook
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Add deactivation hook
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Activation function.
	 */
	public function activate() {
		// Activation code here
		error_log( 'StackUPC plugin activated' );
	}

	/**
	 * Deactivation function.
	 */
	public function deactivate() {
		// Deactivation code here
		error_log( 'StackUPC plugin deactivated' );
	}
}

/**
 * Returns the main instance of StackUPC.
 *
 * @return StackUPC
 */
function StackUPC() {
	return StackUPC::instance();
}

// Global for backwards compatibility.
$GLOBALS['stackupc'] = StackUPC();