jQuery(document).ready(function() {
    var messages = window.exchangeRatesMessages || {};
    function message(key, fallback, values) {
        return (messages[key] || fallback).replace(/%(\d+)\$s|%s/g, function(token, position) {
            return values ? values[position ? Number(position) - 1 : 0] : token;
        });
    }
    var unavailable = '\u2013';
    var controlSequence = 0;

    // Avoid collisions across converters and host-page controls.
    function controlId(role) {
        var id;
        do {
            controlSequence += 1;
            id = 'exchange-rates-' + role + '-' + controlSequence;
        } while (document.getElementById(id));
        return id;
    }

    function accessibleStatus(container) {
        var node = jQuery('<p>').attr({
            'class': 'er-a11y-sr-only', 'role': 'status',
            'aria-live': 'polite', 'aria-atomic': 'true'
        }).appendTo(container);
        var timer;
        return function(message, immediate) {
            clearTimeout(timer);
            if (immediate) { node.text(message); }
            else { timer = setTimeout(function() { node.text(message); }, 350); }
        };
    }

    function amountError(container, input) {
        var error = jQuery('<p>').attr({
            id: controlId('error'), 'class': 'er-a11y-error', hidden: true
        }).text(message('invalidAmount', 'Enter a valid amount using a dot for decimals.')).appendTo(container);
        input.attr({ inputmode: 'decimal', autocomplete: 'off' });
        return function(invalid) {
            error.prop('hidden', !invalid);
            input.attr('aria-describedby', invalid ? error.attr('id') : null);
        };
    }

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
        // Keep a nonzero amount distinct from both zero and unavailable data.
        if (n === 0 && value !== 0) {
            return (value < 0 ? '>-' : '<') + (p > 0 ? '0' + dp + new Array(Number(p)).join('0') + '1' : '1');
        }
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
        // A title repeats the hidden trend text in VoiceOver. Keep one description.
        cell.removeAttr('title');
        cell.find('.er-a11y-trend').remove();
        cell.find('img.exchange-rates-trend').attr({ alt: '', 'aria-hidden': 'true' });
        if (!invalid) {
            jQuery('<span>').attr('class', 'er-a11y-sr-only er-a11y-trend')
                .text('; ' + label).appendTo(cell);
        }
        var change = cell.closest('tr').find('td[data-change]');
        change.text(visible ? cell.attr('data-change-percent') : '\u2013')
            .removeClass('table-success table-danger')
            .attr('aria-label', label).removeAttr('title');
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
    jQuery('.widget-exchange-rates-currency-table table').each(function() {
        var table = jQuery(this);
        var input = table.find('input');
        var base = table.attr('data-base-currency');
        var inverse = table.attr('data-inverse') === 'true';
        if (!base) { return; } // Cached legacy HTML has no accessibility metadata.
        table.attr('aria-label', message(inverse ? 'ratesIn' : 'ratesFor', inverse ? 'Exchange rates in %s' : 'Exchange rates for %s', [base]));
        input.attr('aria-label', inverse ? message('inverseAmount', 'Amount in each listed currency') : message('amountIn', 'Amount in %s', [base]));
        table.data('erA11y', {
            base: base, inverse: inverse, ready: false,
            announce: accessibleStatus(table.parent()),
            error: amountError(table.parent(), input)
        });
        input.data('amount', input.val());
        table.find('td[data-rate]').each(function() {
            updateChange(jQuery(this), Number(table.attr('data-amount')), table);
        });
    });
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
        var accessibility = settings.data('erA11y');
        var spokenRates = [];

        // Preserve empty/negative defaults while allowing explicit zero amounts.
		if ( amount !== null && ( amount < 0 || String( rawAmount ).trim() === '' ) ) {
			amount = 1;
		}

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
            // The server uses zero for unavailable history, not an invalid amount.
            var missingHistory = jQuery(value).attr('data-rate-period') === 'previous' && Number(rate) === 0;
            invalid = invalid || (formatted === null && !missingHistory);
            jQuery(value).text(formatted === null ? unavailable : pre + formatted + after);
            // Cached legacy cells lack trend metadata; retain their image for recovery.
            img.each(function() {
                if (formatted === null) this.style.setProperty('display', 'none', 'important');
                else this.style.removeProperty('display');
            });
            img.appendTo(value);
            updateChange(jQuery(value), amount, settings);
            var quote = jQuery(value).attr('data-currency');
            if (!quote || !accessibility) { return; }
            if (jQuery(value).attr('data-rate-period') === 'previous') { quote = message('previousClose', '%s previous close', [quote]); }
            spokenRates.push(accessibility.inverse ? message('result', '%1$s %2$s equals %3$s %4$s', [amount, quote, formatted === null ? message('unavailable', 'unavailable') : formatted, accessibility.base]) : quote + ': ' + (formatted === null ? message('unavailable', 'unavailable') : formatted));
        });
        jQuery(this).attr('aria-invalid', invalid ? 'true' : null);
        if (!accessibility) { return; }
        accessibility.error(invalid);
        accessibility.announce(invalid ? message('unable', 'Unable to calculate. Enter a valid amount.') :
            (accessibility.inverse ? spokenRates.join('; ') : message('ratesAmount', 'Rates for %1$s %2$s.', [amount, accessibility.base]) + ' ' + spokenRates.join('; ')), !accessibility.ready);
        accessibility.ready = true;
    });
    // Preserve server-formatted initial amounts; only user input triggers recalculation.

    jQuery('.widget-exchange-rates-currency-table.table-responsive').each(function() {
        var wrapper = this;
        var history = wrapper.classList.contains('exchange-rates-table-history');
        var note = jQuery('<p>').attr({ 'class': 'exchange-rates-table-scroll-note', hidden: true })
            .text(message('tableScrollHint', 'Scroll horizontally to see all columns.')).insertAfter(wrapper);
        function fitTable() {
            if (history) {
                // Measure the original layout first so wider tables keep their geometry.
                wrapper.classList.remove('exchange-rates-table-reflow', 'exchange-rates-table-compact', 'exchange-rates-table-short');
                var needsFit = wrapper.scrollWidth > wrapper.clientWidth + 1;
                wrapper.classList.toggle('exchange-rates-table-reflow', needsFit);
                wrapper.classList.toggle('exchange-rates-table-compact', needsFit && wrapper.clientWidth < 320);
                wrapper.classList.toggle('exchange-rates-table-short', wrapper.scrollWidth > wrapper.clientWidth + 1);
            }
            var overflow = wrapper.scrollWidth > wrapper.clientWidth + 1;
            note.prop('hidden', !overflow);
            if (overflow) {
                jQuery(wrapper).attr({ tabindex: '0', role: 'region', 'aria-label': message('tableScrollRegion', 'Exchange rates, horizontally scrollable') });
            } else {
                jQuery(wrapper).removeAttr('tabindex role aria-label');
            }
        }
        fitTable();
        if (window.ResizeObserver) { new ResizeObserver(fitTable).observe(wrapper); }
        window.addEventListener('resize', fitTable);
        wrapper.addEventListener('input', function() { requestAnimationFrame(fitTable); });
        if (document.fonts) { document.fonts.ready.then(fitTable); }
    });
    /* Currency Table - end */

    /* Widget Currency Converter - start */
    jQuery('.widget-exchange-rates-currency-converter').each(function(k, v) {
        var el = jQuery(this).html('');
        var rates = el.data('currencies');

        jQuery('<h2>').attr('class', 'er-a11y-widget-title').text(el.data('title')).appendTo(el);

        var div = jQuery('<div>').attr('class', 'd-flex amount').appendTo(el);
		var input = jQuery( '<input>' ).attr( {
			id: controlId( 'amount' ),
			value: el.data( 'amount' ),
			'aria-label': el.attr( 'data-label-amount' )
		} ).appendTo( div );
		var swap = jQuery( '<button>' ).attr( {
			'class': 'swap',
			'type': 'button',
			'aria-label': el.attr( 'data-label-swap' )
		} ).html('<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M8 13V5.825L5.425 8.4L4 7L9 2L14 7L12.575 8.4L10 5.825V13ZM15 22 10 17 11.425 15.6 14 18.175V11H16V18.175L18.575 15.6L20 17Z"/>' +
            '</svg>').appendTo(div);

        jQuery.each([el.data('from'), el.data('to')], function(_i, s) {
			var sel = jQuery( '<select>' ).attr( {
				id: controlId( _i === 0 ? 'from' : 'to' ),
				'aria-label': el.attr( _i === 0 ? 'data-label-from' : 'data-label-to' )
			} );
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

        var announceResult = accessibleStatus(el);
        var showError = amountError(el, input);
        var initialResult = true;

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
            input.attr('aria-label', message('amountIn', 'Amount in %s', [from]));
            showError(formatted === null);
            announceResult(formatted === null ? message('unable', 'Unable to calculate. Enter a valid amount.') :
                message('result', '%1$s %2$s equals %3$s %4$s', [amount, from, formatted, to]), initialResult);
            initialResult = false;
        });

        var result = jQuery('<div>').attr({ 'class': 'result', 'aria-hidden': 'true' }).text(0).appendTo(el);

        jQuery.each(el.find('select'), function(h, o) {
            jQuery(o).on('change', function(h, o) {
                input.trigger("input");
            })
        });

        input.trigger("input");
    });
    /* Widget Currency Converter - end */
});
