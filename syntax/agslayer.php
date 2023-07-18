<?php
/*
 * Copyright (c) 2017-2020 Mark C. Prins <mprins@users.sf.net>
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
 * adds a AGS layer to your map.
 */
class syntax_plugin_openlayersmap_agslayer extends DokuWiki_Syntax_Plugin
{
    private $dflt = array(
        'id'          => 'olmap',
        'name'        => '',
        'url'         => '',
        'opacity'     => 0.8,
        'attribution' => '',
        'visible'     => false,
        'layers'      => '',
        'format'      => 'png',
        'transparent' => 'true',
        'baselayer'   => 'false',
    );

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
        return 904;
    }

    /**
     * Connect to our special pattern.
     *
     * @see Doku_Parser_Mode::connectTo()
     */
    public function connectTo($mode): void
    {
        // look for: <olmap_agslayer id="olmap" name="cloud"
        // url="http://geoservices2.wallonie.be/arcgis/rest/services/APP_KAYAK/KAYAK/MapServer/export"
        // attribution="wallonie.be" visible="true" layers="show:0,1,2,3,4,7"></olmap_agslayer>
        // sample:
        // http://geoservices2.wallonie.be/arcgis/rest/services/APP_KAYAK/KAYAK/MapServer/export?LAYERS=show%3A0%2C1%2C2%2C3%2C4%2C7&TRANSPARENT=true&FORMAT=png&BBOX=643294.029959%2C6467184.088252%2C645740.014863%2C6469630.073157&SIZE=256%2C256&F=html&BBOXSR=3857&IMAGESR=3857
        $this->Lexer->addSpecialPattern(
            '<olmap_agslayer ?[^>\n]*>.*?</olmap_agslayer>',
            $mode,
            'plugin_openlayersmap_agslayer'
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see DokuWiki_Syntax_Plugin::handle()
     */
    public function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        $param = array();
        $data  = $this->dflt;

        preg_match_all('/(\w*)="(.*?)"/us', $match, $param, PREG_SET_ORDER);

        foreach ($param as $kvpair) {
            list ($matched, $key, $val) = $kvpair;
            if (isset ($data [$key])) {
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

        // incremented for each olmap_agslayer tag in the page source
        static $overlaynumber = 0;

        $renderer->doc .= DOKU_LF . '<script defer="defer" src="data:text/javascript;base64,';
        $str           = '{';
        foreach ($data as $key => $val) {
            $str .= "'" . $key . "' : '" . $val . "',";
        }
        $str           .= "'type':'ags'}";
        $renderer->doc .= base64_encode("olMapOverlays['ags" . $overlaynumber . "'] = " . $str . ";")
            . '"></script>';
        $overlaynumber++;
        return true;
    }
}
