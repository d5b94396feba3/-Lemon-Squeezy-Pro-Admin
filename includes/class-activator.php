<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_Activator {
    // accepts the slug to use dynamic option names
    public static function activate( $plugin_slug ) {
        // Set a flag using the dynamic slug
        if ( ! get_option( $plugin_slug . '_activated' ) ) {
            add_option( $plugin_slug . '_activated', true );
        }

        // Ensure DB Manager is available
        if ( ! class_exists( 'AICA_Plugin_DB_Manager' ) ) {
            // Assume AICA_PLUGIN_PATH is defined and includes/ is the parent dir
            require_once dirname(__FILE__) . '/db/class-db-manager.php';
        }
        AICA_Plugin_DB_Manager::create_table( $plugin_slug );
        
        // Flush rewrite rules for any custom post types
        flush_rewrite_rules();
        
        error_log( $plugin_slug . ' activated successfully' );
    }
}