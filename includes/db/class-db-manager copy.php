<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_DB_Manager {
    private $plugin_slug;
    protected $wpdb;
    protected $table_transactions;
    protected $table_subscriptions;
    protected $table_digital_products;

    public function __construct( $plugin_slug ) {
        global $wpdb;
        $this->plugin_slug = $plugin_slug;
        $this->wpdb = $wpdb;

        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        $this->table_transactions     = $wpdb->prefix . $clean_slug . '_transactions';
        $this->table_subscriptions    = $wpdb->prefix . $clean_slug . '_subscriptions';
        $this->table_digital_products = $wpdb->prefix . $clean_slug . '_digital_products';
    }

    public static function create_table( $plugin_slug ) {
        global $wpdb;
        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        
        $table_tx   = $wpdb->prefix . $clean_slug . '_transactions';
        $table_subs = $wpdb->prefix . $clean_slug . '_subscriptions';
        $table_dp   = $wpdb->prefix . $clean_slug . '_digital_products';

        $charset_collate = $wpdb->get_charset_collate();
    
        // Corrected SQL: Removed (191) prefix on VARCHAR(100) columns
        $sql_tx = "CREATE TABLE $table_tx (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            paddle_order_id VARCHAR(100) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(10) NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY paddle_order_id (paddle_order_id)
        ) $charset_collate;";
    
        $sql_subs = "CREATE TABLE $table_subs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            paddle_sub_id VARCHAR(100) NOT NULL,
            plan_id VARCHAR(100) NOT NULL,
            status VARCHAR(50) NOT NULL,
            next_bill_date DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY paddle_sub_id (paddle_sub_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        $sql_dp = "CREATE TABLE $table_dp (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            paddle_price_id VARCHAR(100) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            file_path TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY paddle_price_id (paddle_price_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_tx );
        dbDelta( $sql_subs );
        dbDelta( $sql_dp );
    }

    public function get_user_purchased_products($user_id) {
        $query = "
            SELECT dp.product_name, dp.file_path, t.created_at
            FROM {$this->table_transactions} t
            INNER JOIN {$this->table_digital_products} dp ON t.paddle_order_id IS NOT NULL 
            WHERE t.user_id = %d AND t.status = 'completed'
            ORDER BY t.created_at DESC
        ";
        return $this->wpdb->get_results($this->wpdb->prepare($query, absint($user_id)), ARRAY_A);
    }

    public function get_all_products() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_digital_products} ORDER BY created_at DESC", ARRAY_A);
    }

    public function add_product($price_id, $name, $file_url) {
        return $this->wpdb->insert(
            $this->table_digital_products,
            [
                'paddle_price_id' => sanitize_text_field($price_id),
                'product_name'    => sanitize_text_field($name),
                'file_path'       => sanitize_text_field($file_url),
                'created_at'      => current_time('mysql')
            ]
        );
    }

    public function get_payment_stats() {
        $total_revenue = $this->wpdb->get_var("SELECT SUM(amount) FROM {$this->table_transactions} WHERE status = 'completed'");
        $active_subs   = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subscriptions} WHERE status = 'active'");
        return ['revenue' => (float)$total_revenue, 'active_subs' => (int)$active_subs];
    }

    public function get_all_transactions($limit = 20) {
        return $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM {$this->table_transactions} ORDER BY created_at DESC LIMIT %d", absint($limit)), ARRAY_A);
    }
}