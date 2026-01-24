(function($) {
    'use strict';

    const WPPP_Paddle = {
        init: function() {
            if (typeof Paddle === 'undefined') return;

            Paddle.Environment.set(wppp_vars.environment);
            
            Paddle.Initialize({ 
                token: wppp_vars.token,
                eventCallback: function(event) {
                    // Paddle v2 uses 'checkout.completed' for successful payments
                    if (event.name === 'checkout.completed') {
                        // Extracting transaction_id from the data object
                        const txId = event.data.transaction_id || event.data.id;
                        const thankYouBase = wppp_vars.thank_you;

                        if (thankYouBase && txId) {
                            const url = new URL(thankYouBase);
                            url.searchParams.set('paddle_transaction_id', txId);
                            
                            // Slight delay ensures Paddle UI processes the completion 
                            // before the browser navigates away.
                            setTimeout(function() {
                                window.location.href = url.toString();
                            }, 500);
                        }
                    }
                }
            });

            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.wppp-paddle-checkout', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const priceId = $btn.data('price-id');
                const $msgContainer = $btn.siblings('.paddle-login-message');

                if (!wppp_vars.is_logged_in) {
                    $msgContainer.text(wppp_vars.login_msg).fadeIn();
                    return;
                }

                if (!priceId) return;

                Paddle.Checkout.open({
                    settings: {
                        displayMode: "overlay",
                        theme: "light",
                        // Securely close the checkout on success
                        allowLogout: false
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