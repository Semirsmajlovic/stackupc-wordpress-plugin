<?php
/**
 * StackUPC Logger
 *
 * This class handles logging functionality for the StackUPC plugin.
 *
 * @package StackUPC
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class StackUPC_Logger {

    /**
     * Log a message.
     *
     * @param string $message The message to log.
     * @param string $level The log level (info, warning, error).
     * @since 1.0.0
     */
    public static function log( $message, $level = 'info' ) {
        $log_file = WP_CONTENT_DIR . '/stackupc-log.txt';
        $timestamp = current_time( 'mysql' );
        $log_entry = sprintf( "[%s] [%s] %s\n", $timestamp, strtoupper( $level ), $message );
        
        error_log( $log_entry, 3, $log_file );
    }
}