/*
 * Copyright (c) 2013 Mark C. Prins <mprins@users.sf.net>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */
/**
 * Accessible overview inset.
 * 
 * @author mprins
 * @class OpenLayersMap.Control.OverviewMap
 * @requires OpenLayers/Control/OverviewMap.js
 */
OpenLayersMap.Control.OverviewMap = OpenLayers.Class(OpenLayers.Control.OverviewMap, {

	/** @override */
	maximized : false,

	/** @override */
	autoPan : true,

	/** @override */
	size : new OpenLayers.Size(140, 140),

	/** @override */
	minRectSize : 10,

	layers : null,

	handlers : null,
	
	/** @override */
	theme: null,
	/**
	 * @constructor OpenLayersMap.Control.OverviewMap Create a new overview map
	 * 
	 * @param options
	 *            {Object} Properties of this object will be set on the overview
	 *            map object. Note, to set options on the map object contained
	 *            in this control, set <mapOptions> as one of the options
	 *            properties.
	 * 
	 */
	initialize : function(options) {
		this.layers = [];
		this.handlers = {};
		OpenLayers.Control.OverviewMap.prototype.initialize.apply(this, [ options ]);
		this.displayClass = 'olOverviewMap';
	},

	/**
	 * Render the control in the browser.
	 * 
	 * @override
	 */
	draw : function() {
		OpenLayers.Control.prototype.draw.apply(this, arguments);
		if (this.layers.length === 0) {
			if (this.map.baseLayer) {
				var layer = this.map.baseLayer.clone();
				this.layers = [ layer ];
			} else {
				this.map.events.register("changebaselayer", this, this.baseLayerDraw);
				return this.div;
			}
		}

		// create overview map DOM elements
		this.element = document.createElement('div');
		this.element.className = this.displayClass + 'Element';
		this.element.style.display = 'none';

		this.mapDiv = document.createElement('div');
		this.mapDiv.style.width = this.size.w + 'px';
		this.mapDiv.style.height = this.size.h + 'px';
		this.mapDiv.style.position = 'relative';
		this.mapDiv.style.overflow = 'hidden';
		this.mapDiv.id = OpenLayers.Util.createUniqueID('overviewMap');

		this.extentRectangle = document.createElement('div');
		this.extentRectangle.style.position = 'absolute';
		this.extentRectangle.style.zIndex = 1000; // HACK
		this.extentRectangle.className = this.displayClass + 'ExtentRectangle';

		this.element.appendChild(this.mapDiv);

		this.div.appendChild(this.element);

		this.div.className += " " + this.displayClass + 'Container';

		// maximize button
		var btn = document.createElement("button");
		btn.innerHTML = '<span>' + OpenLayers.i18n('ovMaximize') + '</span>+';
		btn.name = 'show';
		this.maximizeDiv = btn;
		this.maximizeDiv.style.display = 'none';
		this.maximizeDiv.className = this.displayClass + 'MaximizeButton olOverviewMapButton olButton olHasTooltip';
		this.div.appendChild(this.maximizeDiv);

		// minimize button
		btn = document.createElement("button");
		btn.innerHTML = '<span>' + OpenLayers.i18n("ovMinimize") + '</span>−';
		btn.name = 'hide';
		this.minimizeDiv = btn;
		this.minimizeDiv.style.display = 'none';
		this.minimizeDiv.className = this.displayClass + 'MinimizeButton olOverviewMapButton olButton olHasTooltip';
		this.div.appendChild(this.minimizeDiv);

		this.minimizeControl();

		if (this.map.getExtent()) {
			this.update();
		}

		this.map.events.on({
			buttonclick : this.onButtonClick,
			moveend : this.update,
			scope : this
		});

		if (this.maximized) {
			this.maximizeControl();
		}
		return this.div;
	},

	CLASS_NAME : 'OpenLayersMap.Control.OverviewMap'
});
