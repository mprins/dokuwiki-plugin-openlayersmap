<?php

use dokuwiki\Extension\SyntaxPlugin;

/*
 * Copyright (c) 2012-2020 Mark C. Prins <mprins@users.sf.net>
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
 * adds a WMS 1.3.0 layer to your map.
 */
class syntax_plugin_openlayersmap_wmslayer extends SyntaxPlugin
{
    private $dflt = ['id'          => 'olmap', 'name'        => '', 'url'         => '', 'opacity'     => 0.8, 'attribution' => '', 'visible'     => false, 'layers'      => '', 'version'     => '1.3.0', 'format'      => 'image/png', 'transparent' => 'true', 'baselayer'   => 'false'];

    /**
     * (non-PHPdoc)
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    public function getPType(): string
    {
        return 'block';
    }

    /**
     * (non-PHPdoc)
     *
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    public function getType(): string
    {
        // return 'FIXME: container|baseonly|formatting|substition|protected|disabled|paragraphs';
        return 'baseonly';
    }

    /**
     * (non-PHPdoc)
     *
     * @see Doku_Parser_Mode::getSort()
     */
    public function getSort(): int
    {
        return 902;
    }

    /**
     * Connect to our special pattern.
     *
     * @see Doku_Parser_Mode::connectTo()
     */
    public function connectTo($mode): void
    {
        // look for: <olmap_wmslayer id="olmap" name="cloud" url="http://openweathermap.org/t/tile.cgi?SERVICE=WMS"
        // attribution="OpenWeatherMap" visible="true" layers="GLBETA_PR"></olmap_wmslayer>
        $this->Lexer->addSpecialPattern(
            '<olmap_wmslayer ?[^>\n]*>.*?</olmap_wmslayer>',
            $mode,
            'plugin_openlayersmap_wmslayer'
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see DokuWiki_Syntax_Plugin::handle()
     */
    public function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        $param = [];
        $data  = $this->dflt;

        preg_match_all('/(\w*)="(.*?)"/us', $match, $param, PREG_SET_ORDER);

        foreach ($param as $kvpair) {
            [$matched, $key, $val] = $kvpair;
            if (isset($data [$key])) {
                $key         = strtolower($key);
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
    public function render($format, Doku_Renderer $renderer, $data): bool
    {
        if ($format !== 'xhtml') {
            return false;
        }

        // incremented for each olmap_wmslayer tag in the page source
        static $overlaynumber = 0;

        $renderer->doc .= DOKU_LF . '<script defer="defer" src="data:text/javascript;base64,';
        $str           = '{';
        foreach ($data as $key => $val) {
            $str .= "'" . $key . "' : '" . $val . "',";
        }
        $str           .= "'type':'wms'}";
        $renderer->doc .= base64_encode("olMapOverlays['wms" . $overlaynumber . "'] = " . $str . ";")
            . '"></script>';
        $overlaynumber++;
        return true;
    }
}
