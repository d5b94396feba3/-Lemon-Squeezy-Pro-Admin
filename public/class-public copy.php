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

    public function render_chat_ui() {
        $session_key = $this->get_chat_session_id();
        $transient_key = 'chat_hist_' . $session_key;
        
        // Fetch existing history to restore on refresh
        $existing_history = get_transient($transient_key);
        if (!is_array($existing_history)) {
            $existing_history = [];
        }
    
        $template_path = AICA_PLUGIN_PATH . 'public/partials/chat-template.php';
    
        if ( file_exists( $template_path ) ) {
            ob_start();
            // Pass history to the template
            $chat_history = $existing_history; 
            include $template_path;
            echo ob_get_clean(); 
        }
    }

    public function handle_chat_message_ajax() {
        // 1. Security & Honeypot
        if ( ! empty( $_POST['hp_field'] ) ) {
            wp_send_json_error( array( 'message' => 'Bot submission rejected.' ) );
        }
        
        if ( ! isset( $_POST['chat_nonce'] ) || ! wp_verify_nonce( $_POST['chat_nonce'], 'chat_message_action' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }
    
        $user_message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
        if ( empty( $user_message ) ) {
            wp_send_json_error( array( 'message' => 'Empty message.' ) );
        }
    
        // NEW: Check if this is a regeneration request
        $is_regen = isset($_POST['is_regen']) && ($_POST['is_regen'] === 'true' || $_POST['is_regen'] === true);
    
        // 2. Logic: Get or Create UUID from PHP Session/Cookie
        $session_key = $this->get_chat_session_id();
        $transient_key = 'chat_hist_' . $session_key;
    
        // 3. Get History for AI context
        $history = get_transient($transient_key);
        $is_new_chat = ( ! is_array($history) || empty($history) );
        if ( ! is_array($history) ) {
            $history = [];
        }
    
        // Only add User Message to History if it's NOT a regeneration
        if ( ! $is_regen ) {
            $history[] = [
                'role'    => 'user', 
                'content' => $user_message,
                'time'    => time()
            ];
    
            // Send Admin Notification if it's the first message
            if ( $is_new_chat ) {
                $this->mail_manager->send_chat_notification_admin($user_message);
            }
        }
    
        // 4. Get AI Response from Grok (This contains HTML tags for the UI)
        $llm_processor = new AICA_Plugin_LLM_Processor( $this->plugin_slug );
        $ai_response_html = $llm_processor->get_llm_response( $user_message, $history );
    
        if ( strpos( $ai_response_html, 'Error:' ) === 0 ) {
            wp_send_json_error( array( 'message' => $ai_response_html ) );
        }
    
        $ai_response_plain = wp_strip_all_tags( $ai_response_html );
    
        // 5. Update History Stack
        if ( $is_regen && ! empty($history) ) {
            // Find and replace the last assistant message
            for ( $i = count($history) - 1; $i >= 0; $i-- ) {
                if ( $history[$i]['role'] === 'assistant' ) {
                    $history[$i] = [
                        'role'    => 'assistant', 
                        'content' => $ai_response_plain, 
                        'time'    => time()
                    ];
                    break;
                }
            }
        } else {
            // Standard flow: Append the new assistant message
            $history[] = [
                'role'    => 'assistant', 
                'content' => $ai_response_plain, 
                'time'    => time()
            ];
        }
    
        // 6. Save to Transient (Memory synced to 1 hour)
        set_transient($transient_key, $history, 1 * HOUR_IN_SECONDS);
    
        // 7. Save to Database (DB Manager handles the Update/Insert)
        if ( $this->db_manager ) {
            $save_result = $this->db_manager->save_chat_history($session_key, $history);
            if ( false === $save_result ) {
                error_log('AI Chat Error: Failed to save history to DB for session ' . $session_key);
            }
        }
            
        // 8. Return the HTML version to the Javascript for the nice Chat Bubble UI
        wp_send_json_success( array( 
            'reply' => wp_kses_post( $ai_response_html ) 
        ) );
    }

    public function handle_clear_chat_ajax() {
        check_ajax_referer('chat_message_action', 'nonce');
        
        $session_key = $this->get_chat_session_id();
        delete_transient('chat_hist_' . $session_key);
        
        // Optional: Clear cookie to get a brand new UUID
        setcookie('aica_chat_uuid', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        
        wp_send_json_success();
    }

    // Add to class-public.php
    public function handle_fetch_recent_chats() {
        check_ajax_referer('chat_message_action', 'nonce');
        
        if (!$this->db_manager) wp_send_json_error();

        $logs = $this->db_manager->get_all_chat_logs(); // Fetches all logs for user view
        $output = [];

        foreach ($logs as $log) {
            $history = json_decode($log['full_history'], true);
            $preview = !empty($history) ? $history[0]['content'] : 'Empty conversation';
            
            $output[] = [
                'id' => $log['id'],
                'date' => date('M j, Y', strtotime($log['last_updated'])),
                'preview' => wp_trim_words($preview, 10),
                'history' => $history
            ];
        }

        wp_send_json_success($output);
    }

    public function handle_chat_feedback_ajax() {
        check_ajax_referer('chat_message_action', 'nonce');
        
        $query    = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $response = isset($_POST['response']) ? sanitize_text_field($_POST['response']) : '';
        $rating   = isset($_POST['rating']) ? sanitize_key($_POST['rating']) : ''; // 'like' or 'dislike'
    
        if ($this->db_manager && !empty($query)) {
            $this->db_manager->store_feedback($query, $response, $rating);
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    /**
     * Helper: Get existing Chat UUID from cookie or create a new one
     */
    private function get_chat_session_id() {
        $cookie_name = 'aica_chat_uuid';
        
        if ( isset($_COOKIE[$cookie_name]) ) {
            return sanitize_key($_COOKIE[$cookie_name]);
        }

        // Generate unique UUID
        $uuid = wp_generate_password(12, false) . '_' . time();
        
        // Set cookie for 1 hour
        setcookie($cookie_name, $uuid, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        
        return $uuid;
    }

    public function handle_end_chat_ajax() {
        check_ajax_referer('chat_message_action', 'nonce');
        
        $session_key = $this->get_chat_session_id();
        $transient_key = 'chat_hist_' . $session_key;
        $history = get_transient($transient_key);

        if (is_array($history) && !empty($history)) {
            // Send the full transcript to admin
            $this->mail_manager->send_final_transcript_admin($history);
        }

        // Clear the history
        delete_transient($transient_key);
        
        // Kill the session cookie
        setcookie('aica_chat_uuid', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        
        wp_send_json_success(array('message' => 'Session ended and transcript sent.'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'font-awesome-4',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
            array(),
            '4.7.0'
        );

        wp_enqueue_style( $this->plugin_slug . '-public',  AICA_PLUGIN_URL . 'assets/css/public.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts() {

        // 1. ENQUEUE FIRST
        wp_enqueue_script( 
            $this->plugin_slug . '-public', 
            AICA_PLUGIN_URL . 'assets/js/public.js', 
            array('jquery'), 
            $this->version, 
            true 
        );
    
        // 2. LOCALIZE SECOND (Using the handle enqueued above)
        wp_localize_script(
            $this->plugin_slug . '-public',
            'chatData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('chat_message_action'),
            )
        );
    }
}