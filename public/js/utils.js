/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

var CloseBtn = Class.create({
    initialize: function(elt, options) {
        var btn, imgsrc, style;

        elt = $(elt);
        if (!elt) {
            return;
        }

        style = Object.extend({
            float: "right",
            margin: "2px",
            fontWeight: "bold",
            padding: "0px"
        }, typeof options === "object" ? options.style: {});

        imgsrc = (options && options.closeBtnSrc) || "icons/close.png";
        btn = new Element("input", { type: "image", src: imgsrc, alt: "X"}).setStyle(style);
        elt.insert({top: btn});
        btn.observe("click", function(evt) {
            elt.hide();
        });
    }
});

var Deck = Class.create({
    initialize: function(elt, options) {
        this.element = $(elt);
        this.index = null;
        this.setIndex(parseInt(this.element.readAttribute("selectedindex") || 0, 10));
    },
    setIndex: function(idx) {
        if (idx === this.index) {
            return;
        }

        var childs = this.element.childElements();
        if (childs.length === 0) {
            this.index = -1;
            return;
        }
        idx = Math.max(0, idx);
        idx = Math.min(childs.length - 1, idx);

        childs.each(function(item, i) {
            if (idx === i) {
                item.show();
            } else {
                item.hide();
            }
        });
        this.index = idx;
    },
    getIndex: function() {
        return this.index;
    }
});

Element.addMethods({
    highlight: function(element, color, timeout) {
        var current;
        if (typeof timeout === "undefined") {
            timeout = 0.3;
        }
        current = element.getStyle('backgroundColor');
        Element.setStyle(element, {'backgroundColor': color});
        Element.setStyle.delay(timeout, element, {'backgroundColor': current});
        return element;
    }
});

// wrapper around Form.request that sets up the submit listener, stops the
// submit event, calls presubmit function, calls Form.request and calls a
// postsubmit function
Element.addMethods('form', {
    ajaxize : function(form, options) {
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
    },

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
            tofocus.activate();
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

Element.addMethods('div', {
    setMessage: function(div, message, status) {
        div.clearMessages();
        if (status) {
            div.setMessageStatus(status);
        }
        if (message) {
            div.addMessage(message);
        }
        return div;
    },

    clearMessages: function(div) {
        var node = div.firstChild, nextNode;

        while (node) {
            nextNode = node.nextSibling;
            if (node.nodeType === 3 || node.tagName.toLowerCase() === 'br') {
                div.removeChild(node);
            }
                node = nextNode;
        }

        return div;
    },

    addMessage: function(div, message) {
        var node = (div.ownerDocument || document).createTextNode(message);
        if (!div.empty()) {
            div.insert(new Element('br'));
        }
        div.appendChild(node);
        return div.show();
    },

    setMessageStatus: function(div, status) {
        return div.removeClassName('error').
                removeClassName('warn').
                removeClassName('info').
                removeClassName('success').
                addClassName(status);
    }
});
