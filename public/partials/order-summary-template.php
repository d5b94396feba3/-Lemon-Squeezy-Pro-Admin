<?php
/**
 * Template for the Paddle Order Summary
 * Updated for Digital Product Downloads
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="paddle-upgrade-success">
    <h2 class="paddle-success-header">
        <?php esc_html_e( 'Order Confirmed!', 'wp-paddle-pro' ); ?>
    </h2>
    
    <div class="paddle-success-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.86" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22 4L12 14.01L9 11.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    <h3 class="paddle-success-subheader">
        <?php esc_html_e( 'Thank you for your purchase', 'wp-paddle-pro' ); ?>
    </h3>
    
    <div class="paddle-divider"></div>
    
    <p class="paddle-next-steps">
        <?php esc_html_e( 'Your transaction has been processed successfully. You can now access your digital products and files from your download dashboard.', 'wp-paddle-pro' ); ?>
    </p>
    
    <div class="paddle-action-links">
        <a href="<?php echo esc_url( home_url( '/my-downloads/' ) ); ?>" class="paddle-primary-btn">
            <?php esc_html_e( 'Go to Downloads', 'wp-paddle-pro' ); ?>
            <svg class="paddle-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
        </a>
        
        <a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>" class="paddle-secondary-btn">
            <?php esc_html_e( 'My Account', 'wp-paddle-pro' ); ?>
            <svg class="paddle-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect>
                <path d="M16 2a4 4 0 0 1 4 4v1H4V6a4 4 0 0 1 4-4h8z"></path>
            </svg>
        </a>
    </div>  
</div>