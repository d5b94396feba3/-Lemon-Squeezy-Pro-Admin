<?php
defined( 'ABSPATH' ) || exit;

// Get data from the class instance ($this refers to Plugin_Admin)
$chat_logs = $this->get_all_chat_logs(); 
$stats     = $this->get_chat_stats();
$total_chats = isset($stats['total']) ? $stats['total'] : 0;

$view_all = isset($_GET['view']) && $_GET['view'] === 'all';
$display_logs = $view_all ? $chat_logs : array_slice($chat_logs, 0, 10);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-smart-phone" style="color: #2271b1; margin-right: 10px;"></i>
        AI Agent Command Center
    </h1>
    <hr class="wp-header-end">

    <div class="admin-proposal-stats" style="display: flex; gap: 20px; margin-top: 20px;">
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: #e7f3ff; color: #2271b1; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-format-chat"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html($total_chats); ?></h3>
                <p style="margin: 0; color: #666;">Total Conversations</p>
            </div>
        </div>
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: #f0fdf4; color: #16a34a; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-clock"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html($stats['recent']); ?></h3>
                <p style="margin: 0; color: #666;">Active (Last 7 Days)</p>
            </div>
        </div>
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: #fff7ed; color: #ea580c; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-businessman"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html(get_option('grok_model', 'Grok-2')); ?></h3>
                <p style="margin: 0; color: #666;">Engine Active</p>
            </div>
        </div>
    </div>

    <div class="admin-proposals-table" style="margin-top: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Recent Conversations</h2>
            <?php if (count($chat_logs) > 10 && !$view_all): ?>
                <a href="?page=<?php echo $this->plugin_slug; ?>&view=all" class="button">View All History</a>
            <?php endif; ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 20%;">Session ID</th>
                    <th style="width: 45%;">Last Message Preview</th>
                    <th>Last Active</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($display_logs)): ?>
                    <tr><td colspan="4">No conversations recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($display_logs as $log):
                        $history = json_decode($log['full_history'], true);
                        $first_msg = is_array($history) ? $history[0]['content'] : 'No history';
                        $last_msg = is_array($history) ? end($history) : ['content' => '...'];
                    ?>
                        <tr>
                            <td><strong>Chat #<?php echo esc_html($log['id']); ?></strong></td>
                            <td>
                                <div style="font-weight: 500;">Start: <?php echo esc_html(wp_trim_words($first_msg, 8)); ?></div>
                                <em style="color: #666; font-size: 12px;">Last: <?php echo esc_html(wp_trim_words($last_msg['content'], 10)); ?></em>
                            </td>
                            <td><?php echo date('M j, Y - g:i A', strtotime($log['last_updated'])); ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                    <button class="button view-chat-log" data-history='<?php echo esc_attr($log['full_history']); ?>' title="View">
                                        <i class="dashicons dashicons-visibility" style="margin-top:4px;"></i>
                                    </button>

                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=' . $this->plugin_slug . '&action=export_ai_chats&log_id=' . $log['id']), 'export_chats_nonce'); ?>" 
                                    class="button" title="Export CSV">
                                        <i class="dashicons dashicons-download" style="margin-top:4px;"></i>
                                    </a>

                                    <button class="button action-delete-chat" data-id="<?php echo $log['id']; ?>" style="color: #d63638; border-color: #d63638;" title="Delete">
                                        <i class="dashicons dashicons-trash" style="margin-top:4px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="chat-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999;">
    <div style="background:#fff; width:90%; max-width:700px; max-height:85vh; margin:40px auto; padding:25px; border-radius:12px; overflow-y:auto; position:relative; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Conversation Transcript</h3>
        <div id="chat-modal-content" style="margin-top: 15px;"></div>
        <div style="margin-top: 25px; text-align: right; border-top: 1px solid #eee; padding-top: 15px;">
            <button onclick="jQuery('#chat-modal').hide()" class="button button-primary">Close Window</button>
        </div>
    </div>
</div>