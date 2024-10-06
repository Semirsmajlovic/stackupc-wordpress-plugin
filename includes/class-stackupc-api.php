<?php
/**
 * StackUPC API
 *
 * This class handles the API functionality for the StackUPC plugin.
 *
 * @package StackUPC
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class StackUPC_API {
    /**
     * The API endpoint.
     *
     * @var string
     */
    private $endpoint = 'https://api.upcitemdb.com/prod/trial/lookup';

    /**
     * Search for a UPC code.
     *
     * @param string $upc_code The UPC code to search for.
     * @return array|WP_Error The API response or WP_Error on failure.
     */
    public function search_upc($upc_code) {
        try {
            $args = array(
                'headers' => array(
                    'Accept' => 'application/json',
                    'Accept-Encoding' => 'gzip,deflate'
                ),
                'timeout' => 30
            );

            $url = add_query_arg('upc', $upc_code, $this->endpoint);
            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code !== 200) {
                throw new Exception("API request failed with status code: $http_code");
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Failed to parse API response: " . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            StackUPC_Logger::log("API Error: " . $e->getMessage(), 'error');
            return new WP_Error('api_error', $e->getMessage());
        }
    }
}
