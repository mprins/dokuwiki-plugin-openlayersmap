<?php
/*
 * Copyright (c) 2008 Mark C. Prins <mprins@users.sf.net>
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
 *
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 */

/**
 * Action for Plugin OL Maps: Allow Display of a OpenLayers Map in a wiki page.
 * @author Mark Prins
 */
class action_plugin_openlayersmap extends DokuWiki_Action_Plugin
{
    /**
     * Plugin uses this method to register its handlers with the DokuWiki's event controller.
     *
     * @param $controller DokuWiki's event controller object. Also available as global $EVENT_HANDLER
     */
    final public function register(Doku_Event_Handler $controller): void
    {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insertButton');
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'insertCSSSniffer');
        $controller->register_hook('PLUGIN_POPULARITY_DATA_SETUP', 'AFTER', $this, 'popularity');
    }

    /**
     * Inserts the toolbar button.
     * @param Doku_Event $event the DokuWiki event
     */
    final public function insertButton(Doku_Event $event): void
    {
        $strOpen = '<olmap id="olMapOne" width="550px" height="450px" lat="50.0" ';
        $strOpen .= 'lon="5.1" zoom="12" controls="1" ';
        $strOpen .= 'baselyr="OpenStreetMap" gpxfile="" kmlfile="" geojsonfile="" summary="" >\n';
        $strOpen .= '~~ Plugin olmap help.\n';
        $strOpen .= '~~ Required in the above tag are values for: id (unique on this page), width, heigth.\n';
        $strOpen .= '~~ Also you will want to enter zoomlevel and lat, lon values that make sense for where you';
        $strOpen .= '~~ want the map to start.\n\n';
        $strOpen .= '~~ Below is an example of a POI, you can add as many as you want. ';
        $strOpen .= '~~ More examples: https://dokuwiki.org/plugin:openlayersmap \n';
        $event->data[] = array(
            'type' => 'format',
            'title' => $this->getLang('openlayersmap'),
            'icon' => '../../plugins/openlayersmap/toolbar/map.png',
            'open' => $strOpen,
            'sample' => '50.0117,5.1287,-90,.8,marker-green.png,Pont de Barbouillons; Daverdisse \\\\ external link: 
                        [[https://test.com|test.com]] \\\\ internal link: [[::start]]\\\\ **DW Formatting** \n',
            'close' => '</olmap>\n',
        );
    }

    /**
     * Add a snippet of javascript into the head to do a css operation we can check for later on.
     * @param Doku_Event $event the DokuWiki event
     */
    final public function insertCSSSniffer(Doku_Event $event): void
    {
        $event->data["script"][] = array("_data" => "document.documentElement.className += ' olCSSsupported';");
    }

    /**
     * Add openlayersmap popularity data.
     *
     * @param Doku_Event $event the DokuWiki event
     */
    final public function popularity(Doku_Event $event): void
    {
        $versionInfo = getVersionData();
        $plugin_info = $this->getInfo();
        $event->data['openlayersmap']['version'] = $plugin_info['date'];
        $event->data['openlayersmap']['dwversion'] = $versionInfo['date'];
        $event->data['openlayersmap']['combinedversion'] = $versionInfo['date'] . '_' . $plugin_info['date'];
    }
}
