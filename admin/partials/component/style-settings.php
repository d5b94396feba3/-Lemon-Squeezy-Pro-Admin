<?php
defined( 'ABSPATH' ) || exit;
?>

<form method="post" action="options.php">
<?php settings_fields($this->plugin_slug . '_style_settings'); ?>
<div class="postbox professional-box">
    <div class="postbox-header">
        <h2 class="hndle"><?php esc_html_e( 'Visual Appearance', 'wppp-plugin' ); ?></h2>
    </div>
    <div class="inside">
        <table class="form-table">
        <tr>
            <th scope="row">
                <label><?php esc_html_e( 'Profile Image (Avatar)', 'wppp-plugin' ); ?></label>
            </th>
            <td>
                <?php 
                $logo_id  = get_option('ai_agent_logo_id'); 
                $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : ''; 
                ?>
                <div id="logo-preview-wrap" style="margin-bottom: 12px;">
                <img id="ai_agent_logo_preview" 
                    src="<?php echo $logo_url ? esc_url($logo_url) : esc_url( LSPA_PLUGIN_URL . 'assets/images/default-avater.svg' ); ?>" 
                    style="max-width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 2px solid #f3f4f6; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                    alt="<?php esc_attr_e( 'AI Avatar Preview', 'wppp-plugin' ); ?>">
                </div>
                <input type="hidden" name="ai_agent_logo_id" id="ai_agent_logo_id" value="<?php echo esc_attr($logo_id); ?>">
                
                <div style="margin-bottom: 10px;">
                    <button type="button" class="button button-secondary" id="ai_agent_upload_btn"><?php esc_html_e( 'Change Avatar', 'wppp-plugin' ); ?></button>
                    <button type="button" class="button button-link-delete" id="ai_agent_remove_btn" style="<?php echo ($logo_id) ? '' : 'display:none;'; ?> margin-left: 10px;"><?php esc_html_e( 'Remove Image', 'wppp-plugin' ); ?></button>
                </div>
                
                <p class="description">
                    <?php 
                    /* translators: 1: format list, 2: strong tag start, 3: strong tag end */
                    printf( 
                        esc_html__( 'JPG, PNG, or SVG, up to 1MB. Ideal size: %2$s535 x 535 pixels%3$s.', 'wppp-plugin' ), 
                        'JPG, PNG, SVG', 
                        '<strong>', 
                        '</strong>' 
                    ); 
                    ?>
                    <br>
                    <?php esc_html_e( 'Used as the AI icon in chat responses and optionally on the chat trigger.', 'wppp-plugin' ); ?>
                </p>
            </td>
        </tr>
            <tr>
                <th scope="row">
                    <label for="ai_agent_bubble_icon"><?php esc_html_e( 'Chat Launcher Icon', 'wppp-plugin' ); ?></label>
                </th>
                <td>
                    <select id="ai_agent_bubble_icon" name="ai_agent_bubble_icon" class="regular-text professional-input admin-preview-trigger">
                        <option value="default" <?php selected(get_option('ai_agent_bubble_icon', 'default'), 'default'); ?>><?php esc_html_e( 'Default Message Icon', 'wppp-plugin' ); ?></option>
                        <option value="logo" <?php selected(get_option('ai_agent_bubble_icon'), 'logo'); ?>><?php esc_html_e( 'Use Profile Image', 'wppp-plugin' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Select what displays on the floating button at the bottom of the screen.', 'wppp-plugin' ); ?></p>
                </td>
            </tr>
            <tr>
            <th scope="row">
                <label for="ai_agent_primary_color_input"><?php esc_html_e( 'Primary Brand Color', 'wppp-plugin' ); ?></label>
            </th>
            <td>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <input type="color" 
                        id="ai_agent_primary_color_input" 
                        name="ai_agent_primary_color" 
                        value="<?php echo esc_attr(get_option('ai_agent_primary_color', '#000000')); ?>" 
                        style="height:45px; width:80px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;" class="admin-preview-trigger">
                    
                    <code id="ai_agent_primary_color_hex" style="font-size: 14px; padding: 5px 10px;"><?php echo esc_attr(get_option('ai_agent_primary_color', '#000000')); ?></code>
                    
                    <button type="button" 
                            id="reset_primary_color" 
                            class="button button-secondary" 
                            title="<?php esc_attr_e( 'Reset to Black', 'wppp-plugin' ); ?>" 
                            style="display: flex; align-items: center; justify-content: center; padding: 0 8px; height: 32px;">
                        <span class="dashicons dashicons-undo" style="font-size: 18px; width: 18px; height: 18px; margin-top: 0;"></span>
                    </button>
                </div>
                <p class="description"><?php esc_html_e( 'This color is used for the chat bubble launcher and user message backgrounds.', 'wppp-plugin' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e( 'Widget Theme Mode', 'wppp-plugin' ); ?></label>
            </th>
            <td>
                <div class="wppp-theme-toggle-group" style="display: flex; gap: 20px;">
                    <?php 
                    $current_theme = get_option('ai_agent_theme_mode', 'light'); 
                    $modes = [
                        'light' => __( 'Light Mode', 'wppp-plugin' ),
                        'dark'  => __( 'Dark Mode', 'wppp-plugin' )
                    ];
                    foreach ($modes as $value => $label): 
                        $is_active = ($current_theme === $value);
                    ?>
                        <label class="wppp-theme-card <?php echo $is_active ? 'is-active' : ''; ?>" style="flex: 1; max-width: 180px; cursor: pointer;">
                            <input type="radio" name="ai_agent_theme_mode" value="<?php echo esc_attr($value); ?>" <?php checked($current_theme, $value); ?> class="admin-preview-trigger" style="display: none;">
                            
                            <div class="theme-card-inner">
                                <div class="mini-preview-canvas theme-<?php echo esc_attr($value); ?>">
                                    <div class="mini-widget">
                                        <div class="mini-header"></div>
                                        <div class="mini-content">
                                            <div class="mini-bubble-ai"></div>
                                            <div class="mini-bubble-user"></div>
                                        </div>
                                        <div class="mini-footer"></div>
                                    </div>
                                </div>
                                
                                <div class="theme-label-container">
                                    <div class="theme-label-text"><?php echo esc_html($label); ?></div>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php esc_html_e( 'Select the interface style. The preview on the right will update instantly.', 'wppp-plugin' ); ?></p>
            </td>
        </tr>
        </table>
    </div>
</div>
<?php submit_button( __( 'Save Visual Style', 'wppp-plugin' ), 'primary', 'submit', true, array('id' => 'wppp-main-submit') ); ?>
</form>