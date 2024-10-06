<?php
/**
 * StackUPC Admin
 *
 * This class handles the admin functionality for the StackUPC plugin.
 *
 * @package StackUPC
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class StackUPC_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    /**
     * Add the admin menu item for StackUPC.
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'StackUPC', 'stackupc' ),
            __( 'StackUPC', 'stackupc' ),
            'manage_options',
            'stackupc',
            array( $this, 'display_admin_page' ),
            'dashicons-barcode',
            30
        );
    }

    /**
     * Display the admin page content.
     *
     * @since 1.0.0
     */
    public function display_admin_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Display the admin page content
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p><?php _e( 'Welcome to StackUPC admin page.', 'stackupc' ); ?></p>
            <!-- Add more content here as needed -->
        </div>
        <?php
    }
}
