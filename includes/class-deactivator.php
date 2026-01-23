<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_Deactivator {
    
    public static function deactivate( $plugin_slug ) {
        // Clean up temporary data and Cron
        self::cleanup_temp_data( $plugin_slug );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    private static function cleanup_temp_data( $plugin_slug ) {
        // Remove any temporary transients
        delete_transient( $plugin_slug . '_temp_data' );
        
        // Ensure the automated sync cron is cleared
        if ( file_exists( WPPP_PLUGIN_PATH . 'includes/wp-cron/class-wp-cron.php' ) ) {
            require_once WPPP_PLUGIN_PATH . 'includes/wp-cron/class-wp-cron.php';
            if ( method_exists( 'WPPP_Plugin_WP_Cron', 'clear_schedule' ) ) {
                WPPP_Plugin_WP_Cron::clear_schedule( $plugin_slug );
            }
        }

        // Clear any other general hooks if they exist
        wp_clear_scheduled_hook( $plugin_slug . '_daily_event' );
    }
}