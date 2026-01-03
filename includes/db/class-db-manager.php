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
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL,
            full_history LONGTEXT NOT NULL,
            last_updated DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id(191))
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
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

}