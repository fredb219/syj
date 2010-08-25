/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

// avoid openlayers alerts
OpenLayers.Console.userError = function(error) {
    SYJView.messenger.setMessage(error, "error");
};

var SyjSaveUI = {
    status: "unknown",

    init: function() {
        $("geom_title").observe('contentchange', this.enableSubmit.bindAsEventListener(this));
        return this;
    },

    enable: function() {
        if (this.status === "enabled") {
            return this;
        }
        this.enableSubmit();
        $("geom_title").disabled = false;
        $("geom_title").activate();
        $("geomform").removeClassName("disabled");
        this.status = "enabled";
        return this;
    },

    disable: function() {
        if (this.status === "disabled") {
            return this;
        }
        this.disableSubmit();
        $("geom_title").blur();
        $("geom_title").disabled = true;
        $("geomform").addClassName("disabled");
        this.status = "disabled";
        return this;
    },

    enableSubmit: function() {
        $("geom_submit").disabled = false;
        $("geom_accept").disabled = false;
        this.status = "partial";
        return this;
    },

    disableSubmit: function() {
        $("geom_submit").blur();
        $("geom_submit").disabled = true;
        $("geom_accept").blur();
        $("geom_accept").disabled = true;
        this.status = "partial";
        return this;
    }
};

var SYJPathLength = (function(){
    return {
        update: function() {
            var pathLength = 0, unit;
            if (SYJView.mode === 'view') {
                if (SYJView.viewLayer.features.length) {
                    pathLength = SYJView.viewLayer.features[0].geometry.getGeodesicLength(Mercator);
                }
            } else {
                pathLength = SYJView.editControl.handler.line.geometry.getGeodesicLength(Mercator);
            }

            if (pathLength === 0) {
                $("path-length").hide();
                return;
            }
            $("path-length").show();

            if (pathLength < 1000) {
                // precision: 1 cm
                pathLength = Math.round(pathLength * 100) / 100;
                unit = 'm';
            } else {
                // precision: 1 m
                pathLength = Math.round(pathLength) / 1000;
                unit = 'km';
            }
            $("path-length-content").update(pathLength + ' ' + unit);
        }
    };
}());

var SYJDataUi = (function() {
    var deck = null,
        infotoggler = null,
        getdeck = function() {
            if (!deck) {
                deck = new Deck("data_controls");
            }
            return deck;
        },
        getinfotoggler = function() {
            if (!infotoggler) {
                infotoggler = new Toggler('path-infos-content');
                $("path-infos-toggler").insert({bottom: infotoggler.element});
                $("path-infos-anchor").observe('click', function(evt) {
                    evt.stop();
                    infotoggler.toggle(evt);
                });
                document.observe('toggler:open', function(evt) {
                    if (evt.memo === infotoggler) {
                        // XXX: update informations
                    }
                });
            }
            return infotoggler;
        };
    return {
        viewmode: function() {
            getdeck().setIndex(0);
            if ($("path-infos")) {
                getinfotoggler();
                getinfotoggler().close();
                $("path-infos").show();
            }
        },
        editmode: function() {
            getdeck().setIndex(1);
            if ($("path-infos")) {
                $("path-infos").hide();
            }
        }
    };
}());

OpenLayers.Handler.SyjModifiablePath = OpenLayers.Class(OpenLayers.Handler.ModifiablePath, {
    mouseup: function(evt) {
        // do not add a point when navigating
        var mapControls = this.control.map.controls, idx, ctrl;

        for (idx = mapControls.length; idx-->0; ) {
            ctrl = mapControls[idx];
            if (this.isCtrlNavigationActive(ctrl, evt)) {
                return true;
            }
        }
        return OpenLayers.Handler.ModifiablePath.prototype.mouseup.apply(this, arguments);
    },

    addPoints: function(pixel) {
        // redraw before last point. As we have a special style for last point, we
        // need to redraw before last point after adding a new point (otherwise, it
        // keeps special style forever)
        var oldpoint = this.point;
        OpenLayers.Handler.ModifiablePath.prototype.addPoints.apply(this, arguments);
        this.layer.drawFeature(oldpoint);
    },

    isCtrlNavigationActive: function(ctrl, evt) {
        var tolerance = 4, xDiff, yDiff;

        if (!(ctrl instanceof OpenLayers.Control.Navigation)) {
            return false;
        }

        if (ctrl.zoomBox &&
            ctrl.zoomBox.active &&
            ctrl.zoomBox.handler &&
            ctrl.zoomBox.handler.active &&
            ctrl.zoomBox.handler.dragHandler &&
            ctrl.zoomBox.handler.dragHandler.start) {
            return true;
        }

        if (!ctrl.dragPan ||
            !ctrl.dragPan.active ||
            !ctrl.dragPan.handler ||
            !ctrl.dragPan.handler.started ||
            !ctrl.dragPan.handler.start) {
            return false;
        }

        // if mouse moved 4 or less pixels, consider it has not moved.
        tolerance = 4;

        xDiff = evt.xy.x - ctrl.dragPan.handler.start.x;
        yDiff = evt.xy.y - ctrl.dragPan.handler.start.y;

        if (Math.sqrt(Math.pow(xDiff,2) + Math.pow(yDiff,2)) <= tolerance) {
            return false;
        }
        return true;
    },

    finalize: function(cancel) {
        // do nothing. We don't want to finalize path
    }
});

var styleMap = {
    edit: new OpenLayers.StyleMap({
        "default": new OpenLayers.Style({
            pointRadius: "${radius}", // sized according to type attribute
            fillColor: "#ffcc66",
            strokeColor: "#ff9933",
            strokeWidth: 2,
            strokeOpacity: "${opacity}",
            fillOpacity: "${opacity}"
        },
        {
            context: {
                radius: function(feature) {
                    var features;

                    if (!(feature.geometry instanceof OpenLayers.Geometry.Point)) {
                        return 0;
                    }
                    if (feature.type === "middle") {
                        return 4;
                    }
                    features = feature.layer.features;
                    if (OpenLayers.Util.indexOf(features, feature) === 0) {
                        return 5;
                    } else if (OpenLayers.Util.indexOf(features, feature) === features.length - 1) {
                        return 5;
                    }
                    return 3;
                },
                opacity: function (feature) {
                    if (feature.type === "middle") {
                        return 0.5;
                    } else {
                        return 1;
                    }
                }
            }
        }),

        "select": new OpenLayers.Style({
            externalGraphic: "icons/delete.png",
            graphicHeight: 16
        }),

        "select_for_canvas": new OpenLayers.Style({
            strokeColor: "blue",
            fillColor: "lightblue"
        })

    }),

    view: new OpenLayers.StyleMap({
        "default": new OpenLayers.Style({
            strokeColor: "blue",
            strokeWidth: 5,
            strokeOpacity: 0.7
        })
    })
};

var WGS84 = new OpenLayers.Projection("EPSG:4326");
var Mercator = new OpenLayers.Projection("EPSG:900913");

var SYJView = {
    viewLayer: null,
    editControl: null,
    map: null,
    wkt: new OpenLayers.Format.WKT({ internalProjection: Mercator, externalProjection: WGS84 }),
    needsFormResubmit: false,
    unsavedRoute: null,
    mode: 'view',

    init: function() {
        var externalGraphic, baseURL, baseLayer, layerOptions, extent, hidemessenger;

        // is svg context, opera does not resolve links with base element is svg context
        externalGraphic = styleMap.edit.styles.select.defaultStyle.externalGraphic;
        baseURL = document.getElementsByTagName("base")[0].href;
        styleMap.edit.styles.select.defaultStyle.externalGraphic = baseURL + externalGraphic;

        this.map = new OpenLayers.Map('map', {
            controls: [
                new OpenLayers.Control.Navigation(),
                new OpenLayers.Control.PanZoom(),
                new OpenLayers.Control.Attribution()
            ],
            theme: null
        });

        baseLayer = new OpenLayers.Layer.OSM("OSM", null, { wrapDateLine: true , attribution: SyjStrings.osmAttribution });

        layerOptions = {format:     OpenLayers.Format.WKT,
                        projection: WGS84,
                        styleMap:   styleMap.view,
                        attribution: SyjStrings.geomAttribution };

        this.viewLayer = new OpenLayers.Layer.Vector("View Layer", layerOptions);
        this.map.addLayers([baseLayer, this.viewLayer]);

        if ($("edit-btn")) {
            $("edit-btn").observe('click', function() {
                $("geom_submit").value = SyjStrings.editAction;
                this.messenger.hide();
                this.editMode();
                this.mode = 'edit';
            }.bind(this));
        }

        if ($("create-btn")) {
            $("create-btn").observe('click', function() {
                $("geom_submit").value = SyjStrings.createAction;
                this.messenger.hide();
                this.editMode();
                this.mode = 'create';
            }.bind(this));
        }

        if ($("clone-btn")) {
            $("clone-btn").observe('click', function() {
                $("geom_submit").value = SyjStrings.cloneAction;
                $("geom_title").value = "";
                this.messenger.hide();
                this.editMode();
                this.mode = 'create';
            }.bind(this));
        }

        $("geomform").ajaxize({
                presubmit: this.prepareForm.bind(this),
                onSuccess: this.saveSuccess.bind(this),
                onFailure: this.saveFailure.bind(this)
                });
        SyjSaveUI.init();

        this.messenger = $('message');
        hidemessenger = this.messenger.empty();
        new CloseBtn(this.messenger, {
            style: {
                margin: "-1em"
            }
        });
        if (hidemessenger) {
            this.messenger.hide();
        }

        if (typeof gInitialGeom !== "undefined" && typeof gInitialGeom.data !== "undefined") {
            this.viewLayer.addFeatures([this.wkt.read(gInitialGeom.data)]);
            extent = this.viewLayer.getDataExtent();
            // XXX: ie has not guessed height of map main div yet during map
            // initialisation. Now, it will read it correctly.
            this.map.updateSize();
        } else {
            extent = new OpenLayers.Bounds(gMaxExtent.minlon, gMaxExtent.minlat, gMaxExtent.maxlon, gMaxExtent.maxlat)
                                         .transform(WGS84, Mercator);
        }
        this.map.zoomToExtent(extent);
        document.observe('simplebox:shown', this.observer.bindAsEventListener(this));
        SYJPathLength.update();

        if (FileList && FileReader) {
            $("map").observe("dragenter", function(evt) { evt.stop();});
            $("map").observe("dragover", function(evt) { evt.stop();});
            $("map").observe("drop", function(evt) {
                evt.stop();
                if (this.mode !== "view" || this.viewLayer.features.length) {
                    return;
                }
                if (!evt.dataTransfer.files.length) {
                    return;
                }
                var file = evt.dataTransfer.files[0];
                var reader = new FileReader();
                var readerror = function() {
                    this.messenger.setMessage(SyjStrings.dragFileError, "warn");
                }.bind(this);
                reader.onload = function(evt) {
                    if (evt.error) {
                        readerror();
                        return;
                    }

                    var results = null;
                    var content = evt.target.result;

                    var engine;
                    var formats = ['KML', 'GPX'];

                    for (var i = 0; i < formats.length; i++) {
                        engine = new OpenLayers.Format[formats[i]]({ internalProjection: Mercator, externalProjection: WGS84 });
                        try {
                            results = engine.read(content);
                        } catch(e) {
                        }
                        if (results || results.length) {
                            break;
                        }
                    }
                    if (!results || !results.length) {
                        readerror();
                        return;
                    }


                    var vector = results[0];
                    if (vector.geometry.CLASS_NAME !== "OpenLayers.Geometry.LineString") {
                        readerror();
                        return;
                    }
                    this.viewLayer.addFeatures([vector]);
                    this.map.zoomToExtent(this.viewLayer.getDataExtent());
                    this.editMode();
                    if (vector.data && vector.data.name) {
                        $("geom_title").value = vector.data.name;
                    }
                 }.bind(this);
                reader.readAsText(file);
           }.bind(this));
        }
    },

    observer: function(evt) {
        if (evt.eventName === "simplebox:shown" && evt.memo.element !== $("termsofusearea")) {
            this.messenger.hide();
        }
    },

    prepareForm: function(form) {
        if (!LoginMgr.logged && !$("geom_accept").checked) {
            this.messenger.setMessage(SyjStrings.acceptTermsofuseWarn, "warn");
            $("geom_accept_container").highlight('#F08080');
            $("geom_accept").activate();
            return false;
        }

        var line, realPoints, idx;

        line = new OpenLayers.Geometry.LineString();
        realPoints = this.editControl.handler.realPoints;
        for (idx = 0; idx < realPoints.length; idx++) {
            line.addComponent(realPoints[idx].geometry.clone());
        }
        this.viewLayer.addFeatures(new OpenLayers.Feature.Vector(line));

        this.viewMode();

        $("geom_data").value = this.wkt.write(new OpenLayers.Feature.Vector(line));
        if (this.mode === "edit" && typeof gLoggedInfo.pathid !== "undefined") {
            $("geomform").setAttribute("action", "path/" + gLoggedInfo.pathid.toString() + '/update');
        } else {
            $("geomform").setAttribute("action", "path");
        }
        this.needsFormResubmit = false;
        SyjSaveUI.disable.bind(SyjSaveUI).defer();
        this.messenger.hide();
        return true;
    },

    viewMode: function() {
        var handler = this.editControl.handler;
        OpenLayers.Handler.ModifiablePath.prototype.finalize.apply(handler, arguments);
        // we need to recreate them on next createFeature; otherwise
        // they'll reference destroyed features
        delete(handler.handlers.drag);
        delete(handler.handlers.feature);
        this.editControl.deactivate();
    },

    editMode: function() {
        var components, point0, point, pixels, pixel, idx;

        this.initEditControl();

        this.editControl.activate();
        if (this.viewLayer.features.length) {
            components = this.viewLayer.features[0].geometry.components;
            point0 = components[0];
            if (point0) {
                pixel = this.map.getPixelFromLonLat(new OpenLayers.LonLat(point0.x, point0.y));
                this.editControl.handler.createFeature(pixel);
                this.editControl.handler.lastUp = pixel;
                pixels = [];
                for (idx = 1; idx < components.length; idx++) {
                    point = components[idx];
                    pixels.push(this.map.getPixelFromLonLat(new OpenLayers.LonLat(point.x, point.y)));
                }
                this.editControl.handler.addPoints(pixels);
            }
            this.unsavedRoute = {
                features: this.viewLayer.features.invoke('clone'),
                title: $("geom_title").value
            };
        }

        this.viewLayer.destroyFeatures();

        SYJDataUi.editmode();
        if (this.editControl.handler.realPoints && this.editControl.handler.realPoints.length >= 2) {
            SyjSaveUI.disableSubmit();
        } else {
            SyjSaveUI.disable();
        }
    },

    initEditControl: function() {
        var styles;

        if (this.editControl) {
            return;
        }

        this.editControl = new OpenLayers.Control.DrawFeature(new OpenLayers.Layer.Vector(), OpenLayers.Handler.SyjModifiablePath, {
            callbacks: {
                modify: function(f, line) {
                    SYJPathLength.update();
                    if (!SYJView.unsavedRoute) {
                        SYJView.unsavedRoute = {};
                    }
                    if (this.handler.realPoints.length < 2) {
                        SyjSaveUI.disable();
                    } else {
                        SyjSaveUI.enable();
                    }
                }
            },

            handlerOptions: {
                layerOptions: {
                    styleMap: styleMap.edit
                }
            }
        });
        this.map.addControl(this.editControl);
        if (this.editControl.layer.renderer instanceof OpenLayers.Renderer.Canvas) {
            // using externalGraphic with canvas renderer is definitively too buggy
            styles = this.editControl.handler.layerOptions.styleMap.styles;
            styles.select = styles.select_for_canvas;
        }
        new CloseBtn($("geomform"), {
            style : {
                marginRight: "-40px",
                marginTop: "-20px"
            },
            callback: function(form) {
                this.viewMode();
                this.mode = 'view';
                SYJDataUi.viewmode();
                this.messenger.hide();

                if (this.unsavedRoute && typeof this.unsavedRoute.features !== "undefined") {
                    this.viewLayer.addFeatures(this.unsavedRoute.features);
                }
                if (this.unsavedRoute && typeof this.unsavedRoute.title !== "undefined") {
                    $("geom_title").value = this.unsavedRoute.title;
                } else {
                    $("geom_title").value = "";
                }
                this.unsavedRoute = null;
            }.bind(this)
        });
    },

    saveSuccess: function(transport) {
      this.unsavedRoute = null;

      if (transport.responseJSON && (typeof transport.responseJSON.redirect === "string")) {
          location = transport.responseJSON.redirect;
          return;
      }

      this.messenger.setMessage(SyjStrings.saveSuccess, "success");
      SYJDataUi.viewmode();
      document.title = $('geom_title').value;
    },

    saveFailure: function(transport) {
        var httpCode = 0, message = "";

        if (transport) {
            httpCode = transport.getStatus();
        }
        switch (httpCode) {
            case 0:
                message = SyjStrings.notReachedError;
            break;
            case 400:
            case 404:
                message = SyjStrings.requestError;
                if (transport.responseJSON) {
                    switch (transport.responseJSON.message) {
                        case "uniquepath":
                            message = SyjStrings.uniquePathError;
                        break;
                        default:
                        break;
                    }
                }
            break;
            case 403:
                message = "";
                SYJLogin.messenger.setMessage(SyjStrings.loginNeeded, "warn");
                SYJLogin.modalbox.show();
                this.needsFormResubmit = true;
            break;
            case 410:
                message = SyjStrings.gonePathError;
            break;
            case 500:
                message = SyjStrings.serverError;
                this.needsFormResubmit = true;
            break;
            default:
                message = SyjStrings.unknownError;
            break;
        }

        this.editMode();
        // is some cases, we let the user resubmit, in some other cases, he
        // needs to modify the path before submitting again
        if (this.needsFormResubmit) {
            SyjSaveUI.enable();
        }

        this.messenger.setMessage(message, "error");
    }
};

var SYJModalClass = Class.create({
    type: "",

    init: function() {
        this.area = $(this.type + '_area');
        this.messenger = $(this.type + "_message");
        this.modalbox = new SimpleBox(this.area, {
            closeMethods: ["onescapekey", "onouterclick", "onbutton"]
        });

        $(this.type + "_control_anchor").observe("click", function(evt) {
            this.modalbox.show();
            evt.stop();
        }.bindAsEventListener(this));

        document.observe('simplebox:shown', this.observer.bindAsEventListener(this));
        document.observe('simplebox:hidden', this.observer.bindAsEventListener(this));

        $(this.type + "form").ajaxize({
            presubmit: this.presubmit.bind(this),
            onSuccess: this.success.bind(this),
            onFailure: this.failure.bind(this)
        });
    },

    checkNotEmpty: function(input, message) {
        if ($(input).value.strip().empty()) {
            this.messenger.setMessage(message, "warn");
            $(input).highlight('#F08080').activate();
            return false;
        }
        return true;
    },

    observer: function(evt) {
        var simplebox, input;

        if (evt.eventName === "simplebox:shown" && evt.memo.element !== $("termsofusearea")) {
            simplebox = evt.memo;
            if (simplebox === this.modalbox) {
                input = this.area.select('input[type="text"]')[0];
                (function () {
                    input.activate();
                }).defer();
            } else {
                this.modalbox.hide();
            }

        } else if (evt.eventName === "simplebox:hidden" && evt.memo.element !== $("termsofusearea")) {
            simplebox = evt.memo;
            if (simplebox === this.modalbox) {
                this.reset();
            }
        }
    },

    failure: function(transport) {
        var httpCode = 0, message = SyjStrings.unknownError, input; // default message error

        if (transport) {
            httpCode = transport.getStatus();
        }

        switch (httpCode) {
            case 0:
                message = SyjStrings.notReachedError;
            break;
            case 400:
            case 404:
            case 410:
                message = SyjStrings.requestError;
            break;
            case 500:
                message = SyjStrings.serverError;
            break;
        }

        this.messenger.setMessage(message, "error");
        input = this.area.select('input[type="text"]')[0];
        input.highlight('#F08080').activate();
    },

    reset: function() {
        this.messenger.hide();
        this.area.select('.message').invoke('setMessageStatus', null);
    }
});

var SYJUserClass = Class.create(SYJModalClass, {
    type: "user",
    toubox: null,

    init: function($super) {
        $super();
        $("termsofusearea").hide();

        $$("#user_termsofuse_anchor, #geom_termsofuse_anchor").invoke('observe', "click", function(evt) {
            if (!this.toubox) {
                this.toubox = new SimpleBox($("termsofusearea"), {
                    closeMethods: ["onescapekey", "onouterclick", "onbutton"]
                });
            }
            this.toubox.show();
            if (!$("termsofuseiframe").getAttribute("src")) {
                $("termsofusearea").show();
                $("termsofuseiframe").setAttribute("src", evt.target.href);
            }
            evt.stop();
        }.bindAsEventListener(this));

        $$("#login_area_create > a").invoke('observe', 'click',
            function(evt) {
                this.modalbox.show();
                evt.stop();
            }.bindAsEventListener(this));

        $("user_pseudo-desc").hide();
        $("user_pseudo").observe('contentchange', function(evt) {
            var value = evt.target.value;
            PseudoChecker.reset();
            if (value && !(value.match(/^[a-zA-Z0-9_.]+$/))) {
                $("user_pseudo-desc").show().setMessageStatus("warn");
            } else {
                $("user_pseudo-desc").hide();
            }
        }).timedobserve(function() {
            PseudoChecker.check();
        });

        $("user_password").observe('contentchange', function(evt) {
            if (evt.target.value.length < 6) {
                $("user_password-desc").setMessageStatus("warn");
            } else {
                $("user_password-desc").setMessageStatus("success");
            }
        }.bindAsEventListener(this));

        $('account-create-anchor').insert({after: new Toggler('account-info').element});
    },

    presubmit: function() {
        this.messenger.hide();
        PseudoChecker.reset();
        if (!(this.checkNotEmpty("user_pseudo", SyjStrings.userEmptyWarn))) {
            return false;
        }

        if (!($("user_pseudo").value.match(/^[a-zA-Z0-9_.]+$/))) {
            $("user_pseudo-desc").show().setMessageStatus("warn");
            $("user_pseudo").highlight('#F08080').activate();
            return false;
        }

        if (PseudoChecker.exists[$("user_pseudo").value]) {
            PseudoChecker.availableMessage(false);
            $("user_pseudo").highlight('#F08080').activate();
            return false;
        }

        if (!(this.checkNotEmpty("user_password", SyjStrings.passwordEmptyWarn))) {
            return false;
        }

        if ($("user_password").value.length < 6) {
            $("user_password-desc").setMessageStatus("warn");
            $("user_password").highlight('#F08080').activate();
            return false;
        }

        if ($("user_password").value !== $("user_password_confirm").value) {
            this.messenger.setMessage(SyjStrings.passwordNoMatchWarn, "warn");
            $("user_password").highlight('#F08080').activate();
            return false;
        }

        if (!(this.checkNotEmpty("user_email", SyjStrings.emailEmptyWarn))) {
            return false;
        }

        if (!$("user_accept").checked) {
            this.messenger.setMessage(SyjStrings.acceptTermsofuseWarn, "warn");
            $("user_accept_container").highlight('#F08080');
            $("user_accept").activate();
            return false;
        }

        this.reset();
        return true;
    },

    success: function(transport) {
        LoginMgr.login();
        SYJView.messenger.setMessage(SyjStrings.userSuccess, "success");
        this.modalbox.hide();
        if (SYJView.needsFormResubmit) {
            SYJView.messenger.addMessage(SyjStrings.canResubmit);
            $("geom_submit").activate();
        }
    },

    failure: function($super, transport) {
        var httpCode = 0, focusInput = null, message = "";

        if (transport) {
            httpCode = transport.getStatus();
        }

        focusInput = null;
        message = "";

        switch (httpCode) {
            case 400:
                if (transport.responseJSON) {
                    switch (transport.responseJSON.message) {
                        case "invalidemail":
                            message = SyjStrings.emailInvalidWarn;
                            focusInput = $("user_email");
                        break;
                        case "uniquepseudo":
                            PseudoChecker.availableMessage(false);
                            focusInput = $("user_pseudo");
                        break;
                        case "uniqueemail":
                            message = SyjStrings.uniqueEmailError;
                            focusInput = $("user_email");
                        break;
                    }
                }
            break;
        }

        if (focusInput) {
            if (message) {
                this.messenger.setMessage(message, "error");
            }
            focusInput.highlight('#F08080').activate();
            return;
        }

        $super(transport);
    }
});
var SYJUser = new SYJUserClass();

var SYJLoginClass = Class.create(SYJModalClass, {
    type: "login",

    init: function($super) {
        $super();
    },

    presubmit: function() {
        this.messenger.hide();
        if (!(this.checkNotEmpty("login_user", SyjStrings.userEmptyWarn))) {
            return false;
        }

        this.reset();
        return true;
    },

    success: function(transport) {
        if (!transport.responseJSON ||
            typeof transport.responseJSON.iscreator !== "boolean" ||
            typeof transport.responseJSON.pseudo !== "string"
            ) {
            this.messenger.setMessage(SyjStrings.unknownError, "error");
            return;
        }
        LoginMgr.login(transport.responseJSON.iscreator);
        $$('.logged-pseudo').each(function(elt) {
            $A(elt.childNodes).filter(function(node) {
                return (node.nodeType === 3 || node.tagName.toLowerCase() === 'br');
            }).each(function(node) {
                node.nodeValue = node.nodeValue.replace('%s', transport.responseJSON.pseudo);
            });
        });
        SYJView.messenger.setMessage(SyjStrings.loginSuccess, "success");
        this.modalbox.hide();
        if (SYJView.needsFormResubmit) {
            SYJView.messenger.addMessage(SyjStrings.canResubmit);
            $("geom_submit").activate();
        }
    },

    failure: function($super, transport) {
        var httpCode = 0, focusInput = null, message = "";

        if (transport) {
            httpCode = transport.getStatus();
        }

        focusInput = null;
        message = "";

        switch (httpCode) {
            case 403:
                message = SyjStrings.loginFailure;
                focusInput = $("login_user");
            break;
        }

        if (message) {
            this.messenger.setMessage(message, "error");
            if (focusInput) {
                focusInput.highlight('#F08080').activate();
            }
            return;
        }

        $super(transport);
    }
});
var SYJLogin = new SYJLoginClass();

var SYJNewpwdClass = Class.create(SYJModalClass, {
    type: "newpwd",

    presubmit: function() {
        if (!(this.checkNotEmpty("newpwd_email", SyjStrings.emailEmptyWarn))) {
            return false;
        }
        this.reset();
        return true;
    },
    success: function(transport) {
        SYJView.messenger.setMessage(SyjStrings.newpwdSuccess, "success");
        this.modalbox.hide();
    }

});
var SYJNewpwd = new SYJNewpwdClass();

var LoginMgr = Object.extend(gLoggedInfo, {
    controlsdeck: null,

    updateUI: function() {
        if (!this.controlsdeck) {
            this.controlsdeck = new Deck("login_controls");
        }
        if (this.logged) {
            this.controlsdeck.setIndex(1);
            $$(".logged-hide").invoke('hide');
            $$(".logged-show").invoke('show');
        } else {
            this.controlsdeck.setIndex(0);
            $$(".logged-hide").invoke('show');
            $$(".logged-show").invoke('hide');
        }

        if ($("edit-btn")) {
            if (this.iscreator && SYJView.mode === 'view') {
                $("edit-btn").show();
            } else {
                $("edit-btn").hide();
            }
        }
    },

    login: function(aIsCreator) {
        if (typeof aIsCreator === "boolean") {
            this.iscreator = aIsCreator;
        }
        this.logged = true;
        this.updateUI();
    }
});

var PseudoChecker = {
    req: null,
    exists: {},
    currentvalue: null,
    messageelt: null,
    throbber: null,

    message: function(str, status, throbber) {
        var row;
        if (!this.messageelt) {
            row = new Element('tr');
            // we can't use row.update('<td></td><td><div></div></td>')
            // because gecko would mangle the <td>s
            row.insert(new Element('td'))
               .insert((new Element('td')).update(new Element('div')));

            $("user_pseudo").up('tr').insert({after: row});
            this.messageelt = new Element('span');
            this.throbber = new Element("img", { src: "icons/throbber.gif"});
            row.down('div').insert(this.throbber).insert(this.messageelt);
        }
        if (throbber) {
            this.throbber.show();
        } else {
            this.throbber.hide();
        }
        this.messageelt.up().setStyle({visibility: ''});
        this.messageelt.className = status;
        this.messageelt.update(str);
    },

    availableMessage: function(available) {
        var message = available ? SyjStrings.availablePseudo: SyjStrings.unavailablePseudo,
            status = available ? "success": "warn";
        this.message(message, status, false);
    },

    reset: function() {
        if (this.req) {
            this.req.abort();
            this.req = this.currentvalue = null;
        }
        if (this.messageelt) {
            this.messageelt.up().setStyle({visibility: 'hidden'});
        }
    },

    check: function() {
        var pseudo = $("user_pseudo").value;

        this.reset();

        if (!pseudo || !(pseudo.match(/^[a-zA-Z0-9_.]+$/))) {
            return;
        }

        if (typeof this.exists[pseudo] === "boolean") {
            this.reset();
            this.availableMessage(!this.exists[pseudo]);
            return;
        }

        this.message(SyjStrings.pseudoChecking, "", true);

        this.currentvalue = pseudo;
        this.req = new Ajax.TimedRequest('userexists/' + encodeURIComponent(pseudo), 20, {
            onFailure: this.failure.bind(this),
            onSuccess: this.success.bind(this)
        });
    },

    failure: function(transport) {
        var httpCode = 0, value = this.currentvalue;

        if (transport) {
            httpCode = transport.getStatus();
        }
        this.reset();
        if (httpCode === 404) {
            this.exists[value] = false;
            this.availableMessage(true);
        }

    },

    success: function(transport) {
        var httpCode = transport.getStatus(), value = this.currentvalue;
        this.reset();
        this.exists[value] = true;
        this.availableMessage(false);
    }
};

var Nominatim = (function() {
    var presubmit = function() {
        var input = $("nominatim-search");
        if (input.value.strip().empty()) {
            $("nominatim-message").setMessage(SyjStrings.notEmptyField, "warn");
            input.activate();
            return false;
        }
        $("nominatim-suggestions").hide();
        $("nominatim-message").hide();
        $("nominatim-throbber").show();
        return true;
    };

    var zoomToExtent = function(bounds) { // we must call map.setCenter with forceZoomChange to true. See ol#2798
        var center = bounds.getCenterLonLat();
        if (this.baseLayer.wrapDateLine) {
            var maxExtent = this.getMaxExtent();
            bounds = bounds.clone();
            while (bounds.right < bounds.left) {
                bounds.right += maxExtent.getWidth();
            }
            center = bounds.getCenterLonLat().wrapDateLine(maxExtent);
        }
        this.setCenter(center, this.getZoomForExtent(bounds), false, true);
    };

    var success = function(transport) {
        $("nominatim-throbber").hide();

        if (!transport.responseJSON || !transport.responseJSON.length) {
            $("nominatim-message").setMessage(SyjStrings.noResult, 'error');
            $("nominatim-search").activate();
            return;
        }

        var place = transport.responseJSON[0],
            bbox = place.boundingbox;

        if (!bbox || bbox.length !== 4) {
            $("nominatim-message").setMessage(SyjStrings.requestError, 'error');
            return;
        }

        extent = new OpenLayers.Bounds(bbox[2], bbox[1], bbox[3], bbox[0]).transform(WGS84, Mercator);
        zoomToExtent.call(SYJView.map, extent);

        $("nominatim-suggestions-list").update();

        var clickhandler = function(bbox) {
            return function(evt) {
                evt.stop();
                var extent = new OpenLayers.Bounds(bbox[2], bbox[1], bbox[3], bbox[0]).transform(WGS84, Mercator);
                $("nominatim-suggestions-list").select("li").invoke('removeClassName', 'current');
                evt.target.up('li').addClassName('current');
                SYJView.map.zoomToExtent(extent);
            };
        };

        for (var i = 0; i < transport.responseJSON.length; i++) {
            var item = transport.responseJSON[i];
            if (item.display_name && item.boundingbox && item.boundingbox.length === 4) {
                var li = new Element("li");
                var anchor = new Element("a", {
                    href: "",
                    className: "nominatim-suggestions-link"
                });

                anchor.observe('click', clickhandler(item.boundingbox));
                Element.text(anchor, item.display_name);

                var icon = new Element("img", {
                    className: "nominatim-suggestions-icon",
                    src: item.icon || 'icons/world.png'
                });
                li.insert(icon).insert(anchor);
                $("nominatim-suggestions-list").insert(li);
                if ($("nominatim-suggestions-list").childNodes.length >= 6) {
                    break;
                }
            }
        }

        if ($("nominatim-suggestions-list").childNodes.length > 1) {
            var bottomOffset = $('data_controls').measure('height') + 3;
            $("nominatim-suggestions").setStyle({
                bottom: (document.viewport.getHeight() - $('data_controls').cumulativeOffset().top + 3).toString() + 'px'
            }).show();
            $("nominatim-suggestions-list").select("li:first-child")[0].addClassName('current');
        } else {
            $("nominatim-suggestions").hide();
        }

    };

    var failure = function(transport) {
        $("nominatim-throbber").hide();

        var httpCode = 0, message = SyjStrings.unknownError, input; // default message error

        if (transport) {
            httpCode = transport.getStatus();
        }

        switch (httpCode) {
            case 0:
                message = SyjStrings.notReachedError;
            break;
            case 400:
            case 404:
                message = SyjStrings.requestError;
            break;
            case 500:
                message = SyjStrings.serverError;
            break;
        }

        $("nominatim-message").setMessage(message, 'error');
    };

    return {
        init: function() {
            if (!$("nominatim-form")) {
               return;
            }
            $("nominatim-controls").hide();
            $("nominatim-label").observe('click', function(evt) {
                $("nominatim-controls").show();
                $("nominatim-search").activate();
                evt.stop();
            });

            $("nominatim-form").ajaxize({
                presubmit: presubmit,
                onSuccess: success,
                onFailure: failure
              });
            new CloseBtn($("nominatim-suggestions"));

            $$("#nominatim-message, #nominatim-suggestions, #nominatim-throbber").invoke('hide');
        }
    };
}());

document.observe("dom:loaded", function() {
    SYJLogin.init();
    SYJUser.init();
    SYJDataUi.viewmode();
    SYJView.init();
    SYJNewpwd.init();
    LoginMgr.updateUI();
    Nominatim.init();
});

window.onbeforeunload = function() {
    if (SYJView.unsavedRoute) {
        return SyjStrings.unsavedConfirmExit;
    } else {
        return undefined;
    }
};
