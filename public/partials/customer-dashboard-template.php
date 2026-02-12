<?php
/**
 * Customer Dashboard Template
 *
 * This template displays the list of digital products purchased by the user.
 * * @var array $downloads List of purchased products from DB Manager.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wppp-customer-dashboard">
    <div class="wppp-dashboard-header">
        <h3 class="wppp-dashboard-title">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('My Products', 'wp-paddle-pro'); ?>
        </h3>
        <p class="wppp-dashboard-subtitle">
            <?php esc_html_e('All your purchased products are listed below. Click "Download" to save them to your device.', 'wp-paddle-pro'); ?>
        </p>
    </div>

    <?php if ( ! empty( $downloads ) ) : ?>
        <div class="wppp-table-responsive">
            <table class="wppp-downloads-table">
                <thead>
                    <tr>
                        <th class="col-product"><?php esc_html_e('Product Name', 'wp-paddle-pro'); ?></th>
                        <th class="col-date"><?php esc_html_e('Purchase Date', 'wp-paddle-pro'); ?></th>
                        <th class="col-action"><?php esc_html_e('Action', 'wp-paddle-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $downloads as $item ) : ?>
                        <tr>
                            <td class="col-product">
                                <span class="wppp-product-name"><?php echo esc_html( $item['product_name'] ); ?></span>
                            </td>
                            <td class="col-date">
                                <span class="wppp-date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $item['created_at'] ) ); ?></span>
                            </td>
                            <td class="col-action">
                                <?php 
                                // Generate the secure download URL
                                $download_url = add_query_arg( 'lspa_download', $item['id'], home_url( '/' ) ); 
                                ?>
                                <a href="<?php echo esc_url( $download_url ); ?>" class="wppp-button wppp-download-btn">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php esc_html_e('Download', 'wp-paddle-pro'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <div class="wppp-no-purchases">
            <div class="wppp-empty-icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <p><?php esc_html_e('You haven\'t purchased any digital products yet.', 'wp-paddle-pro'); ?></p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wppp-button">
                <?php esc_html_e('Browse Products', 'wp-paddle-pro'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>