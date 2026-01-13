jQuery(document).ready(function($) {
    function syncAdminPreview() {
        const name = $('input[name="ai_agent_display_name"]').length ? 
                     ($('input[name="ai_agent_display_name"]').val() || 'AI Assistant') : 
                     window.aica_settings.name;

        const welcomeText = $('textarea[name="ai_agent_welcome_msg"]').length ? 
                            ($('textarea[name="ai_agent_welcome_msg"]').val() || '') : 
                            window.aica_settings.welcome;

        const placeholder = $('input[name="ai_agent_placeholder"]').length ? 
                            ($('input[name="ai_agent_placeholder"]').val() || 'Message...') : 
                            window.aica_settings.placeholder;

        const brandColor = $('#ai_agent_primary_color_input').val();
        
        let avatarUrl = $('#ai_agent_logo_preview').attr('src');
        const iconSelect = $('select[name="ai_agent_bubble_icon"]').val();
        const themeMode = $('input[name="ai_agent_theme_mode"]:checked').val() || 'light';

        const $preview = $('#aica-admin-preview-container');
        const $wrapper = $('#ai-chat-wrapper');

        $wrapper.removeClass('theme-light theme-dark').addClass('theme-' + themeMode);

        $preview.find('.header-name, .msg-bot-name').text(name);
        $preview.find('#chat-input').attr('placeholder', placeholder);
        
        const lines = welcomeText.split('\n').filter(l => l.trim() !== "");
        let welcomeHtml = '';
        lines.forEach((line, index) => {
            welcomeHtml += `
                <div class="msg ai welcome-msg">
                    <div class="msg-avatar-container">
                        ${index === 0 ? `<img src="${avatarUrl}" class="msg-avatar">` : ''}
                    </div>
                    <div class="msg-content-wrapper">
                        ${index === 0 ? `<div class="msg-bot-name">${name}</div>` : ''}
                        <div class="bubble">${line}</div>
                    </div>
                </div>`;
        });
        
        $preview.find('.welcome-msg').remove(); 
        $preview.find('#chat-messages').prepend(welcomeHtml);

        $preview.find('#ai-chat-bubble, .user .bubble').css('background-color', brandColor);
        $preview.find('.header-logo, .msg-avatar').attr('src', avatarUrl);

        const $iconContainer = $preview.find('.launcher-img-container');
        if (iconSelect === 'logo') {
            $iconContainer.removeClass('is-default-icon').html(`<img src="${avatarUrl}" class="launcher-img">`);
        } else {
            $iconContainer.addClass('is-default-icon').html('<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>');
        }
    }

    $(document).on('change', 'input[name="ai_agent_theme_mode"]', function() {
        syncAdminPreview();
    });

    $(document).on('input change', '.admin-preview-trigger, #ai_agent_primary_color_input, select[name="ai_agent_bubble_icon"]', function() {
        syncAdminPreview();
    });
});