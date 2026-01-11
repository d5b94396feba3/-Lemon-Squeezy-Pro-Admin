var AICA_Chat = {
    $window: null,
    $messages: null,
    $input: null,
    botImg: '',
    botName: '',
    $welcomeHtml: null,

    init: function($) {
        this.$window = $('#ai-chat-window');
        this.$messages = $('#chat-messages');
        this.$input = $('#chat-input');
        
        // Use standard jQuery to find these
        this.botImg = $('.chat-header .header-logo').attr('src');
        this.botName = $('.chat-header .header-name').text();
        
        // Clone welcome messages for later use
        this.$welcomeHtml = $('.welcome-msg').clone();

        this.bindCoreEvents($);
        
        // Run immediately
        this.updateTimestamps();
        
        // POPUP TRIGGER: Ensure it's not hidden by class and by display
        setTimeout(() => {
            this.triggerPopup();
        }, 3000);

        // Global Heartbeat
        setInterval(() => {
            this.updateTimestamps();
        }, 20000);
    },

    bindCoreEvents: function($) {
        $('#ai-chat-bubble').on('click', () => this.toggleChat());
        $('#chat-close-btn').on('click', (e) => { e.stopPropagation(); this.toggleChat(); });
        
        // Handle popup close
        $(document).on('click', '#close-welcome-popup', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#ai-chat-welcome-popup').fadeOut(200);
        });
    },

    toggleChat: function() {
        // 1. Toggle the window
        this.$window.toggleClass('hidden');
        
        // 2. Hide welcome popup if it exists
        $('#ai-chat-welcome-popup').fadeOut(200);
        
        // 3. Logic for opening
        if (!this.$window.hasClass('hidden')) {
            this.scrollBottom();
            setTimeout(() => this.$input.focus(), 100);
        }
    },

    scrollBottom: function() {
        if (this.$messages.length) {
            this.$messages.scrollTop(this.$messages[0].scrollHeight);
        }
    },

    triggerPopup: function() {
        const $popup = $('#ai-chat-welcome-popup');
        // If chat is closed, show the welcome popup
        if (this.$window.hasClass('hidden')) {
            $popup.hide().removeClass('hidden').fadeIn(500);
        }
    },

    updateTimestamps: function() {
        const now = Math.floor(Date.now() / 1000);
        jQuery('.msg-time').each(function() {
            const sentTime = parseInt(jQuery(this).attr('data-time'));
            if (!sentTime || isNaN(sentTime)) return;

            const diff = now - sentTime;
            if (diff < 60) {
                jQuery(this).text('just now');
            } else if (diff < 3600) {
                jQuery(this).text(Math.floor(diff / 60) + 'm ago');
            } else {
                jQuery(this).text(Math.floor(diff / 3600) + 'h ago');
            }
        });
    }
};

jQuery(document).ready(function($) {
    AICA_Chat.init($);
});