<?php
/*
 * Copyright (c) 2016 Mark C. Prins <mprins@users.sf.net>
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
if (! defined ( 'DOKU_INC' ))
	die ();

if (! defined ( 'DOKU_LF' ))
	define ( 'DOKU_LF', "\n" );
if (! defined ( 'DOKU_TAB' ))
	define ( 'DOKU_TAB', "\t" );
if (! defined ( 'DOKU_PLUGIN' ))
	define ( 'DOKU_PLUGIN', DOKU_INC . 'lib/plugins/' );

require_once DOKU_PLUGIN . 'syntax.php';
/**
 * adds a WMS 1.1.1 layer to your map.
 */
class syntax_plugin_openlayersmapoverlays_searchlayer extends DokuWiki_Syntax_Plugin {
	private $dflt = array (
			'id' => 'olmap',
			'name' => '',
			'search' => '',
			'opacity' => 0.8,
			'attribution' => '',
			'visible' => false,
			'layers' => '',
			'version' => '1.1.1',
			'format' => 'image/png',
			'transparent' => 'true'
	);

	/**
	 * (non-PHPdoc)
	 *
	 * @see DokuWiki_Syntax_Plugin::getPType()
	 */
	public function getPType() {
		return 'block';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DokuWiki_Syntax_Plugin::getType()
	 */
	public function getType() {
		// return 'FIXME: container|baseonly|formatting|substition|protected|disabled|paragraphs';
		return 'baseonly';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Doku_Parser_Mode::getSort()
	 */
	public function getSort() {
		return 902;
	}

	/**
	 * Connect to our special pattern.
	 *
	 * @see Doku_Parser_Mode::connectTo()
	 */
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern ( '<olmap_searchlayer ?[^>\n]*>.*?</olmap_searchlayer>',
				$mode, 'plugin_openlayersmapoverlays_searchlayer' );
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DokuWiki_Syntax_Plugin::handle()
	 */
	public function handle($match, $state, $pos, Doku_Handler $handler) {
		$param = array ();
		$data = $this->dflt;

		preg_match_all ( '/(\w*)="(.*?)"/us', $match, $param, PREG_SET_ORDER );

		foreach ( $param as $kvpair ) {
			list ( $matched, $key, $val ) = $kvpair;
			if (isset ( $data [$key] )) {
				$key = strtolower ( $key );
				$data [$key] = $val;
			}
		}
		dbglog($data,'syntax_plugin_overlayer::handle: parsed data is:');
		return $data;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see DokuWiki_Syntax_Plugin::render()
	 */
	public function render($mode, Doku_Renderer $renderer, $data) {
		if ($mode != 'xhtml')
			return false;

		static $loadedOLlib = false;
		if (! $loadedOLlib) {
			$renderer->doc .= DOKU_LF . '<script type="text/javascript" src="' . DOKU_BASE . 'lib/plugins/openlayersmapoverlays/lib/layers.js' . '"></script>';
			$loadedOLlib = true;
		}
		// incremented for each olmap_wmslayer tag in the page source
		static $overlaynumber = 0;

		list ( $id, $url, $name, $visible ) = $data;
		$renderer->doc .= DOKU_LF . "<script type='text/javascript'><!--//--><![CDATA[//><!--" . DOKU_LF;
		$str = '{';
		foreach ( $data as $key => $val ) {
			$str .= "'" . $key . "' : '" . $val . "',";
		}
		$str .= "'type':'wms'}";
		$renderer->doc .= "olMapOverlays['wms" . $overlaynumber . "'] = " . $str . ";" . DOKU_LF . "//--><!]]></script>";
		$overlaynumber ++;
		return true;
	}
}
