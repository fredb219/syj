/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

document.observe("dom:loaded", function() {
    var form = $("contactform");
    form.focus();

    form.observe('submit', function(evt) {
        var control, errorElements;

        $$('.error').invoke('remove');

        errorElements = $$('input:not([type="submit"]),textarea').findAll(
            function(elt) {
                return (!elt.check(function() {
                    return !this.value.strip().empty();
                }, SyjStrings.notEmptyField));
            });

        if (!errorElements.length) {
            control = $("contact_email");
            if (!control.check(function() {
                return this.value.match(/^[A-Z0-9._\-]+@[A-Z0-9][A-Z0-9.\-]{0,61}[A-Z0-9]\.[A-Z.]{2,6}$/i);
              }, SyjStrings.invalidMail)) {
                errorElements.push($("contact_email"));
            }
        }

        /*
         * if there are errors, cancel submission
         */
        if (errorElements.length) {
            errorElements[0].focus();
            errorElements[0].select();
            evt.stop();
        }
    });

});
