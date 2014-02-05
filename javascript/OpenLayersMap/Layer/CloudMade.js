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
 * This layer allows accessing CloudMade tiles and provides attribution, by
 * default it provides CloudMade Fine Line tiles.
 * 
 * @class OpenLayersMap.Layer.CloudMade
 * @requires OpenLayers/Layer/OSM.js
 * @author mprins
 */
OpenLayersMap.Layer.CloudMade = OpenLayers.Class(OpenLayers.Layer.OSM, {

	/**
	 * The layer name.
	 */
	name : "cloudmade map",

	/**
	 * The tileset URL scheme.
	 */
	url : [ "http://a.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/2/256/${z}/${x}/${y}.png",
			"http://b.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/2/256/${z}/${x}/${y}.png",
			"http://c.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/2/256/${z}/${x}/${y}.png" ],

	/**
	 * The layer attribution.
	 */
	attribution : 'Tiles &copy; 2014 <a target="_blank" href="http://cloudmade.com">CloudMade</a>'
			+ '<img src="http://cloudmade.com/sites/default/files/favicon.ico" alt="CloudMade logo"/>'
			+ ' Data ODbL <a href="http://openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',

	/**
	 * @constructor
	 */
	initialize : function(name, url, options) {
		OpenLayers.Layer.OSM.prototype.initialize.apply(this, arguments);
		this.tileOptions.crossOriginKeyword = null;
	},

	CLASS_NAME : "OpenLayersMap.Layer.CloudMade"
});
