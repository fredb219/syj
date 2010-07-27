/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */
var Deck = Class.create();
Deck.prototype = {
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
};
