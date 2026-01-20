(function(factory){
    if (typeof define === 'function' && define.amd) {
        define(['jquery', 'moment', 'datatables.net'], factory);
    } else if (typeof exports === 'object') {
        module.exports = factory(require('jquery'), require('moment'));
    } else {
        factory(jQuery, moment);
    }
}(function($, moment){
    $.fn.dataTable.moment = function(format, locale){
        var types = $.fn.dataTable.ext.type;
        types.detect.unshift(function(d){
            d = d && typeof d === 'string' ? d.trim() : d;
            return d && moment(d, format, locale, true).isValid() ?
                'datetime-moment' : null;
        });
        types.order['datetime-moment-pre'] = function(d){
            return d ? moment(d, format, locale, true).valueOf() : -Infinity;
        };
    };
}));
