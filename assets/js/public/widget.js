jQuery(document).ready(function() {
    function formatNumber(r, n, p, ts, dp) {
        var t = [];

        if (typeof p  == 'undefined') p  = 2;
        if (typeof ts == 'undefined') ts = ',';
        if (typeof dp == 'undefined') dp = '.';
    
        n = Number((r.toFixed(p) * n).toFixed(p));
        n = n.toString().split('.');
    
        for (var iLen = n[0].length, i = iLen? iLen % 3 || 3 : 0, j = 0; i <= iLen; i+=3) {
            t.push(n[0].substring(j, i));
            j = i;
        }

        return t.join(ts) + (n[1]? dp + n[1] : '');
    }

    jQuery('.widget-exchange-rates-table .caption-button').on('click', function(event) {
        if (jQuery('#' + jQuery(this).data('caption-id')).css('display') === 'none') {
            jQuery(jQuery(this).find('svg')[0]).css('display','none');
            jQuery(jQuery(this).find('svg')[1]).css('display','block');
            jQuery('#' + jQuery(this).data('caption-id')).show();

        } else {
            jQuery(jQuery(this).find('svg')[0]).css('display','block');
            jQuery(jQuery(this).find('svg')[1]).css('display','none')
            jQuery('#' + jQuery(this).data('caption-id')).hide();
        }
    });
    jQuery('.widget-exchange-rates-table input').on('click', function(event) {
        var amount = jQuery(this).val();
        jQuery(this).data('amount', amount).val('');
    }).on('blur', function(event) {
        var amount = jQuery(this).data('amount');
        jQuery(this).val(amount);
    });
    jQuery('.widget-exchange-rates-table input').on('input', function(event) {
        var amount = parseFloat(jQuery(this).val());
        jQuery(this).data('amount', amount);
        jQuery('.widget-exchange-rates-table table tbody td[data-rate]').each(function( index, value ) {
            var settings = jQuery('.widget-exchange-rates-table table');
            var rate = jQuery(value).data('rate');
            var result = formatNumber(rate, amount, settings.data('decimals'), settings.data('thousands-sep'), settings.data('decimal-point'));
            jQuery(value).text(result);
        });
    });
});
