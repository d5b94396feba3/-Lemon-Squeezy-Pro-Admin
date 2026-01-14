(function($) {
    $(document).ready(function() {
        const chat = AICA_Chat;
        const $recentView = $('#recent-chats-view');
        const $recentList = $('#recent-chats-list');
        const $menuPopup = $('#chat-options-popup');
        let recentData = {};

        $('#menu-view-recent').on('click', function(e) {
            e.stopPropagation();
            $menuPopup.addClass('hidden');
            $recentView.removeClass('hidden').show();
            $recentList.html('<div class="recent-loading"><i class="fa fa-spinner fa-spin fa-2x"></i><span>Loading history...</span></div>');
            
            $.post(chatData.ajax_url, { action: 'get_recent_chats', nonce: chatData.nonce }, function(res) {
                if (res.success && res.data.length > 0) {
                    let html = '';
                    recentData = {};
                    res.data.forEach(item => {
                        recentData[item.id] = item.history;
                        html += `<div class="recent-chat-item" data-id="${item.id}"><span class="chat-date">${item.date}</span><span class="chat-preview">${item.preview}</span></div>`;
                    });
                    $recentList.html(html);
                } else {
                    $recentList.html('<div class="recent-loading">No recent chats found.</div>');
                }
            });
        });

        $('#back-to-chat').on('click', () => $recentView.hide().addClass('hidden'));

        $(document).on('click', '.recent-chat-item', function() {
            const id = $(this).data('id');
            const history = recentData[id];
            if (!history) return;

            document.cookie = "aica_chat_uuid=" + id + "; path=/; max-age=3600";
            chat.$messages.empty().append(chat.$welcomeHtml.clone());

            history.forEach(msg => {
                const isBot = (msg.role === 'assistant' || msg.role === 'ai');
                chat.$messages.append(`
                    <div class="msg ${isBot ? 'ai' : 'user'}">
                        ${isBot ? `<div class="msg-avatar-container"><img src="${chat.botImg}" class="msg-avatar"></div>` : ''}
                        <div class="msg-content-wrapper">
                            ${isBot ? `<div class="msg-bot-name">${chat.botName}</div>` : ''}
                            <div class="bubble">${msg.content}</div>
                        </div>
                    </div>`);
            });
            $recentView.hide().addClass('hidden');
            chat.scrollBottom();
        });
    });
})(jQuery);