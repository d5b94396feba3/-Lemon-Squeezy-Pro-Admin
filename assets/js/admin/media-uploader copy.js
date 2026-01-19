// media-uploader.js
jQuery(document).ready(function($) {

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
    
});