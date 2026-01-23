<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_DB_Manager {
    private $plugin_slug;
    protected $wpdb;
    protected $table_transactions;
    protected $table_digital_products;

    public function __construct( $plugin_slug ) {
        global $wpdb;
        $this->plugin_slug = $plugin_slug;
        $this->wpdb = $wpdb;

        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        $this->table_transactions     = $wpdb->prefix . $clean_slug . '_transactions';
        $this->table_digital_products = $wpdb->prefix . $clean_slug . '_digital_products';
    }

    /**
     * Get all digital products
     */
    public function get_all_products() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_digital_products} ORDER BY created_at DESC",
            ARRAY_A
        );
    }

    /**
     * Insert a new digital product
     */
    public function add_product($price_id, $name, $file_url) {
        return $this->wpdb->insert(
            $this->table_digital_products,
            [
                'paddle_price_id' => sanitize_text_field($price_id),
                'product_name'    => sanitize_text_field($name),
                'file_path'       => sanitize_text_field($file_url),
                'created_at'      => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    /**
     * Delete a product
     */
    public function delete_product($id) {
        return $this->wpdb->delete($this->table_digital_products, ['id' => absint($id)], ['%d']);
    }

    /**
     * Creates the necessary database tables on activation.
     */
    public static function create_table( $plugin_slug ) {
        global $wpdb;
        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        
        $table_tx   = $wpdb->prefix . $clean_slug . '_transactions';
        $table_subs = $wpdb->prefix . $clean_slug . '_subscriptions';
        $table_digital_products = $wpdb->prefix . $clean_slug . '_digital_products';


        $charset_collate = $wpdb->get_charset_collate();
    
        // Transactions Table (One-time payments & initial orders)
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
            KEY paddle_order_id (paddle_order_id(191))
        ) $charset_collate;";
    
        // Subscriptions Table (Recurring billing state)
        $sql_subs = "CREATE TABLE $table_subs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            paddle_sub_id VARCHAR(100) NOT NULL,
            plan_id VARCHAR(100) NOT NULL,
            status VARCHAR(50) NOT NULL,
            next_bill_date DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY paddle_sub_id (paddle_sub_id(191)),
            KEY user_id (user_id)
        ) $charset_collate;";

        $sql_dp = "CREATE TABLE $table_digital_products (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            paddle_price_id VARCHAR(100) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            file_path TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY paddle_price_id (paddle_price_id(191))
        ) $charset_collate;";

        dbDelta( $sql_dp );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_tx );
        dbDelta( $sql_subs );
        dbDelta( $table_digital_products );
    }

    /**
     * Logs a completed transaction.
     */
    public function log_transaction($user_id, $order_id, $amount, $currency, $status) {
        return $this->wpdb->insert(
            $this->table_transactions,
            [
                'user_id'         => absint($user_id),
                'paddle_order_id' => sanitize_text_field($order_id),
                'amount'          => floatval($amount),
                'currency'        => sanitize_text_field($currency),
                'status'          => sanitize_text_field($status),
                'created_at'      => current_time('mysql')
            ],
            ['%d', '%s', '%f', '%s', '%s', '%s']
        );
    }

    /**
     * Updates or creates a user subscription record.
     */
    public function update_subscription($user_id, $sub_id, $plan_id, $status, $next_bill = null) {
        $data = [
            'user_id'        => absint($user_id),
            'paddle_sub_id'  => sanitize_text_field($sub_id),
            'plan_id'        => sanitize_text_field($plan_id),
            'status'         => sanitize_text_field($status),
            'created_at'     => current_time('mysql')
        ];
        
        if ($next_bill) {
            $data['next_bill_date'] = $next_bill;
        }

        return $this->wpdb->replace(
            $this->table_subscriptions,
            $data,
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get user subscription status directly for permission checks.
     */
    public function get_user_subscription($user_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_subscriptions} WHERE user_id = %d LIMIT 1",
                absint($user_id)
            ),
            ARRAY_A
        );
    }

    /**
     * Get all transactions for the admin dashboard.
     */
    public function get_all_transactions($limit = 20) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_transactions} ORDER BY created_at DESC LIMIT %d",
                absint($limit)
            ),
            ARRAY_A
        );
    }

    /**
     * Stats for the dashboard widgets.
     */
    public function get_payment_stats() {
        $total_revenue = $this->wpdb->get_var("SELECT SUM(amount) FROM {$this->table_transactions} WHERE status = 'completed'");
        $active_subs   = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subscriptions} WHERE status = 'active'");

        return [
            'revenue'     => (float) $total_revenue,
            'active_subs' => (int) $active_subs
        ];
    }

    public function delete_transaction_log($id) {
        return $this->wpdb->delete($this->table_transactions, array('id' => intval($id)), array('%d'));
    }
}