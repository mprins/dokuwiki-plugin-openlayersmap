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

if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'openlayersmap/StaticMap.php';
/**
 * DokuWiki Plugin openlayersmap (staticmap Helper Component)
 *
 * @author Mark Prins
 */
class helper_plugin_openlayersmap_staticmap extends DokuWiki_Plugin {
	private $maxWidth = 1024;
	private $maxHeight = 1024;
	/**
	 * @override
	 */
	function getMethods(){
		$result = array();
		$result[] = array(
				'name'   => 'getMap',
				'desc'   => 'returns url to the image',
				'params' => array(
						'center' => 'string',
						'zoom' => 'integer',
						'size' => 'string',
						'maptype' => 'string',
						'markers' => 'string',
						'gpx' => 'string',
						'kml' => 'string'),
				'return' => array('image' => 'string'),
		);
		return $result;
	}

	/**
	 * Create the map.
	 *
	 * @param number lat the latitude of the map's center, eg. 40.714728
	 * @param number lon the longitude of the map's center, eg -73.998672
	 * @param number zoom the zoom level in the tile cache, eg. 14
	 * @param mixed size the size in WxH px, eg. 512x512
	 * @param string maptype the maptype, eg. cycle
	 * @param mixed markers associative array of markers, array('lat'=>$lat,'lon'=>$lon,'type'=>$iconStyle), eg. array(	'lat'=>40.702147,	'lon'=>-74.015794,	'type'=>lightblue1);
	 * @param string gpx media link
	 * @param string kml media link
	 *
	 * @return the media id url
	 */
	public function getMap($lat, $lon, $zoom, $size, $maptype, $markers, $gpx, $kml){
		global $conf;
		//dbglog($markers,'helper_plugin_openlayersmap_staticmap::getMap: markers :');

		// normalize zoom
		$zoom = $zoom?intval($zoom):0;
		if($zoom > 18) $zoom = 18;
		// normalize WxH
		list($width, $height) = split('x',$size);
		$width = intval($width);
		if($width > $this->maxWidth) $width = $this->maxWidth;
		$height = intval($height);
		if($height > $this->maxHeight) $height = $this->maxHeight;
		// validate gpx/kml
		$kml=str_replace(":","/",$kml);
		$kml=str_replace("[","/",$kml);
		$kml=str_replace("]","/",$kml);
		$gpx=str_replace(":","/",$gpx);
		$gpx=str_replace("[","/",$gpx);
		$gpx=str_replace("]","/",$gpx);
		//TODO make sure we don't end up with a double file sep. char here
		$gpx = $conf['mediadir'].'/'.$gpx;
		$kml = $conf['mediadir'].'/'.$kml;
		
		// create map
		$map = new StaticMap($lat, $lon, $zoom, $width, $height, $maptype, $markers, $gpx, $kml,
				$conf['mediadir'],
				$conf['cachedir']
		);
		// return the media id url
		$mediaId = str_replace('/', ':',  $map->getMap());
		return $mediaId;
	}
}