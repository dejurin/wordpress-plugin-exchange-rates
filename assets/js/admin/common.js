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
    function split(val) {
        return val.split(/,\s*/);
    }
    function extractLast(term) {
        return split(term).pop();
    }
    jQuery(document).on("keydown", '.plugin__currency__autocomplete', function(event) {
        if (event.keyCode === jQuery.ui.keyCode.TAB && jQuery(this).autocomplete("instance").menu.active) {
            event.preventDefault();
        }

        jQuery(this).autocomplete({
            minLength: 0,
            source: function(request, response) {
                response(
                    jQuery.ui.autocomplete.filter(
                        jQuery('.plugin__currency__autocomplete').data('autocomplete').split(','),
                        extractLast(request.term)
                    )
                );
            },
            focus: function() {
                return false;
            },
            select: function(event, ui) {
                var terms = split(this.value);
                terms.pop();
                terms.push(ui.item.value);
                terms.push("");
                var _term = terms.join(", ");
                console.log(_term);
                console.log(_term.replace(/,\s*jQuery/, ""));
                this.value = _term.replace(/,\s*jQuery/, "");
                return false;
            }
        });
    });
    jQuery(document).on('input', 'input[type="range"]', function() {
        jQuery("#" + jQuery(this).attr('id') + '-show').text(jQuery(this).val());
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
    showMoreLessTable()
});