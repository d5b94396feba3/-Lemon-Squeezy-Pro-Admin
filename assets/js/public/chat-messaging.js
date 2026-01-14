(function($) {
    $(document).ready(function() {
        const chat = AICA_Chat;
        const $input = $('#chat-input');
        const $menuEndChat = $('#menu-end-chat');

        function autoResizeTextarea() {
            const el = $input[0];
            el.style.height = 'auto';
            const newHeight = el.scrollHeight;
            el.style.height = newHeight + 'px';
            if (newHeight >= 150) {
                $input.addClass('is-scrolling');
            } else {
                $input.removeClass('is-scrolling');
            }
        }

        $input.on('input propertychange cut paste drop keyup', autoResizeTextarea);

        $('#chat-send').on('click', sendMessage);
        
        $input.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { 
                e.preventDefault(); 
                sendMessage(); 
            }
        });

        function sendMessage() {
            const text = $input.val().trim();
            if (!text) return;
            chat.$messages.append(`<div class="msg user"><div class="bubble">${$('<div>').text(text).html()}</div></div>`);
            $input.val('');
            $input[0].style.height = '44px';
            $input.removeClass('is-scrolling');
            chat.scrollBottom();
            const tId = 'typing-' + Date.now();
            chat.$messages.append(`
                <div class="msg ai" id="${tId}">
                    <div class="msg-avatar-container"><img src="${chat.botImg}" class="msg-avatar"></div>
                    <div class="msg-content-wrapper">
                        <div class="msg-bot-name">${chat.botName}</div>
                        <div class="bubble typing-dots"><span></span><span></span><span></span></div>
                    </div>
                </div>`);
            chat.scrollBottom();
            $menuEndChat.removeClass('is-disabled');

            $.ajax({
                type: 'POST',
                url: chatData.ajax_url,
                data: { action: 'send_chat_message', message: text, chat_nonce: chatData.nonce },
                success: function(res) {
                    $('#' + tId).remove();
                    if (res.success) {
                        const unix = Math.floor(Date.now() / 1000);
                        chat.$messages.append(renderAiBubble(res.data.reply, unix));
                        chat.scrollBottom();
                        chat.updateTimestamps();
                    }
                }
            });
        }

        function renderAiBubble(reply, unix) {
            return `
                <div class="msg ai">
                    <div class="msg-avatar-container"><img src="${chat.botImg}" class="msg-avatar"></div>
                    <div class="msg-content-wrapper">
                        <div class="msg-bot-name">${chat.botName}</div>
                        <div class="bubble">${reply}</div>
                        <div class="msg-footer">
                            <span class="msg-time" data-time="${unix}">just now</span>
                            <div class="msg-actions">
                                <button class="ai-action-btn copy-msg" title="Copy"><i class="fa fa-clone"></i></button>
                                <button class="ai-action-btn regen-msg" title="Regenerate"><i class="fa fa-refresh"></i></button>
                                <button class="ai-action-btn rate-msg like" data-rating="like"><i class="fa fa-thumbs-o-up"></i></button>
                                <button class="ai-action-btn rate-msg dislike" data-rating="dislike"><i class="fa fa-thumbs-o-down"></i></button>
                            </div>
                        </div>
                    </div>
                </div>`;
        }

        $(document).on('click', '.copy-msg', function() {
            const text = $(this).closest('.msg-content-wrapper').find('.bubble').text();
            navigator.clipboard.writeText(text);
            const $i = $(this).find('i');
            $i.removeClass('fa-clone').addClass('fa-check');
            setTimeout(() => $i.removeClass('fa-check').addClass('fa-clone'), 2000);
        });

        $(document).on('click', '.rate-msg', function() {
            const $btn = $(this);
            const $wrapper = $btn.closest('.msg-content-wrapper');
            $.post(chatData.ajax_url, {
                action: 'submit_chat_feedback',
                nonce: chatData.nonce,
                query: $wrapper.closest('.msg.ai').prev('.msg.user').find('.bubble').text(),
                response: $wrapper.find('.bubble').text(),
                rating: $btn.data('rating')
            });
            $btn.parent().find('.rate-msg').removeClass('active-rating');
            $btn.addClass('active-rating');
        });

        $(document).on('click', '.regen-msg', function() {
            const $aiMsg = $(this).closest('.msg.ai');
            const query = $aiMsg.prev('.msg.user').find('.bubble').text();
            if (!query) return;
            $aiMsg.find('.bubble').html('<div class="typing-dots"><span></span><span></span><span></span></div>');
            $.post(chatData.ajax_url, { action: 'send_chat_message', message: query, is_regen: true, chat_nonce: chatData.nonce }, function(res) {
                if (res.success) {
                    $aiMsg.find('.bubble').html(res.data.reply);
                    chat.updateTimestamps();
                }
            });
        });
    });
})(jQuery);