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
        <a href="<?php echo $page_url; ?>&tab=knowledge-base" class="nav-tab <?php echo $active_tab === 'knowledge-base' ? 'nav-tab-active' : ''; ?>">Knowledge Base</a>
        <a href="<?php echo $page_url; ?>&tab=llm-model" class="nav-tab <?php echo $active_tab === 'llm-model' ? 'nav-tab-active' : ''; ?>">LLM Model</a>
        <a href="<?php echo $page_url; ?>&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">Notifications</a>
    </nav>

    <div style="display: flex; gap: 30px; align-items: flex-start; margin-top: 20px;">
        
        <div class="settings-container" style="flex: 1; min-width: 0; margin-top: 0;">
            <?php if ($active_tab === 'content'): 
            include( AICA_PLUGIN_PATH . 'admin/partials/component/content-settings.php' );
            ?>
            <?php elseif ($active_tab === 'style'): 
            include( AICA_PLUGIN_PATH . 'admin/partials/component/style-settings.php' );
            ?>
            <?php elseif ($active_tab === 'knowledge-base'): 
            include( AICA_PLUGIN_PATH . 'admin/partials/component/knowledge-base-settings.php' );
            ?>
            <?php elseif ($active_tab === 'llm-model'): 
            include( AICA_PLUGIN_PATH . 'admin/partials/component/llm-model-settings.php' );    
            ?>
            <?php elseif ($active_tab === 'notifications'): 
            include( AICA_PLUGIN_PATH . 'admin/partials/component/notifications-settings.php' );
            ?>
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