<?php
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
 * Add OSM style layer to your map.
 */
class syntax_plugin_openlayersmap_osmlayer extends DokuWiki_Syntax_Plugin
{
    private $dflt = array(
        'id'          => 'olmap',
        'name'        => '',
        'url'         => '',
        'opacity'     => 0.8,
        'attribution' => '',
        'visible'     => false,
        'cors'        => null,
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
        return 902;
    }

    /**
     * Connect to our special pattern.
     *
     * @see Doku_Parser_Mode::connectTo()
     */
    public function connectTo($mode): void
    {
        // look for: <olmap_osmlayer id="olmap" name="sport" url="http://tiles.openseamap.org/sport/${z}/${x}/${y}.png"
        // visible="false" opacity=0.6 attribution="Some attribution"></olmap_osmlayer>
        $this->Lexer->addSpecialPattern(
            '<olmap_osmlayer ?[^>\n]*>.*?</olmap_osmlayer>',
            $mode,
            'plugin_openlayersmap_osmlayer'
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
        // dbglog($data,'syntax_plugin_overlayer::handle: parsed data is:');
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

        // incremented for each olmap_osmlayer tag in the page source
        static $overlaynumber = 0;

        $renderer->doc .= DOKU_LF . '<script defer="defer" src="data:text/javascript;base64,';
        $str           = '{';
        foreach ($data as $key => $val) {
            $str .= "'" . $key . "' : '" . $val . "',";
        }
        $str           .= '"type":"osm"}';
        $renderer->doc .= base64_encode("olMapOverlays['osm" . $overlaynumber . "'] = " . $str . ";")
            . '"></script>';
        $overlaynumber++;
        return true;
    }
}
