<?php
/**
 * Plugin Name: AI Chat Agent
 * Plugin URI:  https://foxcomputersolutions.com/
 * Description: AI Powered Chat Agent
 * Version:     1.3.0
 * Author:      Shahibur Rahman
 * Author URI:  https://foxcomputersolutions.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: aica-intelliagent
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

if (!defined('AICA_PLUGIN_NAME')) {
    define('AICA_PLUGIN_NAME', $plugin_data['Name']);
}
if (!defined('AICA_PLUGIN_SLUG')) {
    define('AICA_PLUGIN_SLUG', $plugin_data['TextDomain']); 
}
if (!defined('AICA_PLUGIN_VERSION')) {
    define('AICA_PLUGIN_VERSION', $plugin_data['Version']);
}
if (!defined('AICA_PLUGIN_PATH')) {
    define('AICA_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('AICA_PLUGIN_URL')) {
    define('AICA_PLUGIN_URL', plugin_dir_url(__FILE__));
}

unset($plugin_data);

class AICA_Bootstrap {

    public static function init() {
        self::register_vendor_autoload();
        self::load_dependencies();
        self::run_plugin();
    }

    private static function register_vendor_autoload() {
        spl_autoload_register(function ($class) {
            $prefix = 'Smalot\\';
            $base_dir = AICA_PLUGIN_PATH . 'vendor/PdfParser/Smalot/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) require_once $file;
        });
    }

    private static function load_dependencies() {
        load_plugin_textdomain(
            AICA_PLUGIN_SLUG,
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );

        require_once AICA_PLUGIN_PATH . 'includes/class-loader.php';
        require_once AICA_PLUGIN_PATH . 'includes/class-main.php';
        require_once AICA_PLUGIN_PATH . 'admin/class-admin.php';
        require_once AICA_PLUGIN_PATH . 'includes/llm/class-llm-processor.php';
        require_once AICA_PLUGIN_PATH . 'includes/llm/class-llm-grok.php';
        require_once AICA_PLUGIN_PATH . 'includes/llm/class-llm-gemini.php';
        require_once AICA_PLUGIN_PATH . 'includes/llm/class-llm-openai.php';
        require_once AICA_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        require_once AICA_PLUGIN_PATH . 'includes/mail/class-mail.php';
        require_once AICA_PLUGIN_PATH . 'includes/pdf-parser/class-smalot-parser.php';
        require_once AICA_PLUGIN_PATH . 'includes/scraper/class-shtmldom.php';
        require_once AICA_PLUGIN_PATH . 'includes/wp-cron/class-wp-cron.php';
        require_once AICA_PLUGIN_PATH . 'public/class-public.php';
    }

    private static function run_plugin() {
        $plugin = new AICA_Plugin_Main( AICA_PLUGIN_NAME, AICA_PLUGIN_SLUG, AICA_PLUGIN_VERSION );
        $plugin->run();
    }
}

// Activation
function aica_activate() {
    require_once AICA_PLUGIN_PATH . 'includes/class-activator.php';
    AICA_Plugin_Activator::activate( AICA_PLUGIN_SLUG );
    
    if ( class_exists( 'AICA_Plugin_WP_Cron' ) ) {
        AICA_Plugin_WP_Cron::schedule_sync();
    }
}
register_activation_hook(__FILE__, 'aica_activate');

// Deactivation
function aica_deactivate() {
    require_once AICA_PLUGIN_PATH . 'includes/class-deactivator.php';
    AICA_Plugin_Deactivator::deactivate( AICA_PLUGIN_SLUG );
}

register_deactivation_hook(__FILE__, 'aica_deactivate');

add_action('plugins_loaded', array('AICA_Bootstrap', 'init'));