var WGS84 = new OpenLayers.Projection("EPSG:4326");
var Mercator = new OpenLayers.Projection("EPSG:900913");


document.observe("dom:loaded", function() {
    $("message").hide();
    $$(".map").each(function(elt) {
        var geom = elt.getAttribute('data-geom'),
            baseLayer = new OpenLayers.Layer.OSM("OSM"),
            map = new OpenLayers.Map(elt, { controls: [], theme: null}),
            layerOptions = {format:     OpenLayers.Format.WKT,
                            projection: WGS84,
                            styleMap:   new OpenLayers.StyleMap({
                                            "default": new OpenLayers.Style({
                                                strokeColor: "blue",
                                                strokeWidth: 5,
                                                strokeOpacity: 0.7
                                            })
                                         })},
            wkt = new OpenLayers.Format.WKT({ internalProjection: Mercator, externalProjection: WGS84 }),
            viewLayer = new OpenLayers.Layer.Vector("View Layer", layerOptions),
            extent;

        map.addLayers([baseLayer, viewLayer]);
        viewLayer.addFeatures([wkt.read(geom)]);
        map.zoomToExtent(viewLayer.getDataExtent());
    });

    $$(".item").each(function(elt) {
        new item(elt);
    });
});

function item(elt) {
    this.deleteHandler = elt.on('click', '.delete-link', this.remove.bindAsEventListener(this));
    this.elt = elt;
}
item.prototype = {
    deleteSuccess: function() {
        this.deactivate();
        $("message").setMessage(SyjStrings.deleteSuccess, "success");
    },

    deactivate: function() {
        this.elt.down('.title').update();
        this.elt.down('.geom').update().setStyle({backgroundColor: 'gray'});
        this.deleteHandler.stop();
        this.elt.on('click', 'a', function(evt) { evt.stop(); });
        this.elt.select('a').invoke('setStyle', {textDecoration: 'line-through'});
    },

    deleteFailure: function(transport) {
        var httpCode = 0, message = "";
        if (transport) {
            httpCode = transport.getStatus();
        }
        switch (httpCode) {
            case 0:
                message = SyjStrings.notReachedError;
            break;
            case 400:
            case 403:
                location = loginlink();
                return;
            break;
            case 404:
                 message = SyjStrings.requestError;
            break;
            case 410:
                this.deactivate();
                message = SyjStrings.gonePathError;
            break;
            case 500:
                message = SyjStrings.serverError;
            break;
            default:
                message = SyjStrings.unknownError;
            break;
        }
        $("message").setMessage(message, "error");
    },

    remove: function(evt) {
        evt.stop();
        if (!confirm(SyjStrings.confirmDelete)) {
            return;
        }
        var id = this.elt.getAttribute('data-id');

        $("message").hide();
        new Ajax.Request('path/' + id.toString() + '/delete', {
            method: 'post',
            onSuccess: this.deleteSuccess.bind(this),
            onFailure: this.deleteFailure.bind(this)
        });
    }
};

function loginlink() {
    var lang;
    if (location.search && location.search.length && location.search[0] === '?') {
        lang = location.search.slice(1).split('&').find(function(str) {
            return str.startsWith('lang=');
        });
        if (lang) {
            lang = lang.slice('lang='.length);
        }
    }
    return 'login?redirect=' + encodeURIComponent(location.pathname + location.search) + ((lang) ? '&lang=' + lang: "");
}
