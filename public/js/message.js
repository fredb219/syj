/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */
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
        var node = div.firstChild;
        while (node) {
            var nextNode = node.nextSibling;
            if (node.nodeType == 3 || node.tagName.toLowerCase() == 'br') {
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
