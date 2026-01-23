jQuery(document).ready(function($) {
    let pages = [];

    // Helper to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'knowledge-base') {
        runDiscovery();
    }

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
        
        $.post(wppp_admin_data.ajax_url, {
            action: 'sync_website_knowledge',
            sync_step: 'discovery',
            nonce: wppp_admin_data.nonce
        }, function(res) {
            $('#discovery-loader').hide();
            if(res.success) {
                pages = res.data.pages;
                renderRows(pages);
                updateSyncButtonState();
                $('#sync-data-container').fadeIn();
                const pagesLabel = wppp_admin_data.i18n_pages || 'Pages';
                $('#sync-summary-badge').text(pages.length + ' ' + pagesLabel).show();
            }
        });
    }

    function renderRows(data) {
        let rows = '';
        data.forEach(p => {
            const badgeClass = p.status === 'Synced' ? 'status-approved' : 'status-pending';
            const statusText = p.status === 'Synced' ? (wppp_admin_data.i18n_synced || 'Synced') : (wppp_admin_data.i18n_pending || 'Pending');
            
            rows += `<tr data-url="${escapeHtml(p.url)}">
                <td><strong>${escapeHtml(p.title)}</strong><br><small style="color:#94a3b8">${escapeHtml(p.url)}</small></td>
                <td><span class="status-badge ${badgeClass}">${statusText}</span></td>
                <td class="last-sync-col">${escapeHtml(p.last)}</td>
            </tr>`;
        });
        $('#sync-table-body').html(rows);
    }

    function updateSyncButtonState() {
        const pendingPages = pages.filter(p => p.status !== 'Synced').length;
        const $btn = $('#run-bulk-sync');
        
        if (pendingPages === 0) {
            const upToDateLabel = wppp_admin_data.i18n_uptodate || 'Everything Up to Date';
            $btn.html('<span class="dashicons dashicons-saved" style="margin-top:8px;"></span> ' + upToDateLabel).prop('disabled', false);
            $btn.removeClass('button-primary').addClass('button-secondary');
        } else {
            const syncPendingLabel = wppp_admin_data.i18n_sync_pending || 'Sync {n} Pending Pages';
            const finalLabel = syncPendingLabel.replace('{n}', pendingPages);
            $btn.html(`<span class="dashicons dashicons-update" style="margin-top:8px;"></span> ${finalLabel}`).prop('disabled', false);
            $btn.removeClass('button-secondary').addClass('button-primary');
        }
    }

    $('#run-bulk-sync').on('click', async function() {
        const syncingLabel = wppp_admin_data.i18n_syncing || 'Syncing...';
        const $btn = $(this).prop('disabled', true).text(syncingLabel);
        $('#sync-progress-bar').slideDown();
        $('#sync-success-msg').hide();
        
        let syncedCount = 0;

        for(let i=0; i < pages.length; i++) {
            let p = pages[i];
            let percent = Math.round(((i + 1) / pages.length) * 100);
            
            const processingLabel = wppp_admin_data.i18n_processing || 'Processing:';
            $('#sync-msg').text(`${processingLabel} ${p.title}`);
            $('#sync-fill').css('width', percent + '%');
            $('#sync-percent').text(percent + '%');

            try {
                const response = await $.post(wppp_admin_data.ajax_url, {
                    action: 'sync_website_knowledge',
                    sync_step: 'sync_page',
                    url: p.url,
                    nonce: wppp_admin_data.nonce
                });

                if(response && response.success) {
                    syncedCount++;
                    const $row = $(`tr[data-url="${p.url}"]`);
                    $row.find('.status-badge').attr('class', 'status-badge status-approved').text(wppp_admin_data.i18n_synced || 'Synced');
                    $row.find('.last-sync-col').text(wppp_admin_data.i18n_justnow || 'Just now');
                    pages[i].status = 'Synced';
                }
            } catch (err) {
                console.error("Sync error for " + p.url, err);
            }
        }

        $('#sync-progress-bar').delay(500).slideUp(400, function() {
            $('#sync-success-msg').fadeIn();
            updateSyncButtonState();

            setTimeout(function() {
                $('#sync-success-msg').fadeOut(500);
            }, 4000);
        });
    });

    $('#btn-generate-ai-prompt').on('click', function(e) {
        e.preventDefault();
        
        const confirmMsg = wppp_admin_data.i18n_confirm_gen || "This will replace your current instructions. Proceed?";
        if (!confirm(confirmMsg)) return;

        const $btn = $(this);
        const $textarea = $('#ai_agent_system_prompt_area');
        const $loader = $('#prompt-gen-loader');

        $btn.prop('disabled', true).css('opacity', '0.5');
        $textarea.css('opacity', '0.3');
        $loader.fadeIn(300);

        $.post(wppp_admin_data.ajax_url, {
            action: 'generate_ai_system_prompt',
            nonce: wppp_admin_data.nonce
        }, function(response) {
            $loader.hide();
            $btn.prop('disabled', false).css('opacity', '1');
            $textarea.css('opacity', '1');

            if (response.success) {
                $textarea.val(response.data).addClass('field-changed');
                $('#wppp-unsaved-bar').css('display', 'flex');
            } else {
                alert(response.data);
            }
        });
    });
});