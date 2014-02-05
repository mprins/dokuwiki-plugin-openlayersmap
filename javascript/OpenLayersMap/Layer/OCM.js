/*
 * Copyright (c) 2013-2014 Mark C. Prins <mprins@users.sf.net>
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
 * This layer allows accessing Open Cycle Map tiles and provides attribution.
 * 
 * @class OpenLayersMap.Layer.OCM
 * @requires OpenLayers/Layer/OSM.js
 * @author mprins
 */
OpenLayersMap.Layer.OCM = OpenLayers.Class(OpenLayers.Layer.OSM, {

	/**
	 * The layer name.
	 */
	name : "cycle map",

	/**
	 * The tileset URL scheme.
	 */
	url : [ "http://a.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
			"http://b.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
			"http://c.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png" ],

	/**
	 * The layer attribution.
	 */
	attribution : 'Data &copy;ODbL <a href="http://openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>, '
			+ 'Tiles &copy;<a href="http://opencyclemap.org/" target="_blank">OpenCycleMap</a>'
			+ '<img src="http://opencyclemap.org/favicon.ico" alt="OpenCycleMap logo"/>',

	/**
	 * @constructor
	 */
	initialize : function(name, url, options) {
		OpenLayers.Layer.OSM.prototype.initialize.apply(this, arguments);
		// this.tileOptions.crossOriginKeyword = null;
	},

	CLASS_NAME : "OpenLayersMap.Layer.OCM"
});
