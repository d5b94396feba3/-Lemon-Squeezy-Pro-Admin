<?php
namespace PaddleToMycred;

defined('ABSPATH') || exit;

class Mycred_Handler {
    private $user_id;
    private $last_transaction = null;
    
    public function __construct($user_id = null) {
        $this->user_id = $user_id ? $user_id : get_current_user_id();
    }

    /**
     * Grants myCRED points upon a successful Paddle purchase.
     */
    public function grant_points_on_paddle_purchase($user_id, $amount, $reason = '', $transaction_details = []) {
        if (!function_exists('mycred_add')) {
            error_log('myCred add function not available. Ensure myCRED plugin is active.');
            return false;
        }
        
        $entry = __('Points added for Paddle purchase', 'wp-paddle-pro');
        if ($reason) {
            $entry .= ': ' . $reason;
        }
        
        $log_data = [
            'paddle_transaction_id' => $transaction_details['id'] ?? null,
            'paddle_price_id'       => $transaction_details['price_id'] ?? null,
            'points_granted'        => $amount
        ];
        
        $transaction_id = mycred_add(
            'paddle_purchase_points', 
            $user_id,
            $amount,
            $entry,
            '', 
            $log_data,
            time()
        );
        
        if ($transaction_id === false) {
            error_log("Failed to add {$amount} points for user {$user_id} via Paddle.");
        } else {
            // Update rank immediately after adding points
            if (function_exists('mycred_update_users_rank')) {
                mycred_update_users_rank($user_id);
            }
        }
        return $transaction_id;
    }

    /**
     * Returns balance and rank for the thank you page
     */
    public function mycred_balance_with_rank() {
        if (!function_exists('mycred_get_users_balance')) return false;

        return [
            'balance'   => mycred_get_users_balance($this->user_id),
            'rank_name' => function_exists('mycred_get_users_rank') ? mycred_get_users_rank($this->user_id) : __('Member', 'wp-paddle-pro')
        ];
    }
}