<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_Public {
    private $plugin_slug;
    private $version;

    public function __construct( $plugin_slug, $version ) {
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_slug . '-public', WPPP_PLUGIN_URL . 'assets/css/public/public.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts() {
        $client_token  = get_option('paddle_client_side_token');
        $environment   = get_option('paddle_environment', 'sandbox');
        $thank_you_url = get_option('paddle_thank_you_page_url');

        wp_enqueue_script( 'paddle-js', 'https://cdn.paddle.com/paddle/v2/paddle.js', array(), null, true );

        wp_enqueue_script( $this->plugin_slug . '-checkout', WPPP_PLUGIN_URL . 'assets/js/public/checkout.js', array('jquery', 'paddle-js'), $this->version, true );

        wp_localize_script( $this->plugin_slug . '-checkout', 'wppp_vars', array(
            'token'       => esc_js($client_token),
            'environment' => ($environment === 'live') ? 'production' : 'sandbox',
            'user_id'     => get_current_user_id(),
            'is_logged_in'=> is_user_logged_in(),
            'thank_you'   => esc_url($thank_you_url),
            'login_msg'   => __('Please login to purchase this item.', 'wp-paddle-pro')
        ));
    }

    public function render_checkout_button($atts) {
        $atts = shortcode_atts([
            'price_id' => '',
            'text'     => __('Buy Now', 'wp-paddle-pro'),
            'class'    => 'paddle-checkout-button button-primary'
        ], $atts);

        if (empty($atts['price_id'])) return '';

        ob_start();
        ?>
        <div class="wppp-checkout-container">
            <button class="<?php echo esc_attr($atts['class']); ?> wppp-paddle-checkout" 
                    data-price-id="<?php echo esc_attr($atts['price_id']); ?>">
                <?php echo esc_html($atts['text']); ?>
            </button>
            <div class="paddle-login-message" style="display: none; color: #c00; margin-top: 8px; font-size: 13px;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_order_summary($atts) {
        $tx_id = isset($_GET['paddle_transaction_id']) ? sanitize_text_field($_GET['paddle_transaction_id']) : '';
        if (!$tx_id) return '<p>' . esc_html__('No transaction found.', 'wp-paddle-pro') . '</p>';

        require_once WPPP_PLUGIN_PATH . 'includes/mycred/class-mycred-handler.php';
        $mycred = new \PaddleToMycred\Mycred_Handler();
        
        $mycred_data = $mycred->mycred_balance_with_rank();

        ob_start();
        if (file_exists(WPPP_PLUGIN_PATH . 'public/partials/order-summary-template.php')) {
            include WPPP_PLUGIN_PATH . 'public/partials/order-summary-template.php';
        }
        return ob_get_clean();
    }

    public function render_customer_dashboard() {
        if ( !is_user_logged_in() ) {
            return sprintf(
                '<p>%s <a href="%s">%s</a> %s</p>',
                esc_html__('Please', 'wp-paddle-pro'),
                esc_url(wp_login_url(get_permalink())),
                esc_html__('login', 'wp-paddle-pro'),
                esc_html__('to view your downloads.', 'wp-paddle-pro')
            );
        }
    
        // Initialize DB Manager
        require_once WPPP_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        $db = new WPPP_Plugin_DB_Manager($this->plugin_slug);
        
        $user_id   = get_current_user_id();
        $downloads = $db->get_user_purchased_products($user_id);
    
        ob_start();
        ?>
        <div class="wppp-customer-dashboard">
            <h3 class="wppp-dashboard-title"><?php esc_html_e('My Digital Products', 'wp-paddle-pro'); ?></h3>
            
            <?php if (!empty($downloads)): ?>
                <table class="wppp-downloads-table" style="width:100%; border-collapse: collapse; margin-top: 20px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px; text-align: left;"><?php esc_html_e('Product', 'wp-paddle-pro'); ?></th>
                            <th style="padding: 12px; text-align: left;"><?php esc_html_e('Purchase Date', 'wp-paddle-pro'); ?></th>
                            <th style="padding: 12px; text-align: right;"><?php esc_html_e('Action', 'wp-paddle-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($downloads as $item): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px; font-weight: 500;"><?php echo esc_html($item['product_name']); ?></td>
                                <td style="padding: 12px; color: #64748b;">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($item['created_at'])); ?>
                                </td>
                                <td style="padding: 12px; text-align: right;">
                                    <a href="<?php echo esc_url($item['file_path']); ?>" 
                                       class="wppp-download-button" 
                                       style="background: #2563eb; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px;"
                                       download>
                                        <?php esc_html_e('Download Now', 'wp-paddle-pro'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="wppp-no-downloads" style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 8px; margin-top: 20px;">
                    <p style="color: #64748b;"><?php esc_html_e('You haven\'t purchased any digital products yet.', 'wp-paddle-pro'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}