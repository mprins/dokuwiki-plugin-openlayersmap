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
require_once '../../../../conf/local.php';
/**
 * @author Mark C. Prins <mprins@users.sf.net>
 * @author Gerhard Koch <gerhard.koch AT ymail.com>
 *
 * USAGE:
 *
 *  map.php?center=40.714728,-73.998672&zoom=14&size=512x512&maptype=cycle&markers=40.702147,-74.015794,lightblue1|40.711614,-74.012318,lightblue2|40.718217,-73.998284,lightblue3
 */
class staticMapLite {
	// these should not be changed
	protected $maxWidth = 1024;
	protected $maxHeight = 1024;
	protected $tileSize = 256;

	protected $tileInfo = array(
			// OSM sources
			'openstreetmap'=>array(
					'txt'=>'(c) OpenStreetMap CC-BY-SA',
					'logo'=>'osm_logo.png',
					'url'=>'http://tile.openstreetmap.org/{Z}/{X}/{Y}.png'),
			'cloudmade' =>array(
					'txt'=>'(c) OpenStreetMap CC-BY-SA',
					'logo'=>'osm_logo.png',
					'url'=> 'http://a.tile.cloudmade.com/2f59745a6b525b4ebdb100891d5b6711/3/256/{Z}/{X}/{Y}.png'),
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
			// MapQuest; not sure if this is according to TOS
			'mapquest'=>array(
					'txt'=>'MapQuest tiles',
					'logo'=>'mq_logo.png',
					'url'=>'http://otile3.mqcdn.com/tiles/1.0.0/osm/{Z}/{X}/{Y}.png')
	);
	protected $tileDefaultSrc = 'openstreetmap';

	// set up markers
	protected $markerBaseDir = '../icons';
	protected $markerPrototypes = array(
			// found at http://www.mapito.net/map-marker-icons.html
			'lighblue' => array('regex'=>'/^lightblue([0-9]+)$/',
					'extension'=>'.png',
					'shadow'=>false,
					'offsetImage'=>'0,-19',
					'offsetShadow'=>false
			),
			// openlayers std markers
			'ol-marker'=> array('regex'=>'/^marker(|-blue|-gold|-green|-red)+$/',
					'extension'=>'.png',
					'shadow'=>'marker_shadow.png',
					'offsetImage'=>'-10,-25',
					'offsetShadow'=>'-1,-13'
			),
			'ww_icon'=> array('regex'=>'/^ww\w+$/',
					'extension'=>'.png',
					'shadow'=>'false',
					'offsetImage'=>'-8,-8',
					'offsetShadow'=>'false'
			),
			// assume these are 16x16 px, this is here to prevent crashes, side effect, same setup for all icons..
			'rest' => array('regex'=>'/.+/',
					'extension'=>'.png',
					'shadow'=>'marker_shadow.png',
					'offsetImage'=>'-8,-8',
					'offsetShadow'=>'-1,-1'
			)
	);

	// TODO config options to admin
	// this may fail in some set-ups where the data directory was moved
	// cache options
	protected $useTileCache = true;
	protected $tileCacheBaseDir =  '../../../../data/cache/olmaptiles';
	protected $useMapCache = false;
	protected $mapCacheBaseDir = '../../../../data/cache/olmapmaps';
	protected $mapCacheID = '';
	protected $mapCacheFile = '';
	protected $mapCacheExtension = 'png';
	// other vars
	protected $zoom, $lat, $lon, $width, $height, $markers, $image, $maptype;
	protected $centerX, $centerY, $offsetX, $offsetY;

	public function __construct(){
		$this->zoom = 0;
		$this->lat = 0;
		$this->lon = 0;
		$this->width = 500;
		$this->height = 350;
		$this->markers = array();
		$this->maptype = $this->tileDefaultSrc;
	}

	public function parseParams(){
		global $_GET;
		// get zoom from GET paramter
		$this->zoom = $_GET['zoom']?intval($_GET['zoom']):0;
		if($this->zoom>18)$this->zoom = 18;
		// get lat and lon from GET paramter
		list($this->lat,$this->lon) = split(',',$_GET['center']);
		$this->lat = floatval($this->lat);
		$this->lon = floatval($this->lon);
		// get zoom from GET paramter
		if($_GET['size']){
			list($this->width, $this->height) = split('x',$_GET['size']);
			$this->width = intval($this->width);
			if($this->width > $this->maxWidth) $this->width = $this->maxWidth;
			$this->height = intval($this->height);
			if($this->height > $this->maxHeight) $this->height = $this->maxHeight;
		}
		if($_GET['markers']){
			$markers = split('%7C|\|',$_GET['markers']);
			foreach($markers as $marker){
				list($markerLat, $markerLon, $markerType) = split(',',$marker);
				$markerLat = floatval($markerLat);
				$markerLon = floatval($markerLon);
				$markerType = basename($markerType);
				$this->markers[] = array('lat'=>$markerLat, 'lon'=>$markerLon, 'type'=>$markerType);
			}
		}
		if($_GET['maptype']){
			if(array_key_exists($_GET['maptype'],$this->tileInfo)) $this->maptype = $_GET['maptype'];
		}
	}

	public function lonToTile($long, $zoom){
		return (($long + 180) / 360) * pow(2, $zoom);
	}

	public function latToTile($lat, $zoom){
		return (1 - log(tan($lat * pi()/180) + 1 / cos($lat* pi()/180)) / pi()) /2 * pow(2, $zoom);
	}

	public function initCoords(){
		$this->centerX = $this->lonToTile($this->lon, $this->zoom);
		$this->centerY = $this->latToTile($this->lat, $this->zoom);
		$this->offsetX = floor((floor($this->centerX)-$this->centerX)*$this->tileSize);
		$this->offsetY = floor((floor($this->centerY)-$this->centerY)*$this->tileSize);
	}

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
						error_log('found a match for: '.$markerType.' using regex: '.$markerPrototype['regex']);
						$markerFilename = $matches[0].$markerPrototype['extension'];
						if($markerPrototype['offsetImage']){
							list($markerImageOffsetX, $markerImageOffsetY)  = split(",",$markerPrototype['offsetImage']);
						}
						$markerShadow = $markerPrototype['shadow'];
						if($markerShadow){
							list($markerShadowOffsetX, $markerShadowOffsetY)  = split(",",$markerPrototype['offsetShadow']);
						}
						// get out after 1st match
						break;
					}
				}
			}
			// check required files or set default
			if($markerFilename == '' || !file_exists($this->markerBaseDir.'/'.$markerFilename)){
				$markerIndex++;
				$markerFilename = 'lightblue'.$markerIndex.'.png';
				$markerImageOffsetX = 0;
				$markerImageOffsetY = -19;
			}
			// create img resource
			if(file_exists($this->markerBaseDir.'/'.$markerFilename)){
				$markerImg = imagecreatefrompng($this->markerBaseDir.'/'.$markerFilename);
			} else {
				$markerImg = imagecreatefrompng($this->markerBaseDir.'/lightblue1.png');
			}
			// check for shadow + create shadow recource
			if($markerShadow && file_exists($this->markerBaseDir.'/'.$markerShadow)){
				$markerShadowImg = imagecreatefrompng($this->markerBaseDir.'/'.$markerShadow);
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



	public function tileUrlToFilename($url){
		return $this->tileCacheBaseDir."/".str_replace(array('http://'),'',$url);
	}

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
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0");
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
	 * add copyright and origin notice and icons to the map.
	 */
	public function copyrightNotice(){
		$logoImg = imagecreatefrompng($this->tileInfo['openstreetmap']['logo']);
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
			$iconImg = imagecreatefrompng($this->tileInfo[$this->maptype]['logo']);
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
	 * HTTP response header.
	 * @deprecated should use DW mechanism
	 */
	public function sendHeader(){
		header('Content-Type: image/png');
		$expires = 60*60*24*14;
		header("Pragma: public");
		header("Cache-Control: maxage=".$expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
	}

	public function makeMap(){
		$this->initCoords();
		$this->createBaseMap();
		if(count($this->markers))$this->placeMarkers();
		$this->copyrightNotice();
	}

	public function showMap(){
		$this->parseParams();
		if($this->useMapCache){
			// use map cache, so check cache for map
			if(!$this->checkMapCache()){
				// map is not in cache, needs to be build
				$this->makeMap();
				$this->mkdir_recursive(dirname($this->mapCacheIDToFilename()),0777);
				imagepng($this->image,$this->mapCacheIDToFilename(),9);
				$this->sendHeader();
				if(file_exists($this->mapCacheIDToFilename())){
					return file_get_contents($this->mapCacheIDToFilename());
				} else {
					return imagepng($this->image);
				}
			} else {
				// map is in cache
				$this->sendHeader();
				return file_get_contents($this->mapCacheIDToFilename());
			}
		} else {
			// no cache, make map, send headers and deliver png
			$this->makeMap();
			$this->sendHeader();
			return imagepng($this->image);
		}
	}
}

switch ($_SERVER['REQUEST_METHOD']) {
	// only support GET
	case 'GET':
		// check $_SERVER['SERVER_NAME']);
		// TODO will fail in ipv6 environment
		// TODO make this a configurable list
		if ($_SERVER['REMOTE_ADDR']=='127.0.0.1') {
			// only allow acces from localhost and not from anyone else
			$map = new staticMapLite();
			print $map->showMap();
		}else{
			header('HTTP/1.1 403 Forbidden');
			print 'Direct access is forbidden.';
		}
		break;
	default:
		header('HTTP/1.1 501 Not Implemented');
		print 'The requested method (HTTP '.$_SERVER["REQUEST_METHOD"].') is not implemented.';
}
