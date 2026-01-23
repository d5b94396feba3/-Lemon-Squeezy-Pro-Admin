<?php
defined( 'ABSPATH' ) || exit;

// Initialize DB Manager
$db = new WPPP_Plugin_DB_Manager($this->plugin_slug);
$transactions = $db->get_all_transactions(100); 
$stats        = $db->get_payment_stats();

// Handle View States
$view_all   = isset($_GET['view']) && $_GET['view'] === 'all';
$display_tx = $view_all ? $transactions : array_slice($transactions, 0, 10);
$env        = get_option('paddle_environment', 'sandbox');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-cart" style="color: #2271b1; margin-right: 10px;"></i>
        <?php echo esc_html(WPPP_PLUGIN_NAME); ?> <?php esc_html_e( 'Financial Overview', 'wp-paddle-pro' ); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="admin-proposal-stats" style="display: flex; gap: 20px; margin-top: 20px;">
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: #f0fdf4; color: #16a34a; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-chart-area"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html( '$' . number_format($stats['revenue'], 2) ); ?></h3>
                <p style="margin: 0; color: #666;"><?php esc_html_e( 'Total Revenue', 'wp-paddle-pro' ); ?></p>
            </div>
        </div>
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: #e7f3ff; color: #2271b1; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-update"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html($stats['active_subs']); ?></h3>
                <p style="margin: 0; color: #666;"><?php esc_html_e( 'Active Subscriptions', 'wp-paddle-pro' ); ?></p>
            </div>
        </div>
        <div class="admin-stat-card" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; display: flex; align-items: center;">
            <div class="stat-icon" style="background: <?php echo ($env === 'sandbox') ? '#fff7ed' : '#f0fdf4'; ?>; color: <?php echo ($env === 'sandbox') ? '#ea580c' : '#16a34a'; ?>; padding: 10px; border-radius: 50%; margin-right: 15px;"><i class="dashicons dashicons-shield"></i></div>
            <div class="stat-content">
                <h3 style="margin: 0; font-size: 24px;"><?php echo ucfirst($env); ?></h3>
                <p style="margin: 0; color: #666;"><?php esc_html_e( 'Mode', 'wp-paddle-pro' ); ?></p>
            </div>
        </div>
    </div>

    <div class="admin-proposals-table" style="margin-top: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;"><?php esc_html_e( 'Recent Transactions', 'wp-paddle-pro' ); ?></h2>
            <div style="display: flex; gap: 10px;">
                <?php if (count($transactions) > 10 && !$view_all): ?>
                    <a href="<?php echo esc_url( add_query_arg( 'view', 'all' ) ); ?>" class="button"><?php esc_html_e( 'View All Transactions', 'wp-paddle-pro' ); ?></a>
                <?php elseif ($view_all): ?>
                    <a href="<?php echo esc_url( remove_query_arg( 'view' ) ); ?>" class="button"><?php esc_html_e( 'Show Recent Only', 'wp-paddle-pro' ); ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 25%;"><?php esc_html_e( 'Order ID', 'wp-paddle-pro' ); ?></th>
                    <th style="width: 25%;"><?php esc_html_e( 'Customer', 'wp-paddle-pro' ); ?></th>
                    <th><?php esc_html_e( 'Amount', 'wp-paddle-pro' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'wp-paddle-pro' ); ?></th>
                    <th style="text-align: right;"><?php esc_html_e( 'Actions', 'wp-paddle-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($display_tx)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 40px 0; color: #666;"><?php esc_html_e( 'No transactions recorded yet.', 'wp-paddle-pro' ); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($display_tx as $tx): 
                        $user = get_userdata($tx['user_id']);
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $tx['paddle_order_id'] ); ?></strong></td>
                            <td>
                                <div style="font-weight: 500;"><?php echo $user ? esc_html($user->display_name) : __('Deleted User', 'wp-paddle-pro'); ?></div>
                                <small style="color: #666;"><?php echo $user ? esc_html($user->user_email) : 'ID: ' . $tx['user_id']; ?></small>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #16a34a;">
                                    <?php echo esc_html($tx['currency'] . ' ' . number_format($tx['amount'], 2)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime($tx['created_at'] ) ) ); ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button class="button action-view-receipt" data-id="<?php echo esc_attr( $tx['id'] ); ?>" title="<?php esc_attr_e( 'View Details', 'wp-paddle-pro' ); ?>">
                                        <i class="dashicons dashicons-visibility" style="margin-top:4px;"></i>
                                    </button>
                                    <button class="button action-delete-tx" 
                                            onclick="if(confirm('<?php _e('Are you sure you want to delete this record?', 'wp-paddle-pro'); ?>')) { /* AJAX logic here */ }" 
                                            data-id="<?php echo esc_attr( $tx['id'] ); ?>" 
                                            style="color: #d63638; border-color: #d63638;">
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