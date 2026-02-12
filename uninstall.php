<?php
/**
 * Uninstall WP Paddle Pro
 * 
 * @package WP_Paddle_Pro
 */

// Exit if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$plugin_data = get_file_data( __FILE__, array( 'TextDomain' => 'Text Domain' ), 'plugin' );
$plugin_slug = $plugin_data['TextDomain'];

require_once plugin_dir_path( __FILE__ ) . 'includes/class-uninstaller.php';
LSPA_Plugin_Uninstaller::uninstall( $plugin_slug );