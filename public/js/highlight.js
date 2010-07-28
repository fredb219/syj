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
