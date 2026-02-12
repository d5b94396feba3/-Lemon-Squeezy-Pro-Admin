<?php
defined( 'ABSPATH' ) || exit;

class LSPA_Plugin_Activator {
    
    public static function activate( $plugin_slug ) {
        // Set a flag using the dynamic slug
        if ( ! get_option( $plugin_slug . '_activated' ) ) {
            add_option( $plugin_slug . '_activated', true );
        }

        // 1. Database Table Creation
        if ( ! class_exists( 'LSPA_Plugin_DB_Manager' ) ) {
            require_once dirname(__FILE__) . '/db/class-db-manager.php';
        }
        LSPA_Plugin_DB_Manager::create_table( $plugin_slug );

        // 2. Secure Directory Setup
        self::setup_protected_files_dir();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log( $plugin_slug . ' activated successfully' );
    }

    /**
     * Creates and protects the wppp-protected directory in the WP uploads folder.
     */
    private static function setup_protected_files_dir() {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/wppp-protected';

        // Create directory if not exists
        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }

        // 1. Create .htaccess to deny direct web access
        $htaccess_file = $target_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $rules = "Deny from all";
            @file_put_contents( $htaccess_file, $rules );
        }

        // 2. Create index.html to prevent directory listing
        $index_file = $target_dir . '/index.html';
        if ( ! file_exists( $index_file ) ) {
            @file_put_contents( $index_file, '' );
        }
    }
}