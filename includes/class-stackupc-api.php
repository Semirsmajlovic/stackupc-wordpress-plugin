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
    private const ENDPOINT = 'https://api.upcitemdb.com/prod/trial/lookup';

    /**
     * Request timeout in seconds.
     *
     * @var int
     */
    private const TIMEOUT = 30;

    /**
     * Search for a UPC code.
     *
     * @param string $upc_code The UPC code to search for.
     * @return array|WP_Error The API response or WP_Error on failure.
     */
    public function search_upc($upc_code) {
        try {
            $response = $this->make_request($upc_code);

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code !== 200) {
                throw new Exception("API request failed with status code: $http_code");
            }

            $data = $this->parse_response($response);

            StackUPC_Logger::log("UPC search successful for code: $upc_code", 'info');
            return $data;
        } catch (Exception $e) {
            StackUPC_Logger::log("API Error: " . $e->getMessage(), 'error');
            return new WP_Error('api_error', $e->getMessage());
        }
    }

    /**
     * Make the API request.
     *
     * @param string $upc_code The UPC code to search for.
     * @return array|WP_Error The API response or WP_Error on failure.
     */
    private function make_request($upc_code) {
        $args = array(
            'headers' => array(
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip,deflate'
            ),
            'timeout' => self::TIMEOUT
        );

        $url = add_query_arg('upc', $upc_code, self::ENDPOINT);
        return wp_remote_get($url, $args);
    }

    /**
     * Parse the API response.
     *
     * @param array $response The API response.
     * @return array The parsed data.
     * @throws Exception If the response cannot be parsed.
     */
    private function parse_response($response) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse API response: " . json_last_error_msg());
        }

        return $data;
    }
}