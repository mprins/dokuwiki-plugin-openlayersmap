<?php
/*
 * Copyright (c) 2008-2015 Mark C. Prins <mprins@users.sf.net>
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
 * OL maps plugin, configuration metadata.
 *
 * @author Mark C. Prins
 */

$meta['enableOSM'] = array (
	'onoff'
);
$meta['enableMapQuest'] = array (
	'onoff'
);
$meta['mapquestAPIKey'] = array (
		'string'
);
$meta['enableGoogle'] = array (
	'onoff'
);
$meta['googleAPIkey'] = array (
		'string'
);
$meta['enableBing'] = array (
	'onoff'
);
$meta['bingAPIKey'] = array (
	'string'
);
$meta['iconUrlOverload'] = array (
	'string'
);
$meta['enableA11y'] = array (
	'onoff'
);
$meta['optionStaticMapGenerator'] = array (
		'multichoice','_choices' => array('local','remote')
);
$meta['autoZoomMap'] = array (
		'onoff'
);
$meta ['displayformat'] = array (
		'multichoice',
		'_choices' => array (
				'DD',
				'DMS'
		)
);
