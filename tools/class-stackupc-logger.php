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
     * Log levels.
     */
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';

    /**
     * Log directory.
     */
    private static function get_log_directory() {
        $log_dir = STACKUPC_PLUGIN_DIR . 'logs/';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        return $log_dir;
    }

    /**
     * Log a message.
     *
     * @param string $message The message to log.
     * @param string $level The log level (info, warning, error).
     * @since 1.0.0
     */
    public static function log( $message, $level = self::LEVEL_INFO ) {
        $log_file = self::get_log_directory() . 'stackupc-log.txt';
        $timestamp = current_time( 'mysql' );
        $log_entry = sprintf( "[%s] [%s] %s\n", $timestamp, strtoupper( $level ), $message );

        // Check if the log file is writable
        if (!is_writable($log_file) && !is_writable(dirname($log_file))) {
            error_log("StackUPC Logger Error: Log file is not writable.", 0);
            return;
        }

        // Implement log rotation if the file size exceeds 5MB
        if (file_exists($log_file) && filesize($log_file) > 5 * 1024 * 1024) {
            rename($log_file, $log_file . '.' . time());
        }

        error_log( $log_entry, 3, $log_file );
    }
}