/*
 * Copyright (c) 2008-2011 Mark C. Prins <mc.prins@gmail.com>
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
 * @requires {lib/OpenLayers.js} or other full openlayers build
 * @author Mark C. Prins <mc.prins@gmail.com>
 * 
 */

/**
 * Openlayers selectcontrol.
 * 
 * @type {OpenLayers.Control.SelectFeature}
 * @private
 */
var selectControl,
/**
 * Openlayers bounds used for managing the map extent.
 * 
 * @type {OpenLayers.Bounds}
 * @private
 */
extent;

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

	var pContent = "";
	if (feature.data.rowId !== undefined) {
		pContent += "<div style=''>" + feature.data.rowId + ": </div>";
	}
	if (feature.data.name !== undefined) {
		pContent += "<div style=''>" + feature.data.name + "</div>";
	}
	if (feature.data.ele !== undefined) {
		pContent += "<div style=''>elevation: " + feature.data.ele + "</div>";
	}
	if (feature.data.type !== undefined) {
		pContent += "<div style=''>" + feature.data.type + "></div>";
	}
	if (feature.data.time !== undefined) {
		pContent += "<div style=''>time: " + feature.data.time + "</div>";
	}
	if (feature.data.description !== undefined) {
		pContent += "<div style=''>" + feature.data.description + "</div>";
	}

	if (pContent.length > 0) {
		var popup = new OpenLayers.Popup.FramedCloud("olPopup", pPos, null,
				pContent, null, true, function() {
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

/** init. */
function olInit() {
	// iterator
	var _i = 0;
	// hide the table with POI by giving it a print only style
	var tbls = getElementsByClass('olPOItableSpan', null, null);
	for (_i = 0; _i < tbls.length; _i++) {
		// tbls[i].style.display = 'none';
		tbls[_i].className += ' olPrintOnly';
	}
	// hide the static map image by giving it a print only style
	var statImgs = getElementsByClass('olStaticMap', null, null);
	for (_i = 0; _i < statImgs.length; _i++) {
		// statImgs[i].style.display = 'none';
		statImgs[_i].className += ' olPrintOnly';
	}
	// show the dynamic map but only in the browser, this element is not here
	// when we load the page
	// var dynMaps = getElementsByClass('olContainer', null, null);
	// for (_i = 0; _i < dynMaps.length; _i++) {
	// // dynMaps[i].style.display = 'inline';
	// dynMaps[_i].className += ' olWebOnly';
	// }
}

/**
 * creates a DocumentFragment to insert into the dom.
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
	var mEl = '<div id="'
			+ mapid
			+ '-olContainer" class="olContainer olWebOnly">'
			+ '<div id="'
			+ mapid
			+ '-olToolbar" class="olToolbar"></div>'
			+ '<div class="clearer"></div>'
			+ '<div id="'
			+ mapid
			+ '" style="width:'
			+ width
			+ ';height:'
			+ height
			+ ';" class="olMap"></div>'
			+ '<div id="'
			+ mapid
			+ '-olStatusBar" class="olStatusBarContainer">'
			+ '<div id="'
			+ mapid
			+ '-statusbar-scale" class="olStatusBar olStatusBarScale">scale</div>'
			+ '<div id="'
			+ mapid
			+ '-statusbar-link" class="olStatusBar olStatusBarPermalink"><a href="" id="'
			+ mapid
			+ '-statusbar-link-ref">map link</a></div>'
			+ '<div id="'
			+ mapid
			+ '-statusbar-mouseposition" class="olStatusBar olStatusBarMouseposition"></div>'
			+ '<div id="'
			+ mapid
			+ '-statusbar-projection" class="olStatusBar olStatusBarProjection">proj</div>'
			+ '<div id="' + mapid
			+ '-statusbar-text" class="olStatusBar olStatusBarText">txt</div>'
			+ '</div>\n</div>',
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
 */
function createMap(mapOpts, OLmapPOI) {
	if (!olEnable) {
		return;
	}

	var DocBase = DOKU_BASE;

	OpenLayers.IMAGE_RELOAD_ATTEMPTS = 4;
	OpenLayers.Util.onImageLoadErrorColor = 'pink';
	OpenLayers.Util.onImageLoadError = function() {
		/* transparent gif */
		// IE 8 complains w/ stack overflow... this.src =
		// "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=";
		this.src = DocBase + "lib/plugins/openlayersmap/lib/img/blank.gif";
	};

	// OpenLayers.Layer.Vector.prototype.renderers = ["SVG2", "VML", "Canvas"];

	// http://mapbox.com/documentation/adding-tiles-your-site/openlayers-themes
	// OpenLayers.ImgPath = '';

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
	m = new OpenLayers.Map(mapOpts.id, {
		projection : new OpenLayers.Projection('EPSG:900913'),
		displayProjection : new OpenLayers.Projection('EPSG:4326'),
		units : 'm',
		maxResolution : 156543.0339,
		maxExtent : new OpenLayers.Bounds(-20037508.34, -20037508.34,
				20037508.34, 20037508.34),
		numZoomLevels : 19,
		// panDuration : 100,
		controls : [ /* new OpenLayers.Control.LoadingPanel(), */
		new OpenLayers.Control.KeyboardDefaults(),
				new OpenLayers.Control.Navigation({
					dragPanOptions : {
						enableKinetic : true
					}
				}), new OpenLayers.Control.ScaleLine({
					geodesic : true
				}) ],
		theme : null
	});
	if (osmEnable) {
		/* add OSM map layers */
		m.addLayer(new OpenLayers.Layer.OSM("OpenStreetMap"), {
			transitionEffect : 'resize',
			visibility : false
		});

		m
				.addLayer(
						new OpenLayers.Layer.OSM(
								"t@h",
								[
										"http://a.tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png",
										"http://b.tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png",
										"http://c.tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png" ]),
						{
							transitionEffect : 'resize',
							visibility : false
						});

		m
				.addLayer(
						new OpenLayers.Layer.OSM(
								"cycle map",
								[
										// "http://andy.sandbox.cloudmade.com/tiles/cycle/${z}/${x}/${y}.png",
										"http://a.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
										"http://b.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png",
										"http://c.tile.opencyclemap.org/cycle/${z}/${x}/${y}.png" ]),
						{
							transitionEffect : 'resize',
							attribution : 'Data CC-By-SA <a href="http://openstreetmap.org/" target="_blank">OpenStreetMap</a>, '
									+ 'Tiles <a href="http://opencyclemap.org/" target="_blank">OpenCycleMap</a>'
									+ '<img src="http://opencyclemap.org/favicon.ico" heigth="16" width="16"/>',
							visibility : false
						});

		m
				.addLayer(new OpenLayers.Layer.OSM(
						"cloudmade map",
						"http://tile.cloudmade.com/2f59745a6b525b4ebdb100891d5b6711/3/256/${z}/${x}/${y}.png",
						{
							transitionEffect : 'resize',
							visibility : false
						}));

		m.addLayer(new OpenLayers.Layer.OSM("hike and bike map",
				"http://toolserver.org/tiles/hikebike/${z}/${x}/${y}.png", {
					transitionEffect : 'resize',
					visibility : false
				}));
	}
	/*
	 * add MapQuest map layers, see:
	 * http://developer.mapquest.com/web/products/open/map
	 */
	if (mqEnable) {
		m
				.addLayer(new OpenLayers.Layer.OSM(
						"mapquest road",
						[
								"http://otile1.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png",
								"http://otile2.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png",
								"http://otile3.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png",
								"http://otile4.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png" ],
						{
							transitionEffect : 'resize',
							attribution : 'Data CC-By-SA <a href="http://openstreetmap.org/" target="_blank">OpenStreetMap</a>, '
									+ 'Tiles <a href="http://www.mapquest.com/" target="_blank">MapQuest</a>'
									+ '<img src="http://developer.mapquest.com/content/osm/mq_logo.png" heigth="14" width="14" alt="logo"/>',
							visibility : false
						}));
		// note that global coverage is provided at zoom levels 0-11. Zoom
		// Levels 12+ are provided only in the United States (lower 48).
		m
				.addLayer(new OpenLayers.Layer.OSM(
						"mapquest aerial",
						[
								"http://oatile1.mqcdn.com/naip/${z}/${x}/${y}.jpg",
								"http://oatile2.mqcdn.com/naip/${z}/${x}/${y}.jpg",
								"http://oatile3.mqcdn.com/naip/${z}/${x}/${y}.jpg",
								"http://oatile4.mqcdn.com/naip/${z}/${x}/${y}.jpg" ],
						{
							transitionEffect : 'resize',
							attribution : 'Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a>'
									+ '<img src="http://developer.mapquest.com/content/osm/mq_logo.png" heigth="16" width="16">',
							visibility : false,
							numZoomLevels : 12
						}));
	}

	/* open aerial map layers */
	/*
	 * turn this off; project is asleep:
	 * https://sourceforge.net/tracker/?func=detail&aid=2897327&group_id=239475&atid=1110186
	 * m.addLayer(new OpenLayers.Layer.XYZ("OpenAerialMap",
	 * "http://tile.openaerialmap.org/tiles/1.0.0/openaerialmap-900913/${z}/${x}/${y}.jpg",
	 * {name: "OpenStreetMap", attribution: "Data CC-By by <a
	 * href='http://www.openaerialmap.org/licensing/'>OpenAerialMap</a>",
	 * sphericalMercator: true, transitionEffect: 'resize'} ));
	 */

	/* controle of google/yahoo/ve api's beschikbaar zijn.. */
	if (gEnable) {
		try {
			m.addLayer(new OpenLayers.Layer.Google("google relief", {
				type : google.maps.MapTypeId.TERRAIN,
				// transitionEffect : 'resize',
				numZoomLevels : 16,
				animationEnabled : true,
				visibility : false
			}));
			m.addLayer(new OpenLayers.Layer.Google("google sat", {
				type : google.maps.MapTypeId.SATELLITE,
				// transitionEffect : 'resize',
				// numZoomLevels : 22,
				animationEnabled : true,
				visibility : false
			}));
			m.addLayer(new OpenLayers.Layer.Google("google hybrid", {
				type : google.maps.MapTypeId.HYBRID,
				// transitionEffect : 'resize',
				// numZoomLevels : 20,
				animationEnabled : true,
				visibility : false
			}));
			m.addLayer(new OpenLayers.Layer.Google("google normal", {
				// transitionEffect : 'resize',
				// numZoomLevels : 20,
				animationEnabled : true,
				visibility : false
			}));
		} catch (ol_err1) {
			Openlayers.Console.userError('Error loading Google maps' + ol_err1);
		}
	}

	// if (yEnable) {
	// try {
	// m.addLayer(new OpenLayers.Layer.Yahoo("yahoo", {
	// 'type' : YAHOO_MAP_HYB,
	// 'sphericalMercator' : true,
	// transitionEffect : resize
	// }));
	// } catch (ol_err2) {
	// }
	// }

	if (veEnable) {
		try {
			m.addLayer(new OpenLayers.Layer.VirtualEarth("ve", {
				type : VEMapStyle.Hybrid,
				sphericalMercator : true,
				transitionEffect : 'resize',
				visibility : false
			}));
		} catch (ol_err3) {
		}
	}
	m.setCenter(new OpenLayers.LonLat(mapOpts.lon, mapOpts.lat).transform(
			m.displayProjection, m.projection), mapOpts.zoom);
	extent.extend(m.getExtent());

	// change/set alternative baselyr
	try {
		m.setBaseLayer(((m.getLayersByName(mapOpts.baselyr))[0]));
	} catch (ol_err4) {
		m.setBaseLayer(m.layers[0]);
	}

	if (mapOpts.controls === 1) {
		/* add base controls to map */
		m.addControl(new OpenLayers.Control.LayerSwitcher({
			roundedCorner : false,
			roundedCornerColor : null
		}));
		m.addControl(new OpenLayers.Control.PanZoomBar());
		m.addControl(new OpenLayers.Control.Graticule({
			visible : false
		}));

		// add hillshade, since this is off by default only add when we have a
		// layerswitcher
		m.addLayer(new OpenLayers.Layer.OSM("Hillshade",
				"http://toolserver.org/~cmarqu/hill/${z}/${x}/${y}.png", {
					transitionEffect : 'resize',
					isBaseLayer : false,
					transparent : true,
					visibility : false,
					displayOutsideMaxExtent : true,
					attribution : ''
				}));

		m.addControl(new OpenLayers.Control.OverviewMap({
			size : new OpenLayers.Size(140, 140),
			mapOptions : {
				theme : null
			},
			layers : [ m.baseLayer.clone() ],
			minRectSize : 10
		}));
	}

	if (mapOpts.statusbar === 1) {
		// statusbar control: permalink
		m.addControl(new OpenLayers.Control.Permalink(mapOpts.id
				+ '-statusbar-link-ref'));
		// statusbar control: mouse pos.
		// TODO kijken naar afronding met aNumber.toFixed(0)
		m.addControl(new OpenLayers.Control.MousePosition({
			'div' : OpenLayers.Util.getElement(mapOpts.id
					+ '-statusbar-mouseposition')
		}));
		// statusbar control: scale
		m.addControl(new OpenLayers.Control.Scale(mapOpts.id
				+ '-statusbar-scale'));
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
		var zoomin = new OpenLayers.Control.ZoomBox({
			title : "Zoom in"
		}), /**/zoomout = new OpenLayers.Control.ZoomBox({
			out : true,
			title : "Zoom uit",
			displayClass : "olControlZoomOut"
		}), /**/pan = new OpenLayers.Control.DragPan({
			title : "Verschuif"
		}), /* do "nothing" button... */info = new OpenLayers.Control.Button({
			type : OpenLayers.Control.TYPE_TOOL,
			displayClass : "olControlFeatureInfo"
		/* , trigger : selectControl.activate() */
		}), /* navigation history */
		nav = new OpenLayers.Control.NavigationHistory();
		m.addControl(nav);
		var panel = new OpenLayers.Control.Panel({
			defaultControl : pan,
			displayClass : "olToolbar",
			"div" : OpenLayers.Util.getElement(mapOpts.id + "-olToolbar")
		});
		panel
				.addControls([ zoomin, zoomout, pan, info, nav.next,
						nav.previous ]);
		// panel.addControls([ nav.next, nav.previous ]);
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
					isBaseLayer : false,
					rendererOptions : {
						yOrdering : true
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

	/* GPX layer */
	if (mapOpts.gpxfile.length > 0) {
		var layerGPX = new OpenLayers.Layer.GML("GPS route", DocBase
				+ "lib/exe/fetch.php?media=" + mapOpts.gpxfile, {
			format : OpenLayers.Format.GPX,
			formatOptions : {
				extractWaypoints : true,
				extractTracks : true,
				extractStyles : true,
				extractAttributes : true,
				handleHeight : true,
				maxDepth : 3
			},
			style : {
				strokeColor : "#0000FF",
				strokeWidth : 3,
				strokeOpacity : 0.7,
				pointRadius : 4,
				fillColor : "#0099FF",
				fillOpacity : 0.7
			/*
			 * , label:"${name}"
			 */},
			projection : new OpenLayers.Projection("EPSG:4326")
		});
		m.addLayer(layerGPX);
		layerGPX.events.register('loadend', m, function() {
			extent.extend(layerGPX.getDataExtent());
			m.zoomToExtent(extent);
		});

	}

	/* KML layer */
	if (mapOpts.kmlfile.length > 0) {
		var layerKML = new OpenLayers.Layer.GML("KML file", DocBase
				+ "lib/exe/fetch.php?media=" + mapOpts.kmlfile, {
			format : OpenLayers.Format.KML,
			formatOptions : {
				extractStyles : true,
				extractAttributes : true,
				maxDepth : 3
			},
			style : {
				label : "${name}"
			},
			projection : new OpenLayers.Projection("EPSG:4326")
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

/**
 * ol api flag.
 * 
 * @type {Boolean}
 */
var olEnable = false,
/**
 * MapQuest tiles flag.
 * 
 * @type {Boolean}
 */
mqEnable = false,
/**
 * google map api flag.
 * 
 * @type {Boolean}
 */
gEnable = false,
/**
 * virtual earth map api flag.
 * 
 * @type {Boolean}
 */
veEnable = false,
/**
 * OSM tiles flag.
 * 
 * @type {Boolean}
 */
osmEnable = true;
/**
 * yahoo map api flag.
 * 
 * @type {Boolean}
 */
// yEnable = false;
/* register olInit to run with onload event. */
addInitEvent(olInit);
