/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

// wrapper around Form.request that sets up the submit listener, stops the
// submit event, calls presubmit function, calls Form.request and calls a
// postsubmit function
Element.addMethods('form', {ajaxize : function(form, options) {
    var reqoptions, timeout;

    options = Object.clone(options);
    reqoptions = Object.clone(options);
    timeout = null;

    function onSuccess(transport, json) {
        if (timeout) {
            window.clearTimeout(timeout);
            timeout = null;
        }
        if (transport.getStatus() === 0) {
            options.onFailure(transport, json);
        } else {
            options.onSuccess(transport, json);
        }
    }

    function onFailure(transport, json) {
        if (timeout) {
            window.clearTimeout(timeout);
            timeout = null;
        }
        options.onFailure(transport, json);
    }

    delete(reqoptions.presubmit);
    delete(reqoptions.postsubmit);

    $(form).observe('submit', function(evt) {
        var req;

        evt.stop(); // cancel form submission
        if (Object.isFunction(options.presubmit)) {
            if (options.presubmit(this) === false) {
                return;
            }
        }
        req = this.request(Object.extend(reqoptions, {
            onSuccess: onSuccess,
            onFailure: onFailure
        }));
        timeout = (function() {
            options.onFailure(null);
            req.abort();
        }).delay(options.timeout || 20);
        if (Object.isFunction(options.postsubmit)) {
            options.postsubmit(this);
        }
    });
}});
