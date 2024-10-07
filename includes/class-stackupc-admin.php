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
        add_action( 'admin_init', array($this, 'handle_import' ) );
        add_action( 'wp_ajax_stackupc_search', array($this, 'ajax_upc_search' ) );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_stackupc_import', array($this, 'ajax_import_item' ) );
        add_filter( 'manage_asset_posts_columns', array($this, 'set_custom_asset_columns' ) );
        add_action( 'manage_asset_posts_custom_column', array($this, 'custom_asset_column' ), 10, 2);
        add_filter( 'manage_edit-asset_sortable_columns', array($this, 'set_custom_asset_sortable_columns' ) );
        add_action( 'admin_head-edit.php', array($this, 'add_admin_styles' ) );
    }

    /**
     * Enqueue admin-specific styles.
     *
     * This method is responsible for enqueuing the CSS styles
     * specific to the admin area of the StackUPC plugin.
     * It only enqueues the styles on the plugin's admin page.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_admin_styles($hook) {
        // Only enqueue on our plugin's admin page
        if ($hook != 'toplevel_page_stackupc') {
            return;
        }
        wp_enqueue_style(
            'stackupc-admin-styles', 
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/stackupc-admin.css', 
            array(), 
            $this->version
        );
    }

    // ======================================================================================= //
    // Start "Search UPC".

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
        register_setting( 'stackupc_upclookup_settings', 'stackupc_upc_code' );
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
            $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'upclookup';
            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <?php settings_errors( 'stackupc_messages' ); ?>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=stackupc&tab=upclookup" class="nav-tab <?php echo $active_tab === 'upclookup' ? 'nav-tab-active' : ''; ?>"><?php _e( 'UPC Lookup', 'stackupc' ); ?></a>
                    <a href="?page=stackupc&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e( 'UPC Search', 'stackupc' ); ?></a>
                </h2>
                <?php
                if ( $active_tab === 'upclookup' ) {
                    $this->render_upclookup_settings();
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
     * Render upclookup settings fields.
     *
     * @since 1.0.0
     */
    private function render_upclookup_settings() {
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'stackupc_search_action', 'stackupc_search_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="stackupc_upc_code"><?php _e( 'UPC Code', 'stackupc' ); ?></label></th>
                    <td>
                        <input type="text" id="stackupc_upc_code" name="stackupc_upc_code" value="<?php echo esc_attr( get_option( 'stackupc_upc_code' ) ); ?>" class="regular-text" />
                        <?php submit_button( __( 'Search', 'stackupc' ), 'secondary', 'stackupc_search_button', false ); ?>
                        <p class="description"><?php _e( 'Enter the UPC code and click Search.', 'stackupc' ); ?></p>
                    </td>
                </tr>
            </table>
        </form>
        <div id="stackupc_results_container"></div>
        <?php
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
            try {
                $api = new \StackUPC_API();
                $result = $api->search_upc($upc_code);
                if (is_wp_error($result)) {
                    throw new \Exception($result->get_error_message());
                }
                $this->display_upc_result($result);
                \StackUPC_Logger::log("UPC search completed for code: $upc_code", 'info');
            } catch (\Exception $e) {
                \StackUPC_Logger::log("UPC search failed: " . $e->getMessage(), 'error');
                add_settings_error('stackupc_messages', 'stackupc_error', __('UPC Code search failed: ', 'stackupc') . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * Generates HTML for the UPC search results table.
     *
     * This method takes the UPC search result data and generates an HTML table
     * displaying the product information including image, title, brand, UPC,
     * price, and an import button for each item.
     *
     * @since 1.0.0
     * @access private
     *
     * @param array $result An associative array containing the UPC search results.
     *                      Expected to have a key 'items' which is an array of product items.
     *                      Each item should have keys: 'images', 'title', 'brand', 'upc', 'offers'.
     *
     * @return string The generated HTML for the results table.
     */
    private function generate_result_html($result) {
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
                        <th><?php _e('Import', 'stackupc'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['items'] as $item): ?>
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
                        <td class="stackupc-import-column">
                            <button type="button" class="button button-secondary stackupc-import-button" data-item='<?php echo esc_attr(json_encode($item)); ?>'>
                                <?php _e('Import', 'stackupc'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    // End "Search UPC".
    // ======================================================================================= //




    public function handle_import() {
        if (isset($_POST['stackupc_import_item']) && isset($_POST['stackupc_import_nonce']) && wp_verify_nonce($_POST['stackupc_import_nonce'], 'stackupc_import_action')) {
            $item_index = intval($_POST['stackupc_import_item']);
            $result = get_transient('stackupc_api_result');
            
            if ($result && isset($result['items'][$item_index])) {
                $item = $result['items'][$item_index];
                // Process the import here
                // For example, you might create a new post or update an existing one
                // with the item data
                
                // Placeholder success message
                add_settings_error('stackupc_messages', 'stackupc_import_success', __('Item imported successfully.', 'stackupc'), 'updated');
            } else {
                add_settings_error('stackupc_messages', 'stackupc_import_error', __('Failed to import item. Please try again.', 'stackupc'), 'error');
            }
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook != 'toplevel_page_stackupc') {
            return;
        }
        wp_enqueue_script('stackupc-admin-js', plugin_dir_url(dirname(__FILE__)) . 'admin/js/stackupc-admin.js', array('jquery'), $this->version, true);
        wp_localize_script('stackupc-admin-js', 'stackupc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('stackupc_search_nonce'),
            'import_nonce' => wp_create_nonce('stackupc_import_nonce')
        ));
    }

    public function ajax_upc_search() {
        check_ajax_referer('stackupc_search_nonce', 'nonce');

        if (!isset($_POST['upc_code'])) {
            wp_send_json_error('No UPC code provided');
        }

        $upc_code = sanitize_text_field($_POST['upc_code']);
        
        try {
            $api = new StackUPC_API();
            $result = $api->search_upc($upc_code);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            $html = $this->generate_result_html($result);
            wp_send_json_success($html);
        } catch (Exception $e) {
            StackUPC_Logger::log("UPC search failed: " . $e->getMessage(), 'error');
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_import_item() {
        check_ajax_referer('stackupc_import_nonce', 'nonce');

        if (!isset($_POST['item_data'])) {
            wp_send_json_error('No item data provided');
        }

        $item_data = json_decode(stripslashes($_POST['item_data']), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid item data');
        }

        $post_id = wp_insert_post(array(
            'post_title'   => $item_data['title'],
            'post_content' => $item_data['description'],
            'post_type'    => 'asset',
            'post_status'  => 'publish',
        ));

        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to create asset post');
        }

        $acf_fields = array(
            'title'                  => $item_data['title'],
            'description'            => $item_data['description'],
            'upc'                    => $item_data['upc'],
            'ean'                    => $item_data['ean'],
            'brand'                  => $item_data['brand'],
            'model'                  => $item_data['model'],
            'color'                  => $item_data['color'],
            'size'                   => $item_data['size'],
            'dimension'              => $item_data['dimension'],
            'weight'                 => $item_data['weight'],
            'category'               => $item_data['category'],
            'currency'               => $item_data['currency'],
            'lowest_recorded_price'  => $item_data['lowest_recorded_price'],
            'highest_recorded_price' => $item_data['highest_recorded_price'],
        );

        foreach ($acf_fields as $field_name => $value) {
            update_field($field_name, $value, $post_id);
        }

        // Handle image upload
        if (!empty($item_data['images'][0])) {
            $image_url = $item_data['images'][0];
            $upload = $this->upload_image_from_url($image_url, $post_id);
            if (!is_wp_error($upload)) {
                update_field('images', $upload['attachment_id'], $post_id);
            }
        }

        wp_send_json_success(array(
            'message' => 'Asset imported successfully',
            'post_id' => $post_id,
        ));
    }

    private function upload_image_from_url($image_url, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            return $tmp;
        }

        $file_array = array(
            'name'     => basename($image_url),
            'tmp_name' => $tmp
        );

        $attachment_id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return $attachment_id;
        }

        return array(
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id)
        );
    }

    public function set_custom_asset_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'image' => __('Image', 'stackupc'),
            'title' => __('Title', 'stackupc'),
            'categories' => __('Categories', 'stackupc'),
            'tags' => __('Tags', 'stackupc'),
            'date' => __('Date', 'stackupc')
        );
        return $new_columns;
    }

    public function custom_asset_column($column, $post_id) {
        switch ($column) {
            case 'image':
                $image = get_field('images', $post_id);
                if ($image) {
                    $full_size_url = $image['url']; // URL of the full-size image
                    $medium_size_url = $image['sizes']['medium']; // URL of the medium size image
                    echo '<a href="' . esc_url($full_size_url) . '" target="_blank" rel="noopener noreferrer">';
                    echo '<img src="' . esc_url($medium_size_url) . '" alt="' . esc_attr($image['alt']) . '" />';
                    echo '</a>';
                } else {
                    echo '—';
                }
                break;
            case 'categories':
                $terms = get_the_terms($post_id, 'category');
                if ($terms && !is_wp_error($terms)) {
                    $category_names = array();
                    foreach ($terms as $term) {
                        $category_names[] = $term->name;
                    }
                    echo implode(', ', $category_names);
                } else {
                    echo '—';
                }
                break;
            case 'tags':
                $terms = get_the_terms($post_id, 'post_tag');
                if ($terms && !is_wp_error($terms)) {
                    $tag_names = array();
                    foreach ($terms as $term) {
                        $tag_names[] = $term->name;
                    }
                    echo implode(', ', $tag_names);
                } else {
                    echo '—';
                }
                break;
        }
    }

    public function set_custom_asset_sortable_columns($columns) {
        $columns['categories'] = 'categories';
        $columns['tags'] = 'tags';
        return $columns;
    }

    public function add_admin_styles() {
        echo '<style>
            .column-image { width: 102px; padding: 8px 10px; }
            .column-image img { 
                display: block; 
                width: 100px; 
                height: 100px; 
                object-fit: cover;
                transition: opacity 0.3s ease;
                border: 1px solid rgba(0, 0, 0, 0.1); /* Light black border */
                box-sizing: border-box; /* Ensures border doesn\'t increase image size */
            }
            .column-image a:hover img { 
                opacity: 0.8; 
            }
            .column-title { width: auto; }
            .column-categories, .column-tags { width: 15%; }
            .column-date { width: 10%; }
        </style>';
    }
}