<form method="post" action="options.php">
<?php settings_fields($this->plugin_slug . '_prompt_settings'); ?>

<div class="postbox professional-box knowledge-sync-wrapper">
    <div class="postbox-header" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;" id="toggle-sync-table">
        <h2 class="hndle">Website Knowledge Sync</h2>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span id="sync-summary-badge" class="status-badge" style="display:none; background:#eff6ff; color:#1e40af;">Discovering...</span>
            <span class="dashiacons dashicons-arrow-down-alt2 toggle-icon"></span>
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
<?php submit_button('Save AI Persona & Knowledge', 'primary', 'submit', true, array('id' => 'aica-main-submit')); ?>
</form>