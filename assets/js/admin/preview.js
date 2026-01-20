jQuery(document).ready(function($) {

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    window.syncAdminPreview = function() {
        const nameInput = $('input[name="ai_agent_display_name"]');
        const name = nameInput.length ? nameInput.val() : (localStorage.getItem('aica_preview_name') || 'AI Assistant');
        if (nameInput.length) localStorage.setItem('aica_preview_name', name);

        const welcomeInput = $('textarea[name="ai_agent_welcome_msg"]');
        const welcomeText = welcomeInput.length ? welcomeInput.val() : (localStorage.getItem('aica_preview_welcome') || '');
        if (welcomeInput.length) localStorage.setItem('aica_preview_welcome', welcomeText);

        const placeholderInput = $('input[name="ai_agent_placeholder"]');
        const placeholder = placeholderInput.length ? placeholderInput.val() : (localStorage.getItem('aica_preview_placeholder') || 'Message...');
        if (placeholderInput.length) localStorage.setItem('aica_preview_placeholder', placeholder);

        const brandColor = $('#ai_agent_primary_color_input').val() || '#000000';
        
        let avatarUrl = $('#ai_agent_logo_preview').attr('src');
        if (!avatarUrl || avatarUrl === '' || avatarUrl.includes('default-avater.svg')) {
            avatarUrl = (typeof aica_admin_assets !== 'undefined') ? aica_admin_assets.default_avatar : '';
        }
        
        const iconSelect = $('select[name="ai_agent_bubble_icon"]').val();

        const themeInput = $('input[name="ai_agent_theme_mode"]:checked');
        const themeMode = themeInput.length ? themeInput.val() : (localStorage.getItem('aica_preview_theme') || 'light');
        if (themeInput.length) localStorage.setItem('aica_preview_theme', themeMode);

        const $preview = $('#aica-admin-preview-container');
        const $wrapper = $preview.find('#ai-chat-wrapper'); 

        if (!$wrapper.length) return;

        $wrapper.removeClass('theme-light theme-dark').addClass('theme-' + themeMode);

        $preview.find('.header-name, .msg-bot-name').text(name);
        $preview.find('#chat-input').attr('placeholder', placeholder);
        
        const lines = welcomeText.split('\n').filter(l => l.trim() !== "");
        let welcomeHtml = '';
        
        lines.forEach((line, index) => {
            const safeLine = escapeHtml(line);
            const safeName = escapeHtml(name);
            const safeAvatar = encodeURI(avatarUrl);

            welcomeHtml += `
                <div class="msg ai welcome-msg">
                    <div class="msg-avatar-container">
                        ${index === 0 ? `<img src="${safeAvatar}" class="msg-avatar" alt="">` : ''}
                    </div>
                    <div class="msg-content-wrapper">
                        ${index === 0 ? `<div class="msg-bot-name">${safeName}</div>` : ''}
                        <div class="bubble">${safeLine}</div>
                    </div>
                </div>`;
        });
        
        $preview.find('.welcome-msg').remove();
        $preview.find('#chat-messages').prepend(welcomeHtml);

        $preview.find('#ai-chat-bubble, .user .bubble').css('background-color', brandColor);
        $preview.find('.header-logo, .msg-avatar').attr('src', avatarUrl);

        const $iconContainer = $preview.find('.launcher-img-container');
        if (iconSelect === 'logo') {
            $iconContainer.removeClass('is-default-icon').html(`<img src="${encodeURI(avatarUrl)}" class="launcher-img" alt="">`);
        } else {
            $iconContainer.addClass('is-default-icon').html('<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>');
        }
    };

    $(document).on('input change', '.admin-preview-trigger, #ai_agent_primary_color_input, select[name="ai_agent_bubble_icon"], input[name="ai_agent_theme_mode"]', function() {
        window.syncAdminPreview();
    });

    $(document).on('change', 'input[name="ai_agent_theme_mode"]', function() {
        const $card = $(this).closest('.aica-theme-card');
        $('.aica-theme-card').removeClass('is-active');
        $card.addClass('is-active');
        window.syncAdminPreview();
    });

    window.syncAdminPreview();
});