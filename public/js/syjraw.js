var WGS84 = new OpenLayers.Projection("EPSG:4326");
var Mercator = new OpenLayers.Projection("EPSG:900913");

var styleMap = {
    view: new OpenLayers.StyleMap({
        "default": new OpenLayers.Style({
            strokeColor: "blue",
            strokeWidth: 5,
            strokeOpacity: 0.7
        })
    })
};

function resizeMap() {
    var map = document.getElementById('map');
    map.style.width = map.offsetWidth.toString() + 'px';
    map.style.height = map.offsetHeight.toString() + 'px';
}

function mapquestLayer() {
      return new OpenLayers.Layer.OSM("Mapquest", [
                'http://otile1.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png',
                'http://otile2.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png',
                'http://otile3.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png',
                'http://otile4.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png'],
                    { attribution: SyjStrings.mapquestAttribution});
}

function osmLayer() {
    return new OpenLayers.Layer.OSM("OSM", [
                'http://a.tile.openstreetmap.org/${z}/${x}/${y}.png',
                'http://b.tile.openstreetmap.org/${z}/${x}/${y}.png',
                'http://c.tile.openstreetmap.org/${z}/${x}/${y}.png'],
                { attribution: SyjStrings.osmAttribution});
}

function init() {
    var map = new OpenLayers.Map('map', {
                controls: [ new OpenLayers.Control.Attribution() ],
                theme: null}),

         parameters = OpenLayers.Util.getParameters(window.location.href),
         baseLayer = null,

         layerOptions = {format:     OpenLayers.Format.WKT,
                        projection: WGS84,
                        styleMap:   styleMap.view,
                        attribution: SyjStrings.geomAttribution },

        viewLayer = new OpenLayers.Layer.Vector("View Layer", layerOptions),
        wkt = new OpenLayers.Format.WKT({ internalProjection: Mercator, externalProjection: WGS84 });

    if (parameters.layer) {
        switch (parameters.layer.toUpperCase()) {
            case 'M':
             baseLayer = mapquestLayer();
            break;
            case 'O':
             baseLayer = osmLayer();
            break;
        }
    }

    if (!baseLayer) {
        baseLayer = osmLayer();
    }

    map.addLayers([baseLayer, viewLayer]);
    viewLayer.addFeatures([wkt.read(gInitialGeom.data)]);
    extent = viewLayer.getDataExtent();
    map.updateSize();
    map.zoomToExtent(extent);
    resizeMap();
}

window.onresize = function() {
    resizeMap();
};

if (window.addEventListener) {
    window.addEventListener("load", init, false);
} else {
    window.attachEvent("onload", init);
}
