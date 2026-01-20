<?php
defined( 'ABSPATH' ) || exit;
?>

<form method="post" action="options.php">
<?php settings_fields($this->plugin_slug . '_notif_settings'); ?>
<div class="postbox professional-box">
    <div class="postbox-header">
        <h2 class="hndle"><?php esc_html_e( 'Email Alerts', 'aica-plugin' ); ?></h2>
    </div>
    <div class="inside">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ai_agent_admin_email"><?php esc_html_e( 'Admin Alert Email', 'aica-plugin' ); ?></label>
                </th>
                <td>
                    <input type="email" id="ai_agent_admin_email" name="ai_agent_admin_email" value="<?php echo esc_attr(get_option('ai_agent_admin_email', get_option('admin_email'))); ?>" class="regular-text professional-input">
                    <p class="description">
                        <?php esc_html_e( 'The email address where the system will send conversation transcripts or lead notifications.', 'aica-plugin' ); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>
</div>
<?php submit_button( __( 'Save Notification Settings', 'aica-plugin' ), 'primary', 'submit', true, array('id' => 'aica-main-submit') ); ?>
</form>