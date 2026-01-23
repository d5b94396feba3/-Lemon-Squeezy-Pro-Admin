var wppp_Chat = {
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
        
        const popupDelay = (typeof wppp_public_data !== 'undefined' && wppp_public_data.popup_delay) ? parseInt(wppp_public_data.popup_delay) : 3000;
        
        this.popupTimer = setTimeout(() => {
            this.triggerPopup();
        }, popupDelay);

        setInterval(() => {
            this.updateTimestamps();
        }, 30000); 
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
                wppp_Chat.scrollBottom();
            });
            setTimeout(() => this.$input.focus(), 150);
        } else {
            $window.stop(true, true).fadeOut(300, function() {
                jQuery(this).addClass('hidden');
            });
        }
    },

    scrollBottom: function() {
        if (this.$messages.length) {
            this.$messages.animate({ scrollTop: this.$messages[0].scrollHeight }, 300);
        }
    },

    triggerPopup: function() {
        const $popup = jQuery('#ai-chat-welcome-popup');
        if (this.$window.length && this.$window.hasClass('hidden')) {
            $popup.hide().removeClass('hidden').fadeIn(500);
        }
    },

    updateTimestamps: function() {
        const now = Math.floor(Date.now() / 1000);
        const i18n = (typeof wppp_public_data !== 'undefined') ? wppp_public_data : {};

        jQuery('.msg-time').each(function() {
            const sentTime = parseInt(jQuery(this).attr('data-time'));
            if (!sentTime || isNaN(sentTime)) return;

            const diff = now - sentTime;
            if (diff < 60) {
                jQuery(this).text(i18n.just_now || 'just now');
            } else if (diff < 3600) {
                const mins = Math.floor(diff / 60);
                const label = i18n.m_ago || 'm ago';
                jQuery(this).text(mins + label);
            } else {
                const hrs = Math.floor(diff / 3600);
                const label = i18n.h_ago || 'h ago';
                jQuery(this).text(hrs + label);
            }
        });
    }
};

jQuery(document).ready(function($) {
    if (jQuery('#ai-chat-wrapper').length) {
        wppp_Chat.init($);
    }
});