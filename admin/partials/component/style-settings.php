<form method="post" action="options.php">
<?php settings_fields($this->plugin_slug . '_style_settings'); ?>
<div class="postbox professional-box">
    <div class="postbox-header"><h2 class="hndle">Visual Appearance</h2></div>
    <div class="inside">
        <table class="form-table">
        <tr>
            <th scope="row"><label>Profile Image (Avatar)</label></th>
            <td>
                <?php $logo_id = get_option('ai_agent_logo_id'); $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : ''; ?>
                <div id="logo-preview-wrap" style="margin-bottom: 12px;">
                <img id="ai_agent_logo_preview" 
                    src="<?php echo $logo_url ? esc_url($logo_url) : AICA_PLUGIN_URL . 'assets/images/default-avater.svg'; ?>" 
                    style="max-width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 2px solid #f3f4f6; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                </div>
                <input type="hidden" name="ai_agent_logo_id" id="ai_agent_logo_id" value="<?php echo esc_attr($logo_id); ?>">
                
                <div style="margin-bottom: 10px;">
                    <button type="button" class="button button-secondary" id="ai_agent_upload_btn">Change Avatar</button>
                    <button type="button" class="button button-link-delete" id="ai_agent_remove_btn" style="<?php echo ($logo_id) ? '' : 'display:none;'; ?> margin-left: 10px;">Remove Image</button></div>
                
                <p class="description">
                    JPG, PNG, or SVG, up to 1MB. Ideal size: <strong>535 x 535 pixels</strong>.<br>
                    Used as the AI icon in chat responses and optionally on the chat trigger.
                </p>
            </td>
        </tr>
            <tr>
                <th scope="row"><label>Chat Launcher Icon</label></th>
                <td>
                    <select name="ai_agent_bubble_icon" class="regular-text professional-input admin-preview-trigger">
                        <option value="default" <?php selected(get_option('ai_agent_bubble_icon', 'default'), 'default'); ?>>Default Message Icon</option>
                        <option value="logo" <?php selected(get_option('ai_agent_bubble_icon'), 'logo'); ?>>Use Profile Image</option>
                    </select>
                    <p class="description">Select what displays on the floating button at the bottom of the screen.</p>
                </td>
            </tr>
            <tr>
            <th scope="row"><label>Primary Brand Color</label></th>
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
                            title="Reset to Black" 
                            style="display: flex; align-items: center; justify-content: center; padding: 0 8px; height: 32px;">
                        <span class="dashicons dashicons-undo" style="font-size: 18px; width: 18px; height: 18px; margin-top: 0;"></span>
                    </button>
                </div>
                <p class="description">This color is used for the chat bubble launcher and user message backgrounds.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label>Widget Theme Mode</label></th>
            <td>
                <div class="aica-theme-toggle-group" style="display: flex; gap: 20px;">
                    <?php 
                    $current_theme = get_option('ai_agent_theme_mode', 'light'); 
                    $modes = [
                        'light' => 'Light Mode',
                        'dark'  => 'Dark Mode'
                    ];
                    foreach ($modes as $value => $label): 
                        $is_active = ($current_theme === $value);
                    ?>
                        <label class="aica-theme-card <?php echo $is_active ? 'is-active' : ''; ?>" style="flex: 1; max-width: 180px; cursor: pointer;">
                            <input type="radio" name="ai_agent_theme_mode" value="<?php echo $value; ?>" <?php checked($current_theme, $value); ?> class="admin-preview-trigger" style="display: none;">
                            
                            <div class="theme-card-inner">
                                <div class="mini-preview-canvas theme-<?php echo $value; ?>">
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
                                    <div class="theme-label-text"><?php echo $label; ?></div>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="description">Select the interface style. The preview on the right will update instantly.</p>
            </td>
        </tr>
        </table>
    </div>
</div>
<?php submit_button('Save Visual Style', 'primary', 'submit', true, array('id' => 'aica-main-submit')); ?>
</form>