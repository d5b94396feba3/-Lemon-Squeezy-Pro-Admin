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

    public function log_transaction($user_id, $order_id, $price_id, $amount, $currency, $status) {
        return $this->wpdb->insert($this->table_transactions, [
            'user_id'         => absint($user_id),
            'paddle_order_id' => sanitize_text_field($order_id),
            'paddle_price_id' => sanitize_text_field($price_id), // Link to product
            'amount'          => floatval($amount),
            'currency'        => sanitize_text_field($currency),
            'status'          => sanitize_text_field($status),
            'created_at'      => current_time('mysql')
        ]);
    }

    public function user_has_purchased_product($user_id, $product_id) {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$this->table_transactions} t
             INNER JOIN {$this->table_digital_products} dp ON t.paddle_price_id = dp.paddle_price_id
             WHERE t.user_id = %d AND dp.id = %d AND t.status = 'completed'",
            absint($user_id),
            absint($product_id)
        );
        
        return (bool) $this->wpdb->get_var($query);
    }

    public function get_user_purchased_products($user_id) {
        $query = $this->wpdb->prepare(
            "SELECT dp.id, dp.product_name, dp.file_path, t.created_at 
             FROM {$this->table_transactions} t 
             INNER JOIN {$this->table_digital_products} dp ON t.paddle_price_id = dp.paddle_price_id 
             WHERE t.user_id = %d AND t.status = 'completed' 
             ORDER BY t.created_at DESC",
            absint($user_id)
        );
        
        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        if ( $this->wpdb->last_error ) {
            error_log( "WPPP DB Error in Customer Dashboard: " . $this->wpdb->last_error );
        }
        
        return $results;
    }

    public function get_product_by_id($product_id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_digital_products} WHERE id = %d",
            $product_id
        ), ARRAY_A);
    }

    public function transaction_exists($paddle_order_id) {
        return (bool) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_transactions} WHERE paddle_order_id = %s",
            $paddle_order_id
        ));
    }

    public function add_product($price_id, $name, $file_url) {
        return $this->wpdb->insert($this->table_digital_products, [
            'paddle_price_id' => sanitize_text_field($price_id),
            'product_name'    => sanitize_text_field($name),
            'file_path'       => sanitize_text_field($file_url),
            'created_at'      => current_time('mysql')
        ]);
    }

    public function get_all_products() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_digital_products} ORDER BY created_at DESC", ARRAY_A);
    }

    public function get_payment_stats() {
        $rev = $this->wpdb->get_var("SELECT SUM(amount) FROM {$this->table_transactions} WHERE status = 'completed'");
        $subs = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subscriptions} WHERE status = 'active'");
        return ['revenue' => (float)$rev, 'active_subs' => (int)$subs];
    }

    public function get_all_transactions($limit = 20) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_transactions} ORDER BY created_at DESC LIMIT %d",
            absint($limit)
        ), ARRAY_A);
    }

    public static function create_table($plugin_slug) {
        global $wpdb;
        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        $table_tx   = $wpdb->prefix . $clean_slug . '_transactions';
        $table_subs = $wpdb->prefix . $clean_slug . '_subscriptions';
        $table_dp   = $wpdb->prefix . $clean_slug . '_digital_products';
        $charset_collate = $wpdb->get_charset_collate();

        $sql_tx = "CREATE TABLE $table_tx (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            paddle_order_id VARCHAR(100) NOT NULL,
            paddle_price_id VARCHAR(100) NOT NULL, 
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(10) NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY paddle_order_id (paddle_order_id),
            KEY paddle_price_id (paddle_price_id)
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
        dbDelta($sql_tx);
        dbDelta($sql_subs);
        dbDelta($sql_dp);
    }
}