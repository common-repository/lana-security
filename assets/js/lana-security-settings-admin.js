jQuery(function () {
    var $lanaSecuritySettingsPage = jQuery('body.lana-security_page_lana-security-settings');

    /**
     * Lana Security
     * display cleanup by
     */
    function lanaSecurityDisplayCleanupBy() {
        var $cleanupBy = $lanaSecuritySettingsPage.find('tr' + jQuery(this).data('tr-target')),
            selected = jQuery(this).val();

        if ('1' === selected) {
            /** display */
            $cleanupBy.addClass('d-table-row');

            /** trigger select change */
            $cleanupBy.find('select').trigger('change');

        } else {
            /** hide */
            $cleanupBy.removeClass('d-table-row');
        }

        $cleanupBy.trigger('change');
    }

    /**
     * Lana Security
     * display cleanup
     */
    function lanaSecurityDisplayCleanup() {
        var $cleanup = $lanaSecuritySettingsPage.find('tr' + jQuery(this).data('tr-target')),
            selected = jQuery(this).val();

        if ('1' === selected) {
            /** display */
            $cleanup.addClass('d-table-row');
        } else {
            /** hide */
            $cleanup.removeClass('d-table-row');
        }

        $cleanup.trigger('change');
    }

    /** display log cleanup amount */
    $lanaSecuritySettingsPage.find('#lana-security-logs-cleanup-by-amount').on('change', lanaSecurityDisplayCleanup).trigger('change');
    $lanaSecuritySettingsPage.find('#lana-security-login-logs-cleanup-by-amount').on('change', lanaSecurityDisplayCleanup).trigger('change');

    /** display log cleanup time */
    $lanaSecuritySettingsPage.find('#lana-security-logs-cleanup-by-time').on('change', lanaSecurityDisplayCleanup).trigger('change');
    $lanaSecuritySettingsPage.find('#lana-security-login-logs-cleanup-by-time').on('change', lanaSecurityDisplayCleanup).trigger('change');

    /** display log cleanup by */
    $lanaSecuritySettingsPage.find('#lana-security-logs').on('change', lanaSecurityDisplayCleanupBy).trigger('change');
    $lanaSecuritySettingsPage.find('#lana-security-login-logs').on('change', lanaSecurityDisplayCleanupBy).trigger('change');
});