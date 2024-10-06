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

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stackupc-api.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tools/class-stackupc-logger.php';

class StackUpc_Admin {

    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->version = STACKUPC_VERSION; // Make sure this constant is defined in your main plugin file
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_upc_search' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
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
            'dashicons-database-import',
            30
        );
    }

    /**
     * Register settings for the plugin.
     *
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting( 'stackupc_general_settings', 'stackupc_upc_code' );
        register_setting( 'stackupc_advanced_settings', 'stackupc_cache_duration' );
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

        try {
            $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <?php settings_errors( 'stackupc_messages' ); ?>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=stackupc&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General Settings', 'stackupc' ); ?></a>
                    <a href="?page=stackupc&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Advanced Settings', 'stackupc' ); ?></a>
                </h2>
                <?php
                if ( $active_tab === 'general' ) {
                    $this->render_general_settings();
                } else {
                    ?>
                    <form method="post" action="options.php">
                    <?php
                    settings_fields( 'stackupc_advanced_settings' );
                    do_settings_sections( 'stackupc_advanced_settings' );
                    $this->render_advanced_settings();
                    submit_button();
                    ?>
                    </form>
                    <?php
                }
                ?>
            </div>
            <?php
        } catch ( Exception $e ) {
            error_log( 'StackUPC Admin Error: ' . $e->getMessage() );
            echo '<div class="error"><p>' . __( 'An error occurred while rendering the admin page. Please check the error log for more details.', 'stackupc' ) . '</p></div>';
        }
    }

    /**
     * Render general settings fields.
     *
     * @since 1.0.0
     */
    private function render_general_settings() {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'stackupc_search_action', 'stackupc_search_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="stackupc_upc_code"><?php _e( 'UPC Code', 'stackupc' ); ?></label></th>
                    <td>
                        <input type="text" id="stackupc_upc_code" name="stackupc_upc_code" value="<?php echo esc_attr( get_option( 'stackupc_upc_code' ) ); ?>" class="regular-text" />
                        <?php submit_button( __( 'Search', 'stackupc' ), 'secondary', 'submit', false ); ?>
                        <p class="description"><?php _e( 'Enter the UPC code and click Search.', 'stackupc' ); ?></p>
                    </td>
                </tr>
            </table>
        </form>
        <?php
        // Display the result table if it exists
        $table_html = get_transient('stackupc_result_table');
        if ($table_html) {
            echo $table_html;
            delete_transient('stackupc_result_table');
        }
    }

    /**
     * Render advanced settings fields.
     *
     * @since 1.0.0
     */
    private function render_advanced_settings() {
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Cache Duration', 'stackupc' ); ?></th>
                <td>
                    <input type="number" name="stackupc_cache_duration" value="<?php echo esc_attr( get_option( 'stackupc_cache_duration', 3600 ) ); ?>" />
                    <p class="description"><?php _e( 'Enter the cache duration in seconds. Default is 3600 (1 hour).', 'stackupc' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Handle UPC code search.
     *
     * @since 1.0.0
     */
    public function handle_upc_search() {
        if (isset($_POST['stackupc_upc_code']) && isset($_POST['stackupc_search_nonce']) && wp_verify_nonce($_POST['stackupc_search_nonce'], 'stackupc_search_action')) {
            $upc_code = sanitize_text_field($_POST['stackupc_upc_code']);
            update_option('stackupc_upc_code', $upc_code);

            try {
                $api = new \StackUPC_API(); // Use the fully qualified class name
                $result = $api->search_upc($upc_code);

                if (is_wp_error($result)) {
                    throw new \Exception($result->get_error_message());
                }

                // Process and display the result
                $this->display_upc_result($result);
                \StackUPC_Logger::log("UPC search completed for code: $upc_code", 'info');
            } catch (\Exception $e) {
                \StackUPC_Logger::log("UPC search failed: " . $e->getMessage(), 'error');
                add_settings_error('stackupc_messages', 'stackupc_error', __('UPC Code search failed: ', 'stackupc') . $e->getMessage(), 'error');
            }
        }
    }

    private function display_upc_result($result) {
        $items = isset($result['items']) ? $result['items'] : array();
        
        if (empty($items)) {
            add_settings_error('stackupc_messages', 'stackupc_notice', __('No results found for this UPC code.', 'stackupc'), 'warning');
            return;
        }

        ob_start();
        ?>
        <div class="stackupc-results-wrap">
            <h2><?php _e('UPC Search Results', 'stackupc'); ?></h2>
            <table class="stackupc-results-table">
                <thead>
                    <tr>
                        <th><?php _e('Image', 'stackupc'); ?></th>
                        <th><?php _e('Title', 'stackupc'); ?></th>
                        <th><?php _e('Brand', 'stackupc'); ?></th>
                        <th><?php _e('UPC', 'stackupc'); ?></th>
                        <th><?php _e('Price', 'stackupc'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="stackupc-thumbnail">
                            <?php
                            $image_url = !empty($item['images']) ? esc_url($item['images'][0]) : '';
                            if ($image_url) {
                                echo '<img src="' . $image_url . '" alt="' . esc_attr($item['title']) . '" />';
                            } else {
                                echo '<span class="no-image">'. __('No image', 'stackupc') .'</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($item['title']); ?></td>
                        <td><?php echo esc_html($item['brand']); ?></td>
                        <td><?php echo esc_html($item['upc']); ?></td>
                        <td>
                            <?php 
                            if (!empty($item['offers'])) {
                                echo '$' . number_format($item['offers'][0]['price'], 2);
                            } else {
                                _e('N/A', 'stackupc');
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $table_html = ob_get_clean();
        
        // Store the table HTML in a transient
        set_transient('stackupc_result_table', $table_html, 5 * MINUTE_IN_SECONDS);
    }
    
    public function enqueue_admin_styles($hook) {
        // Only enqueue on our plugin's admin page
        if ($hook != 'toplevel_page_stackupc') {
            return;
        }
        wp_enqueue_style('stackupc-admin-styles', plugin_dir_url(dirname(__FILE__)) . 'admin/css/stackupc-admin.css', array(), $this->version);
    }
}