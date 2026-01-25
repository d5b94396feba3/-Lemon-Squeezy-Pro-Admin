<?php
/**
 * Plugin Name: Paddle WCC Downlaod Page Generator Link
 * Plugin URI:  https://shahisoft.com/
 * Description: Custom WCC Downlaod Page Generator Link
 * Version:     1.0.0
 * Author:      ShahiSoft LLC
 * Author URI:  https://shahisoft.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: paddle-wcc-download-page
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Paddle_WCC_download_Page {
    /**
     * The slug for the WooCommerce account menu item.
     */
    private $downloads_slug = 'my-downloads';
    
    /**
     * The display text for the menu link.
     */
    private $downloads_text = '';
    
    /**
     * The target URL of the product download page.
     */
    private $downloads_url = '';

    public function __construct() {
        // Translation ready text
        $this->downloads_text = __( 'My Downloads', 'pwcc-download-page' );
        
        // Target URL - Ensure this matches the page where you placed the [paddle_dashboard] shortcode
        $this->downloads_url = home_url( '/my-downloads/' );

        add_action( 'init', array( $this, 'register_custom_endpoint' ) );
        add_filter( 'query_vars', array( $this, 'add_custom_query_vars' ) );
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_links_to_account_menu' ) );
        add_action( 'template_redirect', array( $this, 'redirect_custom_endpoint' ) );
    }

    /**
     * Register the custom endpoint for the My Account page.
     */
    public function register_custom_endpoint() {
        add_rewrite_endpoint( $this->downloads_slug, EP_PAGES );
    }

    /**
     * Add the slug to authorized query variables.
     */
    public function add_custom_query_vars( $vars ) {
        $vars[] = $this->downloads_slug;
        return $vars;
    }

    /**
     * Insert the new link immediately after the "Dashboard" tab in WooCommerce.
     */
    public function add_links_to_account_menu( $menu_links ) {
        $new_menu_links = array();

        foreach ( $menu_links as $key => $value ) {
            $new_menu_links[ $key ] = $value;

            if ( 'dashboard' === $key ) {
                $new_menu_links[ $this->downloads_slug ] = $this->downloads_text;
            }
        }

        return $new_menu_links;
    }

    /**
     * Redirect the user when they click the My Account tab.
     */
    public function redirect_custom_endpoint() {
        global $wp_query;

        if ( isset( $wp_query->query_vars[ $this->downloads_slug ] ) ) {
            // Using wp_safe_redirect for internal security
            wp_safe_redirect( esc_url_raw( $this->downloads_url ) );
            exit;
        }
    }
}

// new Paddle_WCC_download_Page();