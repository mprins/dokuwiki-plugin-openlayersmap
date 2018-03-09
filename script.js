/*
 * Copyright (c) 2008-2018 Mark C. Prins <mprins@users.sf.net>
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
 * @fileoverview Javascript voor OpenLayers plugin.
 *
 * @requires {lib/OpenLayers.js} or a full openlayers build
 * @author Mark C. Prins <mprins@users.sf.net>
 *
 */

/**
 * Openlayers selectcontrol.
 *
 * @type {OpenLayers.Control.SelectFeature}
 * @private
 */
var selectControl;

/**
 * handle feature select event.
 *
 * @param {OpenLayers.Feature.Vector}
 *            selFeature the selected feature
 */
function onFeatureSelect(selFeature) {
	// 'this' is selectFeature control
	var pPos = selFeature.geometry.getBounds().getCenterLonLat();
	// != OpenLayers.Geometry.Point
	if (selFeature.geometry.CLASS_NAME === "OpenLayers.Geometry.LineString") {
		try {
			// for lines make the popup show at the cursor position
			pPos = selFeature.layer.map.getLonLatFromViewPortPx(this.handlers.feature.evt.xy);
		} catch (anErr) {
			OpenLayers.Console.warn("unable to get event position; reverting to boundingbox center.");
			pPos = selFeature.geometry.getBounds().getCenterLonLat();
		}
	}

	var pContent = '<div class="spacer">&nbsp;</div>';
	var locDesc = '';
	if (selFeature.data.rowId !== undefined) {
		pContent += '<span class="rowId">' + selFeature.data.rowId + ': </span>';
	}
	if (selFeature.data.name !== undefined) {
		pContent += '<span class="txt">' + selFeature.data.name + '</span>';
		locDesc = selFeature.data.name;
		// TODO strip <p> tag from locDesc
		// locDesc = selFeature.data.name.split(/\s+/).slice(0,2).join('+');
	}
	if (selFeature.data.ele !== undefined) {
		pContent += '<div class="ele">elevation: ' + selFeature.data.ele + '</div>';
	}
	if (selFeature.data.type !== undefined) {
		pContent += '<div>' + selFeature.data.type + '</div>';
	}
	if (selFeature.data.time !== undefined) {
		pContent += '<div class="time">time: ' + selFeature.data.time + '</div>';
	}
	if (selFeature.data.description !== undefined) {
		pContent += '<div class="desc">' + selFeature.data.description + '</div>';
	}
	// mapillary layer
	if (selFeature.attributes.location !== undefined) {
		pContent += '<div class="desc">' + selFeature.data.location + '</div>';
	}
	// mapillary layer
	if (selFeature.attributes.image !== undefined) {
		pContent += '<img class="img" src=' + selFeature.data.image + ' width="320" />';
	}
	// mapillary layer
	if (selFeature.attributes.ca !== undefined) {
		var angle = Math.floor(selFeature.data.ca);
		pContent += '<div class="coord"><img src="' + DOKU_BASE + 'lib/plugins/openlayersmapoverlays/icons/arrow-up-20.png'
				+ '" width="16" height="16" style="transform:rotate(' + angle + 'deg)" alt="' + angle + 'º"/> '+OpenLayers.i18n("compass") + angle + 'º</div>';
	}

	if (selFeature.attributes.img !== undefined) {
		pContent += '<div class="coord" title="lat;lon"><img src="' + selFeature.attributes.img
				+ '" width="16" height="16" style="transform:rotate(' + selFeature.attributes.angle + 'deg)" />&nbsp;'
				+ '<a href="geo:'+ selFeature.data.lat + ',' + selFeature.data.lon
				+ '?q=' + selFeature.data.lat + ',' + selFeature.data.lon + '(' + selFeature.data.alt + ')" title="' + OpenLayers.i18n("navi") + '">'
				+ selFeature.data.latlon + '</a></div>';
	}
	if (pContent.length > 0) {
		// only show when there is something to show...
		var popup = new OpenLayersMap.Popup.FramedCloud("olPopup", pPos, null, pContent, null, true, function() {
			selectControl.unselect(selFeature);
			jQuery('#' + selectControl.layer.map.div.id).focus();
		});
		selFeature.popup = popup;
		selFeature.layer.map.addPopup(popup);
		jQuery('#olPopup').attr("tabindex", -1).focus();
	}
}

/**
 * handle feature unselect event. remove & destroy the popup.
 *
 * @param {OpenLayers.Feature.Vector}
 *            selFeature the un-selected feature
 */
function onFeatureUnselect(selFeature) {
	if (selFeature.popup !== null) {
		selFeature.layer.map.removePopup(selFeature.popup);
		selFeature.popup.destroy();
		selFeature.popup = null;
	}
}
/**
 * Test for css support in the browser by sniffing for a css class we added
 * using javascript added by the action plugin; this is an edge case because
 * browsers that support javascript generally support css as well.
 *
 * @returns {Boolean} true when the browser supports css (and implicitly
 *          javascript)
 */
function olTestCSSsupport() {
	return (jQuery('.olCSSsupported').length > 0);
}

/**
 * Creates a DocumentFragment to insert into the dom.
 *
 * @param mapid
 *            id for the map div
 * @param width
 *            width for the map div
 * @param height
 *            height for the map div
 * @returns a {DocumentFragment} element that can be injected into the dom
 */
function olCreateMaptag(mapid, width, height) {
	var mEl = '<div id="' + mapid + '-olContainer" class="olContainer olWebOnly">'
	// map
	+ '<div id="' + mapid + '" tabindex="0" style="width:' + width + ';height:' + height + ';" class="olMap"></div>'
	// statusbar
	+ '<div id="' + mapid + '-olStatusBar" style="width:' + width + ';" class="olStatusBarContainer">' + '  <div id="'
			+ mapid + '-statusbar-scale" class="olStatusBar olStatusBarScale">scale</div>' + '  <div id="' + mapid
			+ '-statusbar-mouseposition" class="olStatusBar olStatusBarMouseposition"></div>' + '  <div id="' + mapid
			+ '-statusbar-projection" class="olStatusBar olStatusBarProjection">proj</div>' + '  <div id="' + mapid
			+ '-statusbar-text" class="olStatusBar olStatusBarText">txt</div>' + '</div>'
			//
			+ '</div>',
	// fragment
	frag = document.createDocumentFragment(),
	// temp node
	temp = document.createElement('div');
	temp.innerHTML = mEl;
	while (temp.firstChild) {
		frag.appendChild(temp.firstChild);
	}
	return frag;
}

/**
 * Create the map based on the params given.
 *
 * @param {Object}
 *            mapOpts MapOptions hash {id:'olmap', width:500px, height:500px,
 *            lat:6710200, lon:506500, zoom:13, statusbar:1, controls:1,
 *            poihoverstyle:1, baselyr:'', kmlfile:'', gpxfile:'', geojsonfile,
 *            summary:''}
 * @param {Array}
 *            OLmapPOI array with POI's [ {lat:6710300,lon:506000,txt:'instap
 *            punt',angle:180,opacity:.9,img:'', rowId:n},... ]);
 *
 * @return {OpenLayers.Map} the created map
 */
function createMap(mapOpts, OLmapPOI) {
	if (!olEnable) {
		return;
	}
	if (!olTestCSSsupport()) {
		olEnable = false;
		return;
	}
	OpenLayers.ImgPath = DOKU_BASE + 'lib/plugins/openlayersmap/lib/img/';
	OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;

	// find map element location
	var cleartag = document.getElementById(mapOpts.id + '-clearer');
	if (cleartag === null) {
		return;
	}
	// create map element and add to document
	var fragment = olCreateMaptag(mapOpts.id, mapOpts.width, mapOpts.height);
	cleartag.parentNode.insertBefore(fragment, cleartag);

	/** dynamic map extent. */
	var extent = new OpenLayers.Bounds(),

	/** map. */
	m = new OpenLayers.Map({
		div : mapOpts.id,
		projection : 'EPSG:3857',
		displayProjection : new OpenLayers.Projection("EPSG:4326"),
		numZoomLevels : 22,
		controls : [],
		theme : null
	});

	if (osmEnable) {
		/* add OSM map layers */
		m.addLayer(new OpenLayers.Layer.OSM());

		/* open cycle map */
		m.addLayer(new OpenLayersMap.Layer.OCM("cycle map",[
				'//a.tile.thunderforest.com/cycle/${z}/${x}/${y}.png?apikey='+tfApiKey,
				'//b.tile.thunderforest.com/cycle/${z}/${x}/${y}.png?apikey='+tfApiKey,
				'//c.tile.thunderforest.com/cycle/${z}/${x}/${y}.png?apikey='+tfApiKey ], {
			visibility : mapOpts.baselyr === "transport",
			apikey : tfApiKey
		}));
		m.addLayer(new OpenLayersMap.Layer.OCM("transport", [
				"http://a.tile.thunderforest.com/transport/${z}/${x}/${y}.png?apikey="+tfApiKey,
				"http://b.tile.thunderforest.com/transport/${z}/${x}/${y}.png?apikey="+tfApiKey,
				"http://c.tile.thunderforest.com/transport/${z}/${x}/${y}.png?apikey="+tfApiKey ], {
			visibility : mapOpts.baselyr === "transport",
			apikey : tfApiKey
		}));
		m.addLayer(new OpenLayersMap.Layer.OCM("landscape", [
				"http://a.tile.thunderforest.com/landscape/${z}/${x}/${y}.png?apikey="+tfApiKey,
				"http://b.tile.thunderforest.com/landscape/${z}/${x}/${y}.png?apikey="+tfApiKey,
				"http://c.tile.thunderforest.com/landscape/${z}/${x}/${y}.png?apikey="+tfApiKey ], {
			visibility : mapOpts.baselyr === "landscape",
			apikey : tfApiKey
		}));
		m.addLayer(new OpenLayersMap.Layer.OCM("outdoors", [
				"http://a.tile.thunderforest.com/outdoors/${z}/${x}/${y}.png?apikey="+tfApiKey,
				"http://b.tile.thunderforest.com/outdoors/${z}/${x}/${y}.png?apikey="+tfApiKey,
				"http://c.tile.thunderforest.com/outdoors/${z}/${x}/${y}.png?apikey="+tfApiKey ], {
			visibility : mapOpts.baselyr === "outdoors",
			apikey : tfApiKey
		}));

		m.addLayer(new OpenLayers.Layer.OSM(
				"hike and bike map", "http://toolserver.org/tiles/hikebike/${z}/${x}/${y}.png", {
					visibility : mapOpts.baselyr === "hike and bike map",
					tileOptions : {
						crossOriginKeyword : null
					}
		}));
	}
	/*
	 * add Stamen map layers, see: http://maps.stamen.com/
	 */
	if (stamenEnable) {
		m.addLayer(new OpenLayersMap.Layer.StamenTerrain());
		m.addLayer(new OpenLayersMap.Layer.StamenToner());
	}

	if (gEnable) {
		/* load google maps */
		try {
			m.addLayer(new OpenLayers.Layer.Google("google relief", {
				type : google.maps.MapTypeId.TERRAIN,
				numZoomLevels : 16,
				animationEnabled : true,
				visibility : mapOpts.baselyr === "google relief"
			}));
			m.addLayer(new OpenLayers.Layer.Google("google sat", {
				type : google.maps.MapTypeId.SATELLITE,
				animationEnabled : true,
				visibility : mapOpts.baselyr === "google sat"
			}));
			m.addLayer(new OpenLayers.Layer.Google("google hybrid", {
				type : google.maps.MapTypeId.HYBRID,
				animationEnabled : true,
				visibility : mapOpts.baselyr === "google hybrid"
			}));
			m.addLayer(new OpenLayers.Layer.Google("google road", {
				animationEnabled : true,
				visibility : mapOpts.baselyr === "google road"
			}));
		} catch (ol_err1) {
			Openlayers.Console.userError('Error loading Google maps' + ol_err1);
		}
	}

	if (bEnable && bApiKey !== '') {
		try {
			/* add Bing tiles */
			m.addLayer(new OpenLayers.Layer.Bing({
				key : bApiKey,
				type : "Road",
				name : "bing road",
				visibility : mapOpts.baselyr === "bing road",
				wrapDateLine : true,
				attributionTemplate : '<a target="_blank" href="http://www.bing.com/maps/">'
						+ 'Bing™</a><img src="//www.bing.com/favicon.ico" alt="Bing logo"/> ${copyrights}'
						+ '<a target="_blank" href="http://www.microsoft.com/maps/product/terms.html">Terms of Use</a>'
			}));
			m.addLayer(new OpenLayers.Layer.Bing({
				key : bApiKey,
				type : "Aerial",
				name : "bing sat",
				visibility : mapOpts.baselyr === "bing sat",
				wrapDateLine : true,
				attributionTemplate : '<a target="_blank" href="http://www.bing.com/maps/">'
						+ 'Bing™</a><img src="//www.bing.com/favicon.ico" alt="Bing logo"/> ${copyrights}'
						+ '<a target="_blank" href="http://www.microsoft.com/maps/product/terms.html">Terms of Use</a>'
			}));
			m.addLayer(new OpenLayers.Layer.Bing({
				key : bApiKey,
				type : "AerialWithLabels",
				name : "bing hybrid",
				visibility : mapOpts.baselyr === "bing hybrid",
				wrapDateLine : true,
				attributionTemplate : '<a target="_blank" href="http://www.bing.com/maps/">'
						+ 'Bing™</a><img src="//www.bing.com/favicon.ico" alt="Bing logo"/> ${copyrights}'
						+ '<a target="_blank" href="http://www.microsoft.com/maps/product/terms.html">Terms of Use</a>'
			}));
		} catch (ol_errBing) {
			Openlayers.Console.userError('Error loading Bing maps: ' + ol_errBing);
		}
	}

	m.setCenter(new OpenLayers.LonLat(mapOpts.lon, mapOpts.lat).transform(m.displayProjection, m.projection),
			mapOpts.zoom);
	extent.extend(m.getExtent());

	// change/set alternative baselyr
	try {
		m.setBaseLayer(((m.getLayersByName(mapOpts.baselyr))[0]));
	} catch (ol_err4) {
		m.setBaseLayer(m.layers[0]);
	}

	m.addControls([ new OpenLayers.Control.ScaleLine({
		geodesic : true
	}), new OpenLayers.Control.KeyboardDefaults({
		observeElement : mapOpts.id
	}), new OpenLayers.Control.Navigation() ]);

	if (mapOpts.statusbar === 1) {
		// statusbar control: mouse pos.
		m.addControl(new OpenLayers.Control.MousePosition({
			'div' : OpenLayers.Util.getElement(mapOpts.id + '-statusbar-mouseposition')
		}));
		// statusbar control: scale
		m.addControl(new OpenLayers.Control.Scale(mapOpts.id + '-statusbar-scale'));
		// statusbar control: attribution
		m.addControl(new OpenLayers.Control.Attribution({
			'div' : OpenLayers.Util.getElement(mapOpts.id + '-statusbar-text')
		}));
		// statusbar control: projection
		OpenLayers.Util.getElement(mapOpts.id + '-statusbar-projection').innerHTML = m.displayProjection;
	} else {
		OpenLayers.Util.getElement(mapOpts.id + '-olStatusBar').display = 'none';
	}

	if (OLmapPOI.length > 0) {
		var markers = new OpenLayers.Layer.Vector("POI", {
			styleMap : new OpenLayers.StyleMap({
				"default" : {
					cursor : "help",
					externalGraphic : "${img}",
					graphicHeight : 16,
					graphicWidth : 16,
					// graphicXOffset : 0,
					// graphicYOffset : -8,
					graphicOpacity : "${opacity}",
					rotation : "${angle}",
					backgroundGraphic : DOKU_BASE + "lib/plugins/openlayersmap/icons/marker_shadow.png",
					// backgroundXOffset : 0,
					// backgroundYOffset : -4,
					backgroundRotation : "${angle}",
					pointRadius : 10,
					labelXOffset : 8,
					labelYOffset : 8,
					labelAlign : "lb",
					label : "${label}",
					// fontColor : "",
					fontFamily : "monospace",
					fontSize : "12px",
					fontWeight : "bold"
				},
				"select" : {
					cursor : "help",
					externalGraphic : DOKU_BASE + "lib/plugins/openlayersmap/icons/marker-red.png",
					graphicHeight : 16,
					graphicWidth : 16,
					// graphicXOffset : 0,
					// graphicYOffset : -8,
					graphicOpacity : 1.0,
					rotation : "${angle}"
				}
			}),
			isBaseLayer : false,
			rendererOptions : {
				yOrdering : true
			}
		});
		m.addLayer(markers);
		var features = [];
		for (var j = 0; j < OLmapPOI.length; j++) {
			var feat = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(OLmapPOI[j].lon, OLmapPOI[j].lat)
					.transform(m.displayProjection, m.projection), {
				angle : OLmapPOI[j].angle,
				opacity : OLmapPOI[j].opacity,
				img : DOKU_BASE + "lib/plugins/openlayersmap/icons/" + OLmapPOI[j].img,
				label : OLmapPOI[j].rowId
			});
			var _latlon = OLmapPOI[j].lat + 'º;' + OLmapPOI[j].lon + 'º';
			if (mapOpts.displayformat === 'DMS') {
				_latlon = OpenLayers.Util.getFormattedLonLat(OLmapPOI[j].lat, 'lat') + ';'
						+ OpenLayers.Util.getFormattedLonLat(OLmapPOI[j].lon, 'lon');
			}
			feat.data = {
				name : OLmapPOI[j].txt,
				rowId : OLmapPOI[j].rowId,
				latlon : _latlon,
				lat: OLmapPOI[j].lat,
				lon: OLmapPOI[j].lon,
                alt : OLmapPOI[j].img.substring(0, OLmapPOI[j].img.lastIndexOf("."))
			};
			features.push(feat);
		}
		markers.addFeatures(features);
		if (mapOpts.autozoom) {
			extent.extend(markers.getDataExtent());
			m.zoomToExtent(extent);
		}
	}

	/* GPX layer */
	if (mapOpts.gpxfile.length > 0) {
		var layerGPX = new OpenLayers.Layer.Vector("GPS route", {
			protocol : new OpenLayers.Protocol.HTTP({
				url : DOKU_BASE + "lib/exe/fetch.php?media=" + mapOpts.gpxfile,
				format : new OpenLayers.Format.GPX({
					extractWaypoints : true,
					extractTracks : true,
					extractStyles : true,
					extractAttributes : true,
					handleHeight : true,
					maxDepth : 3
				})
			}),
			style : {
				strokeColor : "#0000FF",
				strokeWidth : 3,
				strokeOpacity : 0.7,
				pointRadius : 4,
				fillColor : "#0099FF",
				fillOpacity : 0.7
			// , label:"${name}"
			},
			projection : new OpenLayers.Projection("EPSG:4326"),
			strategies : [ new OpenLayers.Strategy.Fixed() ]
		});
		m.addLayer(layerGPX);
		if (mapOpts.autozoom) {
			layerGPX.events.register('loadend', m, function() {
				extent.extend(layerGPX.getDataExtent());
				m.zoomToExtent(extent);
			});
		}
	}

	/* GeoJSON layer */
	if (mapOpts.geojsonfile.length > 0) {
		var layerGJS = new OpenLayers.Layer.Vector("json data", {
			protocol : new OpenLayers.Protocol.HTTP({
				url : DOKU_BASE + "lib/exe/fetch.php?media=" + mapOpts.geojsonfile,
				format : new OpenLayers.Format.GeoJSON({
					ignoreExtraDims : true
				})
			}),
			style : {
				strokeColor : "#FF00FF",
				strokeWidth : 3,
				strokeOpacity : 0.7,
				pointRadius : 4,
				fillColor : "#FF99FF",
				fillOpacity : 0.7
			// , label:"${name}"
			},
			projection : new OpenLayers.Projection("EPSG:4326"),
			strategies : [ new OpenLayers.Strategy.Fixed() ]
		});
		m.addLayer(layerGJS);
		if (mapOpts.autozoom) {
			layerGJS.events.register('loadend', m, function() {
				extent.extend(layerGJS.getDataExtent());
				m.zoomToExtent(extent);
			});
		}
	}

	/* KML layer */
	if (mapOpts.kmlfile.length > 0) {
		var layerKML = new OpenLayers.Layer.Vector("KML file", {
			protocol : new OpenLayers.Protocol.HTTP({
				url : DOKU_BASE + "lib/exe/fetch.php?media=" + mapOpts.kmlfile,
				format : new OpenLayers.Format.KML({
					extractStyles : true,
					extractAttributes : true,
					maxDepth : 3
				})
			}),
			style : {
				label : "${name}"
			},
			projection : new OpenLayers.Projection("EPSG:4326"),
			strategies : [ new OpenLayers.Strategy.Fixed() ]
		});
		m.addLayer(layerKML);
		if (mapOpts.autozoom) {
			layerKML.events.register('loadend', m, function() {
				extent.extend(layerKML.getDataExtent());
				m.zoomToExtent(extent);
			});
		}
	}

	// selectcontrol for layers
	if ((m.getLayersByClass('OpenLayers.Layer.GML').length > 0)
			|| m.getLayersByClass('OpenLayers.Layer.Vector').length > 0) {
		selectControl = new OpenLayers.Control.SelectFeature((m.getLayersByClass('OpenLayers.Layer.Vector')).concat(m
				.getLayersByClass('OpenLayers.Layer.GML')), {
			hover : mapOpts.poihoverstyle,
			onSelect : onFeatureSelect,
			onUnselect : onFeatureUnselect
		});
		m.addControl(selectControl);
		selectControl.activate();

		// keyboard select control
		var iControl = new OpenLayersMap.Control.KeyboardClick({
			observeElement : mapOpts.id,
			selectControl : selectControl
		});
		m.addControl(iControl);
	}

	if (mapOpts.controls === 1) {
		/* add base controls to map */
		m.addControls([ new OpenLayersMap.Control.LayerSwitcher(), new OpenLayers.Control.Graticule({
			visible : false
		}), new OpenLayersMap.Control.OverviewMap({
			mapOptions : {
				theme : null
			}
		}), new OpenLayersMap.Control.Zoom(), new OpenLayersMap.Control.Fullscreen() ]);

		// add hillshade, since this is off by default only add when we have a
		// layerswitcher
		/*
		m.addLayer(new OpenLayers.Layer.OSM("Hillshade", "http://toolserver.org/~cmarqu/hill/${z}/${x}/${y}.png", {
			isBaseLayer : false,
			transparent : true,
			visibility : false,
			displayOutsideMaxExtent : true,
			attribution : '',
			tileOptions : {
				crossOriginKeyword : null
			}
		}));
		*/
	}

	return m;
}

/** init. */
function olInit() {
	if (olEnable) {
		var _i = 0;
		// create the maps in the page
		for (_i = 0; _i < olMapData.length; _i++) {
			var _id = olMapData[_i].mapOpts.id;
			olMaps[_id] = createMap(olMapData[_i].mapOpts, olMapData[_i].poi);

			// set max-width on help pop-over
			jQuery('#' + _id).parent().parent().find('.olMapHelp').css('max-width', olMapData[_i].mapOpts.width);

			// shrink the map width to fit inside page container
			var _w = jQuery('#' + _id + '-olContainer').parent().innerWidth();
			if (parseInt(olMapData[_i].mapOpts.width) > _w) {
				jQuery('#' + _id).width(_w);
				jQuery('#' + _id + '-olStatusBar').width(_w);
				jQuery('#' + _id).parent().parent().find('.olMapHelp').width(_w);
				olMaps[_id].updateSize();
			}
		}

		var resizeTimer;
		jQuery(window).on('resize', function(e) {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function() {
				for (_i = 0; _i < olMapData.length; _i++) {
					var _id = olMapData[_i].mapOpts.id;
					var _w = jQuery('#' + _id + '-olContainer').parent().innerWidth();
					if (parseInt(olMapData[_i].mapOpts.width) > _w) {
						jQuery('#' + _id).width(_w);
						jQuery('#' + _id + '-olStatusBar').width(_w);
						jQuery('#' + _id).parent().parent().find('.olMapHelp').width(_w);
						olMaps[_id].updateSize();
					}
				}
			}, 250);
		});

		// hide the table(s) with POI by giving it a print-only style
		jQuery('.olPOItableSpan').addClass('olPrintOnly');
		// hide the static map image(s) by giving it a print only style
		jQuery('.olStaticMap').addClass('olPrintOnly');
		// add help button with toggle.
		jQuery('.olWebOnly > .olMap')
				.prepend(
						'<div class="olMapHelpButtonDiv">'
								+ '<button onclick="jQuery(\'.olMapHelp\').toggle(500);" class="olMapHelpButton olHasTooltip"><span>'
								+ OpenLayers.i18n("toggle_help") + '</span>?</button></div>');
		// toggle to switch dynamic vs. static map
		jQuery('.olMapHelp').before(
				'<div class="a11y"><button onclick="jQuery(\'.olPrintOnly\').toggle();jQuery(\'.olWebOnly\').toggle();">'
						+ OpenLayers.i18n("toggle_dynamic_map") + '</button></div>');
	}
}

/**
 * ol api flag.
 *
 * @type {Boolean}
 */
var olEnable = false,
/**
 * An array with data for each map in the page.
 *
 * @type {Array}
 */
olMapData = [],
/**
 * Holds a reference to all of the maps on this page with the map's id as key.
 * Can be used as an extension point.
 *
 * @type {Object}
 */
olMaps = new Object(),
/**
 * Stamen tiles flag.
 *
 * @type {Boolean}
 */
stamenEnable = false,
/**
 * google map api flag.
 *
 * @type {Boolean}
 */
gEnable = false,
/**
 * Bing tiles flag.
 *
 * @type {Boolean}
 */
bEnable = false,
/**
 * Bing API key.
 *
 * @type {String}
 */
bApiKey = '',
/**
 * Google API key.
 *
 * @type {String}
 */
gApiKey = '',
/**
 * Thunderforest API key.
 *
 * @type {String}
 */
tfApiKey = '',
/**
 * OSM tiles flag.
 *
 * @type {Boolean}
 */
osmEnable = true,
/**
 * CSS support flag.
 *
 * @type {Boolean}
 */
olCSSEnable = true;

/* register olInit to run with onload event. */
jQuery(olInit);
