/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */
Element.addMethods(['input', 'textarea'], {
    check: function(control, callback, errorMessage) {
        if (callback.call(control)) {
            return true;
        }
        control.insert({
            after: new Element("div", {className: 'error'}).update(errorMessage)
        });
        return false;
    }
});

Element.addMethods('form', {
    focus: function(form) {
        var tofocus, error;

        tofocus = null;
        error = form.down('.error');
        if (error) {
            tofocus = error.previous('input,textarea');
        } else {
            tofocus = form.down('input:not([readonly],[disabled]),textarea:not([readonly][disabled])');
        }
        if (tofocus) {
            if (error && (typeof tofocus.highlight === "function")) {
                tofocus.highlight('#F08080');
            }
            tofocus.focus();
            tofocus.select();
        }
    },

    checkEmptyElements: function(form, errorMessage) {
        var results = [];
        form.select('.required').each(function(elt) {
            var id = elt.getAttribute('for'), control = $(id);
            if (!control) {
                return;
            }
            if (!control.check(function() {
                    return !this.value.strip().empty();
                }, errorMessage)) {
                results.push(control);
            }
        });
        return results;
    }

});
