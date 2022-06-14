/*
 * Copyright (c) 2008-2022 Mark C. Prins <mprins@users.sf.net>
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
 *            baselyr:'', kmlfile:'', gpxfile:'', geojsonfile,
 *            summary:''}
 * @param {Array}
 *            OLmapPOI array with POI's [ {lat:6710300,lon:506000,txt:'instap
 *            punt',angle:180,opacity:.9,img:'', rowId:n},... ]);
 *
 * @return {OpenLayers.Map} the created map
 */
function createMap(mapOpts, poi) {

    // const mapOpts = olMapData[0].mapOpts;
    // const poi = olMapData[0].poi;

    if (!olEnable) {
        return;
    }
    if (!olTestCSSsupport()) {
        olEnable = false;
        return;
    }

    // find map element location
    var cleartag = document.getElementById(mapOpts.id + '-clearer');
    if (cleartag === null) {
        return;
    }
    // create map element and add to document
    var fragment = olCreateMaptag(mapOpts.id, mapOpts.width, mapOpts.height);
    cleartag.parentNode.insertBefore(fragment, cleartag);

    /** dynamic map extent. */
    let extent = ol.extent.createEmpty();
    overlayGroup = new ol.layer.Group({title: 'Overlays', fold: 'open', layers: []});
    const baseLyrGroup = new ol.layer.Group({'title': 'Base maps', layers: []});

    const map = new ol.Map({
        target:   document.getElementById(mapOpts.id),
        layers:   [baseLyrGroup, overlayGroup],
        view:     new ol.View({
            center:     ol.proj.transform([mapOpts.lon, mapOpts.lat], 'EPSG:4326', 'EPSG:3857'),
            zoom:       mapOpts.zoom,
            projection: 'EPSG:3857'
        }),
        controls: [new ol.control.Zoom()]
    });

    if (osmEnable) {
        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: true,
                title:   'OSM',
                type:    'base',
                source:  new ol.source.OSM()
            }));

        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: mapOpts.baselyr === "cycle map",
                title:   'cycle map',
                type:    'base',
                source:  new ol.source.OSM({
                    url:          'https://{a-c}.tile.thunderforest.com/cycle/{z}/{x}/{y}.png?apikey=' + tfApiKey,
                    attributions: 'Data &copy;ODbL <a href="https://openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>, '
                                      + 'Tiles &copy;<a href="https://www.thunderforest.com/" target="_blank">Thunderforest</a>'
                                      + '<img src="https://www.thunderforest.com/favicon.ico" alt="Thunderforest logo"/>'
                })
            }));

        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: mapOpts.baselyr === "transport",
                title:   'transport',
                type:    'base',
                source:  new ol.source.OSM({
                    url:          'https://{a-c}.tile.thunderforest.com/transport/{z}/{x}/{y}.png?apikey=' + tfApiKey,
                    attributions: 'Data &copy;ODbL <a href="https://openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>, '
                                      + 'Tiles &copy;<a href="https://www.thunderforest.com/" target="_blank">Thunderforest</a>'
                                      + '<img src="https://www.thunderforest.com/favicon.ico" alt="Thunderforest logo"/>'
                })
            }));

        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: mapOpts.baselyr === "landscape",
                title:   'landscape',
                type:    'base',
                source:  new ol.source.OSM({
                    url:          'https://{a-c}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png?apikey=' + tfApiKey,
                    attributions: 'Data &copy;ODbL <a href="https://openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>, '
                                      + 'Tiles &copy;<a href="https://www.thunderforest.com/" target="_blank">Thunderforest</a>'
                                      + '<img src="https://www.thunderforest.com/favicon.ico" alt="Thunderforest logo"/>'
                })
            }));

        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: mapOpts.baselyr === "outdoors",
                title:   'outdoors',
                type:    'base',
                source:  new ol.source.OSM({
                    url:          'https://{a-c}.tile.thunderforest.com/outdoors/{z}/{x}/{y}.png?apikey=' + tfApiKey,
                    attributions: 'Data &copy;ODbL <a href="https://openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>, '
                                      + 'Tiles &copy;<a href="https://www.thunderforest.com/" target="_blank">Thunderforest</a>'
                                      + '<img src="https://www.thunderforest.com/favicon.ico" alt="Thunderforest logo"/>'
                })
            }));
    }

    if (bEnable && bApiKey !== '') {
        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: mapOpts.baselyr === "bing road",
                title:   'bing road',
                type:    'base',
                source:  new ol.source.BingMaps({
                    key:        bApiKey,
                    imagerySet: 'Road'
                })
            }));

        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: mapOpts.baselyr === "bing sat",
                title:   'bing sat',
                type:    'base',
                source:  new ol.source.BingMaps({
                    key:        bApiKey,
                    imagerySet: 'Aerial'
                })
            }));

        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: mapOpts.baselyr === "bing hybrid",
                title:   'bing hybrid',
                type:    'base',
                source:  new ol.source.BingMaps({
                    key:        bApiKey,
                    imagerySet: 'AerialWithLabels'
                })
            }));
    }

    if (stamenEnable) {
        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: false,
                type:    'base',
                title:   'toner',
                source:  new ol.source.Stamen({layer: 'toner'})
            })
        );
        baseLyrGroup.getLayers().push(
            new ol.layer.Tile({
                visible: false,
                type:    'base',
                title:   'terrain',
                source:  new ol.source.Stamen({layer: 'terrain'})
            })
        );
    }

    extent = ol.extent.extend(extent, map.getView().calculateExtent());

    const iconScale = 1.0;
    const vectorSource = new ol.source.Vector();
    poi.forEach((p) => {
        const f = new ol.Feature({
            geometry:    new ol.geom.Point(ol.proj.fromLonLat([p.lon, p.lat])),
            description: p.txt,
            img:         p.img,
            rowId:       p.rowId,
            lat:         p.lat,
            lon:         p.lon,
            angle:       p.angle,
            alt:         p.img.substring(0, p.img.lastIndexOf("."))
        });
        f.setId(p.rowId);
        f.setStyle(new ol.style.Style({
            text:      new ol.style.Text({
                text:           "" + p.rowId,
                textAlign:      'left',
                textBaseline:   'bottom',
                offsetX:        8,
                offsetY:        -8,
                scale:          iconScale,
                fill:           new ol.style.Fill({color: 'rgb(0,0,0)'}),
                font:           '12px monospace bold',
                backgroundFill: new ol.style.Fill({color: 'rgba(255,255,255,.4)'})
            }), image: new ol.style.Icon({
                src:         DOKU_BASE + "lib/plugins/openlayersmap/icons/" + p.img,
                crossOrigin: '',
                opacity:     p.opacity,
                scale:       iconScale,
                rotation:    p.angle * Math.PI / 180,
            }),
        }));
        vectorSource.addFeature(f);
    });

    const vectorLayer = new ol.layer.Vector({title: 'POI', visible: true, source: vectorSource});
    overlayGroup.getLayers().push(vectorLayer);
    if (mapOpts.autozoom) {
        extent = ol.extent.extend(extent, vectorSource.getExtent());
        map.getView().fit(extent);
    }

    map.addControl(new ol.control.ScaleLine({bar: true, text: true}));
    map.addControl(new ol.control.MousePosition({
        coordinateFormat: ol.coordinate.createStringXY(4), projection: 'EPSG:4326',
    }));
    map.addControl(new ol.control.FullScreen({label: '✈'}));
    map.addControl(new ol.control.OverviewMap({
        label:  '+',
        layers: [new ol.layer.Tile({
            source: new ol.source.OSM()
        })]
    }));
    map.addControl(new ol.control.LayerSwitcher({
        activationMode: 'click',
        label:          '\u2630',
        collapseLabel:  '\u00BB',
    }));
    map.addControl(new ol.control.Attribution({
        collapsible: true,
        collapsed:   true
    }));

    if (mapOpts.kmlfile.length > 0) {
        try {
            const kmlSource = new ol.source.Vector({
                url:    DOKU_BASE + "lib/exe/fetch.php?media=" + mapOpts.kmlfile,
                format: new ol.format.KML(),
            });
            overlayGroup.getLayers().push(new ol.layer.Vector({title: 'KML file', visible: true, source: kmlSource}));

            if (mapOpts.autozoom) {
                kmlSource.once('change', function () {
                    extent = ol.extent.extend(extent, kmlSource.getExtent());
                    map.getView().fit(extent);
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    if (mapOpts.geojsonfile.length > 0) {
        try {
            const geoJsonSource = new ol.source.Vector({
                url:    DOKU_BASE + "lib/exe/fetch.php?media=" + mapOpts.geojsonfile,
                format: new ol.format.GeoJSON(),
            });
            overlayGroup.getLayers().push(new ol.layer.Vector({
                title: 'GeoJSON file', visible: true, source: geoJsonSource,
                // TODO
                // style:  {
                //     strokeColor:   "#FF00FF",
                //     strokeWidth:   3,
                //     strokeOpacity: 0.7,
                //     pointRadius:   4,
                //     fillColor:     "#FF99FF",
                //     fillOpacity:   0.7
                // }
            }));

            if (mapOpts.autozoom) {
                geoJsonSource.once('change', function () {
                    extent = ol.extent.extend(extent, geoJsonSource.getExtent());
                    map.getView().fit(extent);
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    if (mapOpts.gpxfile.length > 0) {
        try {
            const gpxSource = new ol.source.Vector({
                url:    DOKU_BASE + "lib/exe/fetch.php?media=" + mapOpts.gpxfile,
                format: new ol.format.GPX(),
            });
            overlayGroup.getLayers().push(new ol.layer.Vector({
                title: 'GPS track', visible: true, source: gpxSource,
                // TODO
                // style:  {
                //     strokeColor:   "#0000FF",
                //     strokeWidth:   3,
                //     strokeOpacity: 0.7,
                //     pointRadius:   4,
                //     fillColor:     "#0099FF",
                //     fillOpacity:   0.7
                // }
            }));

            if (mapOpts.autozoom) {
                gpxSource.once('change', function () {
                    extent = ol.extent.extend(extent, gpxSource.getExtent());
                    map.getView().fit(extent);
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    const container = document.getElementById('popup');
    const content = document.getElementById('popup-content');
    const closer = document.getElementById('popup-closer');

    const overlay = new ol.Overlay({
        element: container, autoPan: true, autoPanAnimation: {
            duration: 250,
        }, //stopEvent: false,
    });
    map.addOverlay(overlay);

    /**
     * Add a click handler to hide the popup.
     * @return {boolean} Don't follow the href.
     */
    closer.onclick = function () {
        overlay.setPosition(undefined);
        closer.blur();
        return false;
    };

    // display popup on click
    map.on('singleclick', function (evt) {
        const selFeature = map.forEachFeatureAtPixel(evt.pixel, function (feature) {
            return feature;
        });
        if (selFeature) {
            overlay.setPosition(evt.coordinate);

            let pContent = '<div class="spacer">&nbsp;</div>';
            let locDesc = '';

            if (selFeature.get('rowId') !== undefined) {
                pContent += '<span class="rowId">' + selFeature.get('rowId') + ': </span>';
            }
            if (selFeature.get('name') !== undefined) {
                pContent += '<span class="txt">' + selFeature.get('name') + '</span>';
                locDesc = selFeature.get('name');
                // TODO strip <p> tag from locDesc
                // locDesc = selFeature.get('name').split(/\s+/).slice(0,2).join('+');
            }
            if (selFeature.get('ele') !== undefined) {
                pContent += '<div class="ele">elevation: ' + selFeature.get('ele') + '</div>';
            }
            if (selFeature.get('type') !== undefined) {
                pContent += '<div>' + selFeature.get('type') + '</div>';
            }
            if (selFeature.get('time') !== undefined) {
                pContent += '<div class="time">time: ' + selFeature.get('time') + '</div>';
            }
            if (selFeature.get('description') !== undefined) {
                pContent += '<div class="desc">' + selFeature.get('description') + '</div>';
            }
            if (selFeature.get('img') !== undefined) {
                pContent += '<div class="coord" title="lat;lon">' +
                    '<img alt="" src="' + DOKU_BASE + 'lib/plugins/openlayersmap/icons/' + selFeature.get('img') +
                    '" width="16" height="16" ' + 'style="transform:rotate(' + selFeature.get('angle') + 'deg)" />&nbsp;' +
                    '<a href="geo:' + selFeature.get('lat') + ',' + selFeature.get('lon') + '?q=' + selFeature.get('lat') +
                    ',' + selFeature.get('lon') + '(' + selFeature.get('alt') + ')" title="Open in navigation app">' +
                    ol.coordinate.format([selFeature.get('lon'), selFeature.get('lat')], '{x}º; {y}º', 4) + '</a></div>';
            }
            content.innerHTML = pContent;
        } else {
            // do nothing...
        }
    });

    // change mouse cursor when over marker
    map.on('pointermove', function (e) {
        const pixel = map.getEventPixel(e.originalEvent);
        const hit = map.hasFeatureAtPixel(pixel);
        map.getTarget().style.cursor = hit ? 'pointer' : '';
    });

    return map;
}

/**
 * add layers to the map based on the olMapOverlays object.
 */
function olovAddToMap() {
    for (const key in olMapOverlays) {
        const overlay = olMapOverlays[key];
        const m = olMaps[overlay.id];

        switch (overlay.type) {
            case 'osm':
                m.addLayer(new ol.layer.Tile({
                    title:   overlay.name,
                    visible: (overlay.visible).toLowerCase() === 'true',
                    opacity: parseFloat(overlay.opacity),
                    source:  new ol.source.OSM({
                        url:          overlay.url,
                        crossOrigin:  'Anonymous',
                        attributions: overlay.attribution
                    })
                }));
                break;
            case 'wms':
                m.addLayer(new ol.layer.Image({
                    title:   overlay.name,
                    opacity: parseFloat(overlay.opacity),
                    visible: (overlay.visible).toLowerCase() === 'true',
                    source:  new ol.source.ImageWMS({
                        url:          overlay.url,
                        params:       {
                            'LAYERS':      overlay.layers,
                            'VERSION':     overlay.version,
                            'TRANSPARENT': overlay.transparent,
                            'FORMAT':      overlay.format
                        },
                        ratio:        1,
                        crossOrigin:  'Anonymous',
                        attributions: overlay.attribution
                    })
                }));
                break;
            case 'ags':
                m.addLayer(new ol.layer.Image({
                    title:   overlay.name,
                    opacity: parseFloat(overlay.opacity),
                    visible: (overlay.visible).toLowerCase() === 'true',
                    source:  new ol.source.ImageArcGISRest({
                        url:          overlay.url,
                        params:       {
                            'LAYERS':      overlay.layers,
                            'TRANSPARENT': overlay.transparent,
                            'FORMAT':      overlay.format
                        },
                        ratio:        1,
                        crossOrigin:  'Anonymous',
                        attributions: overlay.attribution
                    })
                }));
                break;
            // case 'mapillary':
            //     var mUrl = 'http://api.mapillary.com/v1/im/search?';
            //     if (overlay.skey !== '') {
            //         mUrl = 'http://api.mapillary.com/v1/im/sequence?';
            //     }
            //     var mLyr = new OpenLayers.Layer.Vector(
            //         "Mapillary", {
            //             projection:  new OpenLayers.Projection("EPSG:4326"),
            //             strategies:  [new OpenLayers.Strategy.BBOX({
            //                 ratio:     1.1,
            //                 resFactor: 1.5
            //             }) /* ,new OpenLayers.Strategy.Cluster({}) */],
            //             protocol:    new OpenLayers.Protocol.HTTP({
            //                 url:            mUrl,
            //                 format:         new OpenLayers.Format.GeoJSON(),
            //                 params:         {
            //                     // default to max. 250 locations
            //                     'max-results': 250,
            //                     'geojson':     true,
            //                     'skey':        overlay.skey
            //                 },
            //                 filterToParams: function (filter, params) {
            //                     if (filter.type === OpenLayers.Filter.Spatial.BBOX) {
            //                         // override the bbox serialization of
            //                         // the filter to give the Mapillary
            //                         // specific bounds
            //                         params['min-lat'] = filter.value.bottom;
            //                         params['max-lat'] = filter.value.top;
            //                         params['min-lon'] = filter.value.left;
            //                         params['max-lon'] = filter.value.right;
            //                         // if the width of our bbox width is
            //                         // less than 0.15 degrees drop the max
            //                         // results
            //                         if (filter.value.top - filter.value.bottom < .15) {
            //                             OpenLayers.Console.debug('dropping max-results parameter, width is: ',
            //                                 filter.value.top - filter.value.bottom);
            //                             params['max-results'] = null;
            //                         }
            //                     }
            //                     return params;
            //                 }
            //             }),
            //             styleMap:    new OpenLayers.StyleMap({
            //                 'default': {
            //                     cursor:          'help',
            //                     rotation:        '${ca}',
            //                     externalGraphic: DOKU_BASE + 'lib/plugins/openlayersmapoverlays/icons/arrow-up-20.png',
            //                     graphicHeight:   20,
            //                     graphicWidth:    20,
            //                 },
            //                 'select':  {
            //                     externalGraphic: DOKU_BASE + 'lib/plugins/openlayersmapoverlays/icons/arrow-up-20-select.png',
            //                     label:           '${location}',
            //                     fontSize:        '1em',
            //                     fontFamily:      'monospace',
            //                     labelXOffset:    '0.5',
            //                     labelYOffset:    '0.5',
            //                     labelAlign:      'lb',
            //                 }
            //             }),
            //             attribution: '<a href="http://www.mapillary.com/legal.html">' +
            //                              '<img src="http://mapillary.com/favicon.ico" ' +
            //                              'alt="Mapillary" height="16" width="16" />Mapillary (CC-BY-SA)',
            //             visibility:  (overlay.visible).toLowerCase() == 'true',
            //         });
            //     m.addLayer(mLyr);
            //     selectControl.addLayer(mLyr);
            //     break;
            // case 'search':
            //     m.addLayer(new OpenLayers.Layer.Vector(
            //         overlay.name,
            //         overlay.url,
            //         {
            //             layers:      overlay.layers,
            //             version:     overlay.version,
            //             transparent: overlay.transparent,
            //             format:      overlay.format
            //         }, {
            //             opacity:     parseFloat(overlay.opacity),
            //             visibility:  (overlay.visible).toLowerCase() == 'true',
            //             isBaseLayer: !1,
            //             attribution: overlay.attribution
            //         }
            //     ));
            //     break;
        }
    }
}

/** init. */
function olInit() {
    if (olEnable) {
        // add info window to DOM
        const frag = document.createDocumentFragment(),
            temp = document.createElement('div');
        temp.innerHTML = '<div id="popup" class="olPopup"><a href="#" id="popup-closer" class="olPopupCloseBox"></a><div id="popup-content"></div></div>';
        while (temp.firstChild) {
            frag.appendChild(temp.firstChild);
        }
        document.body.appendChild(frag);

        let _i = 0;
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

        // add overlays
        olovAddToMap();

        let resizeTimer;
        jQuery(window).on('resize', function (e) {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
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
                + 'Show or hide help</span>?</button></div>');
        // toggle to switch dynamic vs. static map
        jQuery('.olMapHelp').before(
            '<div class="a11y"><button onclick="jQuery(\'.olPrintOnly\').toggle();jQuery(\'.olWebOnly\').toggle();">'
            + 'Hide or show the dynamic map</button></div>');
    }
}

/**
 * CSS support flag.
 *
 * @type {Boolean}
 */
let olCSSEnable = true;

/* register olInit to run with onload event. */
jQuery(olInit);
