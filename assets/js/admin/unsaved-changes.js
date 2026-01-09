/**
 * AICA Plugin - Unsaved Changes Monitoring
 * Handles the sticky save bar and change detection.
 */
jQuery(document).ready(function($) {
    const $unsavedBar = $('#aica-unsaved-bar');
    let hasChanges = false;

    // --- Core Functions ---

    function showUnsavedNotice() {
        if (!hasChanges) {
            hasChanges = true;
            $unsavedBar.css('display', 'flex').hide().fadeIn(300);
            // Highlight the Save button in the main form too
            $('.submit .button-primary').css('box-shadow', '0 0 0 3px rgba(59, 130, 246, 0.4)');
        }
    }

    // --- Listeners ---

    // 1. Detect text and selection changes
    $(document).on('input change', '.professional-input, select, input[type="color"], textarea', function() {
        $(this).addClass('field-changed');
        showUnsavedNotice();
    });

    // 2. Detect file uploads (Watching the hidden ID inputs)
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

    // 3. Detect "Remove" and "Reset" buttons
    $('.button-link-delete, #reset_primary_color, #ai_agent_remove_btn, #ai_agent_file_remove_btn').on('click', function() {
        showUnsavedNotice();
    });

    // 4. Global listener for AI Prompt Generation button
    $('#btn-generate-ai-prompt').on('click', function() {
        setTimeout(function() {
            showUnsavedNotice();
            $('#ai_agent_system_prompt_area').addClass('field-changed');
        }, 1000); 
    });

    // --- Action ---

    // Sticky Save button trigger
    $('#aica-sticky-save-trigger').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        
        // 1. Find the visible form (the one in the active tab)
        const $visibleForm = $('form[action="options.php"]:visible');
        
        if ($visibleForm.length > 0) {
            $btn.text('Saving...').prop('disabled', true);
            
            const nativeForm = $visibleForm[0];
            if (typeof nativeForm.requestSubmit === 'function') {
                nativeForm.requestSubmit();
            } else {
                nativeForm.submit();
            }
        } else {
            console.error('AICA: No visible settings form found to submit.');
            alert('Error: Could not find the settings form. Please try the save button at the bottom.');
        }
    });
});