(function($) {
    $(document).ready(function() {
        const chat = AICA_Chat;
        const $recentView = $('#recent-chats-view');
        const $recentList = $('#recent-chats-list');
        const $menuPopup = $('#chat-options-popup');
        const publicData = (typeof aica_public_data !== 'undefined') ? aica_public_data : { ajax_url: '', nonce: '' };
        let recentData = {};

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        $('#menu-view-recent').on('click', function(e) {
            e.stopPropagation();
            $menuPopup.addClass('hidden');
            $recentView.removeClass('hidden').show();
            
            const loadingMsg = publicData.i18n_loading_history || 'Loading history...';
            $recentList.html(`<div class="recent-loading"><i class="fa fa-spinner fa-spin fa-2x"></i><span>${loadingMsg}</span></div>`);
            
            $.post(publicData.ajax_url, { 
                action: 'get_recent_chats', 
                nonce: publicData.nonce 
            }, function(res) {
                if (res.success && res.data.length > 0) {
                    let html = '';
                    recentData = {};
                    res.data.forEach(item => {
                        recentData[item.id] = item.history;
                        html += `<div class="recent-chat-item" data-id="${escapeHtml(item.id)}">
                            <span class="chat-date">${escapeHtml(item.date)}</span>
                            <span class="chat-preview">${escapeHtml(item.preview)}</span>
                        </div>`;
                    });
                    $recentList.html(html);
                } else {
                    const noChatsMsg = publicData.i18n_no_recent || 'No recent chats found.';
                    $recentList.html(`<div class="recent-loading">${noChatsMsg}</div>`);
                }
            });
        });

        $('#back-to-chat').on('click', () => $recentView.hide().addClass('hidden'));

        $(document).on('click', '.recent-chat-item', function() {
            const id = $(this).data('id');
            const history = recentData[id];
            if (!history) return;

            document.cookie = "aica_chat_uuid=" + id + "; path=/; max-age=3600; SameSite=Lax";
            
            chat.$messages.empty().append(chat.$welcomeHtml.clone());

            history.forEach(msg => {
                const isBot = (msg.role === 'assistant' || msg.role === 'ai');
                const roleClass = isBot ? 'ai' : 'user';
                chat.$messages.append(`
                    <div class="msg ${roleClass}">
                        ${isBot ? `<div class="msg-avatar-container"><img src="${encodeURI(chat.botImg)}" class="msg-avatar" alt=""></div>` : ''}
                        <div class="msg-content-wrapper">
                            ${isBot ? `<div class="msg-bot-name">${escapeHtml(chat.botName)}</div>` : ''}
                            <div class="bubble">${msg.content}</div>
                        </div>
                    </div>`);
            });
            $recentView.hide().addClass('hidden');
            chat.scrollBottom();
        });
    });
})(jQuery);