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
        $template_path = AICA_PLUGIN_PATH . 'public/partials/chat-template.php';
    
        if ( file_exists( $template_path ) ) {
            ob_start();
            include $template_path;
            echo ob_get_clean(); 
        }
    }

    public function handle_chat_message_ajax() {
        // Security & Honeypot
        if ( ! empty( $_POST['hp_field'] ) ) wp_send_json_error( array( 'message' => 'Bot submission rejected.' ) );
        if ( ! isset( $_POST['chat_nonce'] ) || ! wp_verify_nonce( $_POST['chat_nonce'], 'chat_message_action' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }
    
        $user_message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
        if ( empty( $user_message ) ) wp_send_json_error( array( 'message' => 'Empty message.' ) );
    
        // Logic: Get or Create UUID in PHP
        $session_key = $this->get_chat_session_id();
        $transient_key = 'chat_hist_' . $session_key;
    
        // 1. Get History
        $history = get_transient($transient_key);
        $is_new_chat = ( ! is_array($history) || empty($history) );
        if ( ! is_array($history) ) $history = [];
    
        // 2. Add User Message
        $history[] = ['role' => 'user', 'content' => $user_message];

        if ( $is_new_chat ) {
            $this->mail_manager->send_chat_notification_admin($user_message);
        }
    
        // 3. Get AI Response
        $llm_processor = new AICA_Plugin_LLM_Processor( $this->plugin_slug );
        $ai_response = $llm_processor->get_llm_response( $user_message, $history );
    
        if ( strpos( $ai_response, 'Error:' ) === 0 ) {
            wp_send_json_error( array( 'message' => $ai_response ) );
        }
    
        // 4. Add AI Response
        $history[] = ['role' => 'assistant', 'content' => $ai_response];
    
        // 5. Save to Transient (Memory synced to 1 hour)
        set_transient($transient_key, $history, 1 * HOUR_IN_SECONDS);
    
        // 6. SAVE TO DB (DB Manager handles the Update if session_key exists)
        if ( $this->db_manager ) {
            $this->db_manager->save_chat_history($session_key, $history);
        }
            
        wp_send_json_success( array( 'reply' => wp_kses_post( $ai_response ) ) );
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