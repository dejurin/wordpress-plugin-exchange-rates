jQuery(document).ready(function() {
    function formatNumber(r, n, p, ts, dp) {
        var t = [];

        if (typeof p == 'undefined') p = 2;
        if (typeof ts == 'undefined') ts = ',';
        if (typeof dp == 'undefined') dp = '.';

        n = Number((r * n).toFixed(p));
        n = n.toString().split('.');

        for (var iLen = n[0].length, i = iLen ? iLen % 3 || 3 : 0, j = 0; i <= iLen; i += 3) {
            t.push(n[0].substring(j, i));
            j = i;
        }

        return t.join(ts) + (n[1] ? dp + n[1] : '');
    }
    /* Caption - start */
    jQuery('span.exchange-rates-caption-button').on('click', function(event) {
        if (jQuery('#' + jQuery(this).data('caption-id')).css('display') === 'none') {
            jQuery(jQuery(this).find('svg')[0]).css('display', 'none');
            jQuery(jQuery(this).find('svg')[1]).css('display', 'block');
            jQuery('#' + jQuery(this).data('caption-id')).show();

        } else {
            jQuery(jQuery(this).find('svg')[0]).css('display', 'block');
            jQuery(jQuery(this).find('svg')[1]).css('display', 'none')
            jQuery('#' + jQuery(this).data('caption-id')).hide();
        }
    });
    /* Caption - end */

    /* Currency Table - start */
    jQuery('.widget-exchange-rates-currency-table input').on('click', function(event) {
        var amount = jQuery(this).val();
        jQuery(this).data('amount', amount).val('');
    }).on('blur', function(event) {
        var amount = jQuery(this).data('amount');
        jQuery(this).val(amount);
    });

    jQuery('.widget-exchange-rates-currency-table input').on('input', function(event) {
        var amount = parseFloat(jQuery(this).val());
        var settings = jQuery('.widget-exchange-rates-currency-table table');

        amount = (amount > 0) ? amount : 1;

        jQuery(this).data('amount', amount);
        jQuery('.widget-exchange-rates-currency-table table tbody td[data-rate]').each(function(index, value) {
            var rate = jQuery(value).data('rate');
            var pre = jQuery(value).data('symbol');
            var after = '';

            if (settings.data('after')) {
                after = pre;
                pre = '';
            }

            var result = pre + formatNumber(rate, amount, settings.data('decimals'), settings.data('thousands-sep'), settings.data('decimal-point')) + after;
            var img = jQuery(value).find('img').clone();
            jQuery(value).html(result);
            img.appendTo(value);
        });
    });
    /* Currency Table - end */

    /* Widget Currency Converter - start */
    jQuery('.widget-exchange-rates-currency-converter').each(function(k, v) {
        var el = jQuery(this).html('');
        var rates = el.data('currencies');

        jQuery('<b>').text(el.data('title')).appendTo(el);

        var div = jQuery('<div>').attr('class', 'd-flex amount').appendTo(el);
        var input = jQuery('<input>').attr('value', el.data('amount')).appendTo(div);
        var swap = jQuery('<button>').attr('class', 'swap').html('<svg xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M8 13V5.825L5.425 8.4L4 7L9 2L14 7L12.575 8.4L10 5.825V13ZM15 22 10 17 11.425 15.6 14 18.175V11H16V18.175L18.575 15.6L20 17Z"/>' +
            '</svg>').appendTo(div);

        jQuery.each([el.data('from'), el.data('to')], function(_i, s) {
            var sel = jQuery('<select>');
            sel.appendTo(el);
            jQuery.each(el.data('currencies'), function(code, data) {
                var obj = {
                    'value': code
                }
                if (code == s) {
                    obj['selected'] = s;
                }

                sel.append(jQuery("<option>").attr(obj).text(el.data('code') ? code : code + ' - ' + data['name']));
            });
        });

        jQuery(swap).on('click', function(e) {
            var from = jQuery(el.find('select')[0]);
            var to = jQuery(el.find('select')[1]);
            var _from = from.find('option:selected').val()
            var _to = to.find('option:selected').val()
            from.val(_to).change();
            to.val(_from).change();
            input.trigger("input");
        });

        jQuery(input).on('input', function() {
            var amount = jQuery(this).val();
            var base_currency = el.data('base-currency');

            var from = jQuery(el.find('select')[0]).find('option:selected').val();
            var to = jQuery(el.find('select')[1]).find('option:selected').val();
            var rate = rates[from]['rate'] * (1 / rates[to]['rate']);
            var pre = (el.data('symbol')) ? rates[to]['symbol'] : '';
            var after = '';

            if (el.data('after')) {
                after = pre;
                pre = '';
            }

            result.html(
                pre + formatNumber(
                    rate,
                    amount,
                    el.data('decimals'),
                    el.data('thousands-sep'),
                    el.data('decimal-point')
                ) + after
            );
        });

        var result = jQuery('<div>').attr('class', 'result').text(0).appendTo(el);

        jQuery.each(el.find('select'), function(h, o) {
            jQuery(o).on('change', function(h, o) {
                input.trigger("input");
            })
        });

        input.trigger("input");
    });
    /* Widget Currency Converter - end */
});