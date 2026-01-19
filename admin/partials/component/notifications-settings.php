<form method="post" action="options.php">
<?php settings_fields($this->plugin_slug . '_notif_settings'); ?>
<div class="postbox professional-box">
    <div class="postbox-header"><h2 class="hndle">Email Alerts</h2></div>
    <div class="inside">
        <table class="form-table">
            <tr>
                <th scope="row"><label>Admin Alert Email</label></th>
                <td>
                    <input type="email" name="ai_agent_admin_email" value="<?php echo esc_attr(get_option('ai_agent_admin_email', get_option('admin_email'))); ?>" class="regular-text professional-input">
                    <p class="description">The email address where the system will send conversation transcripts or lead notifications.</p>
                </td>
            </tr>
        </table>
    </div>
</div>
<?php submit_button('Save Notification Settings', 'primary', 'submit', true, array('id' => 'aica-main-submit')); ?>
</form>