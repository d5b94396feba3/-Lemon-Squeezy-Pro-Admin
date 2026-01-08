jQuery(document).ready(function($) {
    // 1. Handle View Transcript Modal
    $('.view-chat-log').on('click', function() {
        const history = $(this).data('history');
        let html = '';
        
        if (history) {
            const historyObj = (typeof history === 'string') ? JSON.parse(history) : history;
            historyObj.forEach(msg => {
                const isUser = msg.role === 'user';
                const label = isUser ? 'Visitor' : 'AI Agent';
                const style = isUser ? 'background:#f0f7ff; border-left:4px solid #2271b1;' : 'background:#f0fdf4; border-left:4px solid #16a34a;';
                
                html += `<div style="margin-bottom:15px; padding:12px; border-radius:8px; ${style}">
                    <strong>${label}:</strong>
                    <div style="margin-top:5px; white-space: pre-wrap;">${msg.content}</div>
                </div>`;
            });
        }
        
        $('#chat-modal-content').html(html);
        $('#chat-modal').fadeIn(200);
    });

    // 2. Handle Delete Chat History (Fixed)
    $('.action-delete-chat').on('click', function(e) {
        e.preventDefault();
        if (!confirm('Delete this conversation forever?')) return;
        
        const btn = $(this);
        const logId = btn.data('id');
        
        btn.prop('disabled', true).text('Deleting...');

        $.post(adminProposalData.ajax_url, {
            action: 'delete_chat_log',
            id: logId,
            nonce: adminProposalData.nonce
        }, function(response) {
            if (response.success) {
                btn.closest('tr').fadeOut(400, function() { 
                    $(this).remove(); 
                    // Update the counter in the UI
                    const countEl = $('.admin-stat-card h3').first();
                    countEl.text(parseInt(countEl.text()) - 1);
                });
            } else {
                alert('Error: ' + (response.data || 'Unknown error occurred'));
                btn.prop('disabled', false).text('Delete');
            }
        }).fail(function() {
            alert('Server error. Please check your connection.');
            btn.prop('disabled', false).text('Delete');
        });
    });

    // 3. Fix Grok Settings Form Submit
    // This allows standard WordPress options saving while preventing double-submission
    $('form[action="options.php"]').on('submit', function() {
        const submitBtn = $(this).find('input[type="submit"]');
        submitBtn.val('Saving...').prop('disabled', true);
    });

    // 4. Real-time Color Picker Update & Reset
    const $colorInput = $('#ai_agent_primary_color_input');
    const $colorHex   = $('#ai_agent_primary_color_hex');
    const $colorReset = $('#reset_primary_color');

    // Update hex text in real-time as user picks color
    $colorInput.on('input', function() {
        const selectedColor = $(this).val().toUpperCase();
        $colorHex.text(selectedColor);
        // Sync the preview border with the primary brand color
        $('#ai_agent_logo_preview').css('border-color', selectedColor);
    });

    // Reset functionality
    $colorReset.on('click', function(e) {
        e.preventDefault();
        const defaultColor = '#000000';
        $colorInput.val(defaultColor);
        $colorHex.text(defaultColor);
        $('#ai_agent_logo_preview').css('border-color', '#f3f4f6');
    });

    // WordPress Media Uploader
    var mediaUploader;
    $('#ai_agent_upload_btn').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) { mediaUploader.open(); return; }

        mediaUploader = wp.media({
            title: 'Select Agent Logo',
            button: { text: 'Use this Image' },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#ai_agent_logo_id').val(attachment.id);
            $('#ai_agent_logo_preview').attr('src', attachment.url).show();
            $('#ai_agent_remove_btn').show();
        });
        mediaUploader.open();
    });

    $('#ai_agent_remove_btn').on('click', function() {
        $('#ai_agent_logo_id').val('');
        $('#ai_agent_logo_preview').hide();
        $(this).hide();
    });

    $('#reset-ai-settings').on('click', function(e) {
        if (confirm('Are you sure? This will revert all Prompt, UI, and Notification settings to factory defaults. Your API Key will remain safe.')) {
            window.location.href = window.location.href + '&action=reset_ai_agent_settings&_wpnonce=<?php echo wp_create_nonce("reset_ai_settings"); ?>';
        }
    });

    $(document).on('click', '.action-export-chat', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const logId = $btn.attr('data-id');
        const $icon = $btn.find('.dashicons');
    
        $btn.prop('disabled', true).css('opacity', '0.5');
        $icon.addClass('spin');
    
        $.ajax({
            type: 'POST',
            url: adminProposalData.ajax_url,
            data: {
                action: 'export_chat_log',
                id: logId,
                nonce: adminProposalData.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false).css('opacity', '1');
                $icon.removeClass('spin');
    
                if (response.success) {
                    const blob = new Blob([response.data.csv_data], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).css('opacity', '1');
                $icon.removeClass('spin');
                console.error('CRITICAL ERROR LOG:', xhr.responseText);
                alert('A system error occurred. Check the browser console.');
            }
        });
    });

    // Knowledge File Uploader
    var fileUploader;
    $('#ai_agent_file_upload_btn').on('click', function(e) {
        e.preventDefault();
        if (fileUploader) { fileUploader.open(); return; }

        fileUploader = wp.media({
            title: 'Upload Knowledge Base File',
            button: { text: 'Use this File' },
            multiple: false
        });

        fileUploader.on('select', function() {
            var attachment = fileUploader.state().get('selection').first().toJSON();
            $('#ai_agent_knowledge_file_id').val(attachment.id);
            $('#file-name-display').text(attachment.filename);
            $('#ai_agent_file_remove_btn').show();
            $('#ai_agent_file_upload_btn').text('Replace File');
        });
        fileUploader.open();
    });

    $('#ai_agent_file_remove_btn').on('click', function() {
        $('#ai_agent_knowledge_file_id').val('');
        $('#file-name-display').text('');
        $(this).hide();
        $('#ai_agent_file_upload_btn').text('Upload Knowledge File');
    });

    // Handle AI Summarization Popup
    $('.action-summarize-chat').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const logId = $btn.data('id');
        const $icon = $btn.find('.dashicons');

        // 1. Prepare UI
        $btn.prop('disabled', true);
        $icon.addClass('spin');
        
        // 2. Open Modal & Show Loader
        $('#chat-modal-title').text('AI Intelligence Summary');
        $('#chat-modal-content').hide().html(''); // Clear old content
        $('#chat-modal-loader').show(); // Show professional loader
        $('#chat-modal').fadeIn(200);

        // 3. Request Summary from Server
        $.post(adminProposalData.ajax_url, {
            action: 'get_chat_summary',
            id: logId,
            nonce: adminProposalData.nonce
        }, function(response) {
            // Restore button state
            $btn.prop('disabled', false);
            $icon.removeClass('spin');
            
            // Hide Loader
            $('#chat-modal-loader').hide();

            if (response.success) {
                const summaryHtml = `
                    <div class="ai-summary-wrapper" style="background: #fffbeb; border: 1px solid #fef3c7; padding: 25px; border-radius: 8px;">
                        <div style="color: #4b5563; line-height: 1.7; font-size: 15px;">
                            ${response.data}
                        </div>
                    </div>
                `;
                $('#chat-modal-content').html(summaryHtml).fadeIn(300);
            } else {
                $('#chat-modal-content').html(`
                    <div style="padding: 20px; color: #dc3232; background: #fef2f2; border-radius: 8px; border: 1px solid #fee2e2;">
                        <strong>Error:</strong> ${response.data}
                    </div>
                `).show();
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $icon.removeClass('spin');
            $('#chat-modal-loader').hide();
            alert('Server error while generating summary.');
        });
    });
    
});