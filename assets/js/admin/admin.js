jQuery(document).ready(function($) {
    // admin.js
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

    $('.action-delete-chat').on('click', function(e) {
        e.preventDefault();
        if (!confirm('Delete this conversation forever?')) return;
        
        const btn = $(this);
        const logId = btn.data('id');
        
        btn.prop('disabled', true).text('Deleting...');

        $.post(aica_admin_data.ajax_url, {
            action: 'delete_chat_log',
            id: logId,
            nonce: aica_admin_data.nonce
        }, function(response) {
            if (response.success) {
                btn.closest('tr').fadeOut(400, function() { 
                    $(this).remove(); 
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

    $('form[action="options.php"]').on('submit', function() {
        const submitBtn = $(this).find('input[type="submit"]');
        submitBtn.val('Saving...').prop('disabled', true);
    });

    const $colorInput = $('#ai_agent_primary_color_input');
    const $colorHex   = $('#ai_agent_primary_color_hex');
    const $colorReset = $('#reset_primary_color');

    $colorInput.on('input', function() {
        const selectedColor = $(this).val().toUpperCase();
        $colorHex.text(selectedColor);
        $('#ai_agent_logo_preview').css('border-color', selectedColor);
    });

    $colorReset.on('click', function(e) {
        e.preventDefault();
        const defaultColor = '#000000';
        $colorInput.val(defaultColor);
        $colorHex.text(defaultColor);
        $('#ai_agent_logo_preview').css('border-color', '#f3f4f6');
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
            url: aica_admin_data.ajax_url,
            data: {
                action: 'export_chat_log',
                id: logId,
                nonce: aica_admin_data.nonce
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

    $('.action-summarize-chat').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const logId = $btn.data('id');
        const $icon = $btn.find('.dashicons');

        $btn.prop('disabled', true);
        $icon.addClass('spin');
        
        $('#chat-modal-title').text('AI Intelligence Summary');
        $('#chat-modal-content').hide().html('');
        $('#chat-modal-loader').show();
        $('#chat-modal').fadeIn(200);

        $.post(aica_admin_data.ajax_url, {
            action: 'get_chat_summary',
            id: logId,
            nonce: aica_admin_data.nonce
        }, function(response) {
            $btn.prop('disabled', false);
            $icon.removeClass('spin');
            
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

    function toggleLLMSettings() {
        var selected = $('#llm_model_selector').val();
        $('.llm-settings-group').hide();
        $('#settings-group-' + selected).show();
    }
    $('#llm_model_selector').on('change', toggleLLMSettings);
    toggleLLMSettings();
    
});