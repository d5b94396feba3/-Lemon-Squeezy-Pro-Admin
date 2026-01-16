var AICA_Chat = {
    $window: null,
    $messages: null,
    $input: null,
    botImg: '',
    botName: '',
    $welcomeHtml: null,

    init: function($) {
        this.$window = jQuery('#ai-chat-window');
        this.$messages = jQuery('#chat-messages');
        this.$input = jQuery('#chat-input');
        
        this.$window.css('display', 'none').addClass('hidden');

        this.botImg = jQuery('.header-logo').attr('src');
        this.botName = jQuery('.header-name').text();
        this.$welcomeHtml = jQuery('.welcome-msg').first().clone();

        this.bindCoreEvents($);
        
        setTimeout(() => {
            this.triggerPopup();
        }, 3000);
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

        if ($window.hasClass('hidden')) {
            $popup.fadeOut(200);
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

    triggerPopup: function() {
        const $popup = jQuery('#ai-chat-welcome-popup');
        if (this.$window.hasClass('hidden')) {
            $popup.hide().removeClass('hidden').css('display', 'flex').hide().fadeIn(500);
        }
    },

    scrollBottom: function() {
        if (this.$messages.length) {
            this.$messages.scrollTop(this.$messages[0].scrollHeight);
        }
    }
};

jQuery(document).ready(function($) {
    AICA_Chat.init($);
});