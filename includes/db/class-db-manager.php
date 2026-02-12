<?php
defined( 'ABSPATH' ) || exit;

class LSPA_Plugin_DB_Manager {
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
        $this->table_licenses         = $wpdb->prefix . $clean_slug . '_licenses';
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

    /**
     * Verify if a transaction ID belongs to a specific user.
     * * @param string $tx_id   The Paddle Transaction ID.
     * @param int    $user_id The WordPress User ID.
     * @return bool
     */
    public function is_transaction_valid_for_user($tx_id, $user_id) {
        if (empty($tx_id) || empty($user_id)) {
            return false;
        }

        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_transactions} 
            WHERE paddle_order_id = %s AND user_id = %d AND status = 'completed'",
            sanitize_text_field($tx_id),
            absint($user_id)
        );

        return (bool) $this->wpdb->get_var($query);
    }

    public static function create_table($plugin_slug) {
        global $wpdb;
        $clean_slug = str_replace('-', '_', sanitize_key($plugin_slug));
        $table_tx   = $wpdb->prefix . $clean_slug . '_transactions';
        $table_subs = $wpdb->prefix . $clean_slug . '_subscriptions';
        $table_dp   = $wpdb->prefix . $clean_slug . '_digital_products';
        $table_licenses = $wpdb->prefix . $clean_slug . '_licenses';
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

        $sql_licenses = "CREATE TABLE $table_licenses (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            license_key varchar(100) NOT NULL,
            item_name varchar(255) NOT NULL,
            license_package varchar(50) DEFAULT 'basic' NOT NULL,
            allowed_domains int(11) DEFAULT 1 NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            activated_domains text DEFAULT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY license_key (license_key),
            KEY item_name (item_name)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_tx);
        dbDelta($sql_subs);
        dbDelta($sql_dp);
        dbDelta($sql_licenses);
    }

    /**
     * Enhanced Validation Logic
     * Checks if the site is already registered or if the limit is reached.
     */
    public function validate_license($key, $item_name, $current_domain) {
        $license = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_licenses} WHERE license_key = %s AND item_name = %s",
            sanitize_text_field($key),
            sanitize_text_field($item_name)
        ));

        if (!$license || $license->status !== 'active') {
            return false;
        }

        // Handle multiple domains stored as a serialized array or comma-separated list
        $activated_domains = !empty($license->activated_domains) ? explode(',', $license->activated_domains) : array();
        $activated_domains = array_map('trim', $activated_domains);

        // 1. If this domain is already activated, it's valid.
        if (in_array($current_domain, $activated_domains)) {
            return true;
        }

        // 2. If not activated, check if we have room for another domain
        if (count($activated_domains) < (int) $license->allowed_domains) {
            $activated_domains[] = $current_domain;
            $new_list = implode(',', $activated_domains);

            $this->wpdb->update(
                $this->table_licenses,
                array('activated_domains' => $new_list),
                array('id' => $license->id)
            );
            return true;
        }

        return false; // Limit reached
    }

    /**
     * Helper to count currently activated domains
     */
    public function get_active_domain_count($license_data) {
        if (empty($license_data->activated_domains)) {
            return 0;
        }
        $domains = explode(',', $license_data->activated_domains);
        return count(array_filter(array_map('trim', $domains)));
    }

    /**
     * Get License details securely
     */
    public function get_license_details($key, $item_name) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_licenses} WHERE license_key = %s AND item_name = %s LIMIT 1",
            $key,
            $item_name
        ));
    }
    
}