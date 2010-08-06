/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */
function insertErrorBefore(elt, messageError) {
    var message = new Element("div", {className: 'error'}).update(messageError);
    elt.insert({before: message});
}

document.observe("dom:loaded", function() {
    var errors, tofocus;

    errors = $$('.error');
    tofocus = null;
    if (errors.length) {
        tofocus = $('newpwd_email');
    } else {
        tofocus = $$('form input:not([readonly],[disabled])')[0];
    }
    if (tofocus) {
        tofocus.focus();
        tofocus.select();
    }

    $("newpwdform").observe('submit', function(evt) {
        $$('.error').invoke('remove');
        if ($('newpwd_email').value.strip().empty()) {
            insertErrorBefore($('newpwdform').select('table')[0], SyjStrings.notEmptyField);
            $('newpwd_email').highlight('#F08080').focus();
            $('newpwd_email').select();
            evt.stop();
        }
    });

});
