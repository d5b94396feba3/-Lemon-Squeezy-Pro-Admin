<?php
defined( 'ABSPATH' ) || exit;

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// WP STANDARD: Build the page URL correctly
$page_url = add_query_arg(
    array( 'page' => $this->plugin_slug . '-settings' ),
    admin_url( 'admin.php' )
);

$env = get_option('paddle_environment', 'sandbox');

/**
 * Dynamic Paddle URLs based on environment
 */
$paddle_dashboard_url = ( 'live' === $env ) ? 'https://vendors.paddle.com/' : 'https://sandbox-vendors.paddle.com/';
$paddle_keys_url      = ( 'live' === $env ) ? 'https://vendors.paddle.com/authentication-v2' : 'https://sandbox-vendors.paddle.com/authentication-v2';
$paddle_notif_url     = ( 'live' === $env ) ? 'https://vendors.paddle.com/notifications' : 'https://sandbox-vendors.paddle.com/notifications';
?>

<div class="wrap wppp-admin-wrap">
    <h1 style="font-weight: 700; font-size: 24px; margin-bottom: 20px;">
        <?php _e( 'Paddle Integration Settings', 'wp-paddle-pro' ); ?>
    </h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'general', $page_url ) ); ?>" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'API Credentials', 'wp-paddle-pro' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'checkout', $page_url ) ); ?>" class="nav-tab <?php echo $active_tab === 'checkout' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'Checkout Configuration', 'wp-paddle-pro' ); ?>
        </a>
    </nav>

    <div style="display: flex; gap: 30px; margin-top: 20px;">
        <div class="settings-container" style="flex: 1; background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <form method="post" action="options.php" id="wppp-settings-form">
                <?php
                if ( 'general' === $active_tab ) :
                    settings_fields( $this->plugin_slug . '_general_settings' );
                    ?>
                    <h2><?php _e( 'Paddle API Credentials', 'wp-paddle-pro' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Environment', 'wp-paddle-pro' ); ?></th>
                            <td>
                                <select name="paddle_environment" style="min-width: 200px;">
                                    <option value="sandbox" <?php selected( $env, 'sandbox' ); ?>><?php _e( 'Sandbox (Testing)', 'wp-paddle-pro' ); ?></option>
                                    <option value="live" <?php selected( $env, 'live' ); ?>><?php _e( 'Live (Production)', 'wp-paddle-pro' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Client-Side Token', 'wp-paddle-pro' ); ?></th>
                            <td>
                                <input type="text" name="paddle_client_side_token" value="<?php echo esc_attr( get_option( 'paddle_client_side_token' ) ); ?>" class="regular-text" />
                                <p class="description"><?php _e( 'Used to open the checkout overlay.', 'wp-paddle-pro' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'API Key (Server-Side)', 'wp-paddle-pro' ); ?></th>
                            <td>
                                <input type="password" name="paddle_api_key" value="<?php echo esc_attr( get_option( 'paddle_api_key' ) ); ?>" class="regular-text" />
                                <p class="description"><?php _e( 'Required for server-side API requests.', 'wp-paddle-pro' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e( 'Webhook Secret', 'wp-paddle-pro' ); ?></th>
                            <td>
                                <input type="password" name="paddle_webhook_secret" value="<?php echo esc_attr( get_option( 'paddle_webhook_secret' ) ); ?>" class="regular-text" />
                                <p class="description"><?php _e( 'Used to verify incoming payment notifications.', 'wp-paddle-pro' ); ?></p>
                            </td>
                        </tr>
                    </table>

                <?php elseif ( 'checkout' === $active_tab ) : 
                    settings_fields( $this->plugin_slug . '_checkout_settings' );
                    ?>
                    <h2><?php _e( 'Checkout Configuration', 'wp-paddle-pro' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Thank You Page URL', 'wp-paddle-pro' ); ?></th>
                            <td>
                                <input type="url" name="paddle_thank_you_page_url" value="<?php echo esc_url( get_option( 'paddle_thank_you_page_url' ) ); ?>" class="regular-text" placeholder="https://yourdomain.com/thank-you/" />
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>

                <div style="margin-top: 20px; display: flex; gap: 10px; align-items: center;">
                    <?php submit_button( '', 'primary', 'submit', false ); ?>
                    <?php if ( 'general' === $active_tab ) : ?>
                        <button type="button" id="wppp-test-connection" class="button button-secondary">
                            <span class="dashicons dashicons-rest-api" style="vertical-align: middle;"></span>
                            <?php _e( 'Test Connection', 'wp-paddle-pro' ); ?>
                        </button>
                        <span id="connection-status-spinner" class="spinner"></span>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div style="width: 350px;">
            <div style="background: #ffffff; border: 1px solid #7c3aed; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #5b21b6; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <?php _e( 'Step-by-Step Setup', 'wp-paddle-pro' ); ?>
                </h3>
                <ol style="font-size: 13px; color: #4b5563; padding-left: 18px; line-height: 1.6;">
                    <li>
                        <a href="<?php echo esc_url( $paddle_keys_url ); ?>" target="_blank" style="font-weight: 600;">
                            <?php _e( 'Click here to get API Key & Client Token', 'wp-paddle-pro' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url( $paddle_notif_url ); ?>" target="_blank" style="font-weight: 600;">
                            <?php _e( 'Click here to get Webhook Secret', 'wp-paddle-pro' ); ?>
                        </a>
                        <p style="margin-top: 5px; font-size: 11px;">
                            <?php _e( 'Scroll to the bottom of your Notification settings to find the Secret Key.', 'wp-paddle-pro' ); ?>
                        </p>
                    </li>
                </ol>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-cloud"></span>
                    <?php _e( 'Webhook Endpoint', 'wp-paddle-pro' ); ?>
                </h3>
                <p style="font-size: 12px; color: #64748b;"><?php _e( 'Paste this into Paddle > Developer Tools > Notifications:', 'wp-paddle-pro' ); ?></p>
                <div style="display: flex; gap: 5px;">
                    <input type="text" id="wppp-webhook-url" value="<?php echo get_rest_url( null, 'wp-paddle-pro/v1/webhook' ); ?>" readonly style="flex: 1; font-size: 11px; background: #fff;">
                    <button type="button" class="button" onclick="document.getElementById('wppp-webhook-url').select(); document.execCommand('copy'); alert('URL Copied!');">
                        <span class="dashicons dashicons-admin-page" style="font-size: 16px; margin-top: 4px;"></span>
                    </button>
                </div>
            </div>
            
            <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0;"><?php _e( 'Quick Links', 'wp-paddle-pro' ); ?></h3>
                <a href="<?php echo esc_url( $paddle_dashboard_url ); ?>" target="_blank" class="button" style="width: 100%; text-align: center;">
                    <?php _e( 'Open Paddle Dashboard', 'wp-paddle-pro' ); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#wppp-test-connection').on('click', function() {
        var $btn = $(this), $spinner = $('#connection-status-spinner');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wppp_test_connection',
                _ajax_nonce: '<?php echo wp_create_nonce("wppp_test_nonce"); ?>'
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                alert( response.success ? response.data.message : 'Error: ' + response.data.message );
            },
            error: function() {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                alert('<?php _e( "Server error during connection test.", "wp-paddle-pro" ); ?>');
            }
        });
    });
});
</script>