<?php
defined( 'ABSPATH' ) || exit;
?>

<form method="post" action="options.php">
<?php settings_fields($this->plugin_slug . '_grok_settings'); ?>
<div class="postbox professional-box">
    <div class="postbox-header">
        <h2 class="hndle"><?php esc_html_e( 'LLM Model Configuration', 'aica-plugin' ); ?></h2>
    </div>
    <div class="inside">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="llm_model_selector"><?php esc_html_e( 'Active LLM Model', 'aica-plugin' ); ?></label>
                </th>
                <td>
                    <select name="llm_model" id="llm_model_selector" class="regular-text professional-input">
                        <option value="Grok" <?php selected(get_option('llm_model', 'Grok'), 'Grok'); ?>><?php esc_html_e( 'xAI Grok', 'aica-plugin' ); ?></option>
                        <option value="OpenAI" <?php selected(get_option('llm_model'), 'OpenAI'); ?>><?php esc_html_e( 'OpenAI (GPT-4o)', 'aica-plugin' ); ?></option>
                        <option value="Gemini" <?php selected(get_option('llm_model'), 'Gemini'); ?>><?php esc_html_e( 'Google Gemini', 'aica-plugin' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Select the core intelligence engine for your AI Agent.', 'aica-plugin' ); ?></p>
                </td>
            </tr>

            <tbody id="settings-group-Grok" class="llm-settings-group">
                <tr style="background: #f8fafc;">
                    <td colspan="2" style="padding: 10px 20px;">
                        <strong><?php esc_html_e( 'xAI Grok Settings', 'aica-plugin' ); ?></strong>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="grok_api_key"><?php esc_html_e( 'Grok API Key', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <input type="password" id="grok_api_key" name="grok_api_key" value="<?php echo esc_attr(get_option('grok_api_key')); ?>" class="regular-text professional-input" placeholder="xai-...">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="grok_model"><?php esc_html_e( 'Grok Model', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <select id="grok_model" name="grok_model" class="regular-text professional-input">
                            <option value="grok-4" <?php selected(get_option('grok_model'), 'grok-4'); ?>><?php esc_html_e( 'Grok-4 (Flagship Reasoning)', 'aica-plugin' ); ?></option>
                            <option value="grok-4-fast-reasoning" <?php selected(get_option('grok_model'), 'grok-4-fast-reasoning'); ?>><?php esc_html_e( 'Grok-4 Fast (Speed Optimized)', 'aica-plugin' ); ?></option>
                            <option value="grok-2-vision-1212" <?php selected(get_option('grok_model'), 'grok-2-vision-1212'); ?>><?php esc_html_e( 'Grok-2 Vision (Legacy Vision)', 'aica-plugin' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="grok_temperature"><?php esc_html_e( 'Grok Temperature', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="grok_temperature" name="grok_temperature" value="<?php echo esc_attr(get_option('grok_temperature', '0.7')); ?>" min="0" max="2" step="0.1" class="small-text professional-input">
                    </td>
                </tr>
            </tbody>

            <tbody id="settings-group-OpenAI" class="llm-settings-group" style="display:none;">
                <tr style="background: #f8fafc;">
                    <td colspan="2" style="padding: 10px 20px;">
                        <strong><?php esc_html_e( 'OpenAI Settings', 'aica-plugin' ); ?></strong>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr(get_option('openai_api_key')); ?>" class="regular-text professional-input" placeholder="sk-...">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="openai_model"><?php esc_html_e( 'OpenAI Model', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <select id="openai_model" name="openai_model" class="regular-text professional-input">
                            <option value="gpt-5.2" <?php selected(get_option('openai_model'), 'gpt-5.2'); ?>><?php esc_html_e( 'GPT-5.2 (Latest Stable)', 'aica-plugin' ); ?></option>
                            <option value="gpt-5-mini" <?php selected(get_option('openai_model'), 'gpt-5-mini'); ?>><?php esc_html_e( 'GPT-5 Mini', 'aica-plugin' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="openai_temperature"><?php esc_html_e( 'OpenAI Temperature', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="openai_temperature" name="openai_temperature" value="<?php echo esc_attr(get_option('openai_temperature', '0.7')); ?>" min="0" max="2" step="0.1" class="small-text professional-input">
                    </td>
                </tr>
            </tbody>

            <tbody id="settings-group-Gemini" class="llm-settings-group" style="display:none;">
                <tr style="background: #f8fafc;">
                    <td colspan="2" style="padding: 10px 20px;">
                        <strong><?php esc_html_e( 'Google Gemini Settings', 'aica-plugin' ); ?></strong>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gemini_api_key"><?php esc_html_e( 'Gemini API Key', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <input type="password" id="gemini_api_key" name="gemini_api_key" value="<?php echo esc_attr(get_option('gemini_api_key')); ?>" class="regular-text professional-input">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gemini_model"><?php esc_html_e( 'Gemini Model', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <select id="gemini_model" name="gemini_model" class="regular-text professional-input">
                            <option value="gemini-2.5-pro" <?php selected(get_option('gemini_model'), 'gemini-2.5-pro'); ?>><?php esc_html_e( 'Gemini 2.5 Pro (Stable Reasoning)', 'aica-plugin' ); ?></option>
                            <option value="gemini-2.5-flash" <?php selected(get_option('gemini_model'), 'gemini-2.5-flash'); ?>><?php esc_html_e( 'Gemini 2.5 Flash (Speed Optimized)', 'aica-plugin' ); ?></option>
                            <option value="gemini-3-pro-preview" <?php selected(get_option('gemini_model'), 'gemini-3-pro-preview'); ?>><?php esc_html_e( 'Gemini 3 Pro (Preview)', 'aica-plugin' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gemini_temperature"><?php esc_html_e( 'Gemini Temperature', 'aica-plugin' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="gemini_temperature" name="gemini_temperature" value="<?php echo esc_attr(get_option('gemini_temperature', '0.7')); ?>" min="0" max="2" step="0.1" class="small-text professional-input">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php submit_button( __( 'Save LLM Configuration', 'aica-plugin' ), 'primary', 'submit', true, array('id' => 'aica-main-submit') ); ?>
</form>