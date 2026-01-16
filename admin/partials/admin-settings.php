<?php
defined( 'ABSPATH' ) || exit;
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'content';
$page_url = admin_url('admin.php?page=' . $this->plugin_slug . '-settings');
// Get the last sync time
$last_sync = get_option(AICA_PLUGIN_SLUG . '_last_cron_sync');
$next_sync = wp_next_scheduled(AICA_PLUGIN_SLUG . '_daily_sync_event');

?>

<div class="wrap ai-agent-admin-wrap">
    <div id="aica-unsaved-bar" style="display:none; position: fixed; top: 32px; left: 160px; right: 20px; z-index: 9999; background: #1e293b; color: #fff; padding: 12px 24px; border-radius: 0 0 12px 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #334155; border-top: none; align-items: center; justify-content: space-between; animation: slideDown 0.3s ease-out;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="dashicons dashicons-warning" style="color: #fbbf24; font-size: 20px; width: 20px; height: 20px;"></span>
            <span style="font-weight: 600; font-size: 14px;">You have unsaved changes</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" onclick="location.reload();" class="button" style="background: transparent; border: 1px solid #475569; color: #cbd5e1; height: 32px;">Discard</button>
            <button type="button" id="aica-sticky-save-trigger" class="button button-primary" style="background: #3b82f6; border: none; height: 32px; padding: 0 20px; font-weight: 600;">Save Changes</button>
        </div>
    </div>
    <h1 class="wp-heading-inline" style="font-weight: 700; font-size: 24px; margin-bottom: 20px;">Chat Widget Configuration</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="<?php echo $page_url; ?>&tab=content" class="nav-tab <?php echo $active_tab === 'content' ? 'nav-tab-active' : ''; ?>">Content</a>
        <a href="<?php echo $page_url; ?>&tab=style" class="nav-tab <?php echo $active_tab === 'style' ? 'nav-tab-active' : ''; ?>">Style</a>
        <a href="<?php echo $page_url; ?>&tab=prompt" class="nav-tab <?php echo $active_tab === 'prompt' ? 'nav-tab-active' : ''; ?>">Knowledge Base</a>
        <a href="<?php echo $page_url; ?>&tab=grok" class="nav-tab <?php echo $active_tab === 'grok' ? 'nav-tab-active' : ''; ?>">LLM Model</a>
        <a href="<?php echo $page_url; ?>&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">Notifications</a>
    </nav>

    <div style="display: flex; gap: 30px; align-items: flex-start; margin-top: 20px;">
        
        <div class="settings-container" style="flex: 1; min-width: 0; margin-top: 0;">
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
                                        <button type="button" class="button button-link-delete" id="ai_agent_remove_btn" style="<?php echo ($logo_id) ? '' : 'display:none;'; ?> margin-left: 10px;">Remove Image</button></div>
                                    
                                    <p class="description">
                                        JPG, PNG, and SVG up to 1MB and appropriate size for the profile image.<br>
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
                                                        <!-- <span class="dashicons <?php echo $value === 'light' ? 'dashicons-lightbulb' : 'dashicons-moon'; ?>"></span> -->
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
                    <?php submit_button('Save Visual Style'); ?>
                </form>

                <?php elseif ($active_tab === 'prompt'): ?>
                <form method="post" action="options.php">
                    <?php settings_fields($this->plugin_slug . '_prompt_settings'); ?>

                    <div class="postbox professional-box knowledge-sync-wrapper">
                        <div class="postbox-header" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;" id="toggle-sync-table">
                            <h2 class="hndle">Website Knowledge Sync</h2>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span id="sync-summary-badge" class="status-badge" style="display:none; background:#eff6ff; color:#1e40af;">Discovering...</span>
                                <span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
                            </div>
                        </div>
                        <div class="inside" id="sync-collapsible-content" style="padding: 20px !important;">
                            
                            <div id="discovery-loader" style="text-align:center; padding: 40px;">
                                <div class="aica-loader-circle"></div>
                                <p style="margin-top: 15px; color: #64748b; font-weight: 500;">Building Page Map...</p>
                            </div>

                            <div id="sync-progress-bar" style="display:none; margin-bottom: 25px; background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);">
                                <div style="display:flex; justify-content: space-between; margin-bottom:12px;">
                                    <span id="sync-msg" style="font-weight:600; color: #1e293b; font-size: 14px;">Preparing...</span>
                                    <span id="sync-percent" style="color: #3b82f6; font-weight: 700;">0%</span>
                                </div>
                                <div class="admin-progress-bar">
                                    <div id="sync-fill" class="admin-progress-fill" style="width: 0%;"></div>
                                </div>
                            </div>

                            <div id="sync-success-msg" style="display:none; text-align:center; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                                    <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span>
                                    <span style="color: #166534; font-weight: 600;">Scrape Successful! Knowledge base is now synchronized.</span>
                                </div>
                            </div>

                            <div id="sync-data-container" style="display:none;">
                                <div class="sync-table-scroll" style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th style="width:60%; padding-left: 15px;">Page Source</th>
                                                <th>Status</th>
                                                <th>Last Sync</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sync-table-body"></tbody>
                                    </table>
                                </div>
                                
                                <div style="margin-top:20px; display:flex; gap: 12px;">
                                    <button type="button" id="run-bulk-sync" class="button button-hero button-primary"></button>
                                    <button type="button" id="start-discovery-refresh" class="button button-secondary">Refresh Map</button>
                                </div>
                            </div>
                        </div>
                        <div class="sync-status-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="status-indicator" style="position: relative; width: 45px; height: 45px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-update" style="color: #16a34a; font-size: 24px; width: 24px; height: 24px;"></span>
                                <span style="position: absolute; top: 2px; right: 2px; width: 10px; height: 10px; background: #22c55e; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);"></span>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b;">Automated Sync Engine</h4>
                                <p style="margin: 4px 0 0; font-size: 12px; color: #64748b; font-weight: 500;">
                                    <?php echo $last_sync ? 'Last backup completed ' . human_time_diff($last_sync, current_time('timestamp')) . ' ago' : 'No background sync recorded yet'; ?>
                                </p>
                            </div>
                        </div>

                        <div style="text-align: right; border-left: 1px solid #f1f5f9; padding-left: 25px;">
                            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; font-weight: 700; margin-bottom: 4px;">Next Scheduled Run</div>
                            <div style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; color: #334155; font-weight: 600;">
                                <?php echo $next_sync ? date_i18n('M j, Y @ H:i', $next_sync) : 'Paused'; ?>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div class="postbox professional-box">
                        <div class="postbox-header"><h2 class="hndle">AI Persona & Knowledge Base</h2></div>
                        <div class="inside">
                            <div style="margin-bottom: 20px; padding: 0 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <label style="font-weight: 700; font-size: 14px; color: #1e293b;">Core System Instructions</label>
                                    <button type="button" id="btn-generate-ai-prompt" class="button button-secondary" style="border-radius: 6px; font-weight: 600; display: flex; align-items: center; gap: 6px; background: #f8fafc; border-color: #cbd5e1;">
                                        <span class="dashicons dashicons-superhero" style="font-size: 17px; margin-top: 3px; color: #6366f1;"></span>
                                        Auto-Generate from Website Content
                                    </button>
                                </div>
                                
                                <div id="prompt-gen-loader" style="display:none; background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 30px; text-align: center; margin-bottom: 15px;">
                                    <div class="aica-loader-circle" style="border-top-color: #6366f1;"></div>
                                    <p style="margin-top: 15px; font-weight: 600; color: #475569;">AI is analyzing your website content and drafting your persona...</p>
                                </div>

                                <textarea id="ai_agent_system_prompt_area" name="ai_agent_system_prompt" rows="14" class="large-text professional-input" style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; line-height: 1.6; background: #fafafa; transition: all 0.3s ease;"><?php echo esc_textarea(get_option('ai_agent_system_prompt', 'You are a professional IT expert...')); ?></textarea>
                                <p class="description">This defines the AI's personality and rules. Click the generator above to build this automatically based on your synced pages.</p>
                            </div>
                            <div style="padding: 20px 10px; border-top: 1px solid #f3f4f6; background: #f9fafb;">
                                <label style="font-weight: 600; display: block; margin-bottom: 8px;">Knowledge Base File (PDF, TXT, or Image)</label>
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
                                <p class="description" style="margin-top: 10px;">Upload a document (PDF/TXT) or image that the AI should use as a reference for all conversations. Grok will analyze this file alongside your prompt.</p>
                            </div>
                        </div>
                    </div>
                    <?php submit_button('Save AI Persona & Knowledge'); ?>
                </form>

                <?php elseif ($active_tab === 'grok'): ?>
                <form method="post" action="options.php">
                    <?php settings_fields($this->plugin_slug . '_grok_settings'); ?>
                    <div class="postbox professional-box">
                        <div class="postbox-header">
                            <h2 class="hndle">LLM Model Configuration</h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label>Active LLM Model</label></th>
                                    <td>
                                        <select name="llm_model" id="llm_model_selector" class="regular-text professional-input">
                                            <option value="Grok" <?php selected(get_option('llm_model', 'Grok'), 'Grok'); ?>>xAI Grok</option>
                                            <option value="OpenAI" <?php selected(get_option('llm_model'), 'OpenAI'); ?>>OpenAI (GPT-4o)</option>
                                            <option value="Gemini" <?php selected(get_option('llm_model'), 'Gemini'); ?>>Google Gemini</option>
                                        </select>
                                        <p class="description">Select the core intelligence engine for your AI Agent.</p>
                                    </td>
                                </tr>

                                <tbody id="settings-group-Grok" class="llm-settings-group">
                                    <tr style="background: #f8fafc;"><td colspan="2" style="padding: 10px 20px;"><strong>xAI Grok Settings</strong></td></tr>
                                    <tr>
                                        <th scope="row"><label>Grok API Key</label></th>
                                        <td>
                                            <input type="password" name="grok_api_key" value="<?php echo esc_attr(get_option('grok_api_key')); ?>" class="regular-text professional-input" placeholder="xai-...">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>Grok Model</label></th>
                                        <td>
                                        <select name="grok_model" class="regular-text professional-input">
                                            <option value="grok-4" <?php selected(get_option('grok_model'), 'grok-4'); ?>>Grok-4 (Flagship Reasoning)</option>
                                            <option value="grok-4-fast-reasoning" <?php selected(get_option('grok_model'), 'grok-4-fast-reasoning'); ?>>Grok-4 Fast (Speed Optimized)</option>
                                            <option value="grok-2-vision-1212" <?php selected(get_option('grok_model'), 'grok-2-vision-1212'); ?>>Grok-2 Vision (Legacy Vision)</option>
                                        </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>Grok Temperature</label></th>
                                        <td>
                                            <input type="number" name="grok_temperature" value="<?php echo esc_attr(get_option('grok_temperature', '0.7')); ?>" min="0" max="2" step="0.1" class="small-text professional-input">
                                        </td>
                                    </tr>
                                </tbody>

                                <tbody id="settings-group-OpenAI" class="llm-settings-group" style="display:none;">
                                    <tr style="background: #f8fafc;"><td colspan="2" style="padding: 10px 20px;"><strong>OpenAI Settings</strong></td></tr>
                                    <tr>
                                        <th scope="row"><label>OpenAI API Key</label></th>
                                        <td>
                                            <input type="password" name="openai_api_key" value="<?php echo esc_attr(get_option('openai_api_key')); ?>" class="regular-text professional-input" placeholder="sk-...">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>OpenAI Model</label></th>
                                        <td>
                                        <select name="openai_model" class="regular-text professional-input">
                                            <option value="gpt-5.2" <?php selected(get_option('openai_model'), 'gpt-5.2'); ?>>GPT-5.2 (Latest Stable)</option>
                                            <option value="gpt-5-mini" <?php selected(get_option('openai_model'), 'gpt-5-mini'); ?>>GPT-5 Mini</option>
                                        </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>OpenAI Temperature</label></th>
                                        <td>
                                            <input type="number" name="openai_temperature" value="<?php echo esc_attr(get_option('openai_temperature', '0.7')); ?>" min="0" max="2" step="0.1" class="small-text professional-input">
                                        </td>
                                    </tr>
                                </tbody>

                                <tbody id="settings-group-Gemini" class="llm-settings-group" style="display:none;">
                                    <tr style="background: #f8fafc;"><td colspan="2" style="padding: 10px 20px;"><strong>Google Gemini Settings</strong></td></tr>
                                    <tr>
                                        <th scope="row"><label>Gemini API Key</label></th>
                                        <td>
                                            <input type="password" name="gemini_api_key" value="<?php echo esc_attr(get_option('gemini_api_key')); ?>" class="regular-text professional-input">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>Gemini Model</label></th>
                                        <td>
                                        <select name="gemini_model" class="regular-text professional-input">
                                            <option value="gemini-2.5-pro" <?php selected(get_option('gemini_model'), 'gemini-2.5-pro'); ?>>Gemini 2.5 Pro (Stable Reasoning)</option>
                                            <option value="gemini-2.5-flash" <?php selected(get_option('gemini_model'), 'gemini-2.5-flash'); ?>>Gemini 2.5 Flash (Speed Optimized)</option>
                                            <option value="gemini-3-pro-preview" <?php selected(get_option('gemini_model'), 'gemini-3-pro-preview'); ?>>Gemini 3 Pro (Preview)</option>
                                        </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>Gemini Temperature</label></th>
                                        <td>
                                            <input type="number" name="gemini_temperature" value="<?php echo esc_attr(get_option('gemini_temperature', '0.7')); ?>" min="0" max="2" step="0.1" class="small-text professional-input">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php submit_button('Save LLM Configuration'); ?>
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

        <div class="aica-settings-preview-sidebar" style="width: 450px; position: sticky; top: 50px;">
            <div id="aica-admin-preview-container" style="transform: scale(0.9); transform-origin: top center; border: 1px solid #e2e8f0; border-radius: 24px; padding: 20px; background: #ffffff; height: 800px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <?php 
                   $is_admin_preview = true;
                   include AICA_PLUGIN_PATH . 'public/partials/chat-template.php'; 
                ?>
            </div>
        </div>

    </div>
</div>