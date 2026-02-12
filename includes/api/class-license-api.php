<?php
defined( 'ABSPATH' ) || exit;

class LSPA_License_API {
    private $plugin_slug;
    private $db_manager;
    // This should match a constant or option on both client and server
    private $api_secret = '4JAr8Ng0Nq7eFMn1kGg5VrD8Rwp5PA4ZmUhUe5Lj21ydBxtTRKJETDaJ0xVQQSsJ';

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        $this->db_manager = new LSPA_Plugin_DB_Manager($this->plugin_slug);
    }

    public function register_routes() {
        register_rest_route('license-api/v1', '/validate', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_validation'),
            // Security: Ensure the request comes with a valid authorization header/secret
            'permission_callback' => array($this, 'check_api_permission'),
            'args'                => array(
                'license_key' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
                'item_name'   => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
                'url'         => array('required' => true, 'sanitize_callback' => 'esc_url_raw'),
            )
        ));
    }

    /**
     * Check if the request is authorized
     */
    public function check_api_permission($request) {
        $auth_token = $request->get_header('X-AICA-Token');
        return $auth_token === $this->api_secret;
    }

    /**
     * Standardized Validation Handler
     */
    public function handle_validation($request) {
        $license_key = sanitize_text_field($request->get_param('license_key'));
        $item_name   = sanitize_text_field($request->get_param('item_name'));
        $site_url    = esc_url_raw($request->get_param('url'));

        if (empty($license_key) || empty($item_name) || empty($site_url)) {
            return new WP_Error('missing_params', __('Required fields are missing.', 'lspa-admin'), array('status' => 400));
        }

        $license_data = $this->db_manager->get_license_details($license_key, $item_name);

        if (!$license_data || $license_data->status !== 'active') {
            return new WP_REST_Response(array(
                'status'  => 'invalid',
                'message' => __('License is inactive or does not exist.', 'lspa-admin')
            ), 200);
        }

        // FIX: Changed 'validate_license_domain' to 'validate_license' to match DB Manager
        $is_domain_valid = $this->db_manager->validate_license($license_key, $item_name, $site_url);

        if (!$is_domain_valid) {
            return new WP_REST_Response(array(
                'status'  => 'invalid',
                'message' => __('Domain limit reached for this license.', 'lspa-admin')
            ), 200);
        }

        return rest_ensure_response(array(
            'status'               => 'valid',
            'package'              => sanitize_text_field($license_data->license_package),
            'allowed_domain_count' => (int) $license_data->allowed_domains,
            // FIX: Added helper to get count or calculate from string
            'activated_count'      => $this->db_manager->get_active_domain_count($license_data),
            'created_at'           => mysql_to_rfc3339($license_data->created_at),
            'checksum'             => wp_hash($license_key . $site_url)
        ));
    }


    public function generate_secure_token() {
        return wp_generate_password( 64, false );
    }
}