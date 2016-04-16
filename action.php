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

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');
/**
 * Plugin OL Maps: Allow Display of a OpenLayers Map in a wiki page.
 * Toolbar button.
 * @author Mark Prins
 */
class action_plugin_openlayersmap extends DokuWiki_Action_Plugin {

	/**
	 * plugin should use this method to register its handlers with the DokuWiki's event controller
	 *
	 * @param    $controller   DokuWiki's event controller object. Also available as global $EVENT_HANDLER
	 */
	function register(Doku_Event_Handler $controller) {
		$controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array ());
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'insertCSSSniffer');
	}

	/**
	 * Inserts the toolbar button.
	 * @param Doku_Event $event the DokuWiki event
	 */
	function insert_button(Doku_Event $event, $param) {
		$strOpen ='<olmap id="olMapOne" width="550px" height="450px" lat="50.0" ';
		$strOpen.='lon="5.1" zoom="12" statusbar="1" controls="1" poihoverstyle="0" ';
		$strOpen.='baselyr="OpenStreetMap" gpxfile="" kmlfile="" geojsonfile="" summary="" >\n';
		$strOpen.='~~ Plugin olmap help.\n';
		$strOpen.='~~ Required in the above tag are values for: id (unique on this page), width, heigth.\n';
		$strOpen.='~~ Also you will want to enter zoomlevel and lat, lon values that make sense for where you want the map to start.\n\n';
		$strOpen.='~~ Below is an example of a POI, you can add as many as you want. ';
		$strOpen.='~~ More examples: http://dokuwiki.org/plugin:openlayersmap \n';
		$event->data[] = array (
			'type' => 'format',
			'title' => $this->getLang('openlayersmap'),
			'icon' => '../../plugins/openlayersmap/toolbar/map.png',
			'open' => $strOpen,
			'sample' => '50.0117,5.1287,-90,.8,marker-green.png,Pont de Barbouillons; Daverdisse \\\\ external link: [[http://test.com|test.com]] \\\\ internal link: [[::start]]\\\\ **DW Formatting** \n',
			'close' => '</olmap>\n',
		);
	}
	/** add a snippet of javascript into the head to do a css operation we can check for lateron.*/
	function insertCSSSniffer(Doku_Event $event, $param) {
		$event->data["script"][] = array (
						"type" => "text/javascript",
						"_data" => "document.documentElement.className += ' olCSSsupported';",
		);
	}
}
