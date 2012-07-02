<?php

/*
 * Copyright (c) 2012 Mark C. Prins <mprins@users.sf.net>
*
* Based on staticMapLite 0.03 available at http://staticmaplite.svn.sourceforge.net/viewvc/staticmaplite/
*
* Copyright (c) 2009 Gerhard Koch <gerhard.koch AT ymail.com>
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*     http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/
include_once('geoPHP/geoPHP.inc');
/**
 * @author Mark C. Prins <mprins@users.sf.net>
 * @author Gerhard Koch <gerhard.koch AT ymail.com>
 *
 */
class StaticMap {
	// these should probably not be changed
	protected $tileSize = 256;

	// the final output
	var $doc = '';

	protected $tileInfo = array(
			// OSM sources
			'openstreetmap'=>array(
					'txt'=>'(c) OpenStreetMap CC-BY-SA',
					'logo'=>'osm_logo.png',
					'url'=>'http://tile.openstreetmap.org/{Z}/{X}/{Y}.png'),
			// cloudmade
			'cloudmade' =>array(
					'txt'=>'CloudMade tiles',
					'logo'=>'cloudmade_logo.png',
					'url'=> 'http://tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/2/256/{Z}/{X}/{Y}.png'),
			'fresh' =>array(
					'txt'=>'CloudMade tiles',
					'logo'=>'cloudmade_logo.png',
					'url'=> 'http://tile.cloudmade.com/BC9A493B41014CAABB98F0471D759707/997/256/{Z}/{X}/{Y}.png'),
			// OCM sources
			'cycle'=>array(
					'txt'=>'OpenCycleMap tiles',
					'logo'=>'cycle_logo.png',
					'url'=>'http://tile.opencyclemap.org/cycle/{Z}/{X}/{Y}.png'),
			'transport'=>array(
					'txt'=>'OpenCycleMap tiles',
					'logo'=>'cycle_logo.png',
					'url'=>'http://tile2.opencyclemap.org/transport/{Z}/{X}/{Y}.png'),
			'landscape'=>array(
					'txt'=>'OpenCycleMap tiles',
					'logo'=>'cycle_logo.png',
					'url'=>'http://tile3.opencyclemap.org/landscape/{Z}/{X}/{Y}.png'),
			// H&B sources
			'hikeandbike'=>array(
					'txt'=>'Hike & Bike Map',
					'logo'=>'hnb_logo.png',
					'url'=>'http://toolserver.org/tiles/hikebike/{Z}/{X}/{Y}.png'),
			//'piste'=>array(
			//		'txt'=>'OpenPisteMap tiles',
			//		'logo'=>'piste_logo.png',
			//		'url'=>''),
			//'sea'=>array(
			//		'txt'=>'OpenSeaMap tiles',
			//		'logo'=>'sea_logo.png',
			//		'url'=>''),
			// MapQuest
			'mapquest'=>array(
					'txt'=>'MapQuest tiles',
					'logo'=>'mq_logo.png',
					'url'=>'http://otile3.mqcdn.com/tiles/1.0.0/osm/{Z}/{X}/{Y}.png')
	);
	protected $tileDefaultSrc = 'openstreetmap';

	// set up markers
	protected $markerPrototypes = array(
			// found at http://www.mapito.net/map-marker-icons.html
			// these are 17x19 px with a pointer at the bottom left
			'lighblue' => array('regex'=>'/^lightblue([0-9]+)$/',
					'extension'=>'.png',
					'shadow'=>false,
					'offsetImage'=>'0,-19',
					'offsetShadow'=>false
			),
			// openlayers std markers are 21x25px with shadow
			'ol-marker'=> array('regex'=>'/^marker(|-blue|-gold|-green|-red)+$/',
					'extension'=>'.png',
					'shadow'=>'marker_shadow.png',
					'offsetImage'=>'-10,-25',
					'offsetShadow'=>'-1,-13'
			),
			// these are 16x16 px
			'ww_icon'=> array('regex'=>'/ww_\S+$/',
					'extension'=>'.png',
					'shadow'=>false,
					'offsetImage'=>'-8,-8',
					'offsetShadow'=>false
			),
			// assume these are 16x16 px
			'rest' => array('regex'=>'/^(?!lightblue([0-9]+)$)(?!(ww_\S+$))(?!marker(|-blue|-gold|-green|-red)+$)(.*)/',
					'extension'=>'.png',
					'shadow'=>'marker_shadow.png',
					'offsetImage'=>'-8,-8',
					'offsetShadow'=>'-1,-1'
			)
	);
	protected $centerX, $centerY, $offsetX, $offsetY, $image;
	protected $zoom, $lat, $lon, $width, $height, $markers, $maptype, $kmlFileName, $gpxFileName;
	protected $tileCacheBaseDir, $mapCacheBaseDir, $mediaBaseDir;
	protected $useTileCache = true;
	protected $mapCacheID = '';
	protected $mapCacheFile = '';
	protected $mapCacheExtension = 'png';

	/**
	 *
	 * @param number $lat
	 * @param number $lon
	 * @param number $zoom
	 * @param number $width
	 * @param number $height
	 * @param string $maptype
	 * @param mixed $markers
	 * @param string $gpx
	 * @param string $kml
	 * @param string $mediaDir
	 * @param string $tileCacheBaseDir
	 */
	public function __construct($lat,$lon,$zoom,$width,$height,$maptype, $markers,$gpx,$kml,$mediaDir,$tileCacheBaseDir){
		$this->zoom = $zoom;
		$this->lat = $lat;
		$this->lon = $lon;
		$this->width = $width;
		$this->height = $height;
		$this->markers = $markers;
		$this->mediaBaseDir = $mediaDir;
		// validate + set maptype
		$this->maptype = $this->tileDefaultSrc;
		if(array_key_exists($maptype,$this->tileInfo)) {
			$this->maptype = $maptype;
		}

		$this->tileCacheBaseDir= $tileCacheBaseDir.'/olmaptiles';
		$this->useTileCache = $this->tileCacheBaseDir !=='';
		$this->mapCacheBaseDir = $mediaDir.'/olmapmaps';

		$this->kmlFileName = $kml;
		$this->gpxFileName = $gpx;
	}

	/**
	 *
	 * @param number $long
	 * @param number $zoom
	 */
	public function lonToTile($long, $zoom){
		return (($long + 180) / 360) * pow(2, $zoom);
	}
	/**
	 *
	 * @param number $lat
	 * @param number $zoom
	 * @return number
	 */
	public function latToTile($lat, $zoom){
		return (1 - log(tan($lat * pi()/180) + 1 / cos($lat* pi()/180)) / pi()) /2 * pow(2, $zoom);
	}
	/**
	 *
	 */
	public function initCoords(){
		$this->centerX = $this->lonToTile($this->lon, $this->zoom);
		$this->centerY = $this->latToTile($this->lat, $this->zoom);
		$this->offsetX = floor((floor($this->centerX)-$this->centerX)*$this->tileSize);
		$this->offsetY = floor((floor($this->centerY)-$this->centerY)*$this->tileSize);
	}

	/**
	 * make basemap image.
	 */
	public function createBaseMap(){
		$this->image = imagecreatetruecolor($this->width, $this->height);
		$startX = floor($this->centerX-($this->width/$this->tileSize)/2);
		$startY = floor($this->centerY-($this->height/$this->tileSize)/2);
		$endX = ceil($this->centerX+($this->width/$this->tileSize)/2);
		$endY = ceil($this->centerY+($this->height/$this->tileSize)/2);
		$this->offsetX = -floor(($this->centerX-floor($this->centerX))*$this->tileSize);
		$this->offsetY = -floor(($this->centerY-floor($this->centerY))*$this->tileSize);
		$this->offsetX += floor($this->width/2);
		$this->offsetY += floor($this->height/2);
		$this->offsetX += floor($startX-floor($this->centerX))*$this->tileSize;
		$this->offsetY += floor($startY-floor($this->centerY))*$this->tileSize;

		for($x=$startX; $x<=$endX; $x++){
			for($y=$startY; $y<=$endY; $y++){
				$url = str_replace(array('{Z}','{X}','{Y}'),array($this->zoom, $x, $y), $this->tileInfo[$this->maptype]['url']);
				$tileData = $this->fetchTile($url);
				if($tileData){
					$tileImage = imagecreatefromstring($tileData);
				} else {
					$tileImage = imagecreate($this->tileSize,$this->tileSize);
					$color = imagecolorallocate($tileImage, 255, 255, 255);
					@imagestring($tileImage,1,127,127,'err',$color);
				}
				$destX = ($x-$startX)*$this->tileSize+$this->offsetX;
				$destY = ($y-$startY)*$this->tileSize+$this->offsetY;
				imagecopy($this->image, $tileImage, $destX, $destY, 0, 0, $this->tileSize, $this->tileSize);
			}
		}
	}

	/**
	 * Place markers on the map and number them in the same order as they are listed in the html.
	 */
	public function placeMarkers(){
		$count=0;
		$color=imagecolorallocate ($this->image,0,0,0 );
		$bgcolor=imagecolorallocate ($this->image,200,200,200 );
		$markerBaseDir = dirname(__FILE__).'/icons';
		// loop thru marker array
		foreach($this->markers as $marker){
			// set some local variables
			$markerLat = $marker['lat'];
			$markerLon = $marker['lon'];
			$markerType = $marker['type'];
			// clear variables from previous loops
			$markerFilename = '';
			$markerShadow = '';
			$matches = false;
			// check for marker type, get settings from markerPrototypes
			if($markerType){
				foreach($this->markerPrototypes as $markerPrototype){
					if(preg_match($markerPrototype['regex'],$markerType,$matches)){
						$markerFilename = $matches[0].$markerPrototype['extension'];
						if($markerPrototype['offsetImage']){
							list($markerImageOffsetX, $markerImageOffsetY)  = split(",",$markerPrototype['offsetImage']);
						}
						$markerShadow = $markerPrototype['shadow'];
						if($markerShadow){
							list($markerShadowOffsetX, $markerShadowOffsetY)  = split(",",$markerPrototype['offsetShadow']);
						}
					}
				}
			}
			// create img resource
			if(file_exists($markerBaseDir.'/'.$markerFilename)){
				$markerImg = imagecreatefrompng($markerBaseDir.'/'.$markerFilename);
			} else {
				$markerImg = imagecreatefrompng($markerBaseDir.'/marker.png');
			}
			// check for shadow + create shadow recource
			if($markerShadow && file_exists($markerBaseDir.'/'.$markerShadow)){
				$markerShadowImg = imagecreatefrompng($markerBaseDir.'/'.$markerShadow);
			}
			// calc position
			$destX = floor(($this->width/2)-$this->tileSize*($this->centerX-$this->lonToTile($markerLon, $this->zoom)));
			$destY = floor(($this->height/2)-$this->tileSize*($this->centerY-$this->latToTile($markerLat, $this->zoom)));
			// copy shadow on basemap
			if($markerShadow && $markerShadowImg){
				imagecopy($this->image, $markerShadowImg, $destX+intval($markerShadowOffsetX), $destY+intval($markerShadowOffsetY),
						0, 0, imagesx($markerShadowImg), imagesy($markerShadowImg));
			}
			// copy marker on basemap above shadow
			imagecopy($this->image, $markerImg, $destX+intval($markerImageOffsetX), $destY+intval($markerImageOffsetY),
					0, 0, imagesx($markerImg), imagesy($markerImg));
			// add label
			imagestring ($this->image , 3 , $destX-imagesx($markerImg)+1 , $destY+intval($markerImageOffsetY)+1 , ++$count , $bgcolor );
			imagestring ($this->image , 3 , $destX-imagesx($markerImg) , $destY+intval($markerImageOffsetY) , $count , $color );
		};
	}
	/**
	 *
	 * @param string $url
	 * @return string
	 */
	public function tileUrlToFilename($url){
		return $this->tileCacheBaseDir."/".str_replace(array('http://'),'',$url);
	}
	/**
	 *
	 * @param string $url
	 */
	public function checkTileCache($url){
		$filename = $this->tileUrlToFilename($url);
		if(file_exists($filename)){
			return file_get_contents($filename);
		}
	}

	public function checkMapCache(){
		$this->mapCacheID = md5($this->serializeParams());
		$filename = $this->mapCacheIDToFilename();
		if(file_exists($filename)) return true;
	}

	public function serializeParams(){
		return join("&",array($this->zoom,$this->lat,$this->lon,$this->width,$this->height, serialize($this->markers),$this->maptype));
	}

	public function mapCacheIDToFilename(){
		if(!$this->mapCacheFile){
			$this->mapCacheFile = $this->mapCacheBaseDir."/".$this->maptype."/".$this->zoom."/cache_".substr($this->mapCacheID,0,2)."/".substr($this->mapCacheID,2,2)."/".substr($this->mapCacheID,4);
		}
		return $this->mapCacheFile.".".$this->mapCacheExtension;
	}

	public function mkdir_recursive($pathname, $mode){
		is_dir(dirname($pathname)) || $this->mkdir_recursive(dirname($pathname), $mode);
		return is_dir($pathname) || @mkdir($pathname, $mode);
	}

	public function writeTileToCache($url, $data){
		$filename = $this->tileUrlToFilename($url);
		$this->mkdir_recursive(dirname($filename),0777);
		file_put_contents($filename, $data);
	}

	public function fetchTile($url){
		if($this->useTileCache && ($cached = $this->checkTileCache($url))) return $cached;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; DokuWikiSpatial HTTP Client; '.PHP_OS.')');
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_URL, $url);
		$tile = curl_exec($ch);
		curl_close($ch);
		if($tile && $this->useTileCache){
			$this->writeTileToCache($url,$tile);
		}
		return $tile;
	}

	/**
	 * Draw gpx trace on the map.
	 */
	public function drawGPX(){
		$gpxgeom = geoPHP::load(file_get_contents($this->gpxFileName),'gpx');
		$col = imagecolorallocate($this->image, 0, 0, 255);

		for ($i = 1; $i < $gpxgeom->numGeometries()+1; $i++) {
			$geom = $gpxgeom->geometryN($i);
			// can be Point or LineString
			switch ($geom->geometryType()) {
				case 'LineString':
					$this->drawLineString($geom, $col);
					break;
				case 'Point':
					$this->drawPoint($geom, $col);
					break;
				default:
					//do nothing
					break;
			}
		}
	}

	/**
	 * Draw kml trace on the map.
	 */
	public function drawKML(){
		//TODO implementation
		$kmlgeom = geoPHP::load(file_get_contents($this->kmlFileName),'kml');
		// TODO get colour from kml node
		$col = imagecolorallocate($this->image, 255, 0, 0);
		
		for ($i = 1; $i < $kmlgeom->numGeometries()+1; $i++) {
			$geom = $kmlgeom->geometryN($i);
			switch ($geom->geometryType()) {
				case 'LineString':
					$this->drawLineString($geom, $col);
					break;
				case 'Point':
					$this->drawPoint($geom, $col);
					break;
				case 'Polygon':
					break;
				default:
					//do nothing
					break;
			}
		}
	}

	private function drawLineString($line, $colour){
		for ($p = 1; $p < $line->numGeometries(); $p++) {
			// get first pair of points
			$p1 = $line->geometryN($p);
			$p2 = $line->geometryN($p+1);
			// translate to paper space
			$x1 = floor(($this->width/2)-$this->tileSize*($this->centerX-$this->lonToTile($p1->x(), $this->zoom)));
			$y1 = floor(($this->height/2)-$this->tileSize*($this->centerY-$this->latToTile($p1->y(), $this->zoom)));
			$x2 = floor(($this->width/2)-$this->tileSize*($this->centerX-$this->lonToTile($p2->x(), $this->zoom)));
			$y2 = floor(($this->height/2)-$this->tileSize*($this->centerY-$this->latToTile($p2->y(), $this->zoom)));
			// draw to image
			imageline ( $this->image ,  $x1 ,  $y1 ,  $x2 ,  $y2 , $colour );
		}
	}
	
	private function drawPoint($point, $colour){
		// translate to paper space
		$cx = floor(($this->width/2)-$this->tileSize*($this->centerX-$this->lonToTile($point->x(), $this->zoom)));
		$cy = floor(($this->height/2)-$this->tileSize*($this->centerY-$this->latToTile($point->y(), $this->zoom)));
		// draw to image
		imageellipse  ( $this->image ,  $cx ,  $cy , 14 /*width*/ , 14 /*height*/ , $colour );
	}
	
	/**
	 * add copyright and origin notice and icons to the map.
	 */
	public function drawCopyright(){
		$logoBaseDir = dirname(__FILE__).'/'.'logo/';
		$logoImg = imagecreatefrompng($logoBaseDir.$this->tileInfo['openstreetmap']['logo']);
		$textcolor = imagecolorallocate($this->image, 0, 0, 0);
		$bgcolor = imagecolorallocate($this->image, 200, 200, 200);

		imagecopy($this->image,
				$logoImg,
				0,
				imagesy($this->image)-imagesy($logoImg),
				0,
				0,
				imagesx($logoImg),
				imagesy($logoImg)
		);
		imagestring ($this->image , 1 , imagesx($logoImg)+2 , imagesy($this->image)-imagesy($logoImg)+1 , $this->tileInfo['openstreetmap']['txt'],$bgcolor );
		imagestring ($this->image , 1 , imagesx($logoImg)+1 , imagesy($this->image)-imagesy($logoImg) , $this->tileInfo['openstreetmap']['txt'] ,$textcolor );

		// additional tile source info, ie. who created/hosted the tiles
		if ($this->maptype!='openstreetmap') {
			$iconImg = imagecreatefrompng($logoBaseDir.$this->tileInfo[$this->maptype]['logo']);
			imagecopy($this->image,
					$iconImg,
					imagesx($logoImg)+1,
					imagesy($this->image)-imagesy($iconImg),
					0,
					0,
					imagesx($iconImg),
					imagesy($iconImg)
			);
			imagestring ($this->image , 1 , imagesx($logoImg)+imagesx($iconImg)+4 , imagesy($this->image)-ceil(imagesy($logoImg)/2)+1 , $this->tileInfo[$this->maptype]['txt'],$bgcolor );
			imagestring ($this->image , 1 , imagesx($logoImg)+imagesx($iconImg)+3 , imagesy($this->image)-ceil(imagesy($logoImg)/2) , $this->tileInfo[$this->maptype]['txt'] ,$textcolor );
		}
	}
	/**
	 * make the map.
	 */
	public function makeMap(){
		$this->initCoords();
		$this->createBaseMap();
		if(count($this->markers))$this->placeMarkers();
		if(file_exists($this->kmlFileName)) $this->drawKML();
		if(file_exists($this->gpxFileName)) $this->drawGPX();
		$this->drawCopyright();
	}
	/**
	 * get the map, this may return a reference to a cached copy.
	 * @return string url relative to media dir
	 */
	public function getMap(){
		// use map cache, so check cache for map
		if(!$this->checkMapCache()){
			// map is not in cache, needs to be build
			$this->makeMap();
			$this->mkdir_recursive(dirname($this->mapCacheIDToFilename()),0777);
			imagepng($this->image,$this->mapCacheIDToFilename(),9);
		}
		$this->doc =$this->mapCacheIDToFilename();
		// make url relative to media dir
		return str_replace($this->mediaBaseDir, '', $this->doc);
	}
}
