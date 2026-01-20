<?php
defined( 'ABSPATH' ) || exit;

$chat_logs = $this->get_all_chat_logs(); 
$stats     = $this->get_chat_stats();
$total_chats = isset($stats['total']) ? $stats['total'] : 0;

$view_all = isset($_GET['view']) && $_GET['view'] === 'all';
$display_logs = $view_all ? $chat_logs : array_slice($chat_logs, 0, 10);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-smart-phone" style="color: #2271b1; margin-right: 10px;"></i>
        <?php echo esc_html(AICA_PLUGIN_NAME); ?> <?php esc_html_e( 'Command Center', 'aica-plugin' ); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="admin-proposal-stats" style="display: flex; gap: 20px; margin-top: 20px;">
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: #e7f3ff; color: #2271b1; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-format-chat"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html($total_chats); ?></h3>
                <p style="margin: 0; color: #666;"><?php esc_html_e( 'Total Conversations', 'aica-plugin' ); ?></p>
            </div>
        </div>
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: #f0fdf4; color: #16a34a; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-clock"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html($stats['recent']); ?></h3>
                <p style="margin: 0; color: #666;"><?php esc_html_e( 'Active (Last 7 Days)', 'aica-plugin' ); ?></p>
            </div>
        </div>
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: #fff7ed; color: #ea580c; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-businessman"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html(get_option('llm_model', 'Grok')); ?></h3>
                <p style="margin: 0; color: #666;"><?php esc_html_e( 'Model Active', 'aica-plugin' ); ?></p>
            </div>
        </div>
    </div>

    <div class="admin-proposals-table" style="margin-top: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;"><?php esc_html_e( 'Recent Conversations', 'aica-plugin' ); ?></h2>
            <div style="display: flex; gap: 10px;">
                <button class="button button-secondary action-export-chat" data-id="0">
                    <i class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -3px;"></i> <?php esc_html_e( 'Export All to CSV', 'aica-plugin' ); ?>
                </button>
                <?php if (count($chat_logs) > 10 && !$view_all): ?>
                    <a href="<?php echo esc_url( add_query_arg( 'view', 'all' ) ); ?>" class="button"><?php esc_html_e( 'View All History', 'aica-plugin' ); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 20%;"><?php esc_html_e( 'Session ID', 'aica-plugin' ); ?></th>
                    <th style="width: 45%;"><?php esc_html_e( 'Last Message Preview', 'aica-plugin' ); ?></th>
                    <th><?php esc_html_e( 'Last Active', 'aica-plugin' ); ?></th>
                    <th style="text-align: right;"><?php esc_html_e( 'Actions', 'aica-plugin' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($display_logs)): ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No conversations recorded yet.', 'aica-plugin' ); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($display_logs as $log):
                        $history = json_decode($log['full_history'], true);
                        $first_msg = is_array($history) ? $history[0]['content'] : __( 'No history', 'aica-plugin' );
                        $last_msg = is_array($history) ? end($history) : ['content' => '...'];
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( sprintf( __( 'Chat #%d', 'aica-plugin' ), $log['id'] ) ); ?></strong></td>
                            <td>
                                <div style="font-weight: 500;"><?php echo esc_html( __( 'Start:', 'aica-plugin' ) . ' ' . wp_trim_words($first_msg, 8)); ?></div>
                                <em style="color: #666; font-size: 12px;"><?php echo esc_html( __( 'Last:', 'aica-plugin' ) . ' ' . wp_trim_words($last_msg['content'], 10)); ?></em>
                            </td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' - g:i A', strtotime($log['last_updated'] ) ) ); ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                    <button class="button action-summarize-chat" data-id="<?php echo esc_attr( $log['id'] ); ?>" title="<?php esc_attr_e( 'AI Summary', 'aica-plugin' ); ?>" style="background: #fff7ed; color: #ea580c; border-color: #fdba74;">
                                        <i class="dashicons dashicons-lightbulb" style="margin-top:4px;"></i>
                                    </button>
                                    <button class="button view-chat-log" data-history='<?php echo esc_attr($log['full_history']); ?>' title="<?php esc_attr_e( 'View', 'aica-plugin' ); ?>">
                                        <i class="dashicons dashicons-visibility" style="margin-top:4px;"></i>
                                    </button>

                                    <button class="button action-export-chat" data-id="<?php echo esc_attr( $log['id'] ); ?>" title="<?php esc_attr_e( 'Export CSV', 'aica-plugin' ); ?>">
                                        <i class="dashicons dashicons-download" style="margin-top:4px;"></i>
                                    </button>

                                    <button class="button action-delete-chat" data-id="<?php echo esc_attr( $log['id'] ); ?>" style="color: #d63638; border-color: #d63638;" title="<?php esc_attr_e( 'Delete', 'aica-plugin' ); ?>">
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
        <h3 id="chat-modal-title" style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;"><?php esc_html_e( 'Conversation Transcript', 'aica-plugin' ); ?></h3>
        
        <div id="chat-modal-loader" style="display:none; padding: 50px 0; text-align: center;">
            <div class="aica-loader-circle"></div>
            <p style="margin-top: 15px; color: #666; font-weight: 500; font-size: 15px;"><?php esc_html_e( 'AI is summarizing conversation...', 'aica-plugin' ); ?></p>
        </div>

        <div id="chat-modal-content" style="margin-top: 15px;"></div>
        
        <div style="margin-top: 25px; text-align: right; border-top: 1px solid #eee; padding-top: 15px;">
            <button onclick="jQuery('#chat-modal').hide()" class="button button-primary"><?php esc_html_e( 'Close Window', 'aica-plugin' ); ?></button>
        </div>
    </div>
</div>