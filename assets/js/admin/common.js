jQuery(document).ready(function() {
    function showMoreLessTable() {
        jQuery("table.exchange-rates-widgets-table").each(function() {
            var display = 'none';
            if (jQuery(this).data('display') == 'show') {
                display = ''
            }
            for (i = 0; i < jQuery(this).find('tbody tr').length; i++) {
                if (i >= jQuery(this).data('rows')) {
                    jQuery(this).find('tbody tr')[i].style.display = display;
                }
            }
        });
    }
    jQuery(document).on('input', 'input[type="range"]', function() {
        console.log("#" + jQuery(this).attr('id') + '-show');
        jQuery("#" + jQuery(this).attr('id') + '-show').html(jQuery(this).val());
    });
    jQuery("button.show-more-table").each(function() {
        var total_table_tr = jQuery("#" + jQuery(this).data('id')).find('tbody tr').length;
        var rows = jQuery("#" + jQuery(this).data('id')).data('rows');
        if (total_table_tr > rows) {
            jQuery(this).text(jQuery(this).text() + ' (' + (total_table_tr - rows) + ')')
        } else {
            jQuery(this).hide();
        }
    });
    jQuery("button.show-more-table").on('click', function() {
        var display = 'hide';
        if (jQuery("#" + jQuery(this).data('id')).data('display') == 'hide') {
            display = 'show';
        }
        jQuery("#" + jQuery(this).data('id')).data('display', display)
        showMoreLessTable()
    });
    showMoreLessTable();
    /* shortcode generator */
    function dejurinExchangeRates_ShortcodeGenerator(_this) {
        var _id = jQuery(_this).find('input[name="id_base"]').val();
        
        if (jQuery(_this).data('shortcode-generator')) {
            _id = jQuery(_this).data('shortcode-generator');
        }

        var serializeArray = jQuery(_this).serializeArray();
        var line = '[' + _id + ' ';
        var currency_list = '';

        jQuery.each(serializeArray, function(index, attr) {
            if (attr.name === 'shortcode-generator' || 
                attr.name === 'id_base' ||
                attr.name === 'widget-width' ||
                attr.name === 'widget-height' ||
                attr.name === 'widget_number' ||
                attr.name === 'widget-id'
                ) {
                // ignore
            } else {
                if (attr.name === 'currency_list') {
                    currency_list += attr.value + ',';
                } else {
                    value = attr.value;
                    var attrName ='';
                    try {
                        attrName = (attr.name.split('[')[2]).slice(0, -1);
                      }
                      catch(err) {
                        attrName = attr.name;
                      }
                    line += attrName + '="' + value + '" ';
                }
            }
        });
        if (currency_list !== '') {
            line += 'currency_list="' + currency_list.slice(0, currency_list.length - 1) + '"';
        }
        line += ']';
        jQuery(_this).find('textarea[name="shortcode-generator"]').text(line);
    }
    jQuery(document).on("input click", 'form[class="form"]', function(event) {
        if (jQuery(event.target).prop("tagName") !== 'TEXTAREA') {
            dejurinExchangeRates_ShortcodeGenerator(this);
            console.log(jQuery(event.target).prop("tagName"))
        }
    });
    dejurinExchangeRates_ShortcodeGenerator('form[class="form"]');
});