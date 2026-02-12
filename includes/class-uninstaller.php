<?php
defined( 'ABSPATH' ) || exit;

class LSPA_Plugin_Uninstaller {
    
    /**
     * Complete removal when plugin is deleted from the WP Dashboard
     */
    public static function uninstall( $plugin_slug ) {
        // 1. Remove database tables
        self::drop_database_tables( $plugin_slug );
        
        // 2. Remove all plugin options
        self::cleanup_plugin_options( $plugin_slug );
        
        // 3. Remove the protected directory and all digital files
        self::remove_protected_directory();
        
        error_log( "WPPP: Plugin uninstalled - all data and protected files removed." );
    }

    /**
     * Drops the custom tables: transactions, subscriptions, and digital_products
     */
    private static function drop_database_tables( $plugin_slug ) {
        global $wpdb;

        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        
        $tables = array(
            $wpdb->prefix . $clean_slug . '_transactions',
            $wpdb->prefix . $clean_slug . '_subscriptions',
            $wpdb->prefix . $clean_slug . '_digital_products'
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS `$table`" );
        }
    }

    /**
     * Deletes all settings saved in the options table
     */
    private static function cleanup_plugin_options( $plugin_slug ) {
        // API Credentials
        delete_option( 'paddle_client_side_token' );
        delete_option( 'paddle_api_key' );
        delete_option( 'paddle_webhook_secret' );
        
        // Configuration
        delete_option( 'paddle_environment' );
        delete_option( 'paddle_thank_you_page_url' );
        
        // Activation flags
        delete_option( $plugin_slug . '_activated' );
        delete_option( 'lspa_paddle_sandbox' );
    }

    /**
     * Securely deletes the wppp-protected directory and everything inside it
     */
    private static function remove_protected_directory() {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/wppp-protected';

        if ( is_dir( $target_dir ) ) {
            $files = glob( $target_dir . '/*', GLOB_MARK ); 
            
            foreach ( $files as $file ) {
                if ( is_dir( $file ) ) {
                    // This handles subdirectories if any exist
                    self::delete_directory_recursive( $file );
                } else {
                    unlink( $file ); // Delete file (the .zip, .htaccess, etc.)
                }
            }
            rmdir( $target_dir ); // Remove the main folder
        }
    }

    /**
     * Helper to recursively delete subfolders
     */
    private static function delete_directory_recursive( $dir ) {
        if ( ! is_dir( $dir ) ) return;
        $files = array_diff( scandir( $dir ), array( '.', '..' ) );
        foreach ( $files as $file ) {
            ( is_dir( "$dir/$file" ) ) ? self::delete_directory_recursive( "$dir/$file" ) : unlink( "$dir/$file" );
        }
        return rmdir( $dir );
    }
}