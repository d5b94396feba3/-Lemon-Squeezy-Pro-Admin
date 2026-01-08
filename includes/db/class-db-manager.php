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
        
        // Define both table names clearly
        $this->table_chat_logs = $wpdb->prefix . $clean_slug . '_chat_logs';
    }

    /**
     * Create Chat Table
     */
    public static function create_table( $plugin_slug ) {
        global $wpdb;
        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        $table_name = $wpdb->prefix . $clean_slug . '_chat_logs';
        $feedback_table = $wpdb->prefix . $clean_slug . '_feedback'; // New Table
        $kb_table = $wpdb->prefix . $clean_slug . '_knowledge_base';

        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL,
            full_history LONGTEXT NOT NULL,
            last_updated DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id(191))
        ) $charset_collate;";
    
        // Table for storing Q&A Feedback
        $sql_feedback = "CREATE TABLE $feedback_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_query TEXT NOT NULL,
            ai_response TEXT NOT NULL,
            rating VARCHAR(10) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

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
        return $this->wpdb->insert(
            $this->wpdb->prefix . str_replace('-', '_', sanitize_key($this->plugin_slug)) . '_feedback',
            [
                'user_query'  => $query,
                'ai_response' => $response,
                'rating'      => $rating,
                'created_at'  => current_time('mysql')
            ]
        );
    }

    public function update_knowledge_base($url, $content) {
        global $wpdb;
        $table = $this->get_kb_table_name();
    
        // Final safety trim before SQL
        $content = trim($content);
    
        return $wpdb->replace(
            $table,
            [
                'source_url'  => esc_url_raw($url),
                'content'     => $content, // Already cleaned by the scraper
                'last_synced' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }
    
    public function get_kb_content_as_string() {
        $table = $this->wpdb->prefix . str_replace('-', '_', sanitize_key($this->plugin_slug)) . '_knowledge_base';
        $results = $this->wpdb->get_results("SELECT content FROM $table", ARRAY_A);
        $text = "";
        foreach($results as $row) {
            $text .= $row['content'] . "\n\n";
        }
        return $text;
    }

    /**
     * Fetch positive feedback to use as examples for the AI
     */
    public function get_positive_feedback(int $limit = 5) {
        $feedback_table = $this->wpdb->prefix . str_replace('-', '_', sanitize_key($this->plugin_slug)) . '_feedback';
        
        // Safety check to ensure table exists
        if($this->wpdb->get_var("SHOW TABLES LIKE '$feedback_table'") != $feedback_table) {
            return [];
        }

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT user_query, ai_response FROM $feedback_table WHERE rating = 'like' ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Fetch feedback by rating type
     */
    public function get_feedback_by_type(string $rating, int $limit = 5) {
        $feedback_table = $this->wpdb->prefix . str_replace('-', '_', sanitize_key($this->plugin_slug)) . '_feedback';
        
        if($this->wpdb->get_var("SHOW TABLES LIKE '$feedback_table'") != $feedback_table) {
            return [];
        }

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT user_query, ai_response FROM $feedback_table WHERE rating = %s ORDER BY created_at DESC LIMIT %d",
                $rating,
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Save Chat History (Called by handle_chat_message_ajax)
     */
    public function save_chat_history($session_id, $history_array) {
        $history_data = json_encode($history_array);

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
        return $this->wpdb->delete($this->table_chat_logs, array('id' => $id), array('%d'));
    }

    /**
     * Fetch a single chat log by its primary ID
     * * @param int $log_id The database ID of the log
     * @return array|null Associative array of the log data or null if not found
     */
    public function get_chat_log_by_id($log_id) {
        global $wpdb;

        // Standardize the table name using the plugin slug
        $clean_slug = str_replace('-', '_', sanitize_key($this->plugin_slug));
        $table_name = $wpdb->prefix . $clean_slug . '_chat_logs';

        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d LIMIT 1",
            $log_id
        );

        return $wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Fetch logs for export (single or all)
     */
    public function get_logs_for_export($id = 0) {
        if ($id > 0) {
            return $this->wpdb->get_results(
                $this->wpdb->prepare("SELECT * FROM {$this->table_chat_logs} WHERE id = %d", $id), 
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
            $this->wpdb->prepare("SELECT last_synced FROM $table WHERE source_url = %s", $url),
            ARRAY_A
        );
    }

}