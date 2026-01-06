<?php
defined( 'ABSPATH' ) || exit;
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'content';
$page_url = admin_url('admin.php?page=' . $this->plugin_slug . '-settings');
?>

<div class="wrap ai-agent-admin-wrap">
    <h1 class="wp-heading-inline" style="font-weight: 700; font-size: 24px; margin-bottom: 20px;">Chat Widget Configuration</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="<?php echo $page_url; ?>&tab=content" class="nav-tab <?php echo $active_tab === 'content' ? 'nav-tab-active' : ''; ?>">Content</a>
        <a href="<?php echo $page_url; ?>&tab=style" class="nav-tab <?php echo $active_tab === 'style' ? 'nav-tab-active' : ''; ?>">Style</a>
        <a href="<?php echo $page_url; ?>&tab=prompt" class="nav-tab <?php echo $active_tab === 'prompt' ? 'nav-tab-active' : ''; ?>">System Prompt</a>
        <a href="<?php echo $page_url; ?>&tab=grok" class="nav-tab <?php echo $active_tab === 'grok' ? 'nav-tab-active' : ''; ?>">Grok Engine</a>
        <a href="<?php echo $page_url; ?>&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">Notifications</a>
    </nav>

    <div class="settings-container" style="margin-top: 20px;">
        <?php if ($active_tab === 'content'): ?>
            <form method="post" action="options.php">
                <?php settings_fields($this->plugin_slug . '_content_settings'); ?>
                <div class="postbox professional-box">
                    <div class="postbox-header"><h2 class="hndle">General Content</h2></div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label>Display Name</label></th>
                                <td>
                                    <input type="text" name="ai_agent_display_name" value="<?php echo esc_attr(get_option('ai_agent_display_name', 'AI Assistant')); ?>" class="large-text professional-input">
                                    <p class="description">This name appears at the top of the chat window and above AI responses.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Initial Welcome Message</label></th>
                                <td>
                                    <textarea name="ai_agent_welcome_msg" rows="3" class="large-text professional-input"><?php echo esc_textarea(get_option('ai_agent_welcome_msg', 'Hi! What can I help you with?')); ?></textarea>
                                    <p class="description">The first message the user sees. Use new lines to create multiple chat bubbles.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Message Placeholder</label></th>
                                <td>
                                    <input type="text" name="ai_agent_placeholder" value="<?php echo esc_attr(get_option('ai_agent_placeholder', 'Type a message...')); ?>" class="large-text professional-input">
                                    <p class="description">The hint text shown in the input box before the user starts typing.</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php submit_button('Save Content Settings'); ?>
            </form>

        <?php elseif ($active_tab === 'style'): ?>
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
                                    <button type="button" class="button button-link-delete" id="ai_agent_remove_btn" style="<?php echo $logo_url ? '' : 'display:none;'; ?> margin-left: 10px;">Remove Image</button>
                                </div>
                                
                                <p class="description">
                                    JPG, PNG, and SVG up to 1MB and appropriate size for the profile image.<br>
                                    Used as the AI icon in chat responses and optionally on the chat trigger.
                                </p>
                            </td>
                        </tr>
                            <tr>
                                <th scope="row"><label>Chat Launcher Icon</label></th>
                                <td>
                                    <select name="ai_agent_bubble_icon" class="regular-text professional-input">
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
                                        style="height:45px; width:80px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
                                    
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
                        </table>
                    </div>
                </div>
                <?php submit_button('Save Visual Style'); ?>
            </form>

            <?php elseif ($active_tab === 'prompt'): ?>
            <form method="post" action="options.php">
                <?php settings_fields($this->plugin_slug . '_prompt_settings'); ?>
                <div class="postbox professional-box">
                    <div class="postbox-header"><h2 class="hndle">AI Persona & Knowledge Base</h2></div>
                    <div class="inside">
                        <div style="margin-bottom: 20px; padding: 0 10px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 8px;">Core System Instructions</label>
                            <textarea name="ai_agent_system_prompt" rows="12" class="large-text professional-input" style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; line-height: 1.6; background: #fafafa;"><?php echo esc_textarea(get_option('ai_agent_system_prompt', 'You are a professional IT expert...')); ?></textarea>
                        </div>

                        <div style="padding: 20px 10px; border-top: 1px solid #f3f4f6; background: #f9fafb;">
                            <label style="font-weight: 600; display: block; margin-bottom: 8px;">Knowledge Base File (PDF, DOCX, TXT, or Image)</label>
                            <?php 
                            $file_id = get_option('ai_agent_knowledge_file_id');
                            $file_url = $file_id ? wp_get_attachment_url($file_id) : '';
                            $file_name = $file_id ? basename($file_url) : '';
                            ?>
                            <div class="file-upload-wrapper" style="display: flex; align-items: center; gap: 15px;">
                                <input type="hidden" name="ai_agent_knowledge_file_id" id="ai_agent_knowledge_file_id" value="<?php echo esc_attr($file_id); ?>">
                                <button type="button" class="button button-secondary" id="ai_agent_file_upload_btn">
                                    <span class="dashicons dashicons-upload" style="margin-top: 4px; margin-right: 5px;"></span> 
                                    <?php echo $file_id ? 'Replace File' : 'Upload Knowledge File'; ?>
                                </button>
                                <span id="file-name-display" style="font-size: 13px; color: #1e3a8a; font-weight: 500;"><?php echo esc_html($file_name); ?></span>
                                <button type="button" class="button-link-delete" id="ai_agent_file_remove_btn" style="<?php echo $file_id ? '' : 'display:none;'; ?> color: #dc3232;">Remove</button>
                            </div>
                            <p class="description" style="margin-top: 10px;">Upload a document (PDF/DOCX/TXT) or image. The system will extract text from documents to provide context to Grok.</p>
                        </div>
                    </div>
                </div>
                <?php submit_button('Save AI Persona & Knowledge'); ?>
            </form>

            <?php elseif ($active_tab === 'grok'): ?>
            <form method="post" action="options.php">
                <?php settings_fields($this->plugin_slug . '_grok_settings'); ?>
                <div class="postbox professional-box">
                    <div class="postbox-header"><h2 class="hndle">Engine Configuration (xAI)</h2></div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label>xAI API Key</label></th>
                                <td>
                                    <input type="password" name="grok_api_key" value="<?php echo esc_attr(get_option('grok_api_key')); ?>" class="regular-text professional-input" placeholder="xai-...">
                                    <p class="description">Obtain your API key from the x.ai console.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Model Version</label></th>
                                <td>
                                    <select name="grok_model" class="regular-text professional-input">
                                        <option value="grok-2-vision-1212" <?php selected(get_option('grok_model'), 'grok-2-vision-1212'); ?>>Grok-2 Vision (Intelligence + Image Support)</option>
                                        
                                        <option value="grok-2-1212" <?php selected(get_option('grok_model'), 'grok-2-1212'); ?>>Grok-2 (High Intelligence)</option>
                                        <option value="grok-4-fast-reasoning" <?php selected(get_option('grok_model'), 'grok-4-fast-reasoning'); ?>>Grok-4 Fast Reasoning (Speed Optimized)</option>
                                    </select>
                                    <p class="description">Select <strong>Grok-2 Vision</strong> if you want the AI to analyze uploaded images in the System Prompt.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label>Creativity (Temperature)</label></th>
                                <td>
                                    <input type="number" name="grok_temperature" value="<?php echo esc_attr(get_option('grok_temperature', '0.7')); ?>" min="0" max="2" step="0.1" class="small-text professional-input">
                                    <p class="description">Lower values (0.2) make the AI consistent and factual. Higher values (0.8) make it more creative.</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php submit_button('Save API Configuration'); ?>
            </form>

        <?php elseif ($active_tab === 'notifications'): ?>
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
                <?php submit_button('Save Notification Settings'); ?>
            </form>
        <?php endif; ?>
    </div>
</div>