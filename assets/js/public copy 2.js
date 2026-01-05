jQuery(document).ready(function($) {
    const $bubble      = $('#ai-chat-bubble');
    const $window      = $('#ai-chat-window');
    const $popup       = $('#ai-chat-welcome-popup');
    const $input       = $('#chat-input');
    const $messages    = $('#chat-messages');
    const $imgWrap     = $('.launcher-img-container');
    const $closeIcon   = $('.launcher-close-icon');

    const botImg       = $('.chat-header .header-logo').attr('src');
    const botName      = $('.chat-header .header-name').text();

    function triggerPopup() {
        if ($window.hasClass('hidden')) {
            $popup.removeClass('hidden').hide().fadeIn(500);
        }
    }
    setTimeout(triggerPopup, 3000);
    setInterval(triggerPopup, 600000);

    function toggleChat() {
        const isOpening = $window.hasClass('hidden');
        $window.toggleClass('hidden');
        $popup.fadeOut(200);
        
        if (isOpening) {
            $imgWrap.css('opacity', '0'); // Hide the icon/image
            $closeIcon.removeClass('hidden');
            $messages.scrollTop($messages[0].scrollHeight);
            setTimeout(() => $input.focus(), 100);
        } else {
            $imgWrap.css('opacity', '1'); // Show the icon/image
            $closeIcon.addClass('hidden');
        }
    }

    $bubble.on('click', toggleChat);
    $('#chat-close-btn').on('click', function(e) {
        e.stopPropagation();
        toggleChat();
    });
    $('#close-welcome-popup').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevents the click from triggering parent actions
        $('#ai-chat-welcome-popup').fadeOut(100);
    });

    function sendMessage() {
        const text = $input.val().trim();
        if (!text) return;
    
        // Sanitize user input for display to prevent XSS (treat user input as text)
        const safeUserText = $('<div>').text(text).html();
    
        // Append User Message
        $messages.append(`
            <div class="msg user">
                <div class="bubble">${safeUserText}</div>
            </div>
        `);
    
        $input.val('').css('height', 'auto');
        $messages.scrollTop($messages[0].scrollHeight);
    
        const typingId = 'typing-' + Date.now();
        
        // Append Typing Indicator
        $messages.append(`
            <div class="msg ai" id="${typingId}">
                <div class="msg-avatar-container">
                    <img src="${botImg}" class="msg-avatar">
                </div>
                <div class="msg-content-wrapper">
                    <div class="msg-bot-name">${botName}</div>
                    <div class="bubble typing-dots"><span></span><span></span><span></span></div>
                </div>
            </div>
        `);
        $messages.scrollTop($messages[0].scrollHeight);
        $.ajax({
            type: 'POST',
            url: chatData.ajax_url,
            data: {
                action: 'send_chat_message',
                message: text,
                chat_nonce: chatData.nonce,
            },
            success: function(response) {
                $('#' + typingId).remove();
                
                if (response.success) {
                    // Inside your AJAX Success function in public.js
                    const currentUnix = Math.floor(Date.now() / 1000);

                    $messages.append(`
                        <div class="msg ai">
                            <div class="msg-avatar-container">
                                <img src="${botImg}" class="msg-avatar">
                            </div>
                            <div class="msg-content-wrapper">
                                <div class="msg-bot-name">${botName}</div>
                                <div class="bubble">${response.data.reply}</div>
                                <div class="msg-footer">
                                    <span class="msg-time" data-time="${currentUnix}">just now</span>
                                    <div class="msg-actions">
                                        <button class="ai-action-btn copy-msg"><i class="fa fa-clone"></i></button>
                                        <button class="ai-action-btn regen-msg"><i class="fa fa-refresh"></i></button>
                                        <button class="ai-action-btn rate-msg like" data-rating="like"><i class="fa fa-thumbs-o-up"></i></button>
                                        <button class="ai-action-btn rate-msg dislike" data-rating="dislike"><i class="fa fa-thumbs-o-down"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    $messages.append(`
                        <div class="msg ai">
                             <div class="msg-avatar-container">
                                <img src="${botImg}" class="msg-avatar">
                            </div>
                            <div class="msg-content-wrapper">
                                <div class="msg-bot-name">${botName}</div>
                                <div class="bubble" style="color:#dc2626; background:#fee2e2; border: 1px solid #fecaca;">
                                    <strong>Error:</strong> ${response.data ? response.data.reply : 'Request failed.'}
                                </div>
                            </div>
                        </div>
                    `);
                }
                $messages.scrollTop($messages[0].scrollHeight);
            },
            error: function() {
                $('#' + typingId).remove();
                alert('Connection error. Please try again.');
            }
        });
    }

    $('#chat-send').on('click', sendMessage);
    $input.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { 
            e.preventDefault(); 
            sendMessage(); 
        }
    });
    $input.on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // 1. COPY MESSAGE
    $(document).on('click', '.copy-msg', function() {
        const text = $(this).closest('.msg-content-wrapper').find('.bubble').text();
        navigator.clipboard.writeText(text);
        const $icon = $(this).find('i');
        $icon.removeClass('fa-clone').addClass('fa-check');
        setTimeout(() => $icon.removeClass('fa-check').addClass('fa-clone'), 2000);
    });

    // 2. LIKE / DISLIKE
    $(document).on('click', '.rate-msg', function() {
        const $btn = $(this);
        const rating = $btn.data('rating');
        const response = $btn.closest('.msg-content-wrapper').find('.bubble').text();
        const query = $btn.closest('.msg.ai').prev('.msg.user').find('.bubble').text();

        $btn.parent().find('.rate-msg').removeClass('active-rating');
        $btn.addClass('active-rating');

        $.post(chatData.ajax_url, {
            action: 'submit_chat_feedback',
            nonce: chatData.nonce,
            query: query,
            response: response,
            rating: rating
        });
    });

    // 3. REGENERATE
    $(document).on('click', '.regen-msg', function() {
        const $aiMsgRow = $(this).closest('.msg.ai');
        const $bubble = $aiMsgRow.find('.bubble');
        const $footer = $aiMsgRow.find('.msg-footer');
        
        // 1. Find the question that came before this AI response
        const lastUserQuery = $aiMsgRow.prev('.msg.user').find('.bubble').text();
        
        if (lastUserQuery) {
            // 2. Clear current text and show typing dots in the EXISTING bubble
            $bubble.html('<div class="typing-dots"><span></span><span></span><span></span></div>');
            $footer.css('opacity', '0'); // Hide icons while generating

            // 3. Request new response
            $.ajax({
                type: 'POST',
                url: chatData.ajax_url,
                data: {
                    action: 'send_chat_message', // Reuse same logic
                    message: lastUserQuery,
                    is_regen: true, // Tell PHP we are replacing, not adding
                    chat_nonce: chatData.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        $bubble.html(response.data.reply);
                        $footer.css('opacity', '1');
                        // Update timestamp to "just now"
                        $aiMsgRow.find('.msg-time').text('just now').attr('data-time', Math.floor(Date.now() / 1000));
                    }
                }
            });
        }
    });

    function updateTimestamps() {
        $('.msg-time').each(function() {
            const sentTime = $(this).data('time');
            if (!sentTime) return;
    
            const now = Math.floor(Date.now() / 1000);
            const diff = now - sentTime;
    
            if (diff < 60) {
                $(this).text('just now');
            } else if (diff < 3600) {
                $(this).text(Math.floor(diff / 60) + 'm ago');
            } else {
                $(this).text(Math.floor(diff / 3600) + 'h ago');
            }
        });
    }
    setInterval(updateTimestamps, 30000); // Update every 30 seconds
    
    // Inside your AJAX Success function, when appending a new AI message, use this footer:
    const nowTimestamp = Math.floor(Date.now() / 1000);
    const footerHtml = `
        <div class="msg-footer">
            <span class="msg-time" data-time="${nowTimestamp}">just now</span>
            <div class="msg-actions">
                <button class="ai-action-btn copy-msg"><i class="fa fa-clone"></i></button>
                <button class="ai-action-btn regen-msg"><i class="fa fa-refresh"></i></button>
                <button class="ai-action-btn rate-msg like" data-rating="like"><i class="fa fa-thumbs-o-up"></i></button>
                <button class="ai-action-btn rate-msg dislike" data-rating="dislike"><i class="fa fa-thumbs-o-down"></i></button>
            </div>
        </div>
    `;

    // Inside jQuery(document).ready
    const $menuBtn = $('#chat-menu-btn');
    const $menuPopup = $('#chat-options-popup');

    // Toggle Menu
    $menuBtn.on('click', function(e) {
        e.stopPropagation();
        $menuPopup.toggleClass('hidden');
    });

    // Close menu when clicking anywhere else
    $(document).on('click', function() {
        $menuPopup.addClass('hidden');
    });


    // Inside jQuery(document).ready(function($) {

    const $confirmOverlay = $('#chat-confirm-overlay');
    const $confirmTitle   = $('#confirm-title');
    const $confirmExec    = $('#confirm-execute');
    let confirmMode       = ''; // 'clear' or 'end'

    // --- 1. OPENING THE OVERLAY ---

    // Click "Start a new chat"
    $('#menu-start-new').on('click', function() {
        confirmMode = 'clear';
        $menuPopup.addClass('hidden');
        $confirmTitle.text('Do you want to start a new chat?');
        $confirmExec.text('Yes, start new');
        $confirmOverlay.removeClass('hidden').hide().fadeIn(200);
    });

    // Click "End chat"
    $('#menu-end-chat').on('click', function() {
        confirmMode = 'end';
        $menuPopup.addClass('hidden');
        $confirmTitle.text('Do you want to end this chat?');
        $confirmExec.text('Yes, end chat');
        $confirmOverlay.removeClass('hidden').hide().fadeIn(200);
    });

    // --- 2. OVERLAY ACTIONS ---

    // Cancel Button
    $('#confirm-cancel').on('click', function() {
        $confirmOverlay.fadeOut(200);
    });

    // Yes Button (Execution)
    $confirmExec.on('click', function() {
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Processing...');

        const ajaxAction = (confirmMode === 'clear') ? 'clear_chat_history' : 'end_chat_session';

        $.post(chatData.ajax_url, {
            action: ajaxAction,
            nonce: chatData.nonce
        }, function(response) {
            $btn.prop('disabled', false).text(originalText);
            
            if (response.success) {
                if (confirmMode === 'clear') {
                    // Start New Chat: Just wipe the UI
                    $messages.find('.msg:not(.welcome-msg)').fadeOut(300, function() { $(this).remove(); });
                    $confirmOverlay.fadeOut(200);
                } else {
                    // End Chat: Wipe UI, show close message, then close
                    $confirmOverlay.fadeOut(200);
                    $messages.find('.msg:not(.welcome-msg)').remove();
                    toggleChat();
                }
            }
        });
    });

    const $recentView = $('#recent-chats-view');
    const $recentList = $('#recent-chats-list');
    let recentChatsData = {}; // Cache to store histories

    // 1. OPEN RECENT CHATS VIEW
    $('#menu-view-recent').on('click', function(e) {
        e.stopPropagation();
        $menuPopup.addClass('hidden');
        $recentView.removeClass('hidden').show();
        
        // Loader centered via the CSS you have
        $recentList.html('<div class="recent-loading"><i class="fa fa-spinner fa-spin fa-2x"></i><span>Loading history...</span></div>');
        
        $.post(chatData.ajax_url, {
            action: 'get_recent_chats',
            nonce: chatData.nonce
        }, function(response) {
            if (response.success && response.data.length > 0) {
                let html = '';
                recentChatsData = {}; // Clear cache
                response.data.forEach(chat => {
                    recentChatsData[chat.id] = chat.history; // Map ID to history array
                    html += `
                        <div class="recent-chat-item" data-id="${chat.id}">
                            <span class="chat-date"><i class="fa fa-calendar-o"></i> ${chat.date}</span>
                            <span class="chat-preview">${chat.preview}</span>
                        </div>`;
                });
                $recentList.html(html);
            } else {
                $recentList.html('<div class="recent-loading">No recent chats found.</div>');
            }
        });
    });

    // 2. BACK TO CHAT
    $('#back-to-chat').on('click', function() {
        $recentView.hide().addClass('hidden');
    });

    $(document).on('click', '.recent-chat-item', function(e) {
        e.preventDefault();
        const sessionId = $(this).data('id');
        const history = recentChatsData[sessionId];
        
        if (!history) return;
    
        // IMPORTANT: To "open" the chat and continue it, 
        // we should set the cookie to this session ID.
        document.cookie = "aica_chat_uuid=" + sessionId + "; path=/; max-age=3600";
    
        $messages.empty();
        
        // Build the UI
        history.forEach(msg => {
            const isBot = (msg.role === 'assistant' || msg.role === 'ai');
            const roleClass = isBot ? 'ai' : 'user';
            const timeVal = msg.time || Math.floor(Date.now() / 1000);
            
            let msgHtml = `
                <div class="msg ${roleClass}">
                    ${isBot ? `<div class="msg-avatar-container"><img src="${botImg}" class="msg-avatar"></div>` : ''}
                    <div class="msg-content-wrapper">
                        ${isBot ? `<div class="msg-bot-name">${botName}</div>` : ''}
                        <div class="bubble">${msg.content}</div>
                    </div>
                </div>`;
            $messages.append(msgHtml);
        });
    
        $recentView.hide().addClass('hidden');
        $messages.scrollTop($messages[0].scrollHeight);
    });

});