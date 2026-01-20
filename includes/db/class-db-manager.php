<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_DB_Manager {

    private $plugin_slug;
    protected $wpdb;
    protected $table_chat_logs;

    public function __construct( $plugin_slug ) {
        global $wpdb;
        $this->plugin_slug = $plugin_slug;
        $this->wpdb = $wpdb;

        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        $this->table_chat_logs = $wpdb->prefix . $clean_slug . '_chat_logs';
    }

    /**
     * Creates the necessary database tables on activation.
     */
    public static function create_table( $plugin_slug ) {
        global $wpdb;
        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        
        $table_name     = $wpdb->prefix . $clean_slug . '_chat_logs';
        $feedback_table = $wpdb->prefix . $clean_slug . '_feedback';
        $kb_table       = $wpdb->prefix . $clean_slug . '_knowledge_base';

        $charset_collate = $wpdb->get_charset_collate();
    
        // Chat Logs Table
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL,
            full_history LONGTEXT NOT NULL,
            last_updated DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id(191))
        ) $charset_collate;";
    
        // Feedback Table
        $sql_feedback = "CREATE TABLE $feedback_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_query TEXT NOT NULL,
            ai_response TEXT NOT NULL,
            rating VARCHAR(10) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Knowledge Base Table
        $sql_kb = "CREATE TABLE $kb_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_url VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            last_synced DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY source_url (source_url(191))
        ) $charset_collate;";
    
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        dbDelta( $sql_feedback );
        dbDelta( $sql_kb );
    }

    public function store_feedback($query, $response, $rating) {
        $table = $this->wpdb->prefix . str_replace('-', '_', sanitize_key($this->plugin_slug)) . '_feedback';
        return $this->wpdb->insert(
            $table,
            [
                'user_query'  => $query,
                'ai_response' => $response,
                'rating'      => $rating,
                'created_at'  => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    public function update_knowledge_base($url, $content) {
        $table = $this->get_kb_table_name();
        $content = trim($content);

        return $this->wpdb->replace(
            $table,
            [
                'source_url'  => esc_url_raw($url),
                'content'     => $content,
                'last_synced' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    public function get_kb_content_as_string() {
        $table = $this->get_kb_table_name();
        
        // Safety check if table exists
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            return "";
        }

        $results = $this->wpdb->get_results("SELECT source_url, content FROM $table", ARRAY_A);
        $text = "";
        if ($results) {
            foreach($results as $row) {
                $text .= "Page URL: " . esc_url($row['source_url']) . "\n";
                $text .= $row['content'] . "\n\n";
            }
        }
        return $text;
    }

    public function get_feedback_by_type(string $rating, int $limit = 5) {
        $feedback_table = $this->wpdb->prefix . str_replace('-', '_', sanitize_key($this->plugin_slug)) . '_feedback';
        
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $feedback_table)) !== $feedback_table) {
            return [];
        }

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT user_query, ai_response FROM $feedback_table WHERE rating = %s ORDER BY created_at DESC LIMIT %d",
                sanitize_key($rating),
                $limit
            ),
            ARRAY_A
        );
    }

    public function save_chat_history($session_id, $history_array) {
        $history_data = json_encode($history_array);
        $session_id   = sanitize_key($session_id);

        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_chat_logs} WHERE session_id = %s", 
            $session_id
        ));

        if ($exists) {
            return $this->wpdb->update(
                $this->table_chat_logs,
                ['full_history' => $history_data, 'last_updated' => current_time('mysql')],
                ['session_id' => $session_id],
                ['%s', '%s'],
                ['%s']
            );
        } else {
            return $this->wpdb->insert(
                $this->table_chat_logs,
                [
                    'session_id'   => $session_id,
                    'full_history' => $history_data,
                    'last_updated' => current_time('mysql')
                ],
                ['%s', '%s', '%s']
            );
        }
    }

    public function get_all_chat_logs() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_chat_logs} ORDER BY last_updated DESC", ARRAY_A);
    }

    public function delete_chat_log($id) {
        return $this->wpdb->delete($this->table_chat_logs, array('id' => intval($id)), array('%d'));
    }

    public function get_chat_log_by_id($log_id) {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_chat_logs} WHERE id = %d LIMIT 1",
            intval($log_id)
        );
        return $this->wpdb->get_row($query, ARRAY_A);
    }

    public function get_logs_for_export($id = 0) {
        if ($id > 0) {
            return $this->wpdb->get_results(
                $this->wpdb->prepare("SELECT * FROM {$this->table_chat_logs} WHERE id = %d", intval($id)), 
                ARRAY_A
            );
        }
        return $this->get_all_chat_logs();
    }

    public function get_kb_table_name() {
        return $this->wpdb->prefix . str_replace('-', '_', sanitize_key($this->plugin_slug)) . '_knowledge_base';
    }

    public function get_sync_status($url) {
        $table = $this->get_kb_table_name();
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT last_synced FROM $table WHERE source_url = %s", esc_url_raw($url)),
            ARRAY_A
        );
    }
}