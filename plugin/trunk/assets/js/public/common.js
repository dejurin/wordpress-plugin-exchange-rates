jQuery(document).ready(function() {
    var unavailable = '\u2013';

    function readAmount(value) {
        value = String(value).trim();
        if (value === '') return 0;
        // Reject partial numbers, alternate radices and unrepresentable amounts.
        if (!/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?$/i.test(value)) return null;
        var amount = Number(value);
        if (!isFinite(amount) || (amount === 0 && /[1-9]/.test(value.split(/e/i)[0]))) return null;
        return amount;
    }

    function formatNumber(r, n, p, ts, dp) {
        var t = [];

        if (typeof p == 'undefined') p = 2;
        if (typeof ts == 'undefined') ts = ',';
        if (typeof dp == 'undefined') dp = '.';

        r = Number(r);
        if (typeof n !== 'number' || !isFinite(n) || !isFinite(r) || r <= 0) return null;
        var value = r * n;
        if (!isFinite(value) || (value === 0 && n !== 0)) return null;

        // Preserve existing rounding, then group only unsigned decimal digits.
        n = Number(value.toFixed(p));
        var sign = n < 0 ? '-' : '';
        n = Math.abs(n).toString();
        var exponent = n.split('e');
        if (exponent.length === 2) {
            var digits = exponent[0].replace('.', '');
            var point = (exponent[0].indexOf('.') < 0 ? exponent[0].length : exponent[0].indexOf('.')) + Number(exponent[1]);
            if (point <= 0) {
                n = '0.' + new Array(1 - point).join('0') + digits;
            } else if (point >= digits.length) {
                n = digits + new Array(point - digits.length + 1).join('0');
            } else {
                n = digits.slice(0, point) + '.' + digits.slice(point);
            }
        }
        n = n.split('.');

        for (var iLen = n[0].length, i = iLen ? iLen % 3 || 3 : 0, j = 0; i <= iLen; i += 3) {
            t.push(n[0].substring(j, i));
            j = i;
        }

        return sign + t.join(ts) + (n[1] ? dp + n[1] : '');
    }
    function updateChange(cell, amount, settings) {
        // Cached legacy markup has no change metadata; keep its existing behavior.
        if (typeof cell.attr('data-change-trend') === 'undefined') return;

        var trend = Number(cell.attr('data-change-trend'));
        var current = formatNumber(cell.data('rate'), amount, settings.data('decimals'), settings.data('thousands-sep'), settings.data('decimal-point'));
        var previous = formatNumber(Number(cell.attr('data-previous-rate')), amount, settings.data('decimals'), settings.data('thousands-sep'), settings.data('decimal-point'));
        var visible = current !== null && previous !== null && trend !== 0 && current !== previous;
        var invalid = current === null || (cell.attr('data-previous-rate') && previous === null);
        var label = invalid ? unavailable : cell.attr(visible ? 'data-change-label' : 'data-change-hidden-label');
        var icon = cell.find('img.exchange-rates-trend');

        if (!visible) {
            icon.remove();
        } else if (!icon.length) {
            jQuery('<img>').attr({
                'class': 'exchange-rates-trend', width: 14, height: 14,
                alt: trend > 0 ? 'up' : 'down', src: cell.attr('data-trend-src')
            }).appendTo(cell);
        }
        cell.attr('title', label);
        var change = cell.closest('tr').find('td[data-change]');
        change.text(visible ? cell.attr('data-change-percent') : '\u2013')
            .removeClass('table-success table-danger')
            .attr({ 'aria-label': label, title: label });
        if (visible) change.addClass(trend > 0 ? 'table-success' : 'table-danger');
    }

    /* Caption - start */
    jQuery('span.exchange-rates-caption-button, strong.exchange-rates-caption-button').on('click', function(event) {
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
        var rawAmount = jQuery(this).val();
        var amount = readAmount(rawAmount);
        var settings = jQuery(this).closest('table');
        var invalid = amount === null;

        // Keep the table's existing empty/zero/negative amount-one policy.
        if (amount !== null && amount <= 0) amount = 1;

        jQuery(this).data('amount', amount === null ? rawAmount : amount);
        settings.find('tbody td[data-rate]').each(function(index, value) {
            var rate = jQuery(value).data('rate');
            var pre = jQuery(value).data('symbol');
            var after = '';

            if (settings.data('after')) {
                after = pre;
                pre = '';
            }

            var formatted = formatNumber(rate, amount, settings.data('decimals'), settings.data('thousands-sep'), settings.data('decimal-point'));
            var img = jQuery(value).find('img').clone();
            invalid = invalid || formatted === null;
            jQuery(value).text(formatted === null ? unavailable : pre + formatted + after);
            // Cached legacy cells lack trend metadata; retain their image for recovery.
            img.each(function() {
                if (formatted === null) this.style.setProperty('display', 'none', 'important');
                else this.style.removeProperty('display');
            });
            img.appendTo(value);
            updateChange(jQuery(value), amount, settings);
        });
        jQuery(this).attr('aria-invalid', invalid ? 'true' : null);
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
            var amount = readAmount(jQuery(this).val());
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

            var formatted = formatNumber(
                rate,
                amount,
                el.data('decimals'),
                el.data('thousands-sep'),
                el.data('decimal-point')
            );
            input.attr('aria-invalid', formatted === null ? 'true' : null);
            result.text(formatted === null ? unavailable : pre + formatted + after);
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
