/*
 * Copyright (c) 2008-2012 Mark C. Prins <mprins@users.sf.net>
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
 *            the selected feature
 */
function onFeatureSelect(feature) {
	var selectedFeature = feature;
	// 'this' is selectFeature control
	var pPos = selectedFeature.geometry.getBounds().getCenterLonLat();
	// != OpenLayers.Geometry.Point
	if (selectedFeature.geometry.CLASS_NAME === "OpenLayers.Geometry.LineString") {
		try {
			// for lines make the popup show at the cursor position
			pPos = feature.layer.map
					.getLonLatFromViewPortPx(this.handlers.feature.evt.xy);
		} catch (anErr) {
			OpenLayers.Console
					.warn("unable to get event position; reverting to boundingbox center.");
			pPos = selectedFeature.geometry.getBounds().getCenterLonLat();
		}
	}

	var pContent = '<div class="spacer">&nbsp;</div>';
	if (feature.data.rowId !== undefined) {
		pContent += '<span class="rowId">' + feature.data.rowId + ': </span>';
	}
	if (feature.data.name !== undefined) {
		pContent += '<span class="txt">' + feature.data.name + '</span>';
	}
	if (feature.data.ele !== undefined) {
		pContent += '<div class="ele">elevation: ' + feature.data.ele + '</div>';
	}
	if (feature.data.type !== undefined) {
		pContent += '<div>' + feature.data.type + '</div>';
	}
	if (feature.data.time !== undefined) {
		pContent += '<div class="time">time: ' + feature.data.time + '</div>';
	}
	if (feature.data.description !== undefined) {
		pContent += '<div class="desc">' + feature.data.description + '</div>';
	}

	if (pContent.length > 0) {
		// only show when there is something to show...
		var popup = new OpenLayers.Popup.FramedCloud("olPopup", pPos, null,
				pContent, null, !0, function() {
					selectControl.unselect(selectedFeature);
				});
		feature.popup = popup;
		feature.layer.map.addPopup(popup);
	}
}

/**
 * handle feature unselect event. remove & destroy the popup.
 *
 * @param {OpenLayers.Feature.Vector}
 *            the un-selected feature
 */
function onFeatureUnselect(feature) {
	if (feature.popup !== null) {
		feature.layer.map.removePopup(feature.popup);
		feature.popup.destroy();
		feature.popup = null;
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
	// TODO: use OpenLayers.i18n()
	var mEl = '<div><a class="olAccesskey" href="" accesskey="1" onclick="document.getElementById(&quot;' + mapid + '&quot;).focus(); return false;" title="Activate map">Activate map</a></div>'
			+ '<div id="' + mapid + '-olContainer" class="olContainer olWebOnly">'
			+ '<div id="' + mapid + '-olToolbar" class="olToolbar"></div>'
			+ '<div class="clearer"></div>'
			+ '<div id="' + mapid + '" tabindex="0" style="width:' + width + ';height:' + height + ';" class="olMap"></div>'
			+ '<div id="' + mapid + '-olStatusBar" style="width:' + width + ';"class="olStatusBarContainer">'
			+ '<div id="' + mapid + '-statusbar-scale" class="olStatusBar olStatusBarScale">scale</div>'
			+ '<div id="' + mapid + '-statusbar-link" class="olStatusBar olStatusBarPermalink">'
			+ '<a href="" id="' + mapid + '-statusbar-link-ref">link</a></div>'
			+ '<div id="' + mapid + '-statusbar-mouseposition" class="olStatusBar olStatusBarMouseposition"></div>'
			+ '<div id="' + mapid + '-statusbar-projection" class="olStatusBar olStatusBarProjection">proj</div>'
			+ '<div id="' + mapid + '-statusbar-text" class="olStatusBar olStatusBarText">txt</div>'
			+ '</div></div>',
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
 * create the map based on the params given.
 *
 * @param {Object}mapOpts
 *            MapOptions hash {id:'olmap', width:500px, height:500px,
 *            lat:6710200, lon:506500, zoom:13, toolbar:1, statusbar:1,
 *            controls:1, poihoverstyle:1, baselyr:'', kmlfile:'', gpxfile:'',
 *            summary:''}
 * @param {Array}OLmapPOI
 *            array with POI's [ {lat:6710300,lon:506000,txt:'instap
 *            punt',angle:180,opacity:.9,img:'', rowId:n},... ]);
 *
 * @return a reference to the map
 */
function createMap(mapOpts, OLmapPOI) {
	if (!olEnable) {
		return;
	}
	if (!olTestCSSsupport()) {
		olEnable = !1;
		return;
	}

	var DocBase = DOKU_BASE;

	OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;
	// OpenLayers.Layer.Vector.prototype.renderers = ["SVG", "VML"];

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
		projection : 'EPSG:900913',
		displayProjection : new OpenLayers.Projection("EPSG:4326"),
		numZoomLevels : 22,
		controls : [
				new OpenLayers.Control.ArgParser(),
				new OpenLayers.Control.KeyboardDefaults({observeElement: mapOpts.id}),
				new OpenLayers.Control.Navigation({dragPanOptions: {enableKinetic: !0}}),
				new OpenLayers.Control.ScaleLine({geodesic : !0})
			],
		theme : null
	});

	if (osmEnable) {
		/* add OSM map layers */
		m.addLayer(new OpenLayers.Layer.OSM("OpenStreetMap", null, {
			transitionEffect : "resize",
			visibility : mapOpts.baselyr === "OpenStreetMap"
		}));

		m.addLayer(new OpenLayers.Layer.OSM("transport",
				[
						"http://a.tile2.opencyclemap.org/transport/${z}/${x}/${y}.png",
						"http://b.tile2.opencyclemap.org/transport/${z}/${x}/${y}.png",
						"http://c.tile2.opencyclemap.org/transport/${z}/${x}/${y}.png" ],
				{
					transitionEffect : "resize",
					attribution : 'Data CC-By-SA <a href="http://openstreetmap.org/" target="_blank">OpenStreetMap</a>, '
							+ 'Tiles <a href="http://opencyclemap.org/" target="_blank">OpenCycleMap</a>'
							+ '<img src="http://opencyclemap.org/favicon.ico" alt="OpenCycleMap logo"/>',
					visibility : mapOpts.baselyr === "transport",
					tileOptions : {
						crossOriginKeyword : null
					}
				}));
		m.addLayer(new OpenLayers.Layer.OSM("landscape",
				[
						"http://a.tile3.opencyclemap.org/landscape/${z}/${x}/${y}.png",
						"http://b.tile3.opencyclemap.org/landscape/${z}/${x}/${y}.png",
						"http://c.tile3.opencyclemap.org/landscape/${z}/${x}/${y}.png" ],
				{
					transitionEffect : "resize",
					attribution : 'Data CC-By-SA <a href="http://openstreetmap.org/" target="_blank">OpenStreetMap</a>, '
							+ 'Tiles <a href="http://opencyclemap.org/" target="_blank">OpenCycleMap</a>'
							+ '<img src="http://opencyclemap.org/favicon.ico" alt="OpenCycleMap logo"/>',
					visibility : mapOpts.baselyr === "transport",
					tileOptions : {
						crossOriginKeyword : null
					}
				}));
		m.addLayer(new OpenLayers.Layer.OSM("cycle map",
				[
						"http://a.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
						"http://b.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
						"http://c.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png" ],
				{
					transitionEffect : "resize",
					attribution : 'Data CC-By-SA <a href="http://openstreetmap.org/" target="_blank">OpenStreetMap</a>, '
							+ 'Tiles <a href="http://opencyclemap.org/" target="_blank">OpenCycleMap</a>'
							+ '<img src="http://opencyclemap.org/favicon.ico" alt="OpenCycleMap logo"/>',
					visibility : mapOpts.baselyr === "cycle map",
					tileOptions : {
						crossOriginKeyword : null
					}
				}));
		// CloudMade Fine Line
		m.addLayer(new OpenLayers.Layer.OSM("cloudmade map",
				[
						"http://a.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/2/256/${z}/${x}/${y}.png",
						"http://b.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/2/256/${z}/${x}/${y}.png",
						"http://c.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/2/256/${z}/${x}/${y}.png" ],
				{
					transitionEffect : "resize",
					attribution : 'Tiles &copy; 2012 <a target="_blank" href="http://cloudmade.com">CloudMade</a>'
							+ '<img src="http://cloudmade.com/favicon.ico" alt="CloudMade logo"/>'
							+ ' Data CC-BY-SA <a href="http://openstreetmap.org/" target="_blank">OpenStreetMap</a>',
					visibility : mapOpts.baselyr === "cloudmade map",
					tileOptions : {
						crossOriginKeyword : null
					}
				}));
		m.addLayer(new OpenLayers.Layer.OSM("cloudmade fresh",
				[
						"http://a.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/997/256/${z}/${x}/${y}.png",
						"http://b.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/997/256/${z}/${x}/${y}.png",
						"http://c.tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/997/256/${z}/${x}/${y}.png" ],
				{
					transitionEffect : "resize",
					attribution : 'Tiles &copy; 2012 <a target="_blank" href="http://cloudmade.com">CloudMade</a>'
							+ '<img src="http://cloudmade.com/favicon.ico" alt="CloudMade logo"/>'
							+ ' Data CC-BY-SA <a href="http://openstreetmap.org/" target="_blank">OpenStreetMap</a>',
					visibility : mapOpts.baselyr === "cloudmade fresh",
					tileOptions : {
						crossOriginKeyword : null
					}
				}));

		m.addLayer(new OpenLayers.Layer.OSM("hike and bike map",
				"http://toolserver.org/tiles/hikebike/${z}/${x}/${y}.png", {
					transitionEffect : "resize",
					visibility : mapOpts.baselyr === "hike and bike map",
					tileOptions : {
						crossOriginKeyword : null
					}
				}));
	}
	/*
	 * add MapQuest map layers, see:
	 * http://developer.mapquest.com/web/products/open/map
	 */
	if (mqEnable) {
		m.addLayer(new OpenLayers.Layer.OSM("mapquest road",
				[
						"http://otile1.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.jpg",
						"http://otile2.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.jpg",
						"http://otile3.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.jpg",
						"http://otile4.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.jpg" ],
				{
					transitionEffect : "resize",
					attribution : 'Data CC-By-SA <a href="http://openstreetmap.org/" target="_blank">OpenStreetMap</a>, '
							+ 'Tiles <a href="http://www.mapquest.com/" target="_blank">MapQuest</a>'
							+ '<img src="http://developer.mapquest.com/content/osm/mq_logo.png" alt="MapQuest logo"/>',
					visibility : mapOpts.baselyr === "mapquest road",
					tileOptions : {
						crossOriginKeyword : null
					}
				}));
		// note that global coverage is provided at zoom levels 0-11. Zoom
		// Levels 12+ are provided only in the United States (lower 48).
		m.addLayer(new OpenLayers.Layer.OSM("mapquest sat",
				[
						"http://otile1.mqcdn.com/tiles/1.0.0/sat/${z}/${x}/${y}.jpg",
						"http://otile2.mqcdn.com/tiles/1.0.0/sat/${z}/${x}/${y}.jpg",
						"http://otile3.mqcdn.com/tiles/1.0.0/sat/${z}/${x}/${y}.jpg",
						"http://otile4.mqcdn.com/tiles/1.0.0/sat/${z}/${x}/${y}.jpg" ],
				{
					transitionEffect : "resize",
					attribution : 'Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a>'
							+ '<img src="http://developer.mapquest.com/content/osm/mq_logo.png" alt="MapQuest logo"/>',
					visibility : mapOpts.baselyr === "mapquest sat",
					numZoomLevels : 12,
					tileOptions : {
						crossOriginKeyword : null
					}
				}));
	}

	if (gEnable) {
		/* load google maps */
		try {
			m.addLayer(new OpenLayers.Layer.Google("google relief", {
				type : google.maps.MapTypeId.TERRAIN,
				// transitionEffect : "resize",
				numZoomLevels : 16,
				animationEnabled : !0,
				visibility : mapOpts.baselyr === "google relief"
			}));
			m.addLayer(new OpenLayers.Layer.Google("google sat", {
				type : google.maps.MapTypeId.SATELLITE,
				// transitionEffect : "resize",
				// numZoomLevels : 22,
				animationEnabled : !0,
				visibility : mapOpts.baselyr === "google sat"
			}));
			m.addLayer(new OpenLayers.Layer.Google("google hybrid", {
				type : google.maps.MapTypeId.HYBRID,
				// transitionEffect : "resize",
				// numZoomLevels : 20,
				animationEnabled : !0,
				visibility : mapOpts.baselyr === "google hybrid"
			}));
			m.addLayer(new OpenLayers.Layer.Google("google road", {
				// transitionEffect : "resize",
				// numZoomLevels : 20,
				animationEnabled : !0,
				visibility : mapOpts.baselyr === "google road"
			}));
		} catch (ol_err1) {
			Openlayers.Console.userError('Error loading Google maps' + ol_err1);
		}
	}

	if (bEnable && bApiKey !== '') {
		try {
			/* add Bing tiles */
			m.addLayer(new OpenLayers.Layer.Bing(
					{
						key : bApiKey,
						type : "Road",
						name : "bing road",
						transitionEffect : "resize",
						visibility : mapOpts.baselyr === "bing road",
						wrapDateLine : !0,
						attributionTemplate : '<a target="_blank" href="http://www.bing.com/maps/">'
								+ 'Bing™</a><img src="http://www.bing.com/favicon.ico" alt="Bing logo"/> ${copyrights}'
								+ '<a target="_blank" href="http://www.microsoft.com/maps/product/terms.html">Terms of Use</a>'
					}));
			m.addLayer(new OpenLayers.Layer.Bing(
					{
						key : bApiKey,
						type : "Aerial",
						name : "bing sat",
						transitionEffect : "resize",
						visibility : mapOpts.baselyr === "bing sat",
						wrapDateLine : !0,
						attributionTemplate : '<a target="_blank" href="http://www.bing.com/maps/">'
								+ 'Bing™</a><img src="http://www.bing.com/favicon.ico" alt="Bing logo"/> ${copyrights}'
								+ '<a target="_blank" href="http://www.microsoft.com/maps/product/terms.html">Terms of Use</a>'
					}));
			m.addLayer(new OpenLayers.Layer.Bing(
					{
						key : bApiKey,
						type : "AerialWithLabels",
						name : "bing hybrid",
						transitionEffect : "resize",
						visibility : mapOpts.baselyr === "bing hybrid",
						wrapDateLine : !0,
						attributionTemplate : '<a target="_blank" href="http://www.bing.com/maps/">'
								+ 'Bing™</a><img src="http://www.bing.com/favicon.ico" alt="Bing logo"/> ${copyrights}'
								+ '<a target="_blank" href="http://www.microsoft.com/maps/product/terms.html">Terms of Use</a>'
					}));
		} catch (ol_errBing) {
			Openlayers.Console.userError('Error loading Bing maps: '
					+ ol_errBing);
		}
	}

	m.setCenter(new OpenLayers.LonLat(mapOpts.lon, mapOpts.lat).transform(m.displayProjection, m.projection), mapOpts.zoom);
	extent.extend(m.getExtent());

	// change/set alternative baselyr
	try {
		m.setBaseLayer(((m.getLayersByName(mapOpts.baselyr))[0]));
	} catch (ol_err4) {
		m.setBaseLayer(m.layers[0]);
	}

	if (mapOpts.controls === 1) {
		/* add base controls to map */
		m.addControl(new OpenLayers.Control.LayerSwitcher());
		m.addControl(new OpenLayers.Control.PanZoomBar());
		m.addControl(new OpenLayers.Control.Graticule({
			visible : !1
		}));

		// add hillshade, since this is off by default only add when we have a
		// layerswitcher
		m.addLayer(new OpenLayers.Layer.OSM("Hillshade",
				"http://toolserver.org/~cmarqu/hill/${z}/${x}/${y}.png", {
					transitionEffect : "resize",
					isBaseLayer : !1, // false
					transparent : !0, // true
					visibility : !1,
					displayOutsideMaxExtent : !0,
					attribution : '',
					tileOptions : {
						crossOriginKeyword : null
					}
				}));
		m.addControl(new OpenLayers.Control.OverviewMap({
			size : new OpenLayers.Size(140, 140),
			minRectSize : 10
		}));
	}

	if (mapOpts.statusbar === 1) {
		// statusbar control: permalink
		m.addControl(new OpenLayers.Control.Permalink(mapOpts.id + '-statusbar-link-ref'));
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

	if (mapOpts.toolbar === 1) {
		// add buttons + panel
		var /* zoom in btn */
		zoomin = new OpenLayers.Control.ZoomBox({
			title : "Zoom in"
		}), /* zoom out btn */
		zoomout = new OpenLayers.Control.ZoomBox({
			out : !0,
			title : "Zoom uit",
			displayClass : "olControlZoomOut"
		}), /* pan btn */
		pan = new OpenLayers.Control.DragPan({
			title : "Verschuif"
		}), /* do "nothing" button... */
		info = new OpenLayers.Control.Button({
			type : OpenLayers.Control.TYPE_TOOL,
			displayClass : "olControlFeatureInfo",
			title : "Info"
		}), /* navigation history btns */
		nav = new OpenLayers.Control.NavigationHistory();
		m.addControl(nav);
		var panel = new OpenLayers.Control.Panel({
			defaultControl : pan,
			displayClass : "olToolbar",
			"div" : OpenLayers.Util.getElement(mapOpts.id + "-olToolbar")
		});
		panel.addControls([ zoomin, zoomout, pan, info, nav.next, nav.previous ]);
		m.addControl(panel);
	} else {
		OpenLayers.Util.getElement(mapOpts.id + '-olToolbar').display = 'none';
	}

	if (OLmapPOI.length > 0) {
		var markers = new OpenLayers.Layer.Vector(
				"POI",
				{
					styleMap : new OpenLayers.StyleMap(
							{
								"default" : {
									externalGraphic : "${img}",
									graphicHeight : 16,
									graphicWidth : 16,
									graphicXOffset : 0,
									graphicYOffset : -8,
									graphicOpacity : "${opacity}",
									rotation : "${angle}",
									backgroundGraphic : DocBase
											+ "lib/plugins/openlayersmap/icons/marker_shadow.png",
									backgroundXOffset : 0,
									backgroundYOffset : -4,
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
									cursor : "crosshair",
									externalGraphic : DocBase
											+ "lib/plugins/openlayersmap/icons/marker-red.png",
									graphicHeight : 16,
									graphicWidth : 16,
									graphicXOffset : 0,
									graphicYOffset : -8,
									graphicOpacity : 1.0,
									rotation : "${angle}"
								}
							}),
					isBaseLayer : !1,
					rendererOptions : {
						yOrdering : !0
					}
				});
		m.addLayer(markers);
		var features = [];
		var lonLat;
		for ( var j = 0; j < OLmapPOI.length; j++) {
			var feat = new OpenLayers.Feature.Vector(
					new OpenLayers.Geometry.Point(OLmapPOI[j].lon,
							OLmapPOI[j].lat).transform(m.displayProjection,
							m.projection), {
						angle : OLmapPOI[j].angle,
						opacity : OLmapPOI[j].opacity,
						img : DocBase + "lib/plugins/openlayersmap/icons/"
								+ OLmapPOI[j].img,
						label : OLmapPOI[j].rowId
					});
			feat.data = {
				name : OLmapPOI[j].txt,
				rowId : OLmapPOI[j].rowId
			};
			features.push(feat);
		}
		markers.addFeatures(features);
		extent.extend(markers.getDataExtent());
		m.zoomToExtent(extent);
	}

	/*
	 * map.addLayer(new OpenLayers.Layer.Vector("GML", { protocol: new
	 * OpenLayers.Protocol.HTTP({ url: "gml/polygon.xml", format: new
	 * OpenLayers.Format.GML() }), strategies: [new OpenLayers.Strategy.Fixed()]
	 * }));
	 */

	/* GPX layer */
	if (mapOpts.gpxfile.length > 0) {
		var layerGPX = new OpenLayers.Layer.Vector("GPS route", {
			protocol : new OpenLayers.Protocol.HTTP({
				url : DocBase + "lib/exe/fetch.php?media=" + mapOpts.gpxfile,
				format : new OpenLayers.Format.GPX({
					extractWaypoints : !0,
					extractTracks : !0,
					extractStyles : !0,
					extractAttributes : !0,
					handleHeight : !0,
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
		layerGPX.events.register('loadend', m, function() {
			extent.extend(layerGPX.getDataExtent());
			m.zoomToExtent(extent);
		});

	}

	/* KML layer */
	if (mapOpts.kmlfile.length > 0) {
		var layerKML = new OpenLayers.Layer.Vector("KML file", {
			protocol : new OpenLayers.Protocol.HTTP({
				url : DocBase + "lib/exe/fetch.php?media=" + mapOpts.kmlfile,
				format : new OpenLayers.Format.KML({
					extractStyles : !0,
					extractAttributes : !0,
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
		layerKML.events.register('loadend', m, function() {
			extent.extend(layerKML.getDataExtent());
			m.zoomToExtent(extent);
		});
	}

	// selectcontrol for layers
	if ((m.getLayersByClass('OpenLayers.Layer.GML').length > 0)
			|| m.getLayersByClass('OpenLayers.Layer.Vector').length > 0) {
		selectControl = new OpenLayers.Control.SelectFeature((m
				.getLayersByClass('OpenLayers.Layer.Vector')).concat(m
				.getLayersByClass('OpenLayers.Layer.GML')), {
			hover : mapOpts.poihoverstyle,
			onSelect : onFeatureSelect,
			onUnselect : onFeatureUnselect
		});
		m.addControl(selectControl);
		selectControl.activate();
	}
	return m;
}

var olTimerId = -1;

/** init. */
function olInit() {
	// TODO: check is this is still needed now that we have jQuery
	if (navigator.userAgent.indexOf('MSIE') !== -1) {
		if (olTimerId === -1) {
			olTimerId = setTimeout("olInit()", 3000);
			olEnable = !1;
		} else {
			clearTimeout(olTimerId);
			olEnable = !0;
		}
	}

	if (olEnable) {
		var _i = 0;
		// create the maps in the page
		for (_i = 0; _i < olMapData.length; _i++) {
			olMaps[olMapData[_i].mapOpts.id] = createMap(olMapData[_i].mapOpts, olMapData[_i].poi);
		}

		// hide the table(s) with POI by giving it a print-only style
		// var tbls = getElementsByClass('olPOItableSpan', null, null);
		var tbls = jQuery('.olPOItableSpan');
		for (_i = 0; _i < tbls.length; _i++) {
			tbls[_i].className += ' olPrintOnly';
		}
		// hide the static map image(s) by giving it a print only style
		// var statImgs = getElementsByClass('olStaticMap', null, null);
		var statImgs = jQuery('.olStaticMap');
		for (_i = 0; _i < statImgs.length; _i++) {
			statImgs[_i].className += ' olPrintOnly';
		}
	}
}

/**
 * ol api flag.
 *
 * @type {Boolean}
 */
var olEnable = !1,
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
 * MapQuest tiles flag.
 *
 * @type {Boolean}
 */
mqEnable = !1,
/**
 * google map api flag.
 *
 * @type {Boolean}
 */
gEnable = !1,
/**
 * Bing tiles flag.
 *
 * @type {Boolean}
 */
bEnable = !1,
/**
 * Bing API key.
 *
 * @type {String}
 */
bApiKey = '',
/**
 * OSM tiles flag.
 *
 * @type {Boolean}
 */
osmEnable = !0,
/**
 * CSS support flag.
 *
 * @type {Boolean}
 */
olCSSEnable = !0;
/**
 * yahoo map api flag.
 *
 * @type {Boolean}
 */
// yEnable = false;
/* register olInit to run with onload event. */
jQuery(olInit);
