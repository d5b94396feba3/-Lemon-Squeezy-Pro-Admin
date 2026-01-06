(function($) {
    $(document).ready(function() {
        const chat = AICA_Chat;
        const $menu = $('#chat-options-popup');
        const $overlay = $('#chat-confirm-overlay');
        let mode = '';

        $('#chat-menu-btn').on('click', (e) => { e.stopPropagation(); $menu.toggleClass('hidden'); });
        $(document).on('click', () => $menu.addClass('hidden'));

        $('#menu-start-new').on('click', function() {
            mode = 'clear';
            showOverlay('Start fresh?', 'This will clear your current messages so you can start a new conversation.');
        });

        $('#menu-end-chat').on('click', function() {
            mode = 'end';
            showOverlay('End this chat?', 'This will clear your current messages and start a new conversation again.');
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
            $btn.prop('disabled', true).text('Processing...');
            const action = (mode === 'clear') ? 'clear_chat_history' : 'end_chat_session';

            $.post(chatData.ajax_url, { action: action, nonce: chatData.nonce }, function(res) {
                $btn.prop('disabled', false).text('Confirm');
                if (res.success) {
                    chat.$messages.empty().append(chat.$welcomeHtml.clone());
                    $overlay.fadeOut(200);
                    if (mode === 'end') chat.toggleChat();
                }
            });
        });
    });
})(jQuery);