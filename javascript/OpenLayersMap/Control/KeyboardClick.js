/*
 * Copyright (c) 2006-2012 by OpenLayers Contributors
 * Copyright (c) 2014-2015 Mark C. Prins <mprins@users.sf.net>
 *
 * Published under the 2-clause BSD license.
 * See license.txt in the OpenLayers distribution or repository for the
 * full text of the license.
 */

/**
 * A custom control that (a) adds a vector point that can be moved using the
 * arrow keys of the keyboard, and (b) displays a browser alert window when the
 * RETURN key is pressed. The control can be activated/deactivated using the "i"
 * key. When activated the control deactivates any KeyboardDefaults control in
 * the map so that the map is not moved when the arrow keys are pressed.
 *
 * This control relies on the OpenLayersMap.Handler.KeyboardPoint custom
 * handler.
 *
 * @class OpenLayersMap.Control.KeyboardClick
 * @requires OpenLayers/Control.js
 * @requires OpenLayersMap/Handler/KeyboardPoint.js
 * @extends OpenLayers.Control
 */
OpenLayersMap.Control.KeyboardClick = OpenLayers.Class(OpenLayers.Control, {
	/**
	 * select control.
	 */
	selectControl : null,

	/**
	 * @constructor
	 */
	initialize : function(options) {
		OpenLayers.Control.prototype.initialize.apply(this, [ options ]);
		var observeElement = this.observeElement || document;
		this.handler = new OpenLayersMap.Handler.KeyboardPoint(this, {
			done : this.onClick,
			cancel : this.deactivate
		}, {
			observeElement : observeElement
		});
		OpenLayers.Event.observe(observeElement, 'keydown', OpenLayers.Function.bindAsEventListener(function(evt) {
			// listen for the "i" key
			if (evt.keyCode == 73) {
				if (this.active) {
					this.deactivate();
				} else {
					this.activate();
				}
			}
		}, this));
	},

	/**
	 * Hit detection of click.
	 *
	 * @param {Openlayers.Geometry}
	 *            geometry with map space coordinates
	 *
	 */
	onClick : function(geometry) {
		var lyrs = this.selectControl.layers, selTarget;
		var px = this.map.getPixelFromLonLat(new OpenLayers.LonLat(geometry.x, geometry.y));
		// calculate radius to be roughly same amount as stepsize * 2^.5
		px = px.add(this.handler.STEP_SIZE * 1.5, 0);
		var lonlat = this.map.getLonLatFromPixel(px);
		var radius = Math.round(lonlat.lon - geometry.x);
		var sides = 8;
		var rotation = 0;
		var clicked = OpenLayers.Geometry.Polygon.createRegularPolygon(geometry, radius, sides, rotation);

		// hit detection, first intersect hits
		for (var resized = 1; resized < 4; resized++) {
			// try a few (resized-1) times with larger click polygon each time
			clicked = clicked.resize(resized, geometry);
			for (var i = 0; i < lyrs.length; i++) {
				if (lyrs[i].getVisibility()) {
					for (var f = 0; f < lyrs[i].features.length; f++) {
						selTarget = lyrs[i].features[f];
						if (clicked.intersects(selTarget.geometry)) {
							this.selectControl.clickFeature(selTarget);
							return;
						}
					}
				}
			}
		}
	},

	/**
	 * Activate control and turn off default keyboard handler.
	 *
	 * @returns {Boolean}
	 */
	activate : function() {
		if (!OpenLayers.Control.prototype.activate.apply(this, arguments)) {
			return false;
		}
		var keyboardDefaults = this.map.getControlsByClass('OpenLayers.Control.KeyboardDefaults')[0];
		if (keyboardDefaults) {
			keyboardDefaults.deactivate();
		}
		return true;
	},

	/**
	 * Deactivate this control and restore default keyboard handler.
	 *
	 * @returns {Boolean}
	 */
	deactivate : function() {
		if (!OpenLayers.Control.prototype.deactivate.apply(this, arguments)) {
			return false;
		}
		var keyboardDefaults = this.map.getControlsByClass('OpenLayers.Control.KeyboardDefaults')[0];
		if (keyboardDefaults) {
			keyboardDefaults.activate();
		}
		return true;
	},

	CLASS_NAME : 'OpenLayersMap.Control.KeyboardClick'
});
