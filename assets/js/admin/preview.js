jQuery(document).ready(function($) {

    function syncAdminPreview() {
        const nameInput = $('input[name="ai_agent_display_name"]');
        if (nameInput.length) localStorage.setItem('aica_preview_name', nameInput.val());
        const name = localStorage.getItem('aica_preview_name') || 'AI Assistant';

        const welcomeInput = $('textarea[name="ai_agent_welcome_msg"]');
        if (welcomeInput.length) localStorage.setItem('aica_preview_welcome', welcomeInput.val());
        const welcomeText = localStorage.getItem('aica_preview_welcome') || '';

        const placeholderInput = $('input[name="ai_agent_placeholder"]');
        if (placeholderInput.length) localStorage.setItem('aica_preview_placeholder', placeholderInput.val());
        const placeholder = localStorage.getItem('aica_preview_placeholder') || 'Message...';

        const brandColor = $('#ai_agent_primary_color_input').val() || '#000000';
        
        let avatarUrl = $('#ai_agent_logo_preview').attr('src');
        if (!avatarUrl || avatarUrl.includes('default-avater.svg')) {
            avatarUrl = '/wp-content/plugins/ai-chat-agent/assets/images/default-avater.svg'; 
        }
        
        const iconSelect = $('select[name="ai_agent_bubble_icon"]').val();

        const themeInput = $('input[name="ai_agent_theme_mode"]:checked');
        if (themeInput.length) localStorage.setItem('aica_preview_theme', themeInput.val());
        const themeMode = localStorage.getItem('aica_preview_theme') || 'light';

        const $preview = $('#aica-admin-preview-container');
        const $wrapper = $preview.find('#ai-chat-wrapper'); 

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
        const $card = $(this).closest('.aica-theme-card');
        $('.aica-theme-card').removeClass('is-active').find('.theme-card-inner').css('border-color', '#e2e8f0');
        $card.addClass('is-active').find('.theme-card-inner').css('border-color', '#3b82f6');
        syncAdminPreview();
    });

    $(document).on('input change', '.admin-preview-trigger, #ai_agent_primary_color_input, select[name="ai_agent_bubble_icon"]', function() {
        syncAdminPreview();
    });

    $('#ai_agent_upload_btn').on('click', function() {
        var custom_uploader = wp.media({
            title: 'Select Avatar',
            button: { text: 'Use this image' },
            multiple: false
        }).on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#ai_agent_logo_preview').attr('src', attachment.url);
            $('#ai_agent_logo_id').val(attachment.id);
            $('#ai_agent_remove_btn').show();
            syncAdminPreview();
        }).open();
    });

    $('#ai_agent_remove_btn').on('click', function() {
        $('#ai_agent_logo_preview').attr('src', '');
        syncAdminPreview();
    });


});