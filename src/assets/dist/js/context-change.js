"use strict";
var DPStructure = window.DPStructure || {};
(function ($) {
    var contextSelector = DPStructure.selectSelector || '';
    if (null === contextSelector || contextSelector == '') {
        var missingSelectorText = DPStructure.missingSelectorText || 'Missing parameter "contextSelector"';
        alert(missingSelectorText);
        return;
    }
    var $contextSelect = $(contextSelector);
    $.select2Change = function () {
        var $this = $(this);
        var val = $this.val();
        if (null === val || val == '') {
            $('option', $contextSelect).prop('disabled', false) || $('option', $contextSelect).removeAttr('disabled');
        } else {
            if (false !== Object.prototype.hasOwnProperty.call(DPStructure, 'getContextUrl')) {
                $.ajax({
                    url: DPStructure.getContextUrl,
                    type: 'POST',
                    data: {'structure_id': val},
                    success: function (data) {
                        $contextSelect.val(data);
                        $('option:not(:selected)', $contextSelect).each(function (i, e) {
                            var $e = $(e);
                            $e.prop('disabled', true);
                        });
                    },
                    error: function (data, textStatus, errorThrown) {
                        alert(data.responseText);
                    }
                });
            } else {
                var missingText = DPStructure.missingText || 'Missing parameter "getContextUrl"';
                alert(missingText);
            }
        }
    };

})(jQuery);

