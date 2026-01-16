var AICA_Chat = {
    $window: null,
    $messages: null,
    $input: null,
    botImg: '',
    botName: '',
    $welcomeHtml: null,
    popupTimer: null,

    init: function($) {
        this.$window = jQuery('#ai-chat-window');
        this.$messages = jQuery('#chat-messages');
        this.$input = jQuery('#chat-input');
        
        this.botImg = jQuery('.chat-header .header-logo').attr('src');
        this.botName = jQuery('.chat-header .header-name').text();
        
        this.$welcomeHtml = jQuery('.welcome-msg').clone();

        this.bindCoreEvents($);
        
        this.updateTimestamps();
        
        this.popupTimer = setTimeout(() => {
            this.triggerPopup();
        }, 3000);

        setInterval(() => {
            this.updateTimestamps();
        }, 20000);
    },

    bindCoreEvents: function($) {
        const self = this;

        jQuery(document).on('click', '#ai-chat-bubble', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self.toggleChat();
        });

        jQuery(document).on('click', '#chat-close-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self.toggleChat();
        });
        
        jQuery(document).on('click', '.popup-bubble-item', function(e) {
            if (jQuery(e.target).is('#close-welcome-popup')) {
                e.preventDefault();
                e.stopPropagation();
                jQuery('#ai-chat-welcome-popup').fadeOut(200);
                return;
            }
            self.toggleChat();
        });
    },

    toggleChat: function() {
        const $popup = jQuery('#ai-chat-welcome-popup');
        const $window = jQuery('#ai-chat-window');

        if (this.popupTimer) {
            clearTimeout(this.popupTimer);
            this.popupTimer = null;
        }

        if ($window.hasClass('hidden')) {
            $popup.stop(true, true).fadeOut(200);
            $window.stop(true, true).fadeIn(300, function() {
                jQuery(this).removeClass('hidden').css('display', 'flex');
            });
            this.scrollBottom();
            setTimeout(() => this.$input.focus(), 150);
        } else {
            $window.stop(true, true).fadeOut(300, function() {
                jQuery(this).addClass('hidden');
            });
        }
    },

    scrollBottom: function() {
        if (this.$messages.length) {
            this.$messages.scrollTop(this.$messages[0].scrollHeight);
        }
    },

    triggerPopup: function() {
        const $popup = jQuery('#ai-chat-welcome-popup');
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