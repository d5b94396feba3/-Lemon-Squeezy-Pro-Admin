jQuery(document).ready(function($) {
    let pages = [];

    // Auto-load Discovery on Page Load (if tab is Knowledge Base)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'prompt') {
        runDiscovery();
    }

    // Toggle Visibility
    $('#toggle-sync-table').on('click', function() {
        $('#sync-collapsible-content').slideToggle(300);
        $(this).find('.toggle-icon').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });

    $('#start-discovery-refresh').on('click', function() {
        runDiscovery();
    });

    function runDiscovery() {
        $('#discovery-loader').show();
        $('#sync-data-container, #sync-success-msg, #sync-progress-bar').hide();
        
        $.post(adminProposalData.ajax_url, {
            action: 'sync_website_knowledge',
            sync_step: 'discovery',
            nonce: adminProposalData.nonce
        }, function(res) {
            $('#discovery-loader').hide();
            if(res.success) {
                pages = res.data.pages;
                renderRows(pages);
                updateSyncButtonState();
                $('#sync-data-container').fadeIn();
                $('#sync-summary-badge').text(pages.length + ' Pages Found').show();
            }
        });
    }

    function renderRows(data) {
        let rows = '';
        data.forEach(p => {
            const badgeClass = p.status === 'Synced' ? 'status-approved' : 'status-pending';
            rows += `<tr data-url="${p.url}">
                <td><strong>${p.title}</strong><br><small style="color:#94a3b8">${p.url}</small></td>
                <td><span class="status-badge ${badgeClass}">${p.status}</span></td>
                <td class="last-sync-col">${p.last}</td>
            </tr>`;
        });
        $('#sync-table-body').html(rows);
    }

    function updateSyncButtonState() {
        const pendingPages = pages.filter(p => p.status !== 'Synced').length;
        const $btn = $('#run-bulk-sync');
        
        if (pendingPages === 0) {
            $btn.html('<span class="dashicons dashicons-saved" style="margin-top:8px;"></span> Everything Up to Date').prop('disabled', false);
            $btn.removeClass('button-primary').addClass('button-secondary');
        } else {
            $btn.html(`<span class="dashicons dashicons-update" style="margin-top:8px;"></span> Sync ${pendingPages} Pending Pages`).prop('disabled', false);
            $btn.removeClass('button-secondary').addClass('button-primary');
        }
    }

    // Bulk Sync Logic
    $('#run-bulk-sync').on('click', async function() {
        const $btn = $(this).prop('disabled', true).text('Syncing...');
        $('#sync-progress-bar').slideDown();
        $('#sync-success-msg').hide();
        
        let syncedCount = 0;

        for(let i=0; i < pages.length; i++) {
            let p = pages[i];
            let percent = Math.round(((i + 1) / pages.length) * 100);
            
            $('#sync-msg').text(`Processing: ${p.title}`);
            $('#sync-fill').css('width', percent + '%');
            $('#sync-percent').text(percent + '%');

            const response = await $.post(adminProposalData.ajax_url, {
                action: 'sync_website_knowledge',
                sync_step: 'sync_page',
                url: p.url,
                nonce: adminProposalData.nonce
            });

            if(response.success) {
                syncedCount++;
                const $row = $(`tr[data-url="${p.url}"]`);
                $row.find('.status-badge').attr('class', 'status-badge status-approved').text('Synced');
                $row.find('.last-sync-col').text('Just now');
                pages[i].status = 'Synced'; // Update local state
            }
        }

        // Completion UI sequence
        $('#sync-progress-bar').delay(500).slideUp(400, function() {
            // 1. Show Success Message
            $('#sync-success-msg').fadeIn();
            updateSyncButtonState();

            // 2. Automatically hide the success message after 4 seconds
            setTimeout(function() {
                $('#sync-success-msg').fadeOut(500);
            }, 4000);
        });
    });
});