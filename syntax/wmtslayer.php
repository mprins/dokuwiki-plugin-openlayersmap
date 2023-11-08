<?php

use dokuwiki\Extension\SyntaxPlugin;

/*
 * Copyright (c) 2023 Mark C. Prins <mprins@users.sf.net>
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
 * adds a WMTS 1.0.0 layer to your map.
 */
class syntax_plugin_openlayersmap_wmtslayer extends SyntaxPlugin
{
    private $dflt = ['id' => 'olmap', 'name' => '', 'url' => '', 'opacity' => 0.8, 'attribution' => '', 'visible' => false, 'layer' => '', 'matrixSet' => '', 'transparent' => 'true', 'baselayer' => 'false'];

    /**
     * (non-PHPdoc)
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    final public function getPType(): string
    {
        return 'block';
    }

    /**
     * (non-PHPdoc)
     *
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    final public function getType(): string
    {
        return 'baseonly';
    }

    /**
     * (non-PHPdoc)
     *
     * @see Doku_Parser_Mode::getSort()
     */
    final public function getSort(): int
    {
        return 902;
    }

    /**
     * Connect to our special pattern.
     *
     * @see Doku_Parser_Mode::connectTo()
     */
    final public function connectTo($mode): void
    {
        // look for: <olmap_wmstlayer id="olmap" name="geolandbasemap" url="https://mapsneu.wien.gv.at/basemapneu/1.0.0/WMTSCapabilities.xml"
        // attribution="basemap.at" visible="true" layer="geolandbasemap" matrixSet=google3857></olmap_wmtslayer>
        $this->Lexer->addSpecialPattern(
            '<olmap_wmtslayer ?[^>\n]*>.*?</olmap_wmtslayer>',
            $mode,
            'plugin_openlayersmap_wmtslayer'
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see DokuWiki_Syntax_Plugin::handle()
     */
    final public function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        $param = [];
        $data = $this->dflt;

        preg_match_all('/(\w*)="(.*?)"/us', $match, $param, PREG_SET_ORDER);

        foreach ($param as $kvpair) {
            [$matched, $key, $val] = $kvpair;
            if (isset($data [$key])) {
                $key = strtolower($key);
                $data [$key] = $val;
            }
        }
        return $data;
    }

    /**
     * (non-PHPdoc)
     *
     * @see DokuWiki_Syntax_Plugin::render()
     */
    final public function render($format, Doku_Renderer $renderer, $data): bool
    {
        if ($format !== 'xhtml') {
            return false;
        }

        // incremented for each olmap_wmtslayer tag in the page source
        static $overlaynumber = 0;

        $renderer->doc .= DOKU_LF . '<script defer="defer" src="data:text/javascript;base64,';
        $str = '{';
        foreach ($data as $key => $val) {
            $str .= "'" . $key . "':'" . $val . "', ";
        }
        $str .= "'type':'wmts'}";
        $renderer->doc .= base64_encode("olMapOverlays['wmts" . $overlaynumber . "'] = " . $str . ";")
            . '"></script>';
        $overlaynumber++;
        return true;
    }
}
