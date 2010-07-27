/* Copyright (c) 2010 Arnaud Renevier, Inc, published under the modified BSD
 * license. */

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
