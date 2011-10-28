/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

"use strict";

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
            if (evt.detail === 0) { // it's not a real click, possibly a submit event
                return;
            }
            if (typeof options.callback === "function") {
                options.callback.call(elt);
            }
            if (typeof elt.clearMessages === "function") {
                elt.clearMessages();
            } else {
                elt.hide();
            }
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
// postsubmit function. If form has some visible and activated file inputs,
// execute presubmit, but do not send the file with ajax.
Element.addMethods('form', {
    ajaxize : function(form, options) {
        var reqoptions;

        options = Object.clone(options || {});

        $(form).observe('submit', function(evt) {

            reqoptions = Object.clone(options);
            delete(reqoptions.presubmit);
            delete(reqoptions.postsubmit);
            delete(reqoptions.delay);

            if (Object.isFunction(options.presubmit)) {
                if (options.presubmit(this) === false) {
                    evt.stop(); // cancel form submission
                    return;
                }
            }

            // get list of input file not disabled, and not hidden
            if (this.getInputs('file').find(function(elt) {
                if (elt.disabled) {
                    return false;
                }
                while (elt && $(elt).identify() !== this.identify()) {
                    if (!elt.visible()) {
                        return false;
                    }
                    elt = elt.parentNode;
                }
                return true;
             }.bind(this))) {
                // form has some file inputs. Do not manage on our own.
                return;
            }

            evt.stop(); // cancel form submission

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
            proceed(element, 'paste', handler.defer.bind(handler));
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

Element.addMethods('div', (function() {
    var supportsTransition = false, endTransitionEventName = null;

    if (window.addEventListener) { // fails badly in ie: prevents page from loading
        var div = $(document.createElement('div'));
        var timeout = null;

        var cleanup = function() {
            if (timeout) {
                window.clearTimeout(timeout);
                timeout = null;
                div.stopObserving('webkitTransitionEnd');
                div.stopObserving('transitionend');
                div.stopObserving('oTransitionend');
                Element.remove.defer(div);
            }
        }

        var handler = function(e) {
            supportsTransition = true;
            endTransitionEventName = e.type;
            cleanup();
        }
        div.observe('webkitTransitionEnd', handler).observe('transitionend', handler) .observe('oTransitionend', handler);
        div.setStyle({'transitionProperty': 'opacity',
                      'MozTransitionProperty': 'opacity',
                      'WebkitTransitionProperty': 'opacity',
                      'OTransitionProperty': 'opacity',
                      'transitionDuration': '1ms',
                      'MozTransitionDuration': '1ms',
                      'WebkitTransitionDuration': '1ms',
                      'OTransitionDuration': '1ms'});
        $(document.documentElement).insert(div);
        Element.setOpacity.defer(div, 0);
        window.setTimeout(cleanup, 100);
    }

    function removeMessages(div) {
        var node = div.firstChild, nextNode;

        while (node) {
            nextNode = node.nextSibling;
            if (node.nodeType === 3 || node.tagName.toLowerCase() === 'br' || node.textContent || node.innerText) {
                div.removeChild(node);
            }
                node = nextNode;
        }
        return div;
    };

    function hasOpacityTransition(div) {
        return ([div.getStyle('transition-property'),
                 div.getStyle('-moz-transition-property'),
                 div.getStyle('-webkit-transition-property'),
                 div.getStyle('-o-transition-property')
                 ].join(' ').split(' ').indexOf('opacity') !== -1);
    }

    function hide(div) {
        div = $(div);
        if (supportsTransition && hasOpacityTransition(div)) {
            div.observe(endTransitionEventName, function() {
                div.stopObserving(endTransitionEventName);
                if (!div.getOpacity()) { // in case show has been called in-between
                  div.hide();
                }
            });
            div.setOpacity(0);
        } else {
            div.hide();
        }
    }

    function show(div) {
        div = $(div);
        div.show();
        // we need to set opacity to 0 before calling hasOpacityTransition
        // otherwise we trigger mozilla #601190
        div.setOpacity(0);
        if (supportsTransition && hasOpacityTransition(div)) {
            // display = '' then opacity = 1;
            Element.setOpacity.defer(div, 1);
        } else {
            div.setOpacity(1);
        }
    }

    function clearMessages(div) {
        if (div.getOpacity()) {
            hide(div);
        }
        return div;
    }

    function setMessage(div, message, status) {
        removeMessages(div);
        if (status) {
            div.setMessageStatus(status);
        }
        if (message) {
            div.addMessage(message);
        }
        return div;
    }

    function addMessage(div, message) {
        var node = (div.ownerDocument || document).createTextNode(message);

        if ($A(div.childNodes).filter(function(node) {
                return (node.nodeType === 3 || node.tagName.toLowerCase() === 'br' || node.textContent || node.innerText);
             }).length) {
            div.insert(new Element('br'));
        }

        div.appendChild(node);
        if (!div.getOpacity()) {
            show(div);
        }
        return div;
    }

    function setMessageStatus(div, status) {
        $A(["error", "warn", "info", "success", "optional"]).each(function(clname) {
            div.removeClassName(clname);
        });
        if (typeof status === "string") {
            div.addClassName(status);
        } else {
            $A(status).each(function(clname) {
                div.addClassName(clname);
            });
        }
        return div;
    }

    return {
        setMessage: setMessage,
        clearMessages: clearMessages,
        addMessage: addMessage,
        setMessageStatus: setMessageStatus
    };

})());
