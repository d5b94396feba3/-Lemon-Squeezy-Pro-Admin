<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap">
    <h1><?php esc_html_e( 'Digital Products Mapping', 'wp-paddle-pro' ); ?></h1>
    
    <div class="card" style="max-width: 100%; margin-top: 20px;">
        <h2><?php esc_html_e( 'Add New Product', 'wp-paddle-pro' ); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('wppp_add_product_action'); ?>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Paddle Price ID', 'wp-paddle-pro' ); ?></label></th>
                    <td><input type="text" name="price_id" class="regular-text" required placeholder="pri_01j..."></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Product Name', 'wp-paddle-pro' ); ?></label></th>
                    <td><input type="text" name="name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'File URL / Path', 'wp-paddle-pro' ); ?></label></th>
                    <td><input type="text" name="file_url" class="regular-text" required placeholder="https://..."></td>
                </tr>
            </table>
            <input type="submit" name="wppp_add_product" class="button button-primary" value="Save Product">
        </form>
    </div>

    <h2 style="margin-top: 30px;"><?php esc_html_e( 'Existing Products', 'wp-paddle-pro' ); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Name', 'wp-paddle-pro' ); ?></th>
                <th><?php esc_html_e( 'Price ID', 'wp-paddle-pro' ); ?></th>
                <th><?php esc_html_e( 'File Path', 'wp-paddle-pro' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><strong><?php echo esc_html($product['product_name']); ?></strong></td>
                    <td><code><?php echo esc_html($product['paddle_price_id']); ?></code></td>
                    <td><?php echo esc_html($product['file_path']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>