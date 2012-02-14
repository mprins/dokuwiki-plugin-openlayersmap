<?php
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
 * OL maps plugin, default configuration settings.
 *
 * @author Mark C. Prins
 */

// http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAjpkAC9ePGem0lIq5XcMiuhT2yXp_ZAY8_ufC3CFXhHIE1NvwkxTS6gjckBmeABOGXIUiOiZObZESPg
$conf['googleScriptUrl'] = '';
// http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1
$conf['veScriptUrl'] = '';
//$conf['yahooScriptUrl'] = '';
$conf['olScriptUrl'] = DOKU_BASE . 'lib/plugins/openlayersmap/lib/OpenLayers.js';
$conf['bingAPIKey'] = '';
$conf['iconUrlOverload'] = '';
$conf['enableMapQuest'] = 1;
$conf['enableGoogle'] = 0;
$conf['enableOSM'] = 1;
$conf['enableBing'] = 0;
$conf['olMapStyle'] = 'classic';
$conf['enableA11y'] = 1;
