jQuery(document).ready(function($) {
    if (typeof wp === 'undefined' || !wp.media) {
        return;
    }

    var frame_cache = {};

    function open_media_frame(button_id, options, callback) {
        $(button_id).off('click').on('click', function(e) {
            e.preventDefault();

            if (frame_cache[button_id]) {
                frame_cache[button_id].open();
                return;
            }

            frame_cache[button_id] = wp.media({
                title: options.title || (aica_admin_data.i18n_select_media || 'Select Media'),
                button: { text: options.button_text || (aica_admin_data.i18n_use_media || 'Use this Media') },
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
        title: aica_admin_data.i18n_select_avatar || 'Select Avatar',
        button_text: aica_admin_data.i18n_use_image || 'Use this Image'
    }, function(attachment) {
        $('#ai_agent_logo_id').val(attachment.id).trigger('change');
        $('#ai_agent_logo_preview').attr('src', attachment.url).show();
        $('#ai_agent_remove_btn').show();
        if (typeof window.syncAdminPreview === 'function') {
            window.syncAdminPreview();
        }
    });

    open_media_frame('#ai_agent_file_upload_btn', {
        title: aica_admin_data.i18n_upload_file || 'Upload Knowledge Base File',
        button_text: aica_admin_data.i18n_use_file || 'Use this File'
    }, function(attachment) {
        $('#ai_agent_knowledge_file_id').val(attachment.id).trigger('change');
        $('#file-name-display').text(attachment.filename);
        $('#ai_agent_file_remove_btn').show();
        
        const replaceLabel = aica_admin_data.i18n_replace_file || 'Replace File';
        $('#ai_agent_file_upload_btn').text(replaceLabel);
    });

    $('#ai_agent_remove_btn').on('click', function() {
        $('#ai_agent_logo_id').val('').trigger('change');
        const defaultAvatar = (typeof aica_admin_assets !== 'undefined') ? aica_admin_assets.default_avatar : '';
        $('#ai_agent_logo_preview').attr('src', defaultAvatar);
        $(this).hide();
        if (typeof window.syncAdminPreview === 'function') window.syncAdminPreview();
    });

    $('#ai_agent_file_remove_btn').on('click', function() {
        $('#ai_agent_knowledge_file_id').val('').trigger('change');
        $('#file-name-display').text('');
        $(this).hide();
        
        const uploadLabel = aica_admin_data.i18n_upload_kb || 'Upload Knowledge File';
        $('#ai_agent_file_upload_btn').text(uploadLabel);
    });
});