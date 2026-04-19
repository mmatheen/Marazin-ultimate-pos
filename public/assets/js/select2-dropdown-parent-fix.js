/**
 * Select2: default dropdownParent to body so dropdowns position correctly
 * inside scrollable/filter rows. Pages may pass their own dropdownParent
 * (e.g. a `.modal`) — we only set body when missing.
 */
(function (window, $) {
    'use strict';
    if (!$ || !$.fn || !$.fn.select2) {
        return;
    }
    var orig = $.fn.select2;
    var $body = $(document.body);

    $.fn.select2 = function () {
        var args = Array.prototype.slice.call(arguments);
        if (
            args.length > 0 &&
            typeof args[0] === 'object' &&
            args[0] !== null &&
            !Array.isArray(args[0])
        ) {
            var opts = $.extend(true, {}, args[0]);
            if (!opts.dropdownParent) {
                opts.dropdownParent = $body;
            }
            args[0] = opts;
        }
        return orig.apply(this, args);
    };
})(window, jQuery);
