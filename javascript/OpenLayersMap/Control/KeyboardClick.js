/* 
 * Copyright (c) 2006-2012 by OpenLayers Contributors
 * Copyright (c) 2014 Mark C. Prins <mprins@users.sf.net>
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
		OpenLayers.Event.observe(observeElement, "keydown", OpenLayers.Function.bindAsEventListener(function(evt) {
			if (evt.keyCode == 73) { // "i"
				if (this.active) {
					this.deactivate();
				} else {
					this.activate();
				}
			}
		}, this));
	},

	/**
	 * 
	 * @param {Openlayers.Geometry}
	 *            geometry with map space coordinates
	 * 
	 */
	onClick : function(geometry) {
		var lyrs = this.selectControl.layers, selFeat;
		// console.debug("geometry; onClick::" + this.CLASS_NAME, geometry);

		var px = this.map.getPixelFromLonLat(new OpenLayers.LonLat(geometry.x, geometry.y));
		// console.debug("px; onClick::" + this.CLASS_NAME, px);
		// enlarge click area with same amount as stepsize
		px = px.add(this.handler.STEP_SIZE * 2, 0);
		// console.debug("px added onClick::" + this.CLASS_NAME, px);

		var lonlat = this.map.getLonLatFromPixel(px);
		var radius = Math.round(lonlat.lon - geometry.x);
		var sides = 8;
		var rotation = 0;
		// console.debug("polygon params; onClick::" + this.CLASS_NAME,
		// geometry, lonlat, radius, sides, rotation);
		var clicked = OpenLayers.Geometry.Polygon.createRegularPolygon(geometry, radius, sides, rotation);

		// var json = new OpenLayers.Format.GeoJSON();
		// console.info(json.write(clicked, false));

		for (var i = 0; i < lyrs.length; i++) {
			for (var f = 0; f < lyrs[i].features.length; f++)
				selFeat = lyrs[i].features[f];
			// console.info(json.write(selFeat.geometry, false));
			if (clicked.intersects(selFeat.geometry)) {
				// console.info("" + this.CLASS_NAME, "intersect found.");
				this.selectControl.clickFeature(selFeat);
				return;
			}
		}
	},

	/**
	 * Activeert deze control en zet de default keyboard handler uit (mits
	 * aanwezig).
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
	 * Deactiveert deze control en zet de default keyboard handler aan (mits
	 * aanwezig).
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