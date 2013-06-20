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
 * Class: OpenLayersMap.Layer.MapQuest This layer allows accessing MapQuest
 * tiles and provides attribution.
 * 
 * @requires OpenLayers/Layer/OSM.js
 * 
 * Inherits from: - <OpenLayers.Layer.OSM>
 */
OpenLayersMap.Layer.MapQuest = OpenLayers.Class(OpenLayers.Layer.OSM, {

	/**
	 * The layer name.
	 */
	name : "mapquest road",

	/**
	 * The tileset URL scheme.
	 */
	url : [ "http://otile1.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.jpg",
			"http://otile2.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.jpg",
			"http://otile3.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.jpg",
			"http://otile4.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.jpg" ],

	/**
	 * The layer attribution.
	 */
	attribution : 'Data ODbL <a href="http://openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>, '
			+ 'Tiles <a href="http://www.mapquest.com/" target="_blank">MapQuest</a>'
			+ '<img src="http://developer.mapquest.com/content/osm/mq_logo.png" alt="MapQuest logo"/>',

	tileOptions : {
		crossOriginKeyword : null
	},

	initialize : function(name, url, options) {
		OpenLayers.Layer.OSM.prototype.initialize.apply(this, arguments);
	},

	CLASS_NAME : "OpenLayersMap.Layer.MapQuest"
});
