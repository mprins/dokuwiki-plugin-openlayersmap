<?php
/*
 * Copyright (c) 2008-2016 Mark C. Prins <mprins@users.sf.net>
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
if (! defined ( 'DOKU_INC' ))
	define ( 'DOKU_INC', realpath ( dirname ( __FILE__ ) . '/../../' ) . '/' );
if (! defined ( 'DOKU_PLUGIN' ))
	define ( 'DOKU_PLUGIN', DOKU_INC . 'lib/plugins/' );
require_once (DOKU_PLUGIN . 'syntax.php');

/**
 * DokuWiki Plugin openlayersmap (Syntax Component).
 * Provides for display of an OpenLayers based map in a wiki page.
 *
 * @author Mark Prins
 */
class syntax_plugin_openlayersmap_olmap extends DokuWiki_Syntax_Plugin {

	/**
	 * defaults of the known attributes of the olmap tag.
	 */
	private $dflt = array (
			'id' => 'olmap',
			'width' => '550px',
			'height' => '450px',
			'lat' => 50.0,
			'lon' => 5.1,
			'zoom' => 12,
			'autozoom' => 1,
			'statusbar' => true,
			'toolbar' => true,
			'controls' => true,
			'poihoverstyle' => false,
			'baselyr' => 'OpenStreetMap',
			'gpxfile' => '',
			'kmlfile' => '',
			'geojsonfile' => '',
			'summary' => ''
	);

	/**
	 *
	 * @see DokuWiki_Syntax_Plugin::getType()
	 */
	function getType() {
		return 'substition';
	}

	/**
	 *
	 * @see DokuWiki_Syntax_Plugin::getPType()
	 */
	function getPType() {
		return 'block';
	}

	/**
	 *
	 * @see Doku_Parser_Mode::getSort()
	 */
	function getSort() {
		return 901;
	}

	/**
	 *
	 * @see Doku_Parser_Mode::connectTo()
	 */
	function connectTo($mode) {
		$this->Lexer->addSpecialPattern ( '<olmap ?[^>\n]*>.*?</olmap>', $mode, 'plugin_openlayersmap_olmap' );
	}

	/**
	 *
	 * @see DokuWiki_Syntax_Plugin::handle()
	 */
	function handle($match, $state, $pos, Doku_Handler $handler) {
		// break matched cdata into its components
		list ( $str_params, $str_points ) = explode ( '>', substr ( $match, 7, - 9 ), 2 );
		// get the lat/lon for adding them to the metadata (used by geotag)
		preg_match ( '(lat[:|=]\"-?\d*\.?\d*\")', $match, $mainLat );
		preg_match ( '(lon[:|=]\"-?\d*\.?\d*\")', $match, $mainLon );
		$mainLat = substr ( $mainLat [0], 5, - 1 );
		$mainLon = substr ( $mainLon [0], 5, - 1 );
		if (!is_numeric($mainLat)) {
			$mainLat = $this->dflt ['lat'];
		}
		if (!is_numeric($mainLon)) {
			$mainLon = $this->dflt ['lon'];
		}

		$gmap = $this->_extract_params ( $str_params );
		$overlay = $this->_extract_points ( $str_points );
		$_firstimageID = '';

		$_nocache = false;
		// choose maptype based on the specified tag
		$imgUrl = "{{";
		if (stripos ( $gmap ['baselyr'], 'google' ) !== false) {
			// Google
			$imgUrl .= $this->_getGoogle ( $gmap, $overlay );
			$imgUrl .= "&.png";
		} elseif (stripos ( $gmap ['baselyr'], 'bing' ) !== false) {
			// Bing
			if (! $this->getConf ( 'bingAPIKey' )) {
				// in case there is no Bing api key we'll use OSM
				$_firstimageID = $this->_getStaticOSM ( $gmap, $overlay );
				$imgUrl .= $_firstimageID;
				if ($this->getConf ( 'optionStaticMapGenerator' ) == 'remote') {
					$imgUrl .= "&.png";
				}
			} else {
				// seems that Bing doesn't like the DW client, turn off caching
				$_nocache = true;
				$imgUrl .= $this->_getBing ( $gmap, $overlay ) . "&.png";
			}
		} /* elseif (stripos ( $gmap ['baselyr'], 'mapquest' ) !== false) {
			// MapQuest
			if (! $this->getConf ( 'mapquestAPIKey' )) {
				// no API key for MapQuest, use OSM
				$_firstimageID = $this->_getStaticOSM ( $gmap, $overlay );
				$imgUrl .= $_firstimageID;
				if ($this->getConf ( 'optionStaticMapGenerator' ) == 'remote') {
					$imgUrl .= "&.png";
				}
			} else {
				$imgUrl .= $this->_getMapQuest ( $gmap, $overlay );
				$imgUrl .= "&.png";
			}
		} */ else {
			// default OSM
			$_firstimageID = $this->_getStaticOSM ( $gmap, $overlay );
			$imgUrl .= $_firstimageID;
			if ($this->getConf ( 'optionStaticMapGenerator' ) == 'remote') {
				$imgUrl .= "&.png";
			}
		}

		// append dw p_render specific params and render
		$imgUrl .= "?" . str_replace ( "px", "", $gmap ['width'] ) . "x" . str_replace ( "px", "", $gmap ['height'] );
		$imgUrl .= "&nolink";

		// add nocache option for selected services
		if ($_nocache) {
			$imgUrl .= "&nocache";
		}

		$imgUrl .= " |".$gmap ['summary'] . " }}";

		// dbglog($imgUrl,"complete image tags is:");

		$mapid = $gmap ['id'];
		// create a javascript parameter string for the map
		$param = '';
		foreach ( $gmap as $key => $val ) {
			$param .= is_numeric ( $val ) ? "$key: $val, " : "$key: '" . hsc ( $val ) . "', ";
		}
		if (! empty ( $param )) {
			$param = substr ( $param, 0, - 2 );
		}
		unset ( $gmap ['id'] );

		// create a javascript serialisation of the point data
		$poi = '';
		$poitable = '';
		$rowId = 0;
		if (! empty ( $overlay )) {
			foreach ( $overlay as $data ) {
				list ( $lat, $lon, $text, $angle, $opacity, $img ) = $data;
				$rowId ++;
				$poi .= ", {lat: $lat, lon: $lon, txt: '$text', angle: $angle, opacity: $opacity, img: '$img', rowId: $rowId}";

				if ($this->getConf ( 'displayformat' ) === 'DMS') {
					$lat = $this->convertLat ( $lat );
					$lon = $this->convertLon ( $lon );
				} else {
					$lat .= 'º';
					$lon .= 'º';
				}

				$poitable .= '
					<tr>
					<td class="rowId">' . $rowId . '</td>
					<td class="icon"><img src="' . DOKU_BASE . 'lib/plugins/openlayersmap/icons/' . $img . '" alt="'. substr($img, 0, -4) . $this->getlang('alt_legend_poi').' " /></td>
					<td class="lat" title="' . $this->getLang ( 'olmapPOIlatTitle' ) . '">' . $lat . '</td>
					<td class="lon" title="' . $this->getLang ( 'olmapPOIlonTitle' ) . '">' . $lon . '</td>
					<td class="txt">' . $text . '</td>
					</tr>';
			}
			$poi = substr ( $poi, 2 );
		}
		if (! empty ( $gmap ['kmlfile'] )) {
			$poitable .= '
					<tr>
					<td class="rowId"><img src="' . DOKU_BASE . 'lib/plugins/openlayersmap/toolbar/kml_file.png" alt="KML file" /></td>
					<td class="icon"><img src="' . DOKU_BASE . 'lib/plugins/openlayersmap/toolbar/kml_line.png" alt="' . $this->getlang('alt_legend_kml') .'" /></td>
					<td class="txt" colspan="3">KML track: ' . $this->getFileName ( $gmap ['kmlfile'] ) . '</td>
					</tr>';
		}
		if (! empty ( $gmap ['gpxfile'] )) {
			$poitable .= '
					<tr>
					<td class="rowId"><img src="' . DOKU_BASE . 'lib/plugins/openlayersmap/toolbar/gpx_file.png" alt="GPX file" /></td>
					<td class="icon"><img src="' . DOKU_BASE . 'lib/plugins/openlayersmap/toolbar/gpx_line.png" alt="' . $this->getlang('alt_legend_gpx') .'" /></td>
					<td class="txt" colspan="3">GPX track: ' . $this->getFileName ( $gmap ['gpxfile'] ) . '</td>
					</tr>';
		}
		if (! empty ( $gmap ['geojsonfile'] )) {
			$poitable .= '
					<tr>
					<td class="rowId"><img src="' . DOKU_BASE . 'lib/plugins/openlayersmap/toolbar/geojson_file.png" alt="GeoJSON file" /></td>
					<td class="icon"><img src="' . DOKU_BASE . 'lib/plugins/openlayersmap/toolbar/geojson_line.png" alt="' . $this->getlang('alt_legend_geojson') .'" /></td>
					<td class="txt" colspan="3">GeoJSON track: ' . $this->getFileName ( $gmap ['geojsonfile'] ) . '</td>
					</tr>';
		}

		$autozoom = empty ( $gmap ['autozoom'] ) ? $this->getConf ( 'autoZoomMap' ) : $gmap ['autozoom'];
		$js .= "{mapOpts: {" . $param . ", displayformat: '" . $this->getConf ( 'displayformat' ) . "', autozoom: " . $autozoom . "}, poi: [$poi]};";
		// unescape the json
		$poitable = stripslashes ( $poitable );

		return array (
				$mapid,
				$js,
				$mainLat,
				$mainLon,
				$poitable,
				$gmap ['summary'],
				$imgUrl,
				$_firstimageID
		);
	}

	/**
	 *
	 * @see DokuWiki_Syntax_Plugin::render()
	 */
	function render($mode, Doku_Renderer $renderer, $data) {
		// set to true after external scripts tags are written
		static $initialised = false;
		// incremented for each map tag in the page source so we can keep track of each map in this page
		static $mapnumber = 0;

		// dbglog($data, 'olmap::render() data.');
		list ( $mapid, $param, $mainLat, $mainLon, $poitable, $poitabledesc, $staticImgUrl, $_firstimage ) = $data;

		if ($mode == 'xhtml') {
			$olscript = '';
			$olEnable = false;
			$gscript = '';
			$gEnable = $this->getConf ( 'enableGoogle' );
			$stamenEnable = $this->getConf ( 'enableStamen' );
			$osmEnable = $this->getConf ( 'enableOSM' );
			$enableBing = $this->getConf ( 'enableBing' );

			$scriptEnable = '';
			if (! $initialised) {
				$initialised = true;
				// render necessary script tags
				if ($gEnable) {
					$gscript = '<script type="text/javascript" src="//maps.google.com/maps/api/js?v=3.29&amp;key='.$this->getConf ( 'googleAPIkey' ).'"></script>';
				}
				$olscript = '<script type="text/javascript" src="' . DOKU_BASE . 'lib/plugins/openlayersmap/lib/OpenLayers.js"></script>';

				$scriptEnable = '<script type="text/javascript">/*<![CDATA[*/';
				$scriptEnable .= $olscript ? 'olEnable = true;' : 'olEnable = false;';
				$scriptEnable .= 'gEnable = ' . ($gEnable ? 'true' : 'false') . ';';
				$scriptEnable .= 'osmEnable = ' . ($osmEnable ? 'true' : 'false') . ';';
				$scriptEnable .= 'stamenEnable = ' . ($stamenEnable ? 'true' : 'false') . ';';
				$scriptEnable .= 'bEnable = ' . ($enableBing ? 'true' : 'false') . ';';
				$scriptEnable .= 'bApiKey="' . $this->getConf ( 'bingAPIKey' ) . '";';
				$scriptEnable .= 'tfApiKey="' . $this->getConf ( 'tfApiKey' ) . '";';
				$scriptEnable .= 'gApiKey="' . $this->getConf ( 'googleAPIkey' ) . '";';
				$scriptEnable .= '/*!]]>*/</script>';
			}
			$renderer->doc .= "$gscript\n$olscript\n$scriptEnable";
			$renderer->doc .= '<div class="olMapHelp">' . $this->locale_xhtml ( "help" ) . '</div>';
			if ($this->getConf ( 'enableA11y' )) {
				$renderer->doc .= '<div id="' . $mapid . '-static" class="olStaticMap">' . p_render ( $mode, p_get_instructions ( $staticImgUrl ), $info ) . '</div>';
			}
			$renderer->doc .= '<div id="' . $mapid . '-clearer" class="clearer"><p>&nbsp;</p></div>';
			if ($this->getConf ( 'enableA11y' )) {
				// render a table of the POI for the print and a11y presentation, it is hidden using javascript
				$renderer->doc .= '<div class="olPOItableSpan" id="' . $mapid . '-table-span">
					<table class="olPOItable" id="' . $mapid . '-table">
					<caption class="olPOITblCaption">' . $this->getLang ( 'olmapPOItitle' ) . '</caption>
					<thead class="olPOITblHeader">
					<tr>
					<th class="rowId" scope="col">id</th>
					<th class="icon" scope="col">' . $this->getLang ( 'olmapPOIicon' ) . '</th>
					<th class="lat" scope="col" title="' . $this->getLang ( 'olmapPOIlatTitle' ) . '">' . $this->getLang ( 'olmapPOIlat' ) . '</th>
					<th class="lon" scope="col" title="' . $this->getLang ( 'olmapPOIlonTitle' ) . '">' . $this->getLang ( 'olmapPOIlon' ) . '</th>
					<th class="txt" scope="col">' . $this->getLang ( 'olmapPOItxt' ) . '</th>
					</tr>
					</thead>';
				if ($poitabledesc != '') {
					$renderer->doc .= '<tfoot class="olPOITblFooter"><tr><td colspan="5">' . $poitabledesc . '</td></tr></tfoot>';
				}
				$renderer->doc .= '<tbody class="olPOITblBody">' . $poitable . '</tbody>
					</table></div>';
			}
			// render inline mapscript parts
			$renderer->doc .= '<script type="text/javascript">/*<![CDATA[*/';
			$renderer->doc .= " olMapData[$mapnumber] = $param /*!]]>*/</script>";
			$mapnumber ++;
			return true;
		} elseif ($mode == 'metadata') {
			if (! (($this->dflt ['lat'] == $mainLat) && ($this->dflt ['lon'] == $mainLon))) {
				// render geo metadata, unless they are the default
				$renderer->meta ['geo'] ['lat'] = $mainLat;
				$renderer->meta ['geo'] ['lon'] = $mainLon;
				if ($geophp = &plugin_load ( 'helper', 'geophp' )) {
					// if we have the geoPHP helper, add the geohash
					// fails with older php versions.. $renderer->meta['geo']['geohash'] = (new Point($mainLon,$mainLat))->out('geohash');
					$p = new Point ( $mainLon, $mainLat );
					$renderer->meta ['geo'] ['geohash'] = $p->out ( 'geohash' );
				}
			}

			if (($this->getConf ( 'enableA11y' )) && (! empty ( $_firstimage ))) {
				// add map local image into relation/firstimage if not already filled and when it is a local image

				global $ID;
				$rel = p_get_metadata ( $ID, 'relation', METADATA_RENDER_USING_CACHE );
				$img = $rel ['firstimage'];
				if (empty ( $img ) /* || $img == $_firstimage*/){
					//dbglog ( $_firstimage, 'olmap::render#rendering image relation metadata for _firstimage as $img was empty or the same.' );
					// This seems to never work; the firstimage entry in the .meta file is empty
					// $renderer->meta['relation']['firstimage'] = $_firstimage;

					// ... and neither does this; the firstimage entry in the .meta file is empty
					// $relation = array('relation'=>array('firstimage'=>$_firstimage));
					// p_set_metadata($ID, $relation, false, false);

					// ... this works
					$renderer->internalmedia ( $_firstimage, $poitabledesc );
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * extract parameters for the map from the parameter string
	 *
	 * @param string $str_params
	 *        	string of key="value" pairs
	 * @return array associative array of parameters key=>value
	 */
	private function _extract_params($str_params) {
		$param = array ();
		preg_match_all ( '/(\w*)="(.*?)"/us', $str_params, $param, PREG_SET_ORDER );
		// parse match for instructions, break into key value pairs
		$gmap = $this->dflt;
		foreach ( $gmap as $key => &$value ) {
			$defval = $this->getConf ( 'default_' . $key );
			if ($defval !== '') {
				$value = $defval;
			}
		}
		unset ( $value );
		foreach ( $param as $kvpair ) {
			list ( $match, $key, $val ) = $kvpair;
			$key = strtolower ( $key );
			if (isset ( $gmap [$key] )) {
				if ($key == 'summary') {
					// preserve case for summary field
					$gmap [$key] = $val;
				} elseif ($key == 'id') {
					// preserve case for id field
					$gmap [$key] = $val;
				} else {
					$gmap [$key] = strtolower ( $val );
				}
			}
		}
		return $gmap;
	}

	/**
	 * extract overlay points for the map from the wiki syntax data
	 *
	 * @param string $str_points
	 *        	multi-line string of lat,lon,text triplets
	 * @return array multi-dimensional array of lat,lon,text triplets
	 */
	private function _extract_points($str_points) {
		$point = array ();
		// preg_match_all('/^([+-]?[0-9].*?),\s*([+-]?[0-9].*?),(.*?),(.*?),(.*?),(.*)$/um', $str_points, $point, PREG_SET_ORDER);
		/*
		 * group 1: ([+-]?[0-9]+(?:\.[0-9]*)?) group 2: ([+-]?[0-9]+(?:\.[0-9]*)?) group 3: (.*?) group 4: (.*?) group 5: (.*?) group 6: (.*)
		 */
		preg_match_all ( '/^([+-]?[0-9]+(?:\.[0-9]*)?),\s*([+-]?[0-9]+(?:\.[0-9]*)?),(.*?),(.*?),(.*?),(.*)$/um', $str_points, $point, PREG_SET_ORDER );
		// create poi array
		$overlay = array ();
		foreach ( $point as $pt ) {
			list ( $match, $lat, $lon, $angle, $opacity, $img, $text ) = $pt;
			$lat = is_numeric ( $lat ) ? $lat : 0;
			$lon = is_numeric ( $lon ) ? $lon : 0;
			$angle = is_numeric ( $angle ) ? $angle : 0;
			$opacity = is_numeric ( $opacity ) ? $opacity : 0.8;
			// TODO validate using exist & set up default img?
			$img = trim ( $img );
			$text = p_get_instructions ( $text );
			// dbg ( $text );
			$text = p_render ( "xhtml", $text, $info );
			// dbg ( $text );
			$text = addslashes ( str_replace ( "\n", "", $text ) );
			$overlay [] = array (
					$lat,
					$lon,
					$text,
					$angle,
					$opacity,
					$img
			);
		}
		return $overlay;
	}

	/**
	 * Create a MapQuest static map API image url.
	 *
	 * @param array $gmap
	 * @param array $overlay
	 */
	 /*
	private function _getMapQuest($gmap, $overlay) {
		$sUrl = $this->getConf ( 'iconUrlOverload' );
		if (! $sUrl) {
			$sUrl = DOKU_URL;
		}
		switch ($gmap ['baselyr']) {
			case 'mapquest hybrid' :
				$maptype = 'hyb';
				break;
			case 'mapquest sat' :
				// because sat coverage is very limited use 'hyb' instead of 'sat' so we don't get a blank map
				$maptype = 'hyb';
				break;
			case 'mapquest road' :
			default :
				$maptype = 'map';
				break;
		}
		$imgUrl = "http://open.mapquestapi.com/staticmap/v4/getmap?declutter=true&";
		if (count ( $overlay ) < 1) {
			$imgUrl .= "?center=" . $gmap ['lat'] . "," . $gmap ['lon'];
			// max level for mapquest is 16
			if ($gmap ['zoom'] > 16) {
				$imgUrl .= "&zoom=16";
			} else {
				$imgUrl .= "&zoom=" . $gmap ['zoom'];
			}
		}
		// use bestfit instead of center/zoom, needs upperleft/lowerright corners
		// $bbox=$this->_calcBBOX($overlay, $gmap['lat'], $gmap['lon']);
		// $imgUrl .= "bestfit=".$bbox['minlat'].",".$bbox['maxlon'].",".$bbox['maxlat'].",".$bbox['minlon'];

		// TODO declutter option works well for square maps but not for rectangular, maybe compensate for that or compensate the mbr..
		$imgUrl .= "&size=" . str_replace ( "px", "", $gmap ['width'] ) . "," . str_replace ( "px", "", $gmap ['height'] );

		// TODO mapquest allows using one image url with a multiplier $NUMBER eg:
		// $NUMBER = 2
		// $imgUrl .= DOKU_URL."/".DOKU_PLUGIN."/".getPluginName()."/icons/".$img.",$NUMBER,C,".$lat1.",".$lon1.",0,0,0,0,C,".$lat2.",".$lon2.",0,0,0,0";
		if (! empty ( $overlay )) {
			$imgUrl .= "&xis=";
			foreach ( $overlay as $data ) {
				list ( $lat, $lon, $text, $angle, $opacity, $img ) = $data;
				// $imgUrl .= $sUrl."lib/plugins/openlayersmap/icons/".$img.",1,C,".$lat.",".$lon.",0,0,0,0,";
				$imgUrl .= $sUrl . "lib/plugins/openlayersmap/icons/" . $img . ",1,C," . $lat . "," . $lon . ",";
			}
			$imgUrl = substr ( $imgUrl, 0, - 1 );
		}
		$imgUrl .= "&imageType=png&type=" . $maptype;
		$imgUrl .= "&key=".$this->getConf ( 'mapquestAPIKey' );
		// dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getMapQuest: MapQuest image url is:');
		return $imgUrl;
	}
	*/

	/**
	 * Create a Google maps static image url w/ the poi.
	 *
	 * @param array $gmap
	 * @param array $overlay
	 */
	private function _getGoogle($gmap, $overlay) {
		$sUrl = $this->getConf ( 'iconUrlOverload' );
		if (! $sUrl) {
			$sUrl = DOKU_URL;
		}
		switch ($gmap ['baselyr']) {
			case 'google hybrid' :
				$maptype = 'hybrid';
				break;
			case 'google sat' :
				$maptype = 'satellite';
				break;
			case 'terrain' :
			case 'google relief' :
				$maptype = 'terrain';
				break;
			case 'google road' :
			default :
				$maptype = 'roadmap';
				break;
		}
		// TODO maybe use viewport / visible instead of center/zoom,
		// see: https://developers.google.com/maps/documentation/staticmaps/index#Viewports
		// http://maps.google.com/maps/api/staticmap?center=51.565690,5.456756&zoom=16&size=600x400&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/marker.png|label:1|51.565690,5.456756&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/marker-blue.png|51.566197,5.458966|label:2&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.567177,5.457909|label:3&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.566283,5.457330|label:4&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.565630,5.457695|label:5&sensor=false&format=png&maptype=roadmap
		$imgUrl = "http://maps.googleapis.com/maps/api/staticmap?";
		$imgUrl .= "&size=" . str_replace ( "px", "", $gmap ['width'] ) . "x" . str_replace ( "px", "", $gmap ['height'] );
		//if (!$this->getConf( 'autoZoomMap')) { // no need for center & zoom params }
		$imgUrl .= "&center=" . $gmap ['lat'] . "," . $gmap ['lon'];
		// max is 21 (== building scale), but that's overkill..
		if ($gmap ['zoom'] > 17) {
			$imgUrl .= "&zoom=17";
		} else {
			$imgUrl .= "&zoom=" . $gmap ['zoom'];
		}
		if (! empty ( $overlay )) {
			$rowId = 0;
			foreach ( $overlay as $data ) {
				list ( $lat, $lon, $text, $angle, $opacity, $img ) = $data;
				$imgUrl .= "&markers=icon%3a" . $sUrl . "lib/plugins/openlayersmap/icons/" . $img . "%7c" . $lat . "," . $lon . "%7clabel%3a" . ++ $rowId;
			}
		}
		$imgUrl .= "&format=png&maptype=" . $maptype;
		global $conf;
		$imgUrl .= "&language=" . $conf ['lang'];
		if ($this->getConf( 'googleAPIkey' )) {
			$imgUrl .= "&key=" . $this->getConf( 'googleAPIkey' );
		}
		// dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getGoogle: Google image url is:');
		return $imgUrl;
	}

	/**
	 * Create a Bing maps static image url w/ the poi.
	 *
	 * @param array $gmap
	 * @param array $overlay
	 */
	private function _getBing($gmap, $overlay) {
		switch ($gmap ['baselyr']) {
			case 've hybrid' :
			case 'bing hybrid' :
				$maptype = 'AerialWithLabels';
				break;
			case 've sat' :
			case 'bing sat' :
				$maptype = 'Aerial';
				break;
			case 've normal' :
			case 've road' :
			case 've' :
			case 'bing road' :
			default :
				$maptype = 'Road';
				break;
		}
		$imgUrl = "http://dev.virtualearth.net/REST/v1/Imagery/Map/" . $maptype;// . "/";
		if ($this->getConf ( 'autoZoomMap' )) {
			$bbox = $this->_calcBBOX ( $overlay, $gmap ['lat'], $gmap ['lon'] );
			//$imgUrl .= "?ma=" . $bbox ['minlat'] . "," . $bbox ['minlon'] . "," . $bbox ['maxlat'] . "," . $bbox ['maxlon'];
			$imgUrl .= "?ma=" . $bbox ['minlat'] . "%2C" . $bbox ['minlon'] . "%2C" . $bbox ['maxlat'] . "%2C" . $bbox ['maxlon'];
			$imgUrl .= "&dcl=1";
		}
		if (strpos ( $imgUrl, "?" ) === false)
			$imgUrl .= "?";

		//$imgUrl .= "&ms=" . str_replace ( "px", "", $gmap ['width'] ) . "," . str_replace ( "px", "", $gmap ['height'] );
		$imgUrl .= "&ms=" . str_replace ( "px", "", $gmap ['width'] ) . "%2C" . str_replace ( "px", "", $gmap ['height'] );
		$imgUrl .= "&key=" . $this->getConf ( 'bingAPIKey' );
		if (! empty ( $overlay )) {
			$rowId = 0;
			foreach ( $overlay as $data ) {
				list ( $lat, $lon, $text, $angle, $opacity, $img ) = $data;
				// TODO icon style lookup, see: http://msdn.microsoft.com/en-us/library/ff701719.aspx for iconStyle
				$iconStyle = 32;
				$rowId ++;
				// NOTE: the max number of pushpins is 18! or we have to use POST (http://msdn.microsoft.com/en-us/library/ff701724.aspx)
				if ($rowId == 18) {
					break;
				}
				//$imgUrl .= "&pp=$lat,$lon;$iconStyle;$rowId";
				$imgUrl .= "&pp=$lat%2C$lon%3B$iconStyle%3B$rowId";

			}
		}
		global $conf;
		$imgUrl .= "&fmt=png";
		$imgUrl .= "&c=" . $conf ['lang'];
		// dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getBing: bing image url is:');
		return $imgUrl;
	}

	/**
	 * Create a static OSM map image url w/ the poi from http://staticmap.openstreetmap.de (staticMapLite)
	 * use http://staticmap.openstreetmap.de "staticMapLite" or a local version
	 *
	 * @param array $gmap
	 * @param array $overlay
	 *
	 * @todo implementation for http://ojw.dev.openstreetmap.org/StaticMapDev/
	 */
	private function _getStaticOSM($gmap, $overlay) {
		global $conf;

		if ($this->getConf ( 'optionStaticMapGenerator' ) == 'local') {
			// using local basemap composer
			if (! $myMap = &plugin_load ( 'helper', 'openlayersmap_staticmap' )) {
				dbglog ( $myMap, 'syntax_plugin_openlayersmap_olmap::_getStaticOSM: openlayersmap_staticmap plugin is not available.' );
			}
			if (! $geophp = &plugin_load ( 'helper', 'geophp' )) {
				dbglog ( $geophp, 'syntax_plugin_openlayersmap_olmap::_getStaticOSM: geophp plugin is not available.' );
			}
			$size = str_replace ( "px", "", $gmap ['width'] ) . "x" . str_replace ( "px", "", $gmap ['height'] );

			$markers = array();
			if (! empty ( $overlay )) {
				foreach ( $overlay as $data ) {
					list ( $lat, $lon, $text, $angle, $opacity, $img ) = $data;
					$iconStyle = substr ( $img, 0, strlen ( $img ) - 4 );
					$markers [] = array (
							'lat' => $lat,
							'lon' => $lon,
							'type' => $iconStyle
					);
				}
			}

			$apikey = '';
			switch ($gmap ['baselyr']) {
				case 'mapnik' :
				case 'openstreetmap' :
					$maptype = 'openstreetmap';
					break;
				case 'transport' :
					$maptype = 'transport';
					$apikey = $this->getConf ( 'tfApiKey' );
					break;
				case 'landscape' :
					$maptype = 'landscape';
					$apikey = $this->getConf ( 'tfApiKey' );
					break;
				case 'outdoors' :
					$maptype = 'outdoors';
					$apikey = $this->getConf ( 'tfApiKey' );
					break;
				case 'cycle map' :
					$maptype = 'cycle';
					$apikey = $this->getConf ( 'tfApiKey' );
					break;
				case 'hike and bike map' :
					$maptype = 'hikeandbike';
					break;
				case 'mapquest hybrid' :
				case 'mapquest road' :
				case 'mapquest sat' :
					$maptype = 'mapquest';
					break;
				default :
					$maptype = '';
					break;
			}

			$result = $myMap->getMap ( $gmap ['lat'], $gmap ['lon'], $gmap ['zoom'], $size, $maptype, $markers, $gmap ['gpxfile'], $gmap ['kmlfile'], $gmap ['geojsonfile'] );
		} else {
			// using external basemap composer

			// http://staticmap.openstreetmap.de/staticmap.php?center=47.000622235634,10.117187497601&zoom=5&size=500x350
			// &markers=48.999812532766,8.3593749976708,lightblue1|43.154850037315,17.499999997306,lightblue1|49.487527053077,10.820312497573,ltblu-pushpin|47.951071133739,15.917968747369,ol-marker|47.921629720114,18.027343747285,ol-marker-gold|47.951071133739,19.257812497236,ol-marker-blue|47.180141361692,19.257812497236,ol-marker-green
			$imgUrl = "http://staticmap.openstreetmap.de/staticmap.php";
			$imgUrl .= "?center=" . $gmap ['lat'] . "," . $gmap ['lon'];
			$imgUrl .= "&size=" . str_replace ( "px", "", $gmap ['width'] ) . "x" . str_replace ( "px", "", $gmap ['height'] );

			if ($gmap ['zoom'] > 16) {
				// actually this could even be 18, but that seems overkill
				$imgUrl .= "&zoom=16";
			} else {
				$imgUrl .= "&zoom=" . $gmap ['zoom'];
			}

			if (! empty ( $overlay )) {
				$rowId = 0;
				$imgUrl .= "&markers=";
				foreach ( $overlay as $data ) {
					list ( $lat, $lon, $text, $angle, $opacity, $img ) = $data;
					$rowId ++;
					$iconStyle = "lightblue$rowId";
					$imgUrl .= "$lat,$lon,$iconStyle%7c";
				}
				$imgUrl = substr ( $imgUrl, 0, - 3 );
			}

			$result = $imgUrl;
		}
		// dbglog ( $result, 'syntax_plugin_openlayersmap_olmap::_getStaticOSM: osm image url is:' );
		return $result;
	}

	/**
	 * Calculate the minimum bbox for a start location + poi.
	 *
	 * @param array $overlay
	 *        	multi-dimensional array of array($lat, $lon, $text, $angle, $opacity, $img)
	 * @param float $lat
	 *        	latitude for map center
	 * @param float $lon
	 *        	longitude for map center
	 * @return multitype:float array describing the mbr and center point
	 */
	private function _calcBBOX($overlay, $lat, $lon) {
		$lats = array($lat);
		$lons = array($lon);
		foreach ( $overlay as $data ) {
			list ( $lat, $lon, $text, $angle, $opacity, $img ) = $data;
			$lats [] = $lat;
			$lons [] = $lon;
		}
		sort ( $lats );
		sort ( $lons );
		// TODO: make edge/wrap around cases work
		$centerlat = $lats [0] + ($lats [count ( $lats ) - 1] - $lats [0]);
		$centerlon = $lons [0] + ($lons [count ( $lats ) - 1] - $lons [0]);
		return array (
				'minlat' => $lats [0],
				'minlon' => $lons [0],
				'maxlat' => $lats [count ( $lats ) - 1],
				'maxlon' => $lons [count ( $lats ) - 1],
				'centerlat' => $centerlat,
				'centerlon' => $centerlon
		);
	}

	/**
	 * Figures out the base filename of a media path.
	 *
	 * @param String $mediaLink
	 */
	private function getFileName($mediaLink) {
		$mediaLink = str_replace ( '[[', '', $mediaLink );
		$mediaLink = str_replace ( ']]', '', $mediaLink );
		$mediaLink = substr ( $mediaLink, 0, - 4 );
		$parts = explode ( ':', $mediaLink );
		$mediaLink = end ( $parts );
		return str_replace ( '_', ' ', $mediaLink );
	}

	/**
	 * Convert decimal degrees to degrees, minutes, seconds format
	 *
	 * @todo move this into a shared library
	 * @param float $decimaldegrees
	 * @return string dms
	 */
	private function _convertDDtoDMS($decimaldegrees) {
		$dms = floor ( $decimaldegrees );
		$secs = ($decimaldegrees - $dms) * 3600;
		$min = floor ( $secs / 60 );
		$sec = round ( $secs - ($min * 60), 3 );
		$dms .= 'º' . $min . '\'' . $sec . '"';
		return $dms;
	}

	/**
	 * convert latitude in decimal degrees to DMS+hemisphere.
	 *
	 * @todo move this into a shared library
	 * @param float $decimaldegrees
	 * @return string
	 */
	private function convertLat($decimaldegrees) {
		if (strpos ( $decimaldegrees, '-' ) !== false) {
			$latPos = "S";
		} else {
			$latPos = "N";
		}
		$dms = $this->_convertDDtoDMS ( abs ( floatval ( $decimaldegrees ) ) );
		return hsc ( $dms . $latPos );
	}

	/**
	 * convert longitude in decimal degrees to DMS+hemisphere.
	 *
	 * @todo move this into a shared library
	 * @param float $decimaldegrees
	 * @return string
	 */
	private function convertLon($decimaldegrees) {
		if (strpos ( $decimaldegrees, '-' ) !== false) {
			$lonPos = "W";
		} else {
			$lonPos = "E";
		}
		$dms = $this->_convertDDtoDMS ( abs ( floatval ( $decimaldegrees ) ) );
		return hsc ( $dms . $lonPos );
	}
}
