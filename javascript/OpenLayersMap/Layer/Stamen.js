/*
 * Copyright (c) 2016 Mark C. Prins <mprins@users.sf.net>
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
 * This layer allows accessing Stamen tiles and provides attribution.
 *
 * @class OpenLayersMap.Layer.StamenTerrain
 * @requires OpenLayers/Layer/OSM.js
 * @author mprins
 */
OpenLayersMap.Layer.StamenTerrain = OpenLayers.Class(OpenLayers.Layer.OSM, {

	/**
	 * The layer name.
	 */
	name : 'terrain',

	/**
	 * The tileset URL scheme.
	 */
	url : [ '//a.tile.stamen.com/terrain/${z}/${x}/${y}.png',
			'//b.tile.stamen.com/terrain/${z}/${x}/${y}.png',
			'//c.tile.stamen.com/terrain/${z}/${x}/${y}.png',
			'//d.tile.stamen.com/terrain/${z}/${x}/${y}.png' ],

	/**
	 * The layer attribution.
	 */
	attribution : 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, '
			+ 'under <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>. '
			+ 'Data by <a href="http://openstreetmap.org">OpenStreetMap</a>, '
			+ 'under <a href="http://www.openstreetmap.org/copyright">ODbL</a>.',


	/**
	 * @constructor
	 */
	initialize : function(name, url, options) {
		OpenLayers.Layer.OSM.prototype.initialize.apply(this, arguments);
		this.tileOptions.crossOriginKeyword = null;
	},

	CLASS_NAME : 'OpenLayersMap.Layer.StamenTerrain'
});

/**
 * This layer allows accessing Stamen toner tiles and provides attribution.
 *
 * @class OpenLayersMap.Layer.StamenToner
 * @requires OpenLayersMap/Layer/Stamen.js
 * @author mprins
 */
OpenLayersMap.Layer.StamenToner = OpenLayers.Class(OpenLayersMap.Layer.StamenTerrain, {

	/**
	 * The layer name.
	 */
	name : 'toner-lite',

	/**
	 * The tileset URL scheme.
	 */

	url : [ '//a.tile.stamen.com/toner-lite/${z}/${x}/${y}.png',
			'//b.tile.stamen.com/toner-lite/${z}/${x}/${y}.png',
			'//c.tile.stamen.com/toner-lite/${z}/${x}/${y}.png',
			'//d.tile.stamen.com/toner-lite/${z}/${x}/${y}.png' ],
	/**
	 * @constructor
	 */
	initialize : function(name, url, options) {
		OpenLayersMap.Layer.StamenTerrain.prototype.initialize.apply(this, arguments);
	},

	CLASS_NAME : 'OpenLayersMap.Layer.StamenToner'
});
