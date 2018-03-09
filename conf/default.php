<?php
/*
 * Copyright (c) 2008-2017 Mark C. Prins <mprins@users.sf.net>
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
 * OL maps plugin, default configuration settings.
 *
 * @author Mark C. Prins
 */
$conf['bingAPIKey'] = '';
$conf['tfApiKey'] = '';
$conf['iconUrlOverload'] = '';
$conf['enableStamen'] = 1;
$conf['enableGoogle'] = 0;
$conf['googleAPIkey'] = '';
$conf['enableOSM'] = 1;
$conf['enableBing'] = 0;
$conf['enableA11y'] = 1;
$conf['optionStaticMapGenerator'] = 'local';
$conf['autoZoomMap'] = 1;
$conf['displayformat'] = 'DD';

$conf['default_id'] = 'olmap'; //invisible
$conf['default_width'] = '550px';
$conf['default_height'] = '450px';
$conf['default_lat'] = 50.0; //invisible
$conf['default_lon'] = 5.1; //invisible
$conf['default_zoom'] = 12;
$conf['default_autozoom'] = $conf['autoZoomMap']; //invisible; duplicate for internal use
$conf['default_statusbar'] = 1;
$conf['default_toolbar'] = 1;
$conf['default_controls'] = 1;
$conf['default_poihoverstyle'] = 0;
$conf['default_baselyr'] = 'OpenStreetMap'; //invisible
$conf['default_kmlfile'] = '';
$conf['default_gpxfile'] = '';
$conf['default_geojsonfile'] = '';
$conf['default_summary'] = ''; //invisible
