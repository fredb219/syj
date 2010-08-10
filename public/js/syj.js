/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */
Element.addMethods('input', {
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
    })
});

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

    hide: function() {
        $("geom_submit").blur();
        $("geom_title").blur();
        $("geomform").hide();
        return this;
    },

    show: function() {
        $("geomform").show();
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
        this.status = "partial";
        return this;
    },

    disableSubmit: function() {
        $("geom_submit").blur();
        $("geom_submit").disabled = true;
        this.status = "partial";
        return this;
    }
};

var SyjEditUI = {
    hide: function() {
        $("edit-btn").blur();
        $("edit-btn").hide();
        return this;
    },

    show: function() {
        $("edit-btn").show();
        return this;
    }
};

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
                        styleMap:   styleMap.view};
        if (gLoggedInfo.creatorname) {
            layerOptions.attribution = SyjStrings.routeBy + ' ' + '<strong>' + gLoggedInfo.creatorname + '</strong>';
        }

        this.viewLayer = new OpenLayers.Layer.Vector("View Layer", layerOptions);
        this.map.addLayers([baseLayer, this.viewLayer]);

        $("edit-btn").observe('click', (function() {
            this.messenger.hide();
            this.editMode();
        }).bind(this));

        $("geomform").ajaxize({
                presubmit: this.prepareForm.bind(this),
                onSuccess: this.saveSuccess.bind(this),
                onFailure: this.saveFailure.bind(this)
                });
        SyjSaveUI.init().hide();

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

        if ($("geom_data").value) {
            this.viewLayer.addFeatures([this.wkt.read($("geom_data").value)]);
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
    },

    observer: function(evt) {
        if (evt.eventName === "simplebox:shown" && evt.memo.element !== $("termsofusearea")) {
            this.messenger.hide();
        }
    },

    prepareForm: function(form) {
        if (!loginMgr.logged && !$("geom_accept").checked) {
            this.messenger.setMessage(SyjStrings.acceptTermsofuseWarn, "warn");
            $("geom_accept_container").highlight('#F08080');
            $("geom_accept").activate();
            return false;
        }

        var line, realPoints, idx, handler;

        line = new OpenLayers.Geometry.LineString();
        realPoints = this.editControl.handler.realPoints;
        for (idx = 0; idx < realPoints.length; idx++) {
            line.addComponent(realPoints[idx].geometry.clone());
        }
        this.viewLayer.addFeatures(new OpenLayers.Feature.Vector(line));
        handler = this.editControl.handler;
        OpenLayers.Handler.ModifiablePath.prototype.finalize.apply(handler, arguments);
        // we need to recreate them on next createFeature; otherwise
        // they'll reference destroyed features
        delete(handler.handlers.drag);
        delete(handler.handlers.feature);
        this.editControl.deactivate();

        $("geom_data").value = this.wkt.write(new OpenLayers.Feature.Vector(line));
        this.needsFormResubmit = false;
        SyjSaveUI.disable.bind(SyjSaveUI).defer();
        this.messenger.hide();
        return true;
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
        }

        this.viewLayer.destroyFeatures();

        SyjEditUI.hide();
        if (this.editControl.handler.realPoints && this.editControl.handler.realPoints.length >= 2) {
            SyjSaveUI.show().disableSubmit();
        } else {
            SyjSaveUI.show().disable();
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
                    if (this.handler.realPoints.length < 2) {
                        SyjSaveUI.show().disable();
                    } else {
                        SyjSaveUI.show().enable();
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
    },

    saveSuccess: function(transport) {
      if (!$("geom_id").value) {
          location = "idx/" + transport.responseText;
          return;
      }
      this.messenger.setMessage(SyjStrings.saveSuccess, "success");

      SyjSaveUI.hide();
      SyjEditUI.show();
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
            case 410:
                message = SyjStrings.requestError; // default message
                if (transport.responseJSON) {
                    switch (transport.responseJSON.message) {
                        case "unreferenced":
                            message = SyjStrings.unreferencedError;
                        break;
                        case "uniquepath":
                            message = SyjStrings.uniquePathError;
                        break;
                        default:
                        break;
                    }
                }
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
                $("termsofusearea").show();
                $("termsofuseiframe").setAttribute("src", evt.target.href);
                this.toubox = new SimpleBox($("termsofusearea"), {
                    closeMethods: ["onescapekey", "onouterclick", "onbutton"]
                });
            }
            this.toubox.show();
            evt.stop();
        }.bindAsEventListener(this));

        $$("#login_area_create > a").invoke('observe', 'click',
            function(evt) {
                this.modalbox.show();
                evt.stop();
            }.bindAsEventListener(this));

        $("user_password").observe('contentchange', function(evt) {
            if (evt.target.value.length < 6) {
                $("user_password-desc").setMessageStatus("warn");
            } else {
                $("user_password-desc").setMessageStatus("success");
            }
        }.bindAsEventListener(this));

        $("account-info").hide();
        $("account-info-bullet").observe('click', function(evt) {
            var elt = $("account-info");
            if (elt.visible()) {
                evt.target.src = "icons/bullet_arrow_right.png";
                elt.hide();
            } else {
                evt.target.src = "icons/bullet_arrow_down.png";
                elt.show();
            }
            evt.stop();
        });
    },

    presubmit: function() {
        if (!(this.checkNotEmpty("user_pseudo", SyjStrings.userEmptyWarn))) {
            return false;
        }

        if (!($("user_pseudo").value.match(/^[a-zA-Z0-9_.]+$/))) {
            this.messenger.setMessage(SyjStrings.invalidPseudo, "warn");
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
        loginMgr.login();
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
                            message = SyjStrings.uniqueUserError;
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
var SYJUser = new SYJUserClass();

var SYJLoginClass = Class.create(SYJModalClass, {
    type: "login",

    init: function($super) {
        $super();
    },

    presubmit: function() {
        if (!(this.checkNotEmpty("login_user", SyjStrings.userEmptyWarn))) {
            return false;
        }

        this.reset();
        return true;
    },

    success: function(transport) {
        if (transport.responseText === "1") {
            loginMgr.login(true);
        } else {
            loginMgr.login();
        }
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

var loginMgr = Object.extend(gLoggedInfo, {
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

        if (this.iscreator) {
            $("data_controls").show();
        } else {
            $("data_controls").hide();
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

document.observe("dom:loaded", function() {
    SYJLogin.init();
    SYJUser.init();
    SYJView.init();
    SYJNewpwd.init();
    loginMgr.updateUI();
});
