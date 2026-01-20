<?php
defined( 'ABSPATH' ) || exit;

// Fetch settings variables (Same as chat template)
$logo_id       = get_option('ai_agent_logo_id');
$logo_url      = $logo_id ? wp_get_attachment_url($logo_id) : AICA_PLUGIN_URL . 'assets/images/default-avater.svg';
$display_name  = get_option('ai_agent_display_name', __( 'AI Assistant', 'aica-plugin' ) );
$welcome_msg   = get_option('ai_agent_welcome_msg', __( "Hi there! I'm your friendly AI Assistant.\nHow can I help you today?", 'aica-plugin' ) );
$placeholder   = get_option('ai_agent_placeholder', __( 'Type a message...', 'aica-plugin' ) );
$primary_color = get_option('ai_agent_primary_color', '#000000');
$theme_mode    = get_option('ai_agent_theme_mode', 'light');
?>

<form method="post" action="options.php">
    <?php settings_fields($this->plugin_slug . '_content_settings'); ?>
    
    <div class="postbox professional-box">
        <div class="postbox-header">
            <h2 class="hndle"><?php esc_html_e( 'General Content', 'aica-plugin' ); ?></h2>
        </div>
        <div class="inside">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ai_agent_display_name"><?php esc_html_e( 'Display Name', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="ai_agent_display_name" name="ai_agent_display_name" value="<?php echo esc_attr($display_name); ?>" class="large-text professional-input admin-preview-trigger">
                        <p class="description"><?php esc_html_e( 'This name appears at the top of the chat window and above AI responses.', 'aica-plugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ai_agent_welcome_msg"><?php esc_html_e( 'Initial Welcome Message', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <textarea id="ai_agent_welcome_msg" name="ai_agent_welcome_msg" rows="3" class="large-text professional-input admin-preview-trigger"><?php echo esc_textarea($welcome_msg); ?></textarea>
                        <p class="description"><?php esc_html_e( 'The first message the user sees. Use new lines to create multiple chat bubbles.', 'aica-plugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ai_agent_placeholder"><?php esc_html_e( 'Message Placeholder', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="ai_agent_placeholder" name="ai_agent_placeholder" value="<?php echo esc_attr($placeholder); ?>" class="large-text professional-input admin-preview-trigger">
                        <p class="description"><?php esc_html_e( 'The hint text shown in the input box before the user starts typing.', 'aica-plugin' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php submit_button( __( 'Save Content Settings', 'aica-plugin' ), 'primary', 'submit', true, array('id' => 'aica-main-submit') ); ?>
</form>