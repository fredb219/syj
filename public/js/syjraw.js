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

function init() {
    var map = new OpenLayers.Map('map', {
                controls: [ new OpenLayers.Control.Attribution() ],
                theme: null}),

         baseLayer = new OpenLayers.Layer.OSM("OSM", null, { attribution: SyjStrings.osmAttribution }),

         layerOptions = {format:     OpenLayers.Format.WKT,
                        projection: WGS84,
                        styleMap:   styleMap.view,
                        attribution: SyjStrings.geomAttribution },

        viewLayer = new OpenLayers.Layer.Vector("View Layer", layerOptions),
        wkt = new OpenLayers.Format.WKT({ internalProjection: Mercator, externalProjection: WGS84 });

    map.addLayers([baseLayer, viewLayer]);
    viewLayer.addFeatures([wkt.read(gInitialGeom.data)]);
    extent = viewLayer.getDataExtent();
    map.updateSize();
    map.zoomToExtent(extent);

}

if (window.addEventListener) {
    window.addEventListener("load", init, false);
} else {
    window.attachEvent("onload", init);
}
