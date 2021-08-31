(function ($) {
    'use strict';

    var inputEvents = "input";
    if (!("oninput" in document || "oninput" in $("<input>")[0])) {
        inputEvents += " keypress keyup";
    }

    jQuery.fn.restrict = function(sanitizationFunc) {

        return this.each(function(){
            // the element(s) to be restricted
            var $this = $(this);

            $this.bind(inputEvents, function() {
                var val = $this.val();
                var sanitizedVal = sanitizationFunc(val);

                if (val !== sanitizedVal) {
                    $this.val(sanitizedVal);
                }
            });
        });
    };

    /*
* Every time the form field is changed, modify its contents by eliminating
* matches for the given regular expression within the field.
*/
    jQuery.fn.regexRestrict = function(regex){
        var sanitize = function(text) {
            return text.replace(regex, '');
        };
        $(this).restrict(sanitize);
    };
})(jQuery);

/* These functions require the jquery.formrestrict.js functions to work!
*
* These functions can be used to replace the functionality of the jQuery
* AlphaNumeric plugin. The usage of this plugin is almost identical to that of
* the AlphaNumeric plugin.
*/

(function ($) {
    jQuery.fn.alphanumeric = function(r) {
        alphanumericHelper(this, r, true, true, false);
    };
    jQuery.fn.numeric = function(r) {
        alphanumericHelper(this, r, false, true, false);
    };
    jQuery.fn.alpha = function(r) {
        alphanumericHelper(this, r, true, false, false);
    };
    jQuery.fn.alphanumericSpaces = function(r) {
        alphanumericHelper(this, r, true, true, true);
    };
    jQuery.fn.numericSpaces = function(r) {
        alphanumericHelper(this, r, false, true, true);
    };
    jQuery.fn.alphaSpaces = function(r) {
        alphanumericHelper(this, r, true, false, true);
    };
    var alphanumericHelper = function(obj, restraints, alpha, numeric, spaces) {
        var regex = "";
        if (spaces)
            regex += " ";
        if (numeric)
            regex += "0-9";
        if (alpha) {
            if (restraints == undefined || !restraints.allcaps)
                regex += "a-z";
            if (restraints == undefined || !restraints.nocaps)
                regex += "A-Z";
        }
        if (restraints != undefined && restraints.allow != undefined)
            regex += RegExp.escape(restraints.allow);

        $(obj).regexRestrict(RegExp("[^"+regex+"]", "g"))
    };
})(jQuery);

RegExp.escape = function(text) {
    return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
};