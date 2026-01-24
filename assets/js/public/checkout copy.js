(function($) {
    'use strict';

    const WPPP_Paddle = {
        init: function() {
            if (typeof Paddle === 'undefined') return;

            // Initialize Paddle
            Paddle.Environment.set(wppp_vars.environment);
            
            Paddle.Initialize({ 
                token: wppp_vars.token,
                eventCallback: function(event) {
                    // EXACT LOGIC: Redirect after checkout.completed
                    if (event.name === 'checkout.completed') {
                        console.log('Checkout Completed:', event);
                        
                        const txId = event.data.transaction_id;
                        const thankYouBase = wppp_vars.thank_you;

                        if (thankYouBase && txId) {
                            const separator = thankYouBase.includes('?') ? '&' : '?';
                            const finalUrl = thankYouBase + separator + 'paddle_transaction_id=' + txId;
                            
                            // Perform the redirect
                            window.location.href = finalUrl;
                        }
                    }
                }
            });

            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            $(document).on('click', '.wppp-paddle-checkout', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const priceId = $btn.data('price-id');
                const $msgContainer = $btn.siblings('.paddle-login-message');

                // 1. Check Login Status first
                if (!wppp_vars.is_logged_in) {
                    $msgContainer.text(wppp_vars.login_msg).fadeIn();
                    return;
                }

                if (!priceId) return;

                // 2. Open Paddle Checkout
                Paddle.Checkout.open({
                    settings: {
                        displayMode: "overlay",
                        theme: "light"
                    },
                    items: [{ 
                        priceId: priceId, 
                        quantity: 1 
                    }],
                    customData: {
                        wp_user_id: wppp_vars.user_id
                    }
                });
            });
        }
    };

    $(document).ready(function() {
        WPPP_Paddle.init();
    });

})(jQuery);