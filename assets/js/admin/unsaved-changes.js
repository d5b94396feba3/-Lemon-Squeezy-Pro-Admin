jQuery(document).ready(function($) {

    const i18n = (typeof aica_admin_data !== 'undefined') ? aica_admin_data : {};
    
    const $unsavedBar = $('#aica-unsaved-bar');
    let hasChanges = false;

    function showUnsavedNotice() {
        if (!hasChanges) {
            hasChanges = true;
            $unsavedBar.css('display', 'flex').hide().fadeIn(300);
            $('.submit .button-primary').css('box-shadow', '0 0 0 3px rgba(59, 130, 246, 0.4)');
        }
    }

    $('.aica-theme-card').on('click', function() {
        showUnsavedNotice();
        $(this).find('.theme-card-inner').addClass('field-changed');
    });

    $(document).on('input change', '.professional-input, select, input[type="color"], textarea[name="ai_agent_system_prompt"]', function() {
        $(this).addClass('field-changed');
        showUnsavedNotice();
    });

    const fileObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === "attributes" && mutation.attributeName === "value") {
                showUnsavedNotice();
            }
        });
    });

    const logoInput = document.getElementById('ai_agent_logo_id');
    const kbFileInput = document.getElementById('ai_agent_knowledge_file_id');

    if (logoInput) fileObserver.observe(logoInput, { attributes: true });
    if (kbFileInput) fileObserver.observe(kbFileInput, { attributes: true });

    $('.button-link-delete, #reset_primary_color, #ai_agent_remove_btn, #ai_agent_file_remove_btn').on('click', function() {
        showUnsavedNotice();
    });

    $('#btn-generate-ai-prompt').on('click', function() {
        setTimeout(function() {
            showUnsavedNotice();
            $('#ai_agent_system_prompt_area').addClass('field-changed');
        }, 1000); 
    });

    $('#aica-sticky-save-trigger').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $visibleForm = $('form[action="options.php"]:visible');
        
        if ($visibleForm.length > 0) {
            const savingLabel = i18n.i18n_saving || 'Saving...';
            $btn.text(savingLabel).prop('disabled', true);
            
            const nativeForm = $visibleForm[0];
            if (typeof nativeForm.requestSubmit === 'function') {
                nativeForm.requestSubmit();
            } else {
                nativeForm.submit();
            }
        } else {
            const errorMsg = i18n.i18n_form_error || 'Error: Could not find the settings form. Please try the save button at the bottom.';
            console.error('AICA: No visible settings form found to submit.');
            alert(errorMsg);
        }
    });
});