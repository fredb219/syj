/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */
document.observe("dom:loaded", function() {
    if (window.top !== window) {
        $("other-language").hide();
        $("footer").hide();
    }
});
