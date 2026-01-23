(function($) {
    $(document).ready(function() {
        const chat = wppp_Chat;
        const $menu = $('#chat-options-popup');
        const $overlay = $('#chat-confirm-overlay');
        const $endChatBtn = $('#menu-end-chat');
        const publicData = (typeof wppp_public_data !== 'undefined') ? wppp_public_data : { ajax_url: '', nonce: '' };
        let mode = '';

        function updateEndChatVisibility() {
            const userMsgCount = chat.$messages.find('.msg.user').length;
            if (userMsgCount > 0) {
                $endChatBtn.removeClass('is-disabled');
            } else {
                $endChatBtn.addClass('is-disabled');
            }
        }

        $('#chat-menu-btn').on('click', (e) => { 
            e.stopPropagation(); 
            updateEndChatVisibility(); 
            $menu.toggleClass('hidden'); 
        });

        $(document).on('click', () => $menu.addClass('hidden'));

        $('#menu-start-new').on('click', function() {
            mode = 'clear';
            const title = publicData.i18n_start_fresh || 'Start fresh?';
            const desc = publicData.i18n_start_fresh_desc || 'This will clear your current messages so you can start a new conversation.';
            showOverlay(title, desc);
        });

        $('#menu-end-chat').on('click', function() {
            if ($(this).hasClass('is-disabled')) return;
            mode = 'end';
            const title = publicData.i18n_end_chat_title || 'End this chat?';
            const desc = publicData.i18n_end_chat_desc || 'This will clear your current messages and start a new conversation again.';
            showOverlay(title, desc);
        });

        function showOverlay(title, desc) {
            $menu.addClass('hidden');
            $('#confirm-title').text(title);
            $('#confirm-desc').text(desc);
            $overlay.removeClass('hidden').hide().fadeIn(200);
        }

        $('#confirm-cancel').on('click', () => $overlay.fadeOut(200));

        $('#confirm-execute').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.text();
            
            $btn.prop('disabled', true).text(publicData.i18n_processing || 'Processing...');
            
            const action = (mode === 'clear') ? 'clear_chat_history' : 'end_chat_session';

            $.post(publicData.ajax_url, { 
                action: action, 
                nonce: publicData.nonce
            }, function(res) {
                $btn.prop('disabled', false).text(originalText);
                if (res.success) {
                    chat.$messages.empty().append(chat.$welcomeHtml.clone());
                    updateEndChatVisibility();
                    $overlay.fadeOut(200);
                    if (mode === 'end') {
                        chat.toggleChat();
                    }
                }
            }).fail(function() {
                $btn.prop('disabled', false).text(originalText);
            });
        });
    });
})(jQuery);