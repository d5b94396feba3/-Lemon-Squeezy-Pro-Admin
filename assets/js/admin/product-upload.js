jQuery(document).ready(function($) {
    // Add logic for file validation if needed
    $('#wppp_product_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if(fileName) {
            console.log('File selected: ' + fileName);
        }
    });

    // Handle Transaction Deletion (Placeholder for your UI)
    $('.action-delete-tx').on('click', function(e) {
        e.preventDefault();
        var tx_id = $(this).data('id');
        var $row = $(this).closest('tr');

        if (confirm('Are you sure you want to delete this transaction record?')) {
            $.post(wppp_admin.ajax_url, {
                action: 'wppp_delete_transaction',
                id: tx_id,
                _ajax_nonce: wppp_admin.nonce
            }, function(response) {
                if (response.success) {
                    $row.fadeOut();
                }
            });
        }
    });
});