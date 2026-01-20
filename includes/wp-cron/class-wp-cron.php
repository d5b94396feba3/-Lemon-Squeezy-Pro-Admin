<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_WP_Cron {

    const HOOK = 'aica_daily_sync_event';
    private $plugin_slug;

    public function __construct( $plugin_slug ) {
        $this->plugin_slug = $plugin_slug;
    }

    public function run_automated_sync() {
        $lock_key = $this->plugin_slug . '_sync_lock';
        
        if ( get_transient( $lock_key ) ) {
            return;
        }

        if ( function_exists( 'wp_raise_memory_limit' ) ) {
            wp_raise_memory_limit( 'admin' );
        }

        set_transient( $lock_key, 'lock', HOUR_IN_SECONDS );

        @set_time_limit( 300 );

        $scraper = new AICA_Plugin_SimpleHTMLDOM();
        $db      = new AICA_Plugin_DB_Manager( $this->plugin_slug );
    
        $scraped_pages = $scraper->sync_website_content();
    
        if ( ! empty( $scraped_pages ) ) {
            foreach ( $scraped_pages as $page ) {
                $db->update_knowledge_base( $page['url'], $page['content'] );
            }
            
            update_option( $this->plugin_slug . '_last_cron_sync', current_time( 'timestamp' ) );
        }

        delete_transient( $lock_key );
    }

    public static function schedule_sync() {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time(), 'daily', self::HOOK );
        }
    }

    public static function clear_schedule() {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
        }
    }
}