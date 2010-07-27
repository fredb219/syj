/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

document.observe("dom:loaded", function() {
    var form = $("loginform");
    form.focus();

    form.observe("submit", function(evt) {
        $$('.error').invoke('remove');

        if (!$("login_user").check(function() { return !this.value.strip().empty(); }, SyjStrings.userEmptyWarn)) {
            evt.stop();
            return;
        }
    });
});
