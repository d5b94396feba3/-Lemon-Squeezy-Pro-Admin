<?php
defined( 'ABSPATH' ) || exit;

// Fetch settings variables (Same as chat template)
$logo_id       = get_option('ai_agent_logo_id');
$logo_url      = $logo_id ? wp_get_attachment_url($logo_id) : AICA_PLUGIN_URL . 'assets/images/default-avater.svg';
$display_name  = get_option('ai_agent_display_name', 'AI Assistant');
$welcome_msg   = get_option('ai_agent_welcome_msg', "Hi there! I'm your friendly AI Assistant.\nHow can I help you today?");
$placeholder   = get_option('ai_agent_placeholder', 'Type a message...');
$primary_color = get_option('ai_agent_primary_color', '#000000');
$theme_mode    = get_option('ai_agent_theme_mode', 'light');
?>

<form method="post" action="options.php">
    <?php settings_fields($this->plugin_slug . '_content_settings'); ?>
    
    <div class="postbox professional-box">
        <div class="postbox-header"><h2 class="hndle">General Content</h2></div>
        <div class="inside">
            <table class="form-table">
                <tr>
                    <th scope="row"><label>Display Name</label></th>
                    <td>
                        <input type="text" name="ai_agent_display_name" value="<?php echo esc_attr($display_name); ?>" class="large-text professional-input admin-preview-trigger">
                        <p class="description">This name appears at the top of the chat window and above AI responses.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Initial Welcome Message</label></th>
                    <td>
                        <textarea name="ai_agent_welcome_msg" rows="3" class="large-text professional-input admin-preview-trigger"><?php echo esc_textarea($welcome_msg); ?></textarea>
                        <p class="description">The first message the user sees. Use new lines to create multiple chat bubbles.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Message Placeholder</label></th>
                    <td>
                        <input type="text" name="ai_agent_placeholder" value="<?php echo esc_attr($placeholder); ?>" class="large-text professional-input admin-preview-trigger">
                        <p class="description">The hint text shown in the input box before the user starts typing.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php submit_button('Save Content Settings', 'primary', 'submit', true, array('id' => 'aica-main-submit')); ?>
</form>