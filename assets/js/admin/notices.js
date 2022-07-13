jQuery(document).ready(function() {
    jQuery('.exchange-rates-notice').on( 'click', '.notice-dismiss', function( event, el ) {
        var admin_notice = jQuery(this).parent('.notice.is-dismissible');
        var dismiss_url = admin_notice.attr('data-dismiss-url');
        if (dismiss_url) {
            jQuery.get(dismiss_url);
        }
    });
});