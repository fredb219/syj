/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

var CloseBtn = Class.create({
    initialize: function(elt, options) {
        var btn, imgsrc, style;

        elt = $(elt);
        if (!elt) {
            return;
        }

        if (typeof options !== "object") {
            options = {};
        }

        style = Object.extend({
            'float': "right",
            margin: "2px",
            fontWeight: "bold",
            padding: "0px"
        }, options.style);

        imgsrc = (options.closeBtnSrc) || "icons/close.png";
        btn = new Element("input", { type: "image", src: imgsrc, alt: "X"}).setStyle(style);
        elt.insert({top: btn});
        btn.observe("click", function(evt) {
            evt.stop();
            if (typeof options.callback === "function") {
                options.callback.call(elt);
            }
            elt.hide();
        });
    }
});

var Toggler = Class.create({
    options: {},

    close: function() {
        this.element.src = this.options.openIcn;
        this.target.hide();
        document.fire('toggler:close', this);
    },

    open: function() {
        this.element.src = this.options.closeIcn;
        this.target.show();
        document.fire('toggler:open', this);
    },

    toggle: function(evt) {
        if (evt && typeof evt.stop === "function") {
            evt.stop();
        }
        if (this.target.visible()) {
            this.close();
        } else {
            this.open();
        }
    },

    initialize: function(target, options) {
        this.options = Object.extend({
                openIcn: 'icons/bullet_arrow_right.png',
                closeIcn: 'icons/bullet_arrow_down.png'
            }, options);

        this.target = $(target).hide();
        this.element = new Element("img").setStyle({ border: 'none',  // in firefox, in image inside an anchor has a border
                                                    verticalAlign: "middle"});
        this.element.observe('click', this.toggle.bindAsEventListener(this));

        if (this.options.autoOpen) {
            this.open();
        } else {
            this.close();
        }
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
    },
    text: function(element, content) {
        if (typeof content === "undefined") { // getter
            if (element.nodeType === 8) {
                return "";
            } else if (element.nodeType === 3 || element.nodeType === 4)  {
                return element.nodeValue;
            } else {
                return $A(element.childNodes).inject("", function(acc, el) {
                    return acc + Element.text(el);
                 });
            }
        } else { // setter
            var node = document.createTextNode(content);
            element.update().appendChild(node);
            return element;
        }
    }
});

Ajax.TimedRequest = Class.create(Ajax.Request, {
    timeout: null,
    delay: null,

    abort: function() {
        // see http://blog.pothoven.net/2007/12/aborting-ajax-requests-for-prototypejs.html
        this.transport.onreadystatechange = Prototype.emptyFunction;
        this.transport.abort();
        Ajax.activeRequestCount--;
    },

    initialize: function($super, url, delay, options) {
        this.delay = delay;
        if (!options) {
            options = {};
        }

        options.onSuccess = options.onSuccess &&
            options.onSuccess.wrap(function(proceed, transport, json) {
            if (this.timeout) {
                window.clearTimeout(this.timeout);
                this.timeout = null;
            }
            if (transport.getStatus() === 0) {
                this.options.onFailure(transport, json);
            } else {
                proceed(transport, json);
            }
        }).bind(this);

        options.onFailure = options.onFailure &&
            options.onFailure.wrap(function(proceed, transport, json) {
            if (this.timeout) {
                window.clearTimeout(this.timeout);
                this.timeout = null;
            }
            proceed(transport, json);
        }).bind(this);

        $super(url, options);
    },

    request: function($super, url) {
        this.timeout = function() {
            if (this.options.onFailure) {
                this.options.onFailure(null);
            }
            this.abort();
        }.bind(this).delay(this.delay);
        $super(url);
    }
});

Ajax.Responders.register({
    // needed for Ajax.TimedRequest.abort to work: see
    // http://blog.pothoven.net/2007/12/aborting-ajax-requests-for-prototypejs.html
    // again
    onComplete: function() {
        Ajax.activeRequestCount--;
        if (Ajax.activeRequestCount < 0) {
            Ajax.activeRequestCount = 0;
        }
    }
});

// wrapper around Form.request that sets up the submit listener, stops the
// submit event, calls presubmit function, calls Form.request and calls a
// postsubmit function
Element.addMethods('form', {
    ajaxize : function(form, options) {
        var reqoptions;

        options = Object.clone(options || {});

        $(form).observe('submit', function(evt) {
            evt.stop(); // cancel form submission

            reqoptions = Object.clone(options);
            delete(reqoptions.presubmit);
            delete(reqoptions.postsubmit);
            delete(reqoptions.delay);

            if (Object.isFunction(options.presubmit)) {
                if (options.presubmit(this) === false) {
                    return;
                }
            }

            var params = reqoptions.parameters, action = this.readAttribute('action') || '';

            if (action.blank()) {
                action = window.location.href;
            }
            reqoptions.parameters = this.serialize(true);

            if (params) {
                if (Object.isString(params)) {
                    params = params.toQueryParams();
                }
                Object.extend(reqoptions.parameters, params);
            }

            if (this.hasAttribute('method') && !reqoptions.method) {
                reqoptions.method = this.method;
            }

            if (reqoptions.onFailure) {
                reqoptions.onFailure = reqoptions.onFailure.wrap(function(proceed, transport, json) {
                    form.enable();
                    proceed(transport, json);
                });
            } else {
                reqoptions.onFailure = function() {
                    form.enable();
                };
            }

            if (reqoptions.onSuccess) {
                reqoptions.onSuccess = reqoptions.onSuccess.wrap(function(proceed, transport, json) {
                    form.enable();
                    proceed(transport, json);
                });
            } else {
                reqoptions.onSuccess = function() {
                    form.enable();
                };
            }

            new Ajax.TimedRequest(action, options.delay || 20, reqoptions);

            if (Object.isFunction(options.postsubmit)) {
                options.postsubmit(this);
            }
            Form.getElements(form).each(function(elt) {
                elt.blur();
                elt.disable();
            });
        });
    },

    setfocus: function(form) {
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
    },

    observe : Element.Methods.observe.wrap(function(proceed, element, eventName, handler) {
        if (eventName === "contentchange") {
            proceed(element, 'keyup', function(evt) {
                if (evt.keyCode === 13) {
                    return;
                }
                handler.apply(null, arguments);
            });
            proceed(element, 'paste', handler);
            return proceed(element, 'change', handler);
        }
        return proceed(element, eventName, handler);
    }),

    timedobserve: function(element, callback, delay) {
        var timeout = null, initialvalue = element.value;

        if (typeof delay !== "number") {
            delay = 0.5;
        }
        delay = delay * 1000;

        var canceltimer = function() {
            if (timeout) {
                clearTimeout(timeout);
                timeout = null;
            }
        };
        var resettimer = function() {
            canceltimer();
            timeout = setTimeout(triggercallback, delay);
        };
        var triggercallback = function() {
            canceltimer();
            if (initialvalue !== element.value) {
                initialvalue = element.value;
                callback.call(element);
            }
        };

        element.observe('blur', triggercallback).
             observe('keyup', resettimer).
             observe('paste', resettimer);
        return element;
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

        if ($A(div.childNodes).filter(function(node) {
                return (node.nodeType === 3 || node.tagName.toLowerCase() === 'br');
             }).length) {
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
