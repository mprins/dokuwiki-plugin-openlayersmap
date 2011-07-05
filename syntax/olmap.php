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
		preg_match('(lat[:|=]\"\d*\.\d*\")',$match,$mainLat);
		preg_match('(lon[:|=]\"\d*\.\d*\")',$match,$mainLon);
		$mainLat=substr($mainLat[0],5,-1);
		$mainLon=substr($mainLon[0],5,-1);

		$gmap = $this->_extract_params($str_params);
		$overlay = $this->_extract_points($str_points);

		$imgUrl = "{{";
		// choose maptype based on tag
		if (stripos($gmap['baselyr'],'google')>0){
			// use google
			$imgUrl .= $this->_getGoogle($gmap, $overlay);
		}elseif (stripos($gmap['baselyr'],'ve')>0){
			// use bing
			$imgUrl .= $this->_getBing($gmap, $overlay);
		}elseif (stripos($gmap['baselyr'],'mapquest')>0){
			// use mapquest
			$imgUrl .=$this->_getMapQuest($gmap,$overlay);
		}else {
			// use http://staticmap.openstreetmap.de
			$imgUrl .=$this->_getStaticOSM($gmap,$overlay);
		}

		// append dw specific params
		$imgUrl .="&.png?".$gmap['width']."x".$gmap['height'];
		$imgUrl .= "&nolink";
		$imgUrl .= " |".$gmap['summary']." }} ";
		// remove 'px'
		$imgUrl = str_replace("px", "",$imgUrl);

		$imgUrl=p_render("xhtml", p_get_instructions($imgUrl), $info);

		$mapid = $gmap['id'];

		// determine width and height (inline styles) for the map image
		// if ($gmap['width'] || $gmap['height']) {
		//	$style = $gmap['width'] ? 'width: ' . $gmap['width'] . ";" : "";
		//	$style .= $gmap['height'] ? 'height: ' . $gmap['height'] . ";" : "";
		//	$style = "style='$style'";
		// } else {
		//	$style = '';
		//}

		// unset gmap values for width and height - they don't go into javascript
		// unset ($gmap['width'], $gmap['height']);

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
		$js .= "createMap({" . $param . " },[$poi]);";
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

			$scriptEnable = '';

			if (!$initialised) {
				$initialised = true;
				// render necessary script tags
				// 				$gscript = $this->getConf('googleScriptUrl');
				// 				$gscript = $gscript ? '<script type="text/javascript" src="' . $gscript . '"></script>' : "";
				if($gEnable){
					$gscript ='<script src="http://maps.google.com/maps/api/js?v=3&sensor=false"></script>';
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
				$scriptEnable .= 'OpenLayers.ImgPath = "'.DOKU_BASE.'lib/plugins/openlayersmap/lib/'.$this->getConf('olMapStyle').'/";';
				$scriptEnable .= "\n" . '//--><!]]>' . "\n" . '</script>';
			}
			$renderer->doc .= "
			$gscript
			$vscript
			$olscript
			$scriptEnable";

			$renderer->doc .= '
				<div id="'.$mapid.'-static" class="olStaticMap">'.$staticImgUrl.'</div>
				<div id="'.$mapid.'-clearer" class="clearer"></div>';

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

			// render inline mapscript
			$renderer->doc .="				<script type='text/javascript'><!--//--><![CDATA[//><!--
			    var $mapid = $param 
			   //--><!]]></script>";

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
			case 'mq hybrid':
				$maptype='hyb (Hybrid)';
				break;
			case 'mq sat':
				$maptype='sat (Satellite)';
				break;
			case 'mq normal':
			default:
				$maptype='map';
				break;
		}


		$imgUrl = "http://open.mapquestapi.com/staticmap/v3/getmap";
		$imgUrl .= "?center=".$gmap['lat'].",".$gmap['lon'];
		$imgUrl .= "&size=".str_replace("px", "",$gmap['width']).",".str_replace("px", "",$gmap['height']);
		// max level for mapquest is 16
		if ($gmap['zoom']>16) {
			$imgUrl .= "&zoom=16";
		} else			{
			$imgUrl .= "&zoom=".$gmap['zoom'];
		}
		// TODO mapquest allows using one image url with a multiplier $NUMBER eg:
		// $NUMBER = 2
		// $imgUrl .= DOKU_URL."/".DOKU_PLUGIN."/".getPluginName()."/icons/".$img.",$NUMBER,C,".$lat1.",".$lon1.",0,0,0,0,C,".$lat2.",".$lon2.",0,0,0,0";
		if (!empty ($overlay)) {
			$imgUrl .= "&xis=";
			foreach ($overlay as $data) {
				list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
				$imgUrl .= $sUrl."lib/plugins/openlayersmap/icons/".$img.",1,C,".$lat.",".$lon.",0,0,0,0,";
			}
			$imgUrl = substr($imgUrl,0,-1);
		}
		$imgUrl .= "&imageType=png&type=".$maptype;
		dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getMapQuest: MapQuest image url is:');
		return $imgUrl;
	}

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
			case 'google normal':
			default:
				$maptype='roadmap';
				break;
		}

		//http://maps.google.com/maps/api/staticmap?center=51.565690,5.456756&zoom=16&size=600x400&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/marker.png|label:1|51.565690,5.456756&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/marker-blue.png|51.566197,5.458966|label:2&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.567177,5.457909|label:3&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.566283,5.457330|label:4&markers=icon:http://wild-water.nl/dokuwiki/lib/plugins/openlayersmap/icons/parking.png|51.565630,5.457695|label:5&sensor=false&format=png&maptype=roadmap
		$imgUrl = "http://maps.google.com/maps/api/staticmap";
		$imgUrl .= "?center=".$gmap['lat'].",".$gmap['lon'];
		$imgUrl .= "&size=".str_replace("px", "",$gmap['width'])."x".str_replace("px", "",$gmap['height']);
		// don't need this anymore $imgUrl .= "&key=".$this->getConf('googleAPIKey');
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
		$imgUrl .= "&format=png&maptype=".$maptype."&sensor=false";
		global $conf;
		$imgUrl .= "&language=".$conf['lang'];
		dbglog($imgUrl,'syntax_plugin_openlayersmap_olmap::_getGoogle: Google image url is:');
		return $imgUrl;
	}

	/**
	 *
	 * Create a bing maps static image url w/ the poi.
	 * @param array $gmap
	 * @param array $overlay
	 */
	private function _getBing($gmap, $overlay){
		switch ($gmap['baselyr']){
			case 've hybrid':
				$maptype='AerialWithLabels';
				break;
			case 've sat':
				$maptype='Aerial';
				break;
			case 've normal':
			case 've':
			default:
				$maptype='Road';
				break;
		}

		// TODO since bing does not provide declutter or autozoom/fit we need to determine the bbox based on the poi and lat/lon ourselves
		//http://dev.virtualearth.net/REST/v1/Imagery/Map/Road/51.56573,5.45690/12?mapSize=400,400&key=Agm4PJzDOGz4Oy9CYKPlV-UtgmsfL2-zeSyfYjRhf57OQB_oj87j5pncKZSay5qY
		$imgUrl = "http://dev.virtualearth.net/REST/v1/Imagery/Map/".$maptype."/".$gmap['lat'].",".$gmap['lon']."/".$gmap['zoom'];
		$imgUrl .= "?ms=".str_replace("px", "",$gmap['width']).",".str_replace("px", "",$gmap['height']);
		// create a bing api key at https://www.bingmapsportal.com/application
		$imgUrl .= "&key=".$this->getConf('bingAPIKey');
		if (!empty ($overlay)) {
			$rowId=0;
			foreach ($overlay as $data) {
				list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
				// TODO icon style lookup, see: http://msdn.microsoft.com/en-us/library/ff701719.aspx for iconStyle
				// NOTE: the max number of pushpins is 18!
				$iconStyle=32;
				$rowId++;
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
}