(function($) {
    $(document).ready(function() {
        const chat = wppp_Chat;
        const $input = $('#chat-input');
        const $menuEndChat = $('#menu-end-chat');
        const publicData = (typeof wppp_public_data !== 'undefined') ? wppp_public_data : { ajax_url: '', nonce: '' };

        function autoResizeTextarea() {
            const el = $input[0];
            if (!el) return;
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
            const safeBotImg = encodeURI(chat.botImg);
            
            chat.$messages.append(`
                <div class="msg ai" id="${tId}">
                    <div class="msg-avatar-container"><img src="${safeBotImg}" class="msg-avatar" alt=""></div>
                    <div class="msg-content-wrapper">
                        <div class="msg-bot-name">${chat.botName}</div>
                        <div class="bubble typing-dots"><span></span><span></span><span></span></div>
                    </div>
                </div>`);
            
            chat.scrollBottom();
            $menuEndChat.removeClass('is-disabled');

            $.ajax({
                type: 'POST',
                url: publicData.ajax_url,
                data: { 
                    action: 'send_chat_message', 
                    message: text, 
                    chat_nonce: publicData.nonce 
                },
                success: function(res) {
                    $('#' + tId).remove();
                    if (res.success) {
                        const unix = Math.floor(Date.now() / 1000);
                        chat.$messages.append(renderAiBubble(res.data.reply, unix));
                        chat.scrollBottom();
                        chat.updateTimestamps();
                    } else {
                        console.error('AI Error:', res.data.message);
                    }
                },
                error: function() {
                    $('#' + tId).remove();
                }
            });
        }

        function renderAiBubble(reply, unix) {
            const i18n = publicData;
            return `
                <div class="msg ai">
                    <div class="msg-avatar-container"><img src="${encodeURI(chat.botImg)}" class="msg-avatar" alt=""></div>
                    <div class="msg-content-wrapper">
                        <div class="msg-bot-name">${chat.botName}</div>
                        <div class="bubble">${reply}</div>
                        <div class="msg-footer">
                            <span class="msg-time" data-time="${unix}">${i18n.just_now || 'just now'}</span>
                            <div class="msg-actions">
                                <button class="ai-action-btn copy-msg" title="${i18n.i18n_copy || 'Copy'}"><i class="fa fa-clone"></i></button>
                                <button class="ai-action-btn regen-msg" title="${i18n.i18n_regen || 'Regenerate'}"><i class="fa fa-refresh"></i></button>
                                <button class="ai-action-btn rate-msg like" data-rating="like" title="${i18n.i18n_helpful || 'Helpful'}"><i class="fa fa-thumbs-o-up"></i></button>
                                <button class="ai-action-btn rate-msg dislike" data-rating="dislike" title="${i18n.i18n_not_helpful || 'Not helpful'}"><i class="fa fa-thumbs-o-down"></i></button>
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
            $.post(publicData.ajax_url, {
                action: 'submit_chat_feedback',
                nonce: publicData.nonce,
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
            
            $.post(publicData.ajax_url, { 
                action: 'send_chat_message', 
                message: query, 
                is_regen: true, 
                chat_nonce: publicData.nonce 
            }, function(res) {
                if (res.success) {
                    $aiMsg.find('.bubble').html(res.data.reply);
                    chat.updateTimestamps();
                }
            });
        });
    });
})(jQuery);