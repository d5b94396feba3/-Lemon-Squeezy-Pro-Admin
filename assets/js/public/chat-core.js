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
        
        this.botImg = $('.chat-header .header-logo').attr('src');
        this.botName = $('.chat-header .header-name').text();
        
        this.$welcomeHtml = $('.welcome-msg').clone();

        this.bindCoreEvents($);
        
        this.updateTimestamps();
        
        setTimeout(() => {
            this.triggerPopup();
        }, 3000);

        setInterval(() => {
            this.updateTimestamps();
        }, 20000);
    },

    bindCoreEvents: function($) {
        $('#ai-chat-bubble').on('click', () => this.toggleChat());
        $('#chat-close-btn').on('click', (e) => { e.stopPropagation(); this.toggleChat(); });
        
        $(document).on('click', '#close-welcome-popup', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#ai-chat-welcome-popup').fadeOut(200);
        });
    },

    toggleChat: function() {
        this.$window.toggleClass('hidden');
        
        $('#ai-chat-welcome-popup').fadeOut(200);
        
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