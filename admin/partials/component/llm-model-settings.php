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
<?php submit_button('Save LLM Configuration', 'primary', 'submit', true, array('id' => 'aica-main-submit')); ?>
</form>