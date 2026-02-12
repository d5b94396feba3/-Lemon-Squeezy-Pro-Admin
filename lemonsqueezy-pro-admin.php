<?php
/**
 * Plugin Name: Lemon Squeezy Pro Admin
 * Plugin URI:  https://example.com/
 * Description: Lemon Squeezy Pro Admin
 * Version:     1.3.0
 * Author:      Shahibur Rahman
 * Author URI:  https://example.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: Lemon-Squeezy-Pro-Admin
 * Domain Path: /languages
 */

if (!defined('WPINC')) {
    die;
}

// Get plugin header data.
$plugin_data = get_file_data( __FILE__, array(
    'Version' => 'Version', 
    'TextDomain' => 'Text Domain', 
    'Name' => 'Plugin Name' 
), 'plugin' );

if (!defined('LSPA_PLUGIN_NAME')) {
    define('LSPA_PLUGIN_NAME', $plugin_data['Name']);
}
if (!defined('LSPA_PLUGIN_SLUG')) {
    define('LSPA_PLUGIN_SLUG', $plugin_data['TextDomain']); 
}
if (!defined('LSPA_PLUGIN_VERSION')) {
    define('LSPA_PLUGIN_VERSION', $plugin_data['Version']);
}
if (!defined('LSPA_PLUGIN_PATH')) {
    define('LSPA_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('LSPA_PLUGIN_URL')) {
    define('LSPA_PLUGIN_URL', plugin_dir_url(__FILE__));
}

unset($plugin_data);

class LSPA_Bootstrap {

    public static function init() {
        self::register_vendor_autoload();
        self::load_dependencies();
        self::run_plugin();
    }

    private static function register_vendor_autoload() {
        spl_autoload_register(function ($class) {
            $prefix = 'Smalot\\';
            $base_dir = LSPA_PLUGIN_PATH . 'vendor/PdfParser/Smalot/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) require_once $file;
        });
    }

    private static function load_dependencies() {
        load_plugin_textdomain(
            LSPA_PLUGIN_SLUG,
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );

        require_once LSPA_PLUGIN_PATH . 'includes/class-loader.php';
        require_once LSPA_PLUGIN_PATH . 'includes/class-main.php';
        require_once LSPA_PLUGIN_PATH . 'admin/class-admin.php';
        require_once LSPA_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        require_once LSPA_PLUGIN_PATH . 'includes/mail/class-mail.php';
        require_once LSPA_PLUGIN_PATH . 'includes/paddle/class-paddle-core.php';
        require_once LSPA_PLUGIN_PATH . 'includes/lemonsqueezy/class-paddle-core.php';
        require_once LSPA_PLUGIN_PATH . 'includes/api/class-license-api.php';
        require_once LSPA_PLUGIN_PATH . 'includes/mycred/class-mycred-handler.php';
        require_once LSPA_PLUGIN_PATH . 'includes/wp-cron/class-wp-cron.php';
        require_once LSPA_PLUGIN_PATH . 'public/class-public.php';
    }

    private static function run_plugin() {
        $plugin = new LSPA_Plugin_Main( LSPA_PLUGIN_NAME, LSPA_PLUGIN_SLUG, LSPA_PLUGIN_VERSION );
        $plugin->run();
    }
}

// Activation
function lspa_activate() {
    require_once LSPA_PLUGIN_PATH . 'includes/class-activator.php';
    LSPA_Plugin_Activator::activate( LSPA_PLUGIN_SLUG );
}
register_activation_hook(__FILE__, 'lspa_activate');

// Deactivation
function lspa_deactivate() {
    require_once LSPA_PLUGIN_PATH . 'includes/class-deactivator.php';
    LSPA_Plugin_Deactivator::deactivate( LSPA_PLUGIN_SLUG );
}

register_deactivation_hook(__FILE__, 'lspa_deactivate');

add_action('plugins_loaded', array('LSPA_Bootstrap', 'init'));