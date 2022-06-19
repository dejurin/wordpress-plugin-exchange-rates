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

    jQuery('.exchange-rates-caption-button').on('click', function(event) {
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





        /// Currency Converter
        
        jQuery('div.currency-converter').each(function(k, v) {

            var el = jQuery(this);

            jQuery('<b>').text('Currency Converter').appendTo(el);
            var div = jQuery('<div>').attr('class','d-flex amount').appendTo(el);
            jQuery('<input>').attr('value',el.data('amount')).appendTo(div);
            jQuery('<button>').attr('class','swap').html('<svg xmlns="http://www.w3.org/2000/svg"><path d="M8 13V5.825L5.425 8.4L4 7L9 2L14 7L12.575 8.4L10 5.825V13ZM15 22 10 17 11.425 15.6 14 18.175V11H16V18.175L18.575 15.6L20 17Z"/></svg>').appendTo(div);

            

            jQuery.each([el.data('base'),el.data('quote')], function( i, s ) {
                var sel = jQuery('<select>').appendTo(el);
                jQuery.each(el.data('currencies'), function(_k,_v){
                    
                    var obj = {
                        'value': _k
                    }

                    if (_k == s) {obj['selected'] = s;}
         
                    sel.append(jQuery("<option>").attr(obj).text(_v['name']));
                });
              });

              jQuery('<div>').attr('class', 'result').text('1.5').appendTo(el);


        });


});
