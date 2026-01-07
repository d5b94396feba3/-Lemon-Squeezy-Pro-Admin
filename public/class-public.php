<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_Public {

    private $plugin_slug;
    private $version;
    private $mail_manager;
    private $db_manager;

    public function __construct( $plugin_slug, $version ) {
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
    
        $this->mail_manager = new AICA_Plugin_Mail($this->plugin_slug);
    
        if ( class_exists('AICA_Plugin_DB_Manager') ) {
            $this->db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        }
    }

    /**
     * Helper: Get the session IDs associated with this specific browser
     */
    private function get_user_session_tracker() {
        $cookie_name = 'aica_user_sessions';
        if (isset($_COOKIE[$cookie_name])) {
            // Returns an array of session UUIDs
            return explode(',', sanitize_text_field($_COOKIE[$cookie_name]));
        }
        return [];
    }

    /**
     * Helper: Add a session ID to the browser's tracker cookie
     */
    private function add_session_to_tracker($uuid) {
        $sessions = $this->get_user_session_tracker();
        if (!in_array($uuid, $sessions)) {
            $sessions[] = $uuid;
            // Limit to last 15 sessions to prevent cookie bloat
            $sessions = array_slice($sessions, -15);
            setcookie('aica_user_sessions', implode(',', $sessions), time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    /**
     * Get existing Chat UUID or create a new one, then track it
     */
    private function get_chat_session_id() {
        $cookie_name = 'aica_chat_uuid';
        
        if ( isset($_COOKIE[$cookie_name]) ) {
            return sanitize_key($_COOKIE[$cookie_name]);
        }

        // Generate unique UUID
        $uuid = wp_generate_password(12, false) . '_' . time();
        
        // Set active session cookie (1 hour)
        setcookie($cookie_name, $uuid, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        
        // Add to the long-term tracker for "Recent Chats"
        $this->add_session_to_tracker($uuid);
        
        return $uuid;
    }

    public function render_chat_ui() {
        $session_key = $this->get_chat_session_id();
        $transient_key = 'chat_hist_' . $session_key;
        
        $existing_history = get_transient($transient_key);
        if (!is_array($existing_history)) {
            $existing_history = [];
        }
    
        $template_path = AICA_PLUGIN_PATH . 'public/partials/chat-template.php';
    
        if ( file_exists( $template_path ) ) {
            ob_start();
            $chat_history = $existing_history; 
            include $template_path;
            echo ob_get_clean(); 
        }
    }

    public function handle_chat_message_ajax() {
        check_ajax_referer('chat_message_action', 'chat_nonce');

        $user_message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
        if ( empty( $user_message ) ) {
            wp_send_json_error( array( 'message' => 'Empty message.' ) );
        }
    
        $is_regen = isset($_POST['is_regen']) && ($_POST['is_regen'] === 'true' || $_POST['is_regen'] === true);
    
        $session_key = $this->get_chat_session_id();
        $transient_key = 'chat_hist_' . $session_key;
    
        $history = get_transient($transient_key);
        if ( ! is_array($history) ) $history = [];
        $is_new_chat = empty($history);
    
        if ( ! $is_regen ) {
            $history[] = [
                'role'    => 'user', 
                'content' => $user_message,
                'time'    => time()
            ];
            if ( $is_new_chat ) {
                $this->mail_manager->send_chat_notification_admin($user_message);
            }
        }
    
        $llm_processor = new AICA_Plugin_LLM_Processor( $this->plugin_slug );
        $ai_response_html = $llm_processor->get_llm_response( $user_message, $history );
    
        if ( strpos( $ai_response_html, 'Error:' ) === 0 ) {
            wp_send_json_error( array( 'message' => $ai_response_html ) );
        }
    
        $ai_response_plain = wp_strip_all_tags( $ai_response_html );
    
        if ( $is_regen && ! empty($history) ) {
            for ( $i = count($history) - 1; $i >= 0; $i-- ) {
                if ( $history[$i]['role'] === 'assistant' ) {
                    $history[$i] = [
                        'role' => 'assistant', 
                        'content' => $ai_response_plain, 
                        'time' => time()
                    ];
                    break;
                }
            }
        } else {
            $history[] = [
                'role' => 'assistant', 
                'content' => $ai_response_plain, 
                'time' => time()
            ];
        }
    
        set_transient($transient_key, $history, 1 * HOUR_IN_SECONDS);
    
        if ( $this->db_manager ) {
            $this->db_manager->save_chat_history($session_key, $history);
        }
            
        wp_send_json_success( array( 'reply' => wp_kses_post( $ai_response_html ) ) );
    }

    /**
     * Clear Chat: Simply removes active cookie so next message starts a NEW session.
     * History is NOT deleted, so it stays in "Recent Chats".
     */
    public function handle_clear_chat_ajax() {
        check_ajax_referer('chat_message_action', 'nonce');
        setcookie('aica_chat_uuid', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        wp_send_json_success();
    }

    /**
     * End Chat: Generates summary, sends transcript, then clears active cookie.
     */
    public function handle_end_chat_ajax() {
        check_ajax_referer('chat_message_action', 'nonce');
        
        $session_key = $this->get_chat_session_id();
        $transient_key = 'chat_hist_' . $session_key;
        $history = get_transient($transient_key);

        if (is_array($history) && !empty($history)) {
            // Generate Summary
            $llm_processor = new AICA_Plugin_LLM_Processor($this->plugin_slug);
            $summary_html = $llm_processor->get_chat_summary($history);

            // Send Email with Summary
            $this->mail_manager->send_final_transcript_admin($history, $summary_html);
        }

        setcookie('aica_chat_uuid', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        wp_send_json_success();
    }

    /**
     * Fetch Recent Chats: Only shows logs that match the IDs in the user's tracker cookie.
     */
    public function handle_fetch_recent_chats() {
        check_ajax_referer('chat_message_action', 'nonce');
        
        $user_sessions = $this->get_user_session_tracker();
        if (empty($user_sessions)) {
            wp_send_json_success([]);
        }

        global $wpdb;
        $clean_slug = str_replace('-', '_', sanitize_key($this->plugin_slug));
        $table_name = $wpdb->prefix . $clean_slug . '_chat_logs';

        // Fetch only the logs belonging to this specific browser's history
        $placeholders = implode(',', array_fill(0, count($user_sessions), '%s'));
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id IN ($placeholders) ORDER BY last_updated DESC",
            $user_sessions
        );

        $logs = $wpdb->get_results($query, ARRAY_A);
        $output = [];

        foreach ($logs as $log) {
            $history = json_decode($log['full_history'], true);
            if (empty($history)) continue;

            // Use the first visitor message as the preview title
            $preview = 'New Conversation';
            foreach($history as $msg) {
                if($msg['role'] === 'user') {
                    $preview = $msg['content'];
                    break;
                }
            }
            
            $output[] = [
                'id' => $log['session_id'],
                'date' => date('M j, g:i a', strtotime($log['last_updated'])),
                'preview' => wp_trim_words($preview, 8),
                'history' => $history
            ];
        }

        wp_send_json_success($output);
    }

    public function handle_chat_feedback_ajax() {
        check_ajax_referer('chat_message_action', 'nonce');
        
        $query    = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $response = isset($_POST['response']) ? sanitize_text_field($_POST['response']) : '';
        $rating   = isset($_POST['rating']) ? sanitize_key($_POST['rating']) : ''; 
    
        if ($this->db_manager && !empty($query)) {
            $this->db_manager->store_feedback($query, $response, $rating);
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function enqueue_styles() {
        wp_enqueue_style('font-awesome-4', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), '4.7.0');
        wp_enqueue_style( $this->plugin_slug . '-public',  AICA_PLUGIN_URL . 'assets/css/public.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts_old() {
        wp_enqueue_script( $this->plugin_slug . '-public', AICA_PLUGIN_URL . 'assets/js/public.js', array('jquery'), $this->version, true );
        wp_localize_script(
            $this->plugin_slug . '-public',
            'chatData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('chat_message_action'),
            )
        );
    }

    public function enqueue_scripts() {
        $js_path = AICA_PLUGIN_URL . 'assets/js/';
        $ver = $this->version;
    
        // Register & Enqueue Modular Scripts
        wp_enqueue_script($this->plugin_slug . '-core', $js_path . 'chat-core.js', array('jquery'), $ver, true);
        wp_enqueue_script($this->plugin_slug . '-messaging', $js_path . 'chat-messaging.js', array($this->plugin_slug . '-core'), $ver, true);
        wp_enqueue_script($this->plugin_slug . '-menu', $js_path . 'chat-menu.js', array($this->plugin_slug . '-core'), $ver, true);
        wp_enqueue_script($this->plugin_slug . '-history', $js_path . 'chat-history.js', array($this->plugin_slug . '-core'), $ver, true);
    
        wp_localize_script($this->plugin_slug . '-core', 'chatData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('chat_message_action'),
        ));
    }
}