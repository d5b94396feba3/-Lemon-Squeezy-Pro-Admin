jQuery(document).ready(function($) {
    var frame_cache = {};

    function open_media_frame(button_id, options, callback) {
        $(button_id).off('click').on('click', function(e) {
            e.preventDefault();

            if (frame_cache[button_id]) {
                frame_cache[button_id].open();
                return;
            }

            frame_cache[button_id] = wp.media({
                title: options.title || 'Select Media',
                button: { text: options.button_text || 'Use this Media' },
                multiple: false
            });

            frame_cache[button_id].on('select', function() {
                var attachment = frame_cache[button_id].state().get('selection').first().toJSON();
                callback(attachment);
            });

            frame_cache[button_id].open();
        });
    }

    open_media_frame('#ai_agent_upload_btn', {
        title: 'Select Avatar',
        button_text: 'Use this Image'
    }, function(attachment) {
        $('#ai_agent_logo_id').val(attachment.id);
        $('#ai_agent_logo_preview').attr('src', attachment.url).show();
        $('#ai_agent_remove_btn').show();
        if (typeof syncAdminPreview === 'function') {
            window.syncAdminPreview();
        }
    });

    open_media_frame('#ai_agent_file_upload_btn', {
        title: 'Upload Knowledge Base File',
        button_text: 'Use this File'
    }, function(attachment) {
        $('#ai_agent_knowledge_file_id').val(attachment.id).trigger('change');
        $('#file-name-display').text(attachment.filename);
        $('#ai_agent_file_remove_btn').show();
        $('#ai_agent_file_upload_btn').text('Replace File');
    });

    $('#ai_agent_remove_btn').on('click', function() {
        $('#ai_agent_logo_id').val('');
        $('#ai_agent_logo_preview').attr('src', '').hide();
        $(this).hide();
        if (typeof syncAdminPreview === 'function')  window.syncAdminPreview();
    });

    $('#ai_agent_file_remove_btn').on('click', function() {
        $('#ai_agent_knowledge_file_id').val('').trigger('change');
        $('#file-name-display').text('');
        $(this).hide();
        $('#ai_agent_file_upload_btn').text('Upload Knowledge File');
    });
});