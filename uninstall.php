<?php
/**
 * Uninstall AI Proposal System
 * 
 * @package AI_Proposal_System
 */

// Exit if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$plugin_data = get_file_data( __FILE__, array( 'TextDomain' => 'Text Domain' ), 'plugin' );
$plugin_slug = $plugin_data['TextDomain'];

require_once plugin_dir_path( __FILE__ ) . 'includes/class-uninstaller.php';
WPPP_Plugin_Uninstaller::uninstall( $plugin_slug );