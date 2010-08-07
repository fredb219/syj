/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

document.observe("dom:loaded", function() {
    var form = $("loginform");
    form.focus();

    form.observe("submit", function(evt) {
        var loginput = $("login_user");
        $$('.error').invoke('remove');

        if (!loginput.check(function() { return !this.value.strip().empty(); }, SyjStrings.userEmptyWarn)) {
            loginput.highlight('#F08080').activate();
            evt.stop();
            return;
        }
    });
});
