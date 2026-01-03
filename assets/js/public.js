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
                    // Ensure the AI response is rendered as HTML
                    $messages.append(`
                        <div class="msg ai">
                            <div class="msg-avatar-container">
                                <img src="${botImg}" class="msg-avatar">
                            </div>
                            <div class="msg-content-wrapper">
                                <div class="msg-bot-name">${botName}</div>
                                <div class="bubble">${response.data.reply}</div>
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
});