<?php
/*
 * Copyright (c) 2008-2015 Mark C. Prins <mprins@users.sf.net>
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
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'admin.php';
/**
 * DokuWiki Plugin openlayersmap (Admin Component).
 * This component purges the cached tiles and maps.
 *
 * @author Mark Prins
 */
class admin_plugin_openlayersmap_purge extends DokuWiki_Admin_Plugin {
	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Admin_Plugin::getMenuSort()
	 */
	public function getMenuSort() {
		return 800;
	}
	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Admin_Plugin::forAdminOnly()
	 */
	public function forAdminOnly() {
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Admin_Plugin::handle()
	 */
	public function handle() {
		global $conf;
		if(!isset($_REQUEST['continue']) || !checkSecurityToken()) return;
		if(isset($_REQUEST['purgetiles'])){
			$path = $conf['cachedir'].'/olmaptiles';
			if ($this->rrmdir($path)) msg($this->getLang('admin_purged_tiles'),0);
		}
		if(isset($_REQUEST['purgemaps'])){
			$path = $conf['mediadir'].'/olmapmaps';
			if ($this->rrmdir($path)) msg($this->getLang('admin_purged_maps'),0);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Admin_Plugin::html()
	 */
	public function html() {
		echo $this->locale_xhtml('admin_intro');
		$form = new Doku_Form(array('id'=>'olmap_purgeform', 'method'=>'post'));
		$form->addHidden('continue','go');

		$form->startFieldset($this->getLang('admin_tiles'));
		$form->addElement('<p>');
		$form->addElement('<input id="purgetiles" name="purgetiles" type="checkbox" value="1" class="checkbox" />');
		$form->addElement('<label for="purgetiles" class="label">'.$this->getLang('admin_purge_tiles').'</label>');
		$form->addElement('</p>');
		$form->endFieldset();

		$form->startFieldset($this->getLang('admin_maps'));
		$form->addElement('<p>');
		$form->addElement('<input id="purgemaps" name="purgemaps" type="checkbox" value="1" class="checkbox" />');
		$form->addElement('<label for="purgemaps" class="label">'.$this->getLang('admin_purge_maps').'</label>');
		$form->addElement('</p>');
		$form->endFieldset();

		$form->addElement(form_makeButton('submit','admin',$this->getLang('admin_submit'),
				array('accesskey'=>'p','title'=>$this->getLang('admin_submit'))));
		$form->printForm();
	}

	/**
	 * Recursively delete the directory.
	 * @param string $sDir directory path
	 * @return boolean true when succesful
	 */
	private function rrmdir($sDir) {
		if (is_dir($sDir)) {
			dbglog($sDir, 'admin_plugin_openlayersmap_purge::rrmdir: recursively removing path: ');
			$sDir = rtrim($sDir, '/');
			$oDir = dir($sDir);
			while (($sFile = $oDir->read()) !== false) {
				if ($sFile != '.' && $sFile != '..') {
					(!is_link("$sDir/$sFile") && is_dir("$sDir/$sFile")) ? $this->rrmdir("$sDir/$sFile") : unlink("$sDir/$sFile");
				}
			}
			$oDir->close();
			rmdir($sDir);
			return true;
		}
		return false;
	}
}
