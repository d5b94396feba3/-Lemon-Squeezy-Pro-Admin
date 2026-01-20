<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_Admin {
    private $plugin_slug;
    private $version;
    private $db_manager;
    private $mail_manager;
    private $llm_processor;

    public function __construct($plugin_slug, $version = '1.0') {
        $this->plugin_slug = $plugin_slug;  
        $this->version = $version;
        $this->mail_manager = new AICA_Plugin_Mail($this->plugin_slug);
        $this->llm_processor = new AICA_Plugin_LLM_Processor($this->plugin_slug);
    }

    public function add_admin_menu() {
        $icon_url = AICA_PLUGIN_URL . 'assets/images/plugin-icon.svg';
        add_menu_page(
            AICA_PLUGIN_NAME,
            AICA_PLUGIN_NAME,
            'manage_options',
            $this->plugin_slug,
            array($this, 'display_admin_dashboard'),
            $icon_url,
            30
        );
        add_submenu_page(
            $this->plugin_slug,
            __( 'Dashboard', 'aica-plugin' ),
            __( 'Dashboard', 'aica-plugin' ),
            'manage_options',
            $this->plugin_slug,
            array($this, 'display_admin_dashboard')
        );

        add_submenu_page(
            $this->plugin_slug,
            __( 'Settings', 'aica-plugin' ),
            __( 'Settings', 'aica-plugin' ),
            'manage_options',
            $this->plugin_slug . '-settings',
            array($this, 'display_settings_page')
        );
    }

    public function register_settings() {
        register_setting($this->plugin_slug . '_content_settings', 'ai_agent_display_name');
        register_setting($this->plugin_slug . '_content_settings', 'ai_agent_welcome_msg');
        register_setting($this->plugin_slug . '_content_settings', 'ai_agent_placeholder');
    
        register_setting($this->plugin_slug . '_style_settings', 'ai_agent_logo_id');
        register_setting($this->plugin_slug . '_style_settings', 'ai_agent_bubble_icon');
        register_setting($this->plugin_slug . '_style_settings', 'ai_agent_primary_color');
        register_setting($this->plugin_slug . '_style_settings', 'ai_agent_theme_mode');
        // Prompt / Knowledge Base settings
        register_setting($this->plugin_slug . '_prompt_settings', 'ai_agent_system_prompt');
        register_setting($this->plugin_slug . '_prompt_settings', 'ai_agent_knowledge_file_id');
    
        // Master Selection
        register_setting($this->plugin_slug . '_grok_settings', 'llm_model');

        // Grok Keys
        register_setting($this->plugin_slug . '_grok_settings', 'grok_api_key');
        register_setting($this->plugin_slug . '_grok_settings', 'grok_model');
        register_setting($this->plugin_slug . '_grok_settings', 'grok_temperature');

        // OpenAI Keys
        register_setting($this->plugin_slug . '_grok_settings', 'openai_api_key');
        register_setting($this->plugin_slug . '_grok_settings', 'openai_model');
        register_setting($this->plugin_slug . '_grok_settings', 'openai_temperature');

        // Gemini Keys
        register_setting($this->plugin_slug . '_grok_settings', 'gemini_api_key');
        register_setting($this->plugin_slug . '_grok_settings', 'gemini_model');
        register_setting($this->plugin_slug . '_grok_settings', 'gemini_temperature');

        register_setting($this->plugin_slug . '_notif_settings', 'ai_agent_admin_email');
    }

    public function get_all_chat_logs() {
        $db = $this->get_db_manager();
        return $db->get_all_chat_logs();
    }

    public function get_chat_stats() {
        $db = $this->get_db_manager();
        return $db->get_chat_stats();
    }

    public function handle_delete_chat_ajax() {
        check_ajax_referer('aica_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error( __( 'Unauthorized', 'aica-plugin' ) );
        }
    
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error( __( 'Invalid ID', 'aica-plugin' ) );
        }
    
        $db = $this->get_db_manager();
        if ($db->delete_chat_log($id)) {
            wp_send_json_success( __( 'Log deleted successfully', 'aica-plugin' ) );
        } else {
            wp_send_json_error( __( 'Database deletion failed', 'aica-plugin' ) );
        }
    }


    public function handle_export_chat_ajax() {
        while (ob_get_level()) {
            ob_end_clean();
        }

        check_ajax_referer('aica_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error( __( 'Unauthorized', 'aica-plugin' ) );
        }

        $log_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        $db = $this->get_db_manager();
        $logs = $db->get_logs_for_export($log_id);

        if (empty($logs)) {
            wp_send_json_error( __( 'No logs found.', 'aica-plugin' ) );
        }

        ob_start();
        $df = fopen("php://output", 'w');
        // UTF-8 BOM
        fprintf($df, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($df, array(
            __( 'ID', 'aica-plugin' ), 
            __( 'Date', 'aica-plugin' ), 
            __( 'Transcript', 'aica-plugin' )
        ));

        foreach ($logs as $l) {
            $history = json_decode($l['full_history'], true);
            $transcript = "";
            if (is_array($history)) {
                foreach ($history as $msg) {
                    $role = ($msg['role'] === 'user') ? __( 'Visitor', 'aica-plugin' ) : __( 'AI Agent', 'aica-plugin' );
                    $clean_text = wp_strip_all_tags($msg['content']);
                    $clean_content = str_replace(array("\r", "\n", '"'), array(" ", " ", "'"), $clean_text);
                    $transcript .= "[$role]: " . $clean_content . " | ";
                }
            }
            fputcsv($df, array($l['id'], $l['last_updated'], trim($transcript)));
        }
        
        fclose($df);
        $csv_data = ob_get_clean();

        wp_send_json_success(array(
            'csv_data' => $csv_data,
            'filename' => 'chat-export-' . ($log_id ? 'id-' . $log_id : 'all') . '-' . date('Y-m-d') . '.csv'
        ));
    }

    public function handle_get_chat_summary_ajax() {
        check_ajax_referer('aica_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error( __( 'Unauthorized', 'aica-plugin' ) );
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        $db = $this->get_db_manager();
        $log = $db->get_chat_log_by_id($id);

        if (!$log || empty($log['full_history'])) {
            wp_send_json_error( __( 'Conversation history not found.', 'aica-plugin' ) );
        }

        $history = json_decode($log['full_history'], true);
        
        $summary = $this->llm_processor->get_chat_summary($history);

        if (empty($summary)) {
            wp_send_json_error( __( 'The AI was unable to generate a summary for this chat.', 'aica-plugin' ) );
        }

        wp_send_json_success($summary);
    }

    public function handle_sync_website_ajax() {
        check_ajax_referer('aica_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error( __( 'Unauthorized', 'aica-plugin' ) );
    
        $step = isset($_POST['sync_step']) ? sanitize_text_field($_POST['sync_step']) : 'discovery';
        $db = $this->get_db_manager();
        
        $scraper = new AICA_Plugin_SimpleHTMLDOM();
    
        if ($step === 'discovery') {
            $all_pages = get_posts([
                'post_type'      => ['page'], 
                'posts_per_page' => -1, 
                'post_status'    => 'publish'
            ]);
            
            $pages = [];
    
            $excluded_slugs = [
                'cart', 'checkout', 'my-account', 'logout', 'login', 'register', 
                'forgot', 'password-reset', 'change-password', 'payment-success',
                'payment-cancelled', 'thank-you', 'sample-page', 'blog', 'blogs'
            ];
            
            foreach ($all_pages as $page) {
                if (in_array($page->post_name, $excluded_slugs)) continue;
                
                $url = get_permalink($page->ID);
                $sync_data = $db->get_sync_status($url);
                
                $pages[] = [
                    'url'    => $url,
                    'title'  => $page->post_title,
                    'status' => $sync_data ? __( 'Synced', 'aica-plugin' ) : __( 'Pending', 'aica-plugin' ),
                    'last'   => $sync_data ? date_i18n(get_option('date_format'), strtotime($sync_data['last_synced'])) : '-'
                ];
            }
            wp_send_json_success(['pages' => $pages]);
        }
    
        if ($step === 'sync_page') {
            $url = esc_url_raw($_POST['url']);
            
            $content = $scraper->scrape_page($url);
            
            if (empty($content)) {
                $content = "Page source available at " . $url . " (No text content found by scraper)";
            }
    
            $db->update_knowledge_base($url, $content);
            
            wp_send_json_success([
                'status' => __( 'Synced', 'aica-plugin' ), 
                'last'   => current_time('mysql')
            ]);
        }
    }

    public function handle_manual_cron_sync_ajax() {
        check_ajax_referer('aica_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error( __( 'Unauthorized', 'aica-plugin' ) );
    
        if (class_exists('AICA_Plugin_WP_Cron')) {
            // Static call logic per your architecture
            AICA_Plugin_WP_Cron::run_automated_sync();
            wp_send_json_success( __( 'Sync completed successfully!', 'aica-plugin' ) );
        } else {
            wp_send_json_error( __( 'Cron class not found.', 'aica-plugin' ) );
        }
    }

    public function handle_generate_system_prompt_ajax() {
        check_ajax_referer('aica_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error( __( 'Unauthorized', 'aica-plugin' ) );
    
        $new_prompt = $this->llm_processor->generate_automated_system_prompt();
    
        if (strpos($new_prompt, 'Error:') !== false) {
            wp_send_json_error($new_prompt);
        }
    
        wp_send_json_success($new_prompt);
    }
    
    public function display_admin_dashboard() {
        $dashboard_file = AICA_PLUGIN_PATH . 'admin/partials/admin-dashboard.php';
        if (file_exists($dashboard_file)) {
            include $dashboard_file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html(AICA_PLUGIN_NAME) . ' ' . __( 'Dashboard', 'aica-plugin' ) . '</h1>';
            echo '<p>' . __( 'Welcome to the AICA IntelliAgent system.', 'aica-plugin' ) . '</p>';
            echo '</div>';
        }
    }

    public function display_settings_page() {
        $settings_file = AICA_PLUGIN_PATH . 'admin/partials/admin-settings.php';
        if (file_exists($settings_file)) {
            include $settings_file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html(AICA_PLUGIN_NAME) . ' ' . __( 'Settings', 'aica-plugin' ) . '</h1>';
            echo '<p>' . __( 'Settings file not found. Please check the plugin installation.', 'aica-plugin' ) . '</p>';
            echo '</div>';
        }
    }

    private function get_db_manager() {
        if (!isset($this->db_manager)) {
            $this->db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        }
        return $this->db_manager;
    }

    public function enqueue_styles($hook) {
        if (strpos($hook, $this->plugin_slug) === false) {
            return;
        }

        wp_enqueue_style($this->plugin_slug . '-dashboard', AICA_PLUGIN_URL . 'assets/css/admin/dashboard.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_slug . '-settings', AICA_PLUGIN_URL . 'assets/css/admin/settings.css', array(), $this->version, 'all');
        wp_enqueue_style( $this->plugin_slug . '-theme-logic',  AICA_PLUGIN_URL . 'assets/css/public/theme-logic.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_slug . '-chat-widget',  AICA_PLUGIN_URL . 'assets/css/public/chat-widget.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, $this->plugin_slug) === false) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script($this->plugin_slug . '-admin', AICA_PLUGIN_URL . 'assets/js/admin/admin.js', array('jquery'), $this->version, true);
        wp_enqueue_script($this->plugin_slug . '-unsaved-changes', AICA_PLUGIN_URL . 'assets/js/admin/unsaved-changes.js', array('jquery'), $this->version, true);
        wp_enqueue_script($this->plugin_slug . '-knowledge-base', AICA_PLUGIN_URL . 'assets/js/admin/knowledge-base.js', array('jquery'), $this->version, true);
        wp_enqueue_script($this->plugin_slug . '-preview', AICA_PLUGIN_URL . 'assets/js/admin/preview.js', array('jquery'), $this->version, true);
        wp_enqueue_script($this->plugin_slug . '-media-uploader', AICA_PLUGIN_URL . 'assets/js/admin/media-uploader.js', array('jquery'), $this->version, true);

        $admin_data = array(
            'ajax_url'            => admin_url('admin-ajax.php'),
            'nonce'               => wp_create_nonce('aica_admin_nonce'),
            'i18n_visitor'        => __( 'Visitor', 'aica-plugin' ),
            'i18n_ai'             => __( 'AI Agent', 'aica-plugin' ),
            'i18n_deleting'       => __( 'Deleting...', 'aica-plugin' ),
            'i18n_saving'         => __( 'Saving...', 'aica-plugin' ),
            'i18n_summary_title'  => __( 'AI Intelligence Summary', 'aica-plugin' ),
            'i18n_confirm_delete' => __( 'Delete this conversation forever?', 'aica-plugin' ),
            'i18n_pages'          => __( 'Pages', 'aica-plugin' ),
            'i18n_synced'         => __( 'Synced', 'aica-plugin' ),
            'i18n_pending'        => __( 'Pending', 'aica-plugin' ),
            'i18n_uptodate'       => __( 'Everything Up to Date', 'aica-plugin' ),
            'i18n_sync_pending'   => __( 'Sync %s Pending Pages', 'aica-plugin' ),
            'i18n_syncing'        => __( 'Syncing...', 'aica-plugin' ),
            'i18n_processing'     => __( 'Processing:', 'aica-plugin' ),
            'i18n_justnow'        => __( 'Just now', 'aica-plugin' ),
            'i18n_confirm_gen'    => __( 'This will replace your current instructions with a new AI-generated version based on your website content. Proceed?', 'aica-plugin' ),
            'i18n_select_avatar'  => __( 'Select Avatar', 'aica-plugin' ),
            'i18n_use_image'      => __( 'Use this Image', 'aica-plugin' ),
            'i18n_upload_file'    => __( 'Upload Knowledge Base File', 'aica-plugin' ),
            'i18n_use_file'       => __( 'Use this File', 'aica-plugin' ),
            'i18n_replace_file'   => __( 'Replace File', 'aica-plugin' ),
            'i18n_upload_kb'      => __( 'Upload Knowledge File', 'aica-plugin' ),
        );

        wp_localize_script($this->plugin_slug . '-admin', 'aica_admin_data', $admin_data);
        wp_localize_script($this->plugin_slug . '-knowledge-base', 'aica_admin_data', $admin_data);
        wp_localize_script($this->plugin_slug . '-media-uploader', 'aica_admin_data', $admin_data);

        wp_localize_script($this->plugin_slug . '-preview', 'aica_admin_assets', array(
            'default_avatar' => AICA_PLUGIN_URL . 'assets/images/default-avater.svg'
        ));
    }
}