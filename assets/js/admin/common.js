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


});