<?php
/*
 * Copyright (c) 2012-2018 Mark C. Prins <mprins@users.sf.net>
 *
 * In part based on staticMapLite 0.03 available at http://staticmaplite.svn.sourceforge.net/viewvc/staticmaplite/
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
include_once (realpath ( dirname ( __FILE__ ) ) . '/../geophp/geoPHP/geoPHP.inc');
/**
 *
 * @author Mark C. Prins <mprins@users.sf.net>
 * @author Gerhard Koch <gerhard.koch AT ymail.com>
 *
 */
class StaticMap {
	// this should probably not be changed
	protected $tileSize = 256;

	// the final output
	var $doc = '';

	protected $tileInfo = array (
			// OSM sources
			'openstreetmap' => array (
					'txt' => '(c) OpenStreetMap CC-BY-SA',
					'logo' => 'osm_logo.png',
					'url' => 'http://tile.openstreetmap.org/{Z}/{X}/{Y}.png'
			),
			// OCM sources
			'cycle' => array (
					'txt' => 'OpenCycleMap tiles',
					'logo' => 'cycle_logo.png',
					'url' => 'https://tile.thunderforest.com/cycle/{Z}/{X}/{Y}.png?apikey='
			),
			'transport' => array (
					'txt' => 'OpenCycleMap tiles',
					'logo' => 'cycle_logo.png',
					'url' => 'https://tile.thunderforest.com/transport/{Z}/{X}/{Y}.png?apikey='
			),
			'landscape' => array (
					'txt' => 'OpenCycleMap tiles',
					'logo' => 'cycle_logo.png',
					'url' => 'https://tile.thunderforest.com/landscape/{Z}/{X}/{Y}.png?apikey='
			),
			'outdoors' => array (
					'txt' => 'OpenCycleMap tiles',
					'logo' => 'cycle_logo.png',
					'url' => 'https://tile.thunderforest.com/outdoors/{Z}/{X}/{Y}.png?apikey='
			),
			'toner-lite' => array (
					'txt' => 'Stamen tiles',
					'logo' => 'stamen.png',
					'url' => 'http://tile.stamen.com/toner-lite/{Z}/{X}/{Y}.png'
			),
			'terrain' => array (
					'txt' => 'Stamen tiles',
					'logo' => 'stamen.png',
					'url' => 'http://tile.stamen.com/terrain/{Z}/{X}/{Y}.png'
			),
			// 'piste'=>array(
			// 'txt'=>'OpenPisteMap tiles',
			// 'logo'=>'piste_logo.png',
			// 'url'=>''),
			// 'sea'=>array(
			// 'txt'=>'OpenSeaMap tiles',
			// 'logo'=>'sea_logo.png',
			// 'url'=>''),
			// H&B sources
			'hikeandbike' => array (
					'txt' => 'Hike & Bike Map',
					'logo' => 'hnb_logo.png',
					//'url' => 'http://toolserver.org/tiles/hikebike/{Z}/{X}/{Y}.png'
					//moved to: https://www.toolserver.org/tiles/hikebike/12/2105/1388.png
					'url' => 'http://c.tiles.wmflabs.org/hikebike/{Z}/{X}/{Y}.png'
			)
	);
	protected $tileDefaultSrc = 'openstreetmap';

	// set up markers
	protected $markerPrototypes = array (
			// found at http://www.mapito.net/map-marker-icons.html
			// these are 17x19 px with a pointer at the bottom left
			'lightblue' => array (
					'regex' => '/^lightblue([0-9]+)$/',
					'extension' => '.png',
					'shadow' => false,
					'offsetImage' => '0,-19',
					'offsetShadow' => false
			),
			// openlayers std markers are 21x25px with shadow
			'ol-marker' => array (
					'regex' => '/^marker(|-blue|-gold|-green|-red)+$/',
					'extension' => '.png',
					'shadow' => 'marker_shadow.png',
					'offsetImage' => '-10,-25',
					'offsetShadow' => '-1,-13'
			),
			// these are 16x16 px
			'ww_icon' => array (
					'regex' => '/ww_\S+$/',
					'extension' => '.png',
					'shadow' => false,
					'offsetImage' => '-8,-8',
					'offsetShadow' => false
			),
			// assume these are 16x16 px
			'rest' => array (
					'regex' => '/^(?!lightblue([0-9]+)$)(?!(ww_\S+$))(?!marker(|-blue|-gold|-green|-red)+$)(.*)/',
					'extension' => '.png',
					'shadow' => 'marker_shadow.png',
					'offsetImage' => '-8,-8',
					'offsetShadow' => '-1,-1'
			)
	);
	protected $centerX, $centerY, $offsetX, $offsetY, $image;
	protected $zoom, $lat, $lon, $width, $height, $markers, $maptype, $kmlFileName, $gpxFileName, $geojsonFileName, $autoZoomExtent, $apikey;
	protected $tileCacheBaseDir, $mapCacheBaseDir, $mediaBaseDir;
	protected $useTileCache = true;
	protected $mapCacheID = '';
	protected $mapCacheFile = '';
	protected $mapCacheExtension = 'png';

	/**
	 * Constructor.
	 *
	 * @param float $lat
	 *        	Latitude (x) of center of map
	 * @param float $lon
	 *        	Longitude (y) of center of map
	 * @param int $zoom
	 *        	Zoomlevel
	 * @param int $width
	 *        	Width in pixels
	 * @param int $height
	 *        	Height in pixels
	 * @param string $maptype
	 *        	Name of the map
	 * @param mixed $markers
	 *        	array of markers
	 * @param string $gpx
	 *        	GPX filename
	 * @param string $kml
	 *        	KML filename
	 * @param string $mediaDir
	 *        	Directory to store/cache maps
	 * @param string $tileCacheBaseDir
	 *        	Directory to cache map tiles
	 * @param boolean $autoZoomExtent
	 *        	Wheter or not to override zoom/lat/lon and zoom to the extent of gpx/kml and markers
	 * @param apikey
	 *          Some service require a key to access
	 */
	public function __construct($lat, $lon, $zoom, $width, $height, $maptype, $markers, $gpx, $kml, $geojson, $mediaDir, $tileCacheBaseDir, $autoZoomExtent = TRUE, $apikey = '') {
		$this->zoom = $zoom;
		$this->lat = $lat;
		$this->lon = $lon;
		$this->width = $width;
		$this->height = $height;
		// validate + set maptype
		$this->maptype = $this->tileDefaultSrc;
		if (array_key_exists ( $maptype, $this->tileInfo )) {
			$this->maptype = $maptype;
		}
		$this->markers = $markers;
		$this->kmlFileName = $kml;
		$this->gpxFileName = $gpx;
		$this->geojsonFileName = $geojson;
		$this->mediaBaseDir = $mediaDir;
		$this->tileCacheBaseDir = $tileCacheBaseDir . '/olmaptiles';
		$this->useTileCache = $this->tileCacheBaseDir !== '';
		$this->mapCacheBaseDir = $mediaDir . '/olmapmaps';
		$this->autoZoomExtent = $autoZoomExtent;
		$this->apikey = $apikey;
	}

	/**
	 *
	 * @param number $long
	 * @param number $zoom
	 * @return number
	 */
	public function lonToTile($long, $zoom) {
		return (($long + 180) / 360) * pow ( 2, $zoom );
	}
	/**
	 *
	 * @param number $lat
	 * @param number $zoom
	 * @return number
	 */
	public function latToTile($lat, $zoom) {
		return (1 - log ( tan ( $lat * pi () / 180 ) + 1 / cos ( $lat * M_PI / 180 ) ) / M_PI) / 2 * pow ( 2, $zoom );
	}

	/**
	 */
	public function initCoords() {
		$this->centerX = $this->lonToTile ( $this->lon, $this->zoom );
		$this->centerY = $this->latToTile ( $this->lat, $this->zoom );
		$this->offsetX = floor ( (floor ( $this->centerX ) - $this->centerX) * $this->tileSize );
		$this->offsetY = floor ( (floor ( $this->centerY ) - $this->centerY) * $this->tileSize );
	}

	/**
	 * make basemap image.
	 */
	public function createBaseMap() {
		$this->image = imagecreatetruecolor ( $this->width, $this->height );
		$startX = floor ( $this->centerX - ($this->width / $this->tileSize) / 2 );
		$startY = floor ( $this->centerY - ($this->height / $this->tileSize) / 2 );
		$endX = ceil ( $this->centerX + ($this->width / $this->tileSize) / 2 );
		$endY = ceil ( $this->centerY + ($this->height / $this->tileSize) / 2 );
		$this->offsetX = - floor ( ($this->centerX - floor ( $this->centerX )) * $this->tileSize );
		$this->offsetY = - floor ( ($this->centerY - floor ( $this->centerY )) * $this->tileSize );
		$this->offsetX += floor ( $this->width / 2 );
		$this->offsetY += floor ( $this->height / 2 );
		$this->offsetX += floor ( $startX - floor ( $this->centerX ) ) * $this->tileSize;
		$this->offsetY += floor ( $startY - floor ( $this->centerY ) ) * $this->tileSize;

		for($x = $startX; $x <= $endX; $x ++) {
			for($y = $startY; $y <= $endY; $y ++) {
				$url = str_replace ( array (
						'{Z}',
						'{X}',
						'{Y}'
				), array (
						$this->zoom,
						$x,
						$y
				), $this->tileInfo [$this->maptype] ['url'] );
				$url .= $this->apikey;
				
				$tileData = $this->fetchTile ( $url );
				if ($tileData) {
					$tileImage = imagecreatefromstring ( $tileData );
				} else {
					$tileImage = imagecreate ( $this->tileSize, $this->tileSize );
					$color = imagecolorallocate ( $tileImage, 255, 255, 255 );
					@imagestring ( $tileImage, 1, 127, 127, 'err', $color );
				}
				$destX = ($x - $startX) * $this->tileSize + $this->offsetX;
				$destY = ($y - $startY) * $this->tileSize + $this->offsetY;
				dbglog($this->tileSize,"imagecopy tile into image: $destX, $destY");
				imagecopy ( $this->image, $tileImage, $destX, $destY, 0, 0, $this->tileSize, $this->tileSize );
			}
		}
	}

	/**
	 * Place markers on the map and number them in the same order as they are listed in the html.
	 */
	public function placeMarkers() {
		$count = 0;
		$color = imagecolorallocate ( $this->image, 0, 0, 0 );
		$bgcolor = imagecolorallocate ( $this->image, 200, 200, 200 );
		$markerBaseDir = dirname ( __FILE__ ) . '/icons';
		// loop thru marker array
		foreach ( $this->markers as $marker ) {
			// set some local variables
			$markerLat = $marker ['lat'];
			$markerLon = $marker ['lon'];
			$markerType = $marker ['type'];
			// clear variables from previous loops
			$markerFilename = '';
			$markerShadow = '';
			$matches = false;
			// check for marker type, get settings from markerPrototypes
			if ($markerType) {
				foreach ( $this->markerPrototypes as $markerPrototype ) {
					if (preg_match ( $markerPrototype ['regex'], $markerType, $matches )) {
						$markerFilename = $matches [0] . $markerPrototype ['extension'];
						if ($markerPrototype ['offsetImage']) {
							list ( $markerImageOffsetX, $markerImageOffsetY ) = explode ( ",", $markerPrototype ['offsetImage'] );
						}
						$markerShadow = $markerPrototype ['shadow'];
						if ($markerShadow) {
							list ( $markerShadowOffsetX, $markerShadowOffsetY ) = explode ( ",", $markerPrototype ['offsetShadow'] );
						}
					}
				}
			}
			// create img resource
			if (file_exists ( $markerBaseDir . '/' . $markerFilename )) {
				$markerImg = imagecreatefrompng ( $markerBaseDir . '/' . $markerFilename );
			} else {
				$markerImg = imagecreatefrompng ( $markerBaseDir . '/marker.png' );
			}
			// check for shadow + create shadow recource
			if ($markerShadow && file_exists ( $markerBaseDir . '/' . $markerShadow )) {
				$markerShadowImg = imagecreatefrompng ( $markerBaseDir . '/' . $markerShadow );
			}
			// calc position
			$destX = floor ( ($this->width / 2) - $this->tileSize * ($this->centerX - $this->lonToTile ( $markerLon, $this->zoom )) );
			$destY = floor ( ($this->height / 2) - $this->tileSize * ($this->centerY - $this->latToTile ( $markerLat, $this->zoom )) );
			// copy shadow on basemap
			if ($markerShadow && $markerShadowImg) {
				imagecopy ( $this->image, $markerShadowImg, $destX + intval ( $markerShadowOffsetX ), $destY + intval ( $markerShadowOffsetY ), 0, 0, imagesx ( $markerShadowImg ), imagesy ( $markerShadowImg ) );
			}
			// copy marker on basemap above shadow
			imagecopy ( $this->image, $markerImg, $destX + intval ( $markerImageOffsetX ), $destY + intval ( $markerImageOffsetY ), 0, 0, imagesx ( $markerImg ), imagesy ( $markerImg ) );
			// add label
			imagestring ( $this->image, 3, $destX - imagesx ( $markerImg ) + 1, $destY + intval ( $markerImageOffsetY ) + 1, ++ $count, $bgcolor );
			imagestring ( $this->image, 3, $destX - imagesx ( $markerImg ), $destY + intval ( $markerImageOffsetY ), $count, $color );
		}
		;
	}

	/**
	 *
	 * @param string $url
	 * @return string
	 */
	public function tileUrlToFilename($url) {
		return $this->tileCacheBaseDir . "/" . str_replace ( array (
				'http://'
		), '', $url );
	}

	/**
	 *
	 * @param string $url
	 */
	public function checkTileCache($url) {
		$filename = $this->tileUrlToFilename ( $url );
		if (file_exists ( $filename )) {
			return file_get_contents ( $filename );
		}
	}
	public function checkMapCache() {
		$this->mapCacheID = md5 ( $this->serializeParams () );
		$filename = $this->mapCacheIDToFilename ();
		if (file_exists ( $filename ))
			return true;
	}
	public function serializeParams() {
		return join ( "&", array (
				$this->zoom,
				$this->lat,
				$this->lon,
				$this->width,
				$this->height,
				serialize ( $this->markers ),
				$this->maptype,
				$this->kmlFileName,
				$this->gpxFileName,
				$this->geojsonFileName
		) );
	}
	public function mapCacheIDToFilename() {
		if (! $this->mapCacheFile) {
			$this->mapCacheFile = $this->mapCacheBaseDir . "/" . $this->maptype . "/" . $this->zoom . "/cache_" . substr ( $this->mapCacheID, 0, 2 ) . "/" . substr ( $this->mapCacheID, 2, 2 ) . "/" . substr ( $this->mapCacheID, 4 );
		}
		return $this->mapCacheFile . "." . $this->mapCacheExtension;
	}

	/**
	 * Recursively create the directory.
	 *
	 * @param string $pathname
	 *        	The directory path.
	 * @param int $mode
	 *        	File access mode. For more information on modes, read the details on the chmod manpage.
	 */
	public function mkdir_recursive($pathname, $mode) {
		is_dir ( dirname ( $pathname ) ) || $this->mkdir_recursive ( dirname ( $pathname ), $mode );
		return is_dir ( $pathname ) || @mkdir ( $pathname, $mode );
	}

	/**
	 * Write a tile into the cache.
	 *
	 * @param string $url
	 * @param mixed $data
	 */
	public function writeTileToCache($url, $data) {
		$filename = $this->tileUrlToFilename ( $url );
		$this->mkdir_recursive ( dirname ( $filename ), 0777 );
		file_put_contents ( $filename, $data );
	}

	/**
	 * Fetch a tile and (if configured) store it in the cache.
	 *
	 * @param string $url
	 */
	public function fetchTile($url) {
		if ($this->useTileCache && ($cached = $this->checkTileCache ( $url )))
			return $cached;

		$_UA = 'Mozilla/4.0 (compatible; DokuWikiSpatial HTTP Client; ' . PHP_OS . ')';
		if (function_exists ( "curl_init" )) {
			// use cUrl
			$ch = curl_init ();
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt ( $ch, CURLOPT_USERAGENT, $_UA );
			curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
			curl_setopt ( $ch, CURLOPT_URL, $url );
			$tile = curl_exec ( $ch );
			curl_close ( $ch );
		} else {
			// use file_get_contents
			global $conf;
			$opts = array (
					'http' => array (
							'method' => "GET",
							'header' => "Accept-language: en\r\n" . "User-Agent: $_UA\r\n" . "accept: image/png\r\n",
							'proxy' => "tcp://" . $conf ['proxy'] ['host'] . ":" . $conf ['proxy'] ['port'],
							'request_fulluri' => true
					)
			);
			$context = stream_context_create ( $opts );
			$tile = file_get_contents ( $url, false, $context );
		}
		if ($tile && $this->useTileCache) {
			$this->writeTileToCache ( $url, $tile );
		}
		return $tile;
	}

	/**
	 * Draw gpx trace on the map.
	 */
	public function drawGPX() {
		$col = imagecolorallocatealpha ( $this->image, 0, 0, 255, .4 * 127 );
		$gpxgeom = geoPHP::load ( file_get_contents ( $this->gpxFileName ), 'gpx' );
		$this->drawGeometry ( $gpxgeom, $col );
	}

	/**
	 * Draw geojson on the map.
	 */
	public function drawGeojson() {
		$col = imagecolorallocatealpha ( $this->image, 255, 0, 255, .4 * 127 );
		$gpxgeom = geoPHP::load ( file_get_contents ( $this->geojsonFileName ), 'json' );
		$this->drawGeometry ( $gpxgeom, $col );
	}

	/**
	 * Draw kml trace on the map.
	 */
	public function drawKML() {
		// TODO get colour from kml node (not currently supported in geoPHP)
		$col = imagecolorallocatealpha ( $this->image, 255, 0, 0, .4 * 127 );
		$kmlgeom = geoPHP::load ( file_get_contents ( $this->kmlFileName ), 'kml' );
		$this->drawGeometry ( $kmlgeom, $col );
	}

	/**
	 * Draw geometry or geometry collection on the map.
	 *
	 * @param Geometry $geom
	 * @param int $colour
	 *        	drawing colour
	 */
	private function drawGeometry($geom, $colour) {
		switch ($geom->geometryType ()) {
			case 'GeometryCollection' :
				// recursively draw part of the collection
				for($i = 1; $i < $geom->numGeometries () + 1; $i ++) {
					$_geom = $geom->geometryN ( $i );
					$this->drawGeometry ( $_geom, $colour );
				}
				break;
			case 'MultiPolygon' :
				// TODO implement / do nothing
				break;
			case 'MultiLineString' :
				// TODO implement / do nothing
				break;
			case 'MultiPoint' :
				// TODO implement / do nothing
				break;
			case 'Polygon' :
				$this->drawPolygon ( $geom, $colour );
				break;
			case 'LineString' :
				$this->drawLineString ( $geom, $colour );
				break;
			case 'Point' :
				$this->drawPoint ( $geom, $colour );
				break;
			default :
				// draw nothing
				break;
		}
	}

	/**
	 * Draw a line on the map.
	 *
	 * @param LineString $line
	 * @param int $colour
	 *        	drawing colour
	 */
	private function drawLineString($line, $colour) {
		imagesetthickness ( $this->image, 2 );
		for($p = 1; $p < $line->numGeometries (); $p ++) {
			// get first pair of points
			$p1 = $line->geometryN ( $p );
			$p2 = $line->geometryN ( $p + 1 );
			// translate to paper space
			$x1 = floor ( ($this->width / 2) - $this->tileSize * ($this->centerX - $this->lonToTile ( $p1->x (), $this->zoom )) );
			$y1 = floor ( ($this->height / 2) - $this->tileSize * ($this->centerY - $this->latToTile ( $p1->y (), $this->zoom )) );
			$x2 = floor ( ($this->width / 2) - $this->tileSize * ($this->centerX - $this->lonToTile ( $p2->x (), $this->zoom )) );
			$y2 = floor ( ($this->height / 2) - $this->tileSize * ($this->centerY - $this->latToTile ( $p2->y (), $this->zoom )) );
			// draw to image
			imageline ( $this->image, $x1, $y1, $x2, $y2, $colour );
		}
		imagesetthickness ( $this->image, 1 );
	}

	/**
	 * Draw a point on the map.
	 *
	 * @param Point $point
	 * @param int $colour
	 *        	drawing colour
	 */
	private function drawPoint($point, $colour) {
		imagesetthickness ( $this->image, 2 );
		// translate to paper space
		$cx = floor ( ($this->width / 2) - $this->tileSize * ($this->centerX - $this->lonToTile ( $point->x (), $this->zoom )) );
		$cy = floor ( ($this->height / 2) - $this->tileSize * ($this->centerY - $this->latToTile ( $point->y (), $this->zoom )) );
		$r = 5;
		// draw to image
		// imageellipse($this->image, $cx, $cy,$r, $r, $colour);
		imagefilledellipse ( $this->image, $cx, $cy, $r, $r, $colour );
		// don't use imageellipse because the imagesetthickness function has
		// no effect. So the better workaround is to use imagearc.
		imagearc ( $this->image, $cx, $cy, $r, $r, 0, 359, $colour );
		imagesetthickness ( $this->image, 1 );
	}

	/**
	 * Draw a polygon on the map.
	 *
	 * @param Polygon $polygon
	 * @param int $colour
	 *        	drawing colour
	 */
	private function drawPolygon($polygon, $colour) {
		// TODO implementation of drawing holes,
		// maybe draw the polygon to an in-memory image and use imagecopy, draw polygon in col., draw holes in bgcol?

		// print_r('Polygon:<br />');
		// print_r($polygon);
		$extPoints = array ();
		// extring is a linestring actually..
		$extRing = $polygon->exteriorRing ();

		for($i = 1; $i < $extRing->numGeometries (); $i ++) {
			$p1 = $extRing->geometryN ( $i );
			$x = floor ( ($this->width / 2) - $this->tileSize * ($this->centerX - $this->lonToTile ( $p1->x (), $this->zoom )) );
			$y = floor ( ($this->height / 2) - $this->tileSize * ($this->centerY - $this->latToTile ( $p1->y (), $this->zoom )) );
			$extPoints [] = $x;
			$extPoints [] = $y;
		}
		// print_r('points:('.($i-1).')<br />');
		// print_r($extPoints);
		// imagepolygon ($this->image, $extPoints, $i-1, $colour );
		imagefilledpolygon ( $this->image, $extPoints, $i - 1, $colour );
	}

	/**
	 * add copyright and origin notice and icons to the map.
	 */
	public function drawCopyright() {
		$logoBaseDir = dirname ( __FILE__ ) . '/' . 'logo/';
		$logoImg = imagecreatefrompng ( $logoBaseDir . $this->tileInfo ['openstreetmap'] ['logo'] );
		$textcolor = imagecolorallocate ( $this->image, 0, 0, 0 );
		$bgcolor = imagecolorallocate ( $this->image, 200, 200, 200 );

		imagecopy ( $this->image, $logoImg, 0, imagesy ( $this->image ) - imagesy ( $logoImg ), 0, 0, imagesx ( $logoImg ), imagesy ( $logoImg ) );
		imagestring ( $this->image, 1, imagesx ( $logoImg ) + 2, imagesy ( $this->image ) - imagesy ( $logoImg ) + 1, $this->tileInfo ['openstreetmap'] ['txt'], $bgcolor );
		imagestring ( $this->image, 1, imagesx ( $logoImg ) + 1, imagesy ( $this->image ) - imagesy ( $logoImg ), $this->tileInfo ['openstreetmap'] ['txt'], $textcolor );

		// additional tile source info, ie. who created/hosted the tiles
		if ($this->maptype != 'openstreetmap') {
			$iconImg = imagecreatefrompng ( $logoBaseDir . $this->tileInfo [$this->maptype] ['logo'] );
			imagecopy ( $this->image, $iconImg, imagesx ( $logoImg ) + 1, imagesy ( $this->image ) - imagesy ( $iconImg ), 0, 0, imagesx ( $iconImg ), imagesy ( $iconImg ) );
			imagestring ( $this->image, 1, imagesx ( $logoImg ) + imagesx ( $iconImg ) + 4, imagesy ( $this->image ) - ceil ( imagesy ( $logoImg ) / 2 ) + 1, $this->tileInfo [$this->maptype] ['txt'], $bgcolor );
			imagestring ( $this->image, 1, imagesx ( $logoImg ) + imagesx ( $iconImg ) + 3, imagesy ( $this->image ) - ceil ( imagesy ( $logoImg ) / 2 ), $this->tileInfo [$this->maptype] ['txt'], $textcolor );
		}
	}

	/**
	 * make the map.
	 */
	public function makeMap() {
		$this->initCoords ();
		$this->createBaseMap ();
		if (! empty ( $this->markers ))
			$this->placeMarkers ();
		if (file_exists ( $this->kmlFileName ))
			$this->drawKML ();
		if (file_exists ( $this->gpxFileName ))
			$this->drawGPX ();
		if (file_exists ( $this->geojsonFileName ))
			$this->drawGeojson ();

		$this->drawCopyright ();
	}

	/**
	 * Calculate the lat/lon/zoom values to make sure that all of the markers and gpx/kml are on the map.
	 * can throw an error like
	 * "Fatal error: Uncaught Exception: Cannot create a collection with non-geometries in
	 * D:\www\wild-water.nl\www\dokuwiki\lib\plugins\geophp\geoPHP\lib\geometry\Collection.class.php:29"
	 *
	 * @param float $paddingFactor
	 *        	buffer constant to enlarge (>1.0) the zoom level
	 */
	private function autoZoom($paddingFactor = 1.0) {
		$geoms = array ();
		$geoms [] = new Point ( $this->lon, $this->lat );
		if (! empty ( $this->markers )) {
			foreach ( $this->markers as $marker ) {
				$geoms [] = new Point ( $marker ['lon'], $marker ['lat'] );
			}
		}
		$g = FALSE;
		if (file_exists ( $this->kmlFileName )) {
			$g = geoPHP::load ( file_get_contents ( $this->kmlFileName ), 'kml' );
			if($g !== FALSE) {
				$geoms [] = $g;
			}
		}
		if (file_exists ( $this->gpxFileName )) {
			$g = geoPHP::load ( file_get_contents ( $this->gpxFileName ), 'gpx' );
			if($g !== FALSE) {
				$geoms [] = $g;
			}
		}
		if (file_exists ( $this->geojsonFileName )) {
			$g = geoPHP::load ( file_get_contents ( $this->geojsonFileName ), 'geojson' );
			if($g !== FALSE) {
				$geoms [] = $g;
			}
		}

		if (count ( $geoms ) <= 1) {
			dbglog($geoms,"StaticMap::autoZoom: Skip setting autozoom options");
			return;
		}

		$geom = new GeometryCollection ( $geoms );
		$centroid = $geom->centroid ();
		$bbox = $geom->getBBox ();

		// determine vertical resolution, this depends on the distance from the equator
		// $vy00 = log(tan(M_PI*(0.25 + $centroid->getY()/360)));
		$vy0 = log ( tan ( M_PI * (0.25 + $bbox ['miny'] / 360) ) );
		$vy1 = log ( tan ( M_PI * (0.25 + $bbox ['maxy'] / 360) ) );
		dbglog("StaticMap::autoZoom: vertical resolution: $vy0, $vy1");
		$zoomFactorPowered = ($this->height / 2) / (40.7436654315252 * ($vy1 - $vy0));
		$resolutionVertical = 360 / ($zoomFactorPowered * $this->tileSize);
		// determine horizontal resolution
		$resolutionHorizontal = ($bbox ['maxx'] - $bbox ['minx']) / $this->width;
		$resolution = max ( $resolutionHorizontal, $resolutionVertical ) * $paddingFactor;
		$zoom = log ( 360 / ($resolution * $this->tileSize), 2 );

		if (is_finite($zoom) && $zoom < 15 && $zoom > 2) {
			$this->zoom = floor ( $zoom );
		}
		$this->lon = $centroid->getX ();
		$this->lat = $centroid->getY ();
		dbglog("StaticMap::autoZoom: Set autozoom options to: z: $this->zoom, lon: $this->lon, lat: $this->lat");
	}

	/**
	 * get the map, this may return a reference to a cached copy.
	 *
	 * @return string url relative to media dir
	 */
	public function getMap() {
		try {
			if ($this->autoZoomExtent) {
				$this->autoZoom ();
			}
		} catch (Exception $e) {
			dbglog($e);
		}

			// use map cache, so check cache for map
		if (! $this->checkMapCache ()) {
			// map is not in cache, needs to be build
			$this->makeMap ();
			$this->mkdir_recursive ( dirname ( $this->mapCacheIDToFilename () ), 0777 );
			imagepng ( $this->image, $this->mapCacheIDToFilename (), 9 );
		}
		$this->doc = $this->mapCacheIDToFilename ();
		// make url relative to media dir
		return str_replace ( $this->mediaBaseDir, '', $this->doc );
	}
}
