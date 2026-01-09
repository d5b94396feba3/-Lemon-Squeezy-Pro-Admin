<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_WP_Cron {

    private $plugin_slug;
    private $hook_name;

    public function __construct( $plugin_slug ) {
        $this->plugin_slug = $plugin_slug;
        $this->hook_name = $plugin_slug . '_daily_sync_event';

        add_action( $this->hook_name, array( $this, 'run_automated_sync' ) );
        
        if ( ! wp_next_scheduled( $this->hook_name ) ) {
            wp_schedule_event( time(), 'daily', $this->hook_name );
        }
    }

    public function run_automated_sync() {
        $scraper = new AICA_Plugin_SimpleHTMLDOM();
        $db      = new AICA_Plugin_DB_Manager( $this->plugin_slug );
    
        $scraped_pages = $scraper->sync_website_content();
    
        if ( ! empty( $scraped_pages ) ) {
            foreach ( $scraped_pages as $page ) {
                $db->update_knowledge_base( $page['url'], $page['content'] );
            }
            
            // SAVE THE TIMESTAMP ON SUCCESS
            update_option( $this->plugin_slug . '_last_cron_sync', current_time( 'timestamp' ) );
        }
    }

    public static function clear_schedule( $plugin_slug ) {
        $hook = $plugin_slug . '_daily_sync_event';
        $timestamp = wp_next_scheduled( $hook );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, $hook );
        }
    }
}