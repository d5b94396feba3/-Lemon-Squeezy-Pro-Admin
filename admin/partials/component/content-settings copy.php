<?php
defined( 'ABSPATH' ) || exit;
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
                        <input type="text" name="ai_agent_display_name" value="<?php echo esc_attr(get_option('ai_agent_display_name', 'AI Assistant')); ?>" class="large-text professional-input admin-preview-trigger">
                        <p class="description">This name appears at the top of the chat window and above AI responses.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Initial Welcome Message</label></th>
                    <td>
                        <textarea name="ai_agent_welcome_msg" rows="3" class="large-text professional-input admin-preview-trigger"><?php echo esc_textarea(get_option('ai_agent_welcome_msg', 'Hi! What can I help you with?')); ?></textarea>
                        <p class="description">The first message the user sees. Use new lines to create multiple chat bubbles.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Message Placeholder</label></th>
                    <td>
                        <input type="text" name="ai_agent_placeholder" value="<?php echo esc_attr(get_option('ai_agent_placeholder', 'Type a message...')); ?>" class="large-text professional-input admin-preview-trigger">
                        <p class="description">The hint text shown in the input box before the user starts typing.</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php submit_button('Save Content Settings', 'primary', 'submit', true, array('id' => 'aica-main-submit')); ?>
</form>