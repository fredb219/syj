/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

document.observe("dom:loaded", function() {
    var currentmail = $("account_email").value, form = $("accountform");

    form.focus();

    form.observe('submit', function(evt) {
        var control, errorElements;

        $$('.error').invoke('remove');

        errorElements = $$('#account_email, #account_password').findAll(
            function(elt) {
                return (!elt.check(function() {
                    return !this.value.strip().empty();
                }, SyjStrings.notEmptyField));
            });


        if (!errorElements.length) {
            control = $("account_password");
            if (!control.check(function() {
                return this.value.length >= 6;
            }, SyjStrings.passwordLenghtWarn)) {
                errorElements.push(control);
            }
        }

        if (!errorElements.length) {
            control = $("account_password");
            if (control.check(function() {
                return this.value === $("account_password_confirm").value;
            }, SyjStrings.passwordNoMatchWarn)) {
                errorElements.push(control);
            }
        }

        if (!errorElements.length) {
            control = $("account_email");
            if (!control.check(function() {
                return this.value !== currentmail || $("account_password").value !== $("account_password_current").value;
            }, SyjStrings.nochangeWarn)) {
                errorElements.push(control);
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
