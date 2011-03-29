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
	/** defaults of the recognized attributes of the olmap tag. */
	private $dflt = array (
		'id' => 'olmap',
		'width' => '550px',
		'height' => '450px',
		'lat' => 50.0,
		'lon' => 5.1,
		'zoom' => 12,
		'toolbar' => true,
		'statusbar' => true,
		'controls' => true,
		'poihoverstyle' => false,
		'baselyr'=>'OpenStreetMap',
	 	'gpxfile' => '',
 		'kmlfile' => ''
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

 			$mapid = $gmap['id'];

 			// determine width and height (inline styles) for the map image
 			if ($gmap['width'] || $gmap['height']) {
 				$style = $gmap['width'] ? 'width: ' . $gmap['width'] . ";" : "";
 				$style .= $gmap['height'] ? 'height: ' . $gmap['height'] . ";" : "";
 				$style = "style='$style'";
 			} else {
 				$style = '';
 			}

 			// unset gmap values for width and height - they don't go into javascript
 			unset ($gmap['width'], $gmap['height']);

 			// create a javascript parameter string for the map
 			$param = '';
 			foreach ($gmap as $key => $val) {
 				$param .= is_numeric($val) ? "$key:$val," : "$key:'" . hsc($val) . "',";
 			}
 			if (!empty ($param)) {
 				$param = substr($param, 0, -1);
 			}
 			unset ($gmap['id']);

 			// create a javascript serialisation of the point data
 			$poi = '';
 			if (!empty ($overlay)) {
 				foreach ($overlay as $data) {
 					list ($lat, $lon, $text, $angle, $opacity, $img) = $data;
 					$poi .= ",{lat: $lat, lon: $lon, txt: '$text', angle: $angle, opacity: $opacity, img: '$img'}";
 				}
 				$poi = substr($poi, 1);
 			}
 			$js .= "createMap({" . $param . " },[$poi]);";

 			return array (
 			$mapid,
 			$style,
 			$js,
 			$mainLat,
 			$mainLon
 			);
 		}

 		/**
 		 * render html tag/output. render the content.
 		 * @Override
 		 */
 		function render($mode, &$renderer, $data) {
 			static $initialised = false; // set to true after script initialisation
 			list ($mapid, $style, $param, $mainLat, $mainLon) = $data;

 			if ($mode == 'xhtml') {
 				$olscript = '';
 				$olEnable = false;
 				$gscript = '';
 				$gEnable = false;
 				$vscript = '';
 				$vEnable = false;
 				$yscript = '';
 				$yEnable = false;

 				$scriptEnable = '';

 				if (!$initialised) {
 					$initialised = true;

 					$gscript = $this->getConf('googleScriptUrl');
 					$gscript = $gscript ? '<script type="text/javascript" src="' . $gscript . '"></script>' : "";

 					$vscript = $this->getConf('veScriptUrl');
 					$vscript = $vscript ? '<script type="text/javascript" src="' . $vscript . '"></script>' : "";

 					$yscript = $this->getConf('yahooScriptUrl');
 					$yscript = $yscript ? '<script type="text/javascript" src="' . $yscript . '"></script>' : "";

 					$olscript = $this->getConf('olScriptUrl');
 					$olscript = $olscript ? '<script type="text/javascript" src="' . $olscript . '"></script>' : "";
 					$olscript = str_replace('DOKU_PLUGIN', DOKU_PLUGIN, $olscript);

 					$scriptEnable = '<script type="text/javascript">' . "\n" . '//<![CDATA[' . "\n";
 					$scriptEnable .= $olscript ? 'olEnable = true;' : 'olEnable = false;';
 					$scriptEnable .= $yscript ? ' yEnable = true;' : ' yEnable = false;';
 					$scriptEnable .= $vscript ? ' veEnable = true;' : ' veEnable = false;';
 					$scriptEnable .= $gscript ? ' gEnable = true;' : ' gEnable = false;';
 					$scriptEnable .= "\n" . '//]]>' . "\n" . '</script>';
 				}
 				$renderer->doc .= "
 				$olscript
 				$gscript
 				$vscript
 				$yscript
 				$scriptEnable
			    <div id='olContainer' class='olContainer'>
			        <div id='$mapid-olToolbar' class='olToolbar'></div>
			        <div style='clear:both;'></div>
			        <div id='$mapid' $style ></div>
			        <div id='$mapid-olStatusBar' class='olStatusBarContainer'>
			            <div id='$mapid-statusbar-scale' class='olStatusBar olStatusBarScale'>scale</div>
			            <div id='$mapid-statusbar-link' class='olStatusBar olStatusBarPermalink'>
			                <a href='' id='$mapid-statusbar-link-ref'>map link</a>
			            </div>
			            <div id='$mapid-statusbar-mouseposition' class='olStatusBar olStatusBarMouseposition'></div>
			            <div id='$mapid-statusbar-projection' class='olStatusBar olStatusBarProjection'>proj</div>
			            <div id='$mapid-statusbar-text' class='olStatusBar olStatusBarText'>txt</div>
			        </div>
			    </div>
			    <p>&nbsp;</p>
			    <script type='text/javascript'>//<![CDATA[
			    var $mapid = $param 
			   //]]></script>";
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
 		function _extract_params($str_params) {
 			$param = array ();
 			preg_match_all('/(\w*)="(.*?)"/us', $str_params, $param, PREG_SET_ORDER);
 			// parse match for instructions, break into key value pairs
 			$gmap = $this->dflt;
 			foreach ($param as $kvpair) {
 				list ($match, $key, $val) = $kvpair;
 				$key = strtolower($key);
 				if (isset ($gmap[$key])){
 					$gmap[$key] = strtolower($val);
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
 		function _extract_points($str_points) {
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
 				// TODO validate & set up default img?
 				$text = addslashes(str_replace("\n", "", p_render("xhtml", p_get_instructions($text), $info)));
 				$overlay[] = array($lat, $lon, $text, $angle, $opacity, $img);
 			}
 			return $overlay;
 		}
}