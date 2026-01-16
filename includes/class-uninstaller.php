<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_Uninstaller {
    
    /**
     * Plugin uninstall - COMPLETE removal when plugin is deleted
     */
    public static function uninstall( $plugin_slug ) {
        // Remove the database table and all data
        self::drop_database_table( $plugin_slug );
        
        // Clean up all plugin options
        self::cleanup_plugin_options( $plugin_slug );
        
        // Clean up any temporary data
        self::cleanup_temp_data( $plugin_slug );
        
        error_log( "AI Chat Agent: Plugin uninstalled - all data removed" );
    }

    /**
     * Drop the database tables when plugin is uninstalled, including _chat_logs and _feedback tables.
     */
    private static function drop_database_table( $plugin_slug ) {
        global $wpdb;

        // Generate clean slug part used in table creation (see class-db-manager.php)
        $clean_slug_part = sanitize_key( $plugin_slug );
        $final_slug_part = str_replace('-', '_', $clean_slug_part);

        // Get all relevant table names (chat_logs, feedback, knowledge_base)
        $chat_logs_table = $wpdb->prefix . $final_slug_part . '_chat_logs';
        $feedback_table  = $wpdb->prefix . $final_slug_part . '_feedback';
        $kb_table        = $wpdb->prefix . $final_slug_part . '_knowledge_base';

        // Tables to drop
        $tables = array( $chat_logs_table, $feedback_table, $kb_table );

        foreach ( $tables as $table_name ) {
            // Check if table exists before trying to drop it
            $table_exists = $wpdb->get_var( $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ) );

            if ( $table_exists ) {
                // Drop the table using WordPress database functions
                $sql = "DROP TABLE IF EXISTS `$table_name`";
                $result = $wpdb->query( $sql );

                if ( $result !== false ) {
                    error_log( "AI Chat Agent: Successfully dropped table {$table_name}" );
                } else {
                    error_log( "AI Chat Agent: Failed to drop table {$table_name} - " . $wpdb->last_error );
                }
            } else {
                error_log( "AI Chat Agent: Table {$table_name} does not exist, skipping drop." );
            }
        }
    }

    /**
     * Clean up plugin options from the database
     */
    private static function cleanup_plugin_options( $plugin_slug ) {
        // Remove Content Tab Options
        delete_option( 'ai_agent_display_name' );
        delete_option( 'ai_agent_welcome_msg' );
        delete_option( 'ai_agent_placeholder' );

        // Remove Style Tab Options
        delete_option( 'ai_agent_logo_id' );
        delete_option( 'ai_agent_bubble_icon' );
        delete_option( 'ai_agent_primary_color' );

        // Remove Prompt Tab Options
        delete_option( 'ai_agent_system_prompt' );

        // Remove Grok Tab Options
        delete_option( $plugin_slug . '_grok_settings' );
        delete_option( 'grok_api_key' );
        delete_option( 'grok_model' );
        delete_option( 'grok_temperature' );
        delete_option( 'llm_model' );
        
        // Remove Notifications Tab Option
        delete_option( 'ai_agent_admin_email' );
        delete_option( 'ai_agent_theme_mode' );

        // Remove any other plugin-specific options
        delete_option( $plugin_slug . '_plugin_version' );
        delete_option( $plugin_slug . '_installed_time' );
        delete_option( $plugin_slug . '_last_cron_sync' );
        
        error_log( "AI Chat Agent: Cleaned up all options for {$plugin_slug}" );
    }

    private static function cleanup_temp_data( $plugin_slug ) {
        // Remove any temporary transients using the dynamic slug
        delete_transient( $plugin_slug . '_temp_data' );
    }
}