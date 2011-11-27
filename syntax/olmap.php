<?php
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
 * Plugin OL Maps: Allow Display of a OpenLayers Map in a wiki page.
 *
 * @author Mark Prins
 */

if (!defined('DOKU_INC'))
define('DOKU_INC', realpath(dirname(__FILE__) . '/../../') . '/');
if (!defined('DOKU_PLUGIN'))
define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_openlayersmap_olmap extends DokuWiki_Syntax_Plugin {
	/** defaults of the known attributes of the olmap tag. */
	private $dflt = array (
		'id'		=> 'olmap',
		'width'		=> '550px',
		'height'	=> '450px',
		'lat'		=> 50.0,
		'lon'		=> 5.1,
		'zoom'		=> 12,
		'toolbar'	=> true,
		'statusbar'	=> true,
		'controls'	=> true,
		'poihoverstyle'	=> false,
		'baselyr'	=>'OpenStreetMap',
	 	'gpxfile'	=> '',
 		'kmlfile'	=> '',
		'summary'	=>''
	);

	/**
	 * Return the type of syntax this plugin defines.
	 * @Override
	 */
	function getType() {
		return 'substition';
	}

	/**
	 * Defines how this syntax is handled regarding paragraphs.
	 * @Override
	 */
	function getPType() {
		//normal block stack
		return 'block';
	}

	/**
	 * Returns a number used to determine in which order modes are added.
	 * @Override
	 */
	function getSort() {
		return 901;
	}

	/**
	 * This function is inherited from Doku_Parser_Mode.
	 * Here is the place to register the regular expressions needed
	 * to match your syntax.
	 * @Override
	 */
	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('<olmap ?[^>\n]*>.*?</olmap>', $mode, 'plugin_openlayersmap_olmap');
	}

	/**
	 * handle each olmap tag. prepare the matched syntax for use in the renderer.
	 * @Override
	 */
	function handle($match, $state, $pos, &$handler) {
		// break matched cdata into its components
		list ($str_params, $str_points) = explode('>', substr($match, 7, -9), 2);
		// get the lat/lon for adding them to the metadata (used by geotag)
		preg_match('(lat[:|=]\"-?\d*\.\d*\")',$match,$mainLat);
		preg_match('(lon[:|=]\"-?\d*\.\d*\")',$match,$mainLon);
		$mainLat=substr($mainLat[0],5,-1);
		$mainLon=substr($mainLon[0],5,-1);

		$gmap = $this->_extract_params($str_params);
		$overlay = $this->_extract_points($str_points);

		$imgUrl = "{{";
		// choose maptype based on tag
		if (stripos($gmap['baselyr'],'google') !== false){
			// use google
			$imgUrl .= $this->_getGoogle($gmap, $overlay);
		} elseif (stripos($gmap['baselyr'],'ve') !== false){
			// use bing
			$imgUrl .= $this->_getBing($gmap, $overlay);
		} elseif (stripos($gmap['baselyr'],'bing') !== false){
			// use bing
			$imgUrl .= $this->_getBing($gmap, $overlay);
		} elseif (stripos($gmap['baselyr'],'mapquest') !== false){
			// use mapquest
			$imgUrl .=$this->_getMapQuest($gmap,$overlay);
		} else {
			// use http://staticmap.openstreetmap.de "staticMapLite"
			$imgUrl .=$this->_getStaticOSM($gmap,$overlay);
		}
		// TODO implementation for http://ojw.dev.openstreetmap.org/StaticMapDev/

		// append dw specific params
		$imgUrl .="&.png?".$gmap['width']."x".$gmap['height'];
		$imgUrl .= "&nolink";
		$imgUrl .= " |".$gmap['summary']."}} ";
		// remove 'px'
		$imgUrl = str_replace("px", "",$imgUrl);

		$imgUrl=p_render("xhtml", p_get_instructions($imgUrl), $info);

		$mapid = $gmap['id'];

		// create a javascript parameter string for the map
		$param = '';
		foreach ($gmap as $key => $val) {
			$param .= is_numeric($val) ? "$key: $val, " : "$key: '" . hsc($val) . "', ";
		}
		if (!empty ($param)) {
			$param = substr($param, 0, -2);
		}
		unset ($gmap['id']);

		// create a javascript serialisation of the point data
		$poi = '';
		$poitable='';
		$rowId=0;
		if (!empty ($overlay)) {
			foreach ($overlay as $data) {
				list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
				$rowId++;
				$poi .= ", {lat: $lat, lon: $lon, txt: '$text', angle: $angle, opacity: $opacity, img: '$img', rowId: $rowId}";
				$poitable .='
			<tr>
				<td class="rowId">'.$rowId.'</td>
				<td class="icon"><img src="'.DOKU_BASE.'/lib/plugins/openlayersmap/icons/'.$img.'" alt="icon" /></td>
				<td class="lat" title="'.$this->getLang('olmapPOIlatTitle').'">'.$lat.'</td>
				<td class="lon" title="'.$this->getLang('olmapPOIlonTitle').'">'.$lon.'</td>
				<td class="txt">'.$text.'</td>
			</tr>';
			}
			$poi = substr($poi, 2);
		}
		//$js .= "createMap({" . $param . " },[$poi]);";
		//$js .= "[{" . $param . " },[$poi]];";
		$js .= "{mapOpts:{" . $param . " },poi:[$poi]};";
		// unescape the json
		$poitable = stripslashes($poitable);

		return array($mapid,$js,$mainLat,$mainLon,$poitable,$gmap['summary'],$imgUrl);
	}

	/**
	 * render html tag/output. render the content.
	 * @Override
	 */
	function render($mode, &$renderer, $data) {
		static $initialised = false; // set to true after script initialisation
		static $mapnumber = 0; // incremeted for each map tag in the page source
		list ($mapid, $param, $mainLat, $mainLon, $poitable, $poitabledesc, $staticImgUrl) = $data;

		if ($mode == 'xhtml') {
			$olscript = '';
			$olEnable = false;
			$gscript = '';
			$gEnable = $this->getConf('enableGoogle');
			$vscript = '';
			$vEnable = false;
			//$yscript = '';
			//$yEnable = false;
			$mqEnable = $this->getConf('enableMapQuest');
			$osmEnable = $this->getConf('enableOSM');
			$enableBing = $this->getConf('enableBing');

			$scriptEnable = '';

			if (!$initialised) {
				$initialised = true;
				// render necessary script tags
				if($gEnable){
					$gscript ='<script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3&amp;sensor=false"></script>';
				}

				$vscript = $this->getConf('veScriptUrl');
				$vscript = $vscript ? '<script type="text/javascript" src="' . $vscript . '"></script>' : "";

				//$yscript = $this->getConf('yahooScriptUrl');
				//$yscript = $yscript ? '<script type="text/javascript" src="' . $yscript . '"></script>' : "";

				$olscript = $this->getConf('olScriptUrl');
				$olscript = $olscript ? '<script type="text/javascript" src="' . $olscript . '"></script>' : "";
				$olscript = str_replace('DOKU_BASE/', DOKU_BASE, $olscript);

				$scriptEnable = '<script type="text/javascript">' . "\n" . '<!--//--><![CDATA[//><!--' . "\n";
				$scriptEnable .= $olscript ? 'olEnable = true;' : 'olEnable = false;';
				//$scriptEnable .= $yscript ? ' yEnable = true;' : ' yEnable = false;';
				$scriptEnable .= $vscript ? ' veEnable = true;' : ' veEnable = false;';
				$scriptEnable .= 'gEnable = '.($gEnable ? 'true' : 'false').';';
				$scriptEnable .= 'osmEnable = '.($osmEnable ? 'true' : 'false').';';
				$scriptEnable .= 'mqEnable = '.($mqEnable ? 'true' : 'false').';';
				$scriptEnable .= 'bEnable = '.($enableBing ? 'true' : 'false').';';
				$scriptEnable .= 'bApiKey="'.$this->getConf('bingAPIKey').'";';
				$scriptEnable .= 'OpenLayers.ImgPath = "'.DOKU_BASE.'lib/plugins/openlayersmap/lib/'.$this->getConf('olMapStyle').'/";';
				$scriptEnable .= "\n" . '//--><!]]>' . "\n" . '</script>';
			}
			$renderer->doc .= "
			$gscript
			$vscript
			$olscript
			$scriptEnable";
			if ($this->getConf('enableA11y')){
				$renderer->doc .= '				<div id="'.$mapid.'-static" class="olStaticMap">'.$staticImgUrl.'</div>';
			}
			$renderer->doc .= '				<div id="'.$mapid.'-clearer" class="clearer"><p>&nbsp;</p></div>';
			if ($this->getConf('enableA11y')){
				// render a (hidden) table of the POI for the print and a11y presentation
				$renderer->doc .= ' 	<div class="olPOItableSpan" id="'.$mapid.'-table-span"><table class="olPOItable" id="'.$mapid.'-table" summary="'.$poitabledesc.'" title="'.$this->getLang('olmapPOItitle').'">
		<caption class="olPOITblCaption">'.$this->getLang('olmapPOItitle').'</caption>
		<thead class="olPOITblHeader">
			<tr>
				<th class="rowId" scope="col">id</th>
				<th class="icon" scope="col">'.$this->getLang('olmapPOIicon').'</th>
				<th class="lat" scope="col" title="'.$this->getLang('olmapPOIlatTitle').'">'.$this->getLang('olmapPOIlat').'</th>
				<th class="lon" scope="col" title="'.$this->getLang('olmapPOIlonTitle').'">'.$this->getLang('olmapPOIlon').'</th>
				<th class="txt" scope="col">'.$this->getLang('olmapPOItxt').'</th>
			</tr>
		</thead>
		<tfoot class="olPOITblFooter"><tr><td colspan="5">'.$poitabledesc.'</td></tr></tfoot>
		<tbody class="olPOITblBody">'.$poitable.'</tbody>
	</table></div>';

				//TODO no tfoot when $poitabledesc is empty
			}
			// render inline mapscript
			$renderer->doc .="\n		<script type='text/javascript'><!--//--><![CDATA[//><!--\n";
			// var $mapid = $param
			$renderer->doc .="		olMapData[$mapnumber] = $param
				//--><!]]></script>";
			$mapnumber++;
			return true;
		} elseif ($mode == 'metadata') {
			// render metadata if available
			if (!(($this->dflt['lat']==$mainLat)||($thisdflt['lon']==$mainLon))){
				// unless they are the default
				$renderer->meta['geo']['lat'] = $mainLat;
				$renderer->meta['geo']['lon'] = $mainLon;
			}
			return true;
		}
		return false;
	}

	/**
	 * extract parameters for the map from the parameter string
	 *
	 * @param   string    $str_params   string of key="value" pairs
	 * @return  array                   associative array of parameters key=>value
	 */
	private function _extract_params($str_params) {
		$param = array ();
		preg_match_all('/(\w*)="(.*?)"/us', $str_params, $param, PREG_SET_ORDER);
		// parse match for instructions, break into key value pairs
		$gmap = $this->dflt;
		foreach ($param as $kvpair) {
			list ($match, $key, $val) = $kvpair;
			$key = strtolower($key);
			if (isset ($gmap[$key])){
				if ($key == 'summary'){
					// preserve case for summary field
					$gmap[$key] = $val;
				}else {
					$gmap[$key] = strtolower($val);
				}
			}
		}
		return $gmap;
	}

	/**
	 * extract overlay points for the map from the wiki syntax data
	 *
	 * @param   string    $str_points   multi-line string of lat,lon,text triplets
	 * @return  array                   multi-dimensional array of lat,lon,text triplets
	 */
	private function _extract_points($str_points) {
		$point = array ();
		//preg_match_all('/^([+-]?[0-9].*?),\s*([+-]?[0-9].*?),(.*?),(.*?),(.*?),(.*)$/um', $str_points, $point, PREG_SET_ORDER);
		/*
		group 1: ([+-]?[0-9]+(?:\.[0-9]*)?)
		group 2: ([+-]?[0-9]+(?:\.[0-9]*)?)
		group 3: (.*?)
		group 4: (.*?)
		group 5: (.*?)
		group 6: (.*)
		*/
		preg_match_all('/^([+-]?[0-9]+(?:\.[0-9]*)?),\s*([+-]?[0-9]+(?:\.[0-9]*)?),(.*?),(.*?),(.*?),(.*)$/um', $str_points, $point, PREG_SET_ORDER);
		// create poi array
		$overlay = array ();
		foreach ($point as $pt) {
			list ($match, $lat, $lon, $angle, $opacity, $img, $text) = $pt;
			$lat = is_numeric($lat) ? $lat : 0;
			$lon = is_numeric($lon) ? $lon : 0;
			$angle = is_numeric($angle) ? $angle : 0;
			$opacity = is_numeric($opacity) ? $opacity : 0.8;
			$img = trim($img);
			// TODO validate using exist & set up default img?
			$text = addslashes(str_replace("\n", "", p_render("xhtml", p_get_instructions($text), $info)));
			$overlay[] = array($lat, $lon, $text, $angle, $opacity, $img);
		}
		return $overlay;
	}

	/**
	 * Create a MapQuest static map API image url.
	 * @param array $gmap
	 * @param array $overlay
	 */
	private function _getMapQuest($gmap,$overlay) {
		$sUrl=$this->getConf('iconUrlOverload');
		if (!$sUrl){
			$sUrl=DOKU_URL;
		}
		switch ($gmap['baselyr']){
			case 'mapquest hybrid':
				$maptype='hyb';
				break;
			case 'mapquest sat':
				// $maptype='sat'
				// because sat coverage is very limited use 'hyb' instead of 'sat' so we don't get a blank map
				$maptype='hyb';
				break;
			case 'mapquest road':
			default:
				$maptype='map';
				break;
		}
		$imgUrl = "http://open.mapquestapi.com/staticmap/v3/getmap?declutter=true&";
		if (count($overlay)< 1){
			$imgUrl .= "?center=".$gmap['lat'].",".$gmap['lon'];
			//max level for mapquest is 16
			if ($gmap['zoom']>16) {
				$imgUrl .= "&zoom=16";
			} else			{
				$imgUrl .= "&zoom=".$gmap['zoom'];
			}
		}
		// use bestfit instead of center/zoom, needs upperleft/lowerright corners
		//$bbox=$this->_calcBBOX($overlay, $gmap['lat'], $gmap['lon']);
		//$imgUrl .= "bestfit=".$bbox['minlat'].",".$bbox['maxlon'].",".$bbox['maxlat'].",".$bbox['minlon'];

		// TODO declutter option works well for square maps but not for rectangular, maybe compensate for that or compensate the mbr..
		$imgUrl .= "&size=".str_replace("px", "",$gmap['width']).",".str_replace("px", "",$gmap['height']);

		// TODO mapquest allows using one image url with a multiplier $NUMBER eg:
		// $NUMBER = 2
		// $imgUrl .= DOKU_URL."/".DOKU_PLUGIN."/".getPluginName()."/icons/".$img.",$NUMBER,C,".$lat1.",".$lon1.",0,0,0,0,C,".$lat2.",".$lon2.",0,0,0,0";
		if (!empty ($overlay)) {
			$imgUrl .= "&xis=";
			foreach ($overlay as $data) {
				list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
				//$imgUrl .= $sUrl."lib/plugins/openlayersmap/icons/".$img.",1,C,".$lat.",".$lon.",0,0,0,0,";
				$imgUrl .= $sUrl."lib/plugins/openlayersmap/icons/".$img.",1,C,".$lat.",".$lon.",";
			}
			$imgUrl = substr($imgUrl,0,-1);
		}
		$imgUrl .= "&imageType=png&type=".$maptype;
		dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getMapQuest: MapQuest image url is:');
		return $imgUrl;
	}
	/**
	 *
	 * Create a Google maps static image url w/ the poi.
	 * @param array $gmap
	 * @param array $overlay
	 */
	private function _getGoogle($gmap, $overlay){
		$sUrl=$this->getConf('iconUrlOverload');
		if (!$sUrl){
			$sUrl=DOKU_URL;
		}
		switch ($gmap['baselyr']){
			case 'google hybrid':
				$maptype='hybrid';
				break;
			case 'google sat':
				$maptype='satellite';
				break;
			case 'google relief':
				$maptype='terrain';
				break;
			case 'google road':
			default:
				$maptype='roadmap';
				break;
		}
		// TODO maybe use viewport / visible instead of center/zoom,
		//		see: https://code.google.com/intl/nl/apis/maps/documentation/staticmaps/#ImplicitPositioning
		//http://maps.google.com/maps/api/staticmap?center=51.565690,5.456756&zoom=16&size=600x400&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/marker.png|label:1|51.565690,5.456756&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/marker-blue.png|51.566197,5.458966|label:2&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.567177,5.457909|label:3&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.566283,5.457330|label:4&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.565630,5.457695|label:5&sensor=false&format=png&maptype=roadmap
		$imgUrl = "http://maps.google.com/maps/api/staticmap?sensor=false";
		$imgUrl .= "&size=".str_replace("px", "",$gmap['width'])."x".str_replace("px", "",$gmap['height']);
		$imgUrl .= "&center=".$gmap['lat'].",".$gmap['lon'];
		// max is 21 (== building scale), but that's overkill..
		if ($gmap['zoom']>16) {
			$imgUrl .= "&zoom=16";
		} else			{
			$imgUrl .= "&zoom=".$gmap['zoom'];
		}

		if (!empty ($overlay)) {
			$rowId=0;
			foreach ($overlay as $data) {
				list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
				$imgUrl .= "&markers=icon%3a".$sUrl."lib/plugins/openlayersmap/icons/".$img."%7c".$lat.",".$lon."%7clabel%3a".++$rowId;
			}
		}
		$imgUrl .= "&format=png&maptype=".$maptype;
		global $conf;
		$imgUrl .= "&language=".$conf['lang'];
		dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getGoogle: Google image url is:');
		return $imgUrl;
	}

	/**
	 *
	 * Create a Bing maps static image url w/ the poi.
	 * @param array $gmap
	 * @param array $overlay
	 */
	private function _getBing($gmap, $overlay){
		if(!$this->getConf('bingAPIKey')){
			// in case there is no Bing api key
			$this->_getMapQuest($gmap, $overlay);
		}
		switch ($gmap['baselyr']){
			case 've hybrid':
			case 'bing hybrid':
				$maptype='AerialWithLabels';
				break;
			case 've sat':
			case 'bing sat':
				$maptype='Aerial';
				break;
			case 've normal':
			case 've road':
			case 've':
			case 'bing road':
			default:
				$maptype='Road';
				break;
		}
		$bbox=$this->_calcBBOX($overlay, $gmap['lat'], $gmap['lon']);
		//$imgUrl = "http://dev.virtualearth.net/REST/v1/Imagery/Map/".$maptype."/".$gmap['lat'].",".$gmap['lon']."/".$gmap['zoom'];
		$imgUrl = "http://dev.virtualearth.net/REST/v1/Imagery/Map/".$maptype."/";
		$imgUrl .= "?mapArea=".$bbox['minlat'].",".$bbox['minlon'].",".$bbox['maxlat'].",".$bbox['maxlon'];
		// TODO declutter option works well for square maps but not for rectangular, maybe compensate for that or compensate the mbr..
		$imgUrl .= "&declutter=1";
		$imgUrl .= "&ms=".str_replace("px", "",$gmap['width']).",".str_replace("px", "",$gmap['height']);
		$imgUrl .= "&key=".$this->getConf('bingAPIKey');
		if (!empty ($overlay)) {
			$rowId=0;
			foreach ($overlay as $data) {
				list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
				// TODO icon style lookup, see: http://msdn.microsoft.com/en-us/library/ff701719.aspx for iconStyle
				$iconStyle=32;
				$rowId++;
				// NOTE: the max number of pushpins is 18!
				if ($rowId==18) {
					break;
				}
				$imgUrl .= "&pp=$lat,$lon;$iconStyle;$rowId";
			}
		}
		dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getBing: bing image url is:');
		return $imgUrl;
	}

	/**
	 *
	 * Create a static OSM map image url w/ the poi from http://staticmap.openstreetmap.de (staticMapLite)
	 * @param array $gmap
	 * @param array $overlay
	 */
	private function _getStaticOSM($gmap, $overlay){
		//http://staticmap.openstreetmap.de/staticmap.php?center=47.000622235634,10.117187497601&zoom=5&size=500x350
		// &markers=48.999812532766,8.3593749976708,lightblue1|43.154850037315,17.499999997306,lightblue1|49.487527053077,10.820312497573,ltblu-pushpin|47.951071133739,15.917968747369,ol-marker|47.921629720114,18.027343747285,ol-marker-gold|47.951071133739,19.257812497236,ol-marker-blue|47.180141361692,19.257812497236,ol-marker-green
		$imgUrl = "http://staticmap.openstreetmap.de/staticmap.php";
		$imgUrl .= "?center=".$gmap['lat'].",".$gmap['lon'];
		$imgUrl .= "&size=".str_replace("px", "",$gmap['width'])."x".str_replace("px", "",$gmap['height']);
		if ($gmap['zoom']>16) {
			$imgUrl .= "&zoom=16";
		} else			{
			$imgUrl .= "&zoom=".$gmap['zoom'];
		}

		switch ($gmap['baselyr']){
			case 'mapnik':
				$maptype='mapnik';
				break;
			case 't@h':
				$maptype='osmarenderer';
				break;
			case 'cycle map':
				$maptype='cycle';
				break;
			default:
				$maptype='';
			break;
		}
		$imgUrl .= "&maptype=".$maptype;

		if (!empty ($overlay)) {
			$rowId=0;
			$imgUrl .= "&markers=";
			foreach ($overlay as $data) {
				list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
				$rowId++;
				$iconStyle = "lightblue$rowId";
				$imgUrl .= "$lat,$lon,$iconStyle%7c";
			}
			$imgUrl = substr($imgUrl,0,-3);
		}

		dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getStaticOSM: bing image url is:');
		return $imgUrl;
	}
	/**
	 * Calculate the minimum bbox for a start location + poi.
	 *
	 * @param array $overlay  multi-dimensional array of array($lat, $lon, $text, $angle, $opacity, $img)
	 * @param float $lat latitude for map center
	 * @param float $lon longitude for map center
	 * @return multitype:float array describing the mbr and center point
	 */
	private function _calcBBOX($overlay, $lat, $lon){
		$lats[] = $lat;
		$lons[] = $lon;
		foreach ($overlay as $data) {
			list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
			$lats[] = $lat;
			$lons[] = $lon;
		}
		sort($lats);
		sort($lons);
		// TODO: make edge/wrap around cases work
		$centerlat = $lats[0]+($lats[count($lats)-1]-$lats[0]);
		$centerlon = $lons[0]+($lons[count($lats)-1]-$lons[0]);
		return array('minlat'=>$lats[0], 'minlon'=>$lons[0],
						'maxlat'=>$lats[count($lats)-1], 'maxlon'=>$lons[count($lats)-1],
						'centerlat'=>$centerlat,'centerlon'=>$centerlon);
	}
}