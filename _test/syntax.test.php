<?php
/*
* Copyright (c) 2022 Mark C. Prins <mprins@users.sf.net>
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

/**
 * Syntax tests for the openlayersmap plugin.
 *
 * @group plugin_openlayersmap
 * @group plugins
 */
class syntax_plugin_openlayersmap_test extends DokuWikiTest {
    protected $pluginsEnabled = array('openlayersmap', 'geophp');

    /**
     * copy data and add pages to the index.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $conf;
        $conf['allowdebug'] = 1;
        $conf['dontlog']    = '';
        $conf['cachetime']  = -1;

        $conf['plugin']['openlayersmap']['displayformat']            = 'DD';
        $conf['plugin']['openlayersmap']['optionStaticMapGenerator'] = 'local';
        $conf['plugin']['openlayersmap']['autoZoomMap']              = 1;

        TestUtils::rcopy(TMP_DIR, __DIR__ . '/data/');
    }

    final public function setUp(): void {
        parent::setUp();

        global $conf;
//        $data              = array();
//        search($data, $conf['datadir'], 'search_allpages', array('skipacl' => true));
//        foreach($data as $val) {
//            idx_addPage($val['id']);
//        }
        if($conf['allowdebug']) {
            if(mkdir(DOKU_TMP_DATA . 'data/log/debug/', 0777, true)) {
                touch(DOKU_TMP_DATA . 'data/log/debug/' . date('Y-m-d') . '.log');
            }

        }
    }

    final public function tearDown(): void {
        parent::tearDown();

        global $conf;
        // try to get the debug log after running the test, print and clear
        if($conf['allowdebug']) {
            print "\n";
            readfile(DOKU_TMP_DATA . 'data/log/debug/' . date('Y-m-d') . '.log');
            unlink(DOKU_TMP_DATA . 'data/log/debug/' . date('Y-m-d') . '.log');
        }
    }

    final public function test_rur(): void {
        $request  = new TestRequest();
        $response = $request->get(array('id' => 'rur'));
        self::assertNotNull($response);

        $_content = $response->getContent();
        self::assertStringContainsString('Rur', $_content);
        self::assertStringContainsString('<script defer="defer" src="/lib/plugins/openlayersmap/ol/ol.js"></script>', $_content);
        self::assertStringContainsString('<div id="olMap-static" class="olStaticMap">', $_content);
        self::assertStringContainsString('<table id="olMap-table" class="olPOItable">', $_content);

        // <img src="/./lib/exe/fetch.php?w=650&amp;h=550&amp;tok=72bf3a&amp;media=olmapmaps:openstreetmap:13:cache_8b:9b:94cd3dabd2d1c470a2d5b4bea6df.png"
        // class="medialeft" loading="lazy" title="Rur parkings " alt="Rur parkings " width="650" height="550" />
        $_staticImage = $response->queryHTML('img[src*="olmapmaps:openstreetmap:13:cache_8b:9b:94cd3dabd2d1c470a2d5b4bea6df.png"]');
        self::assertNotEmpty($_staticImage);
        self::assertEquals('medialeft', $_staticImage->attr('class'));
        self::assertEquals('650', $_staticImage->attr('width'));
        self::assertEquals('550', $_staticImage->attr('height'));
        self::assertStringContainsString('Rur parkings', $_staticImage->attr('title'));

        // <div class="olPOItableSpan" id="olMap-table-span">\n
        //    <table class="olPOItable" id="olMap-table">\n
        //    <caption class="olPOITblCaption">Points of Interest</caption>\n
        //    <thead class="olPOITblHeader">\n
        //    <tr>\n
        //    <th class="rowId" scope="col">id</th>\n
        //    <th class="icon" scope="col">symbol</th>\n
        //    <th class="lat" scope="col" title="latitude in decimal degrees">latitude</th>\n
        //    <th class="lon" scope="col" title="longitude in decimal degrees">longitude</th>\n
        //    <th class="txt" scope="col">description</th>\n
        //    </tr>\n
        //    </thead><tfoot class="olPOITblFooter"><tr><td colspan="5">Rur parkings</td></tr></tfoot><tbody class="olPOITblBody">\n
        //    <tr>\n
        //    <td class="rowId">1</td>\n
        //    <td class="icon"><img src="/./lib/plugins/openlayersmap/icons/parking.png" alt="parking" /></td>\n
        //    <td class="lat" title="latitude in decimal degrees">50.548611º</td>\n
        //    <td class="lon" title="longitude in decimal degrees">6.228889º</td>\n
        //    <td class="txt"><p>Parking Dreistegen</p></td>\n
        //    </tr>\n
        //    <tr>\n
        //    <td class="rowId">2</td>\n
        //    <td class="icon"><img src="/./lib/plugins/openlayersmap/icons/parking.png" alt="parking" /></td>\n
        //    <td class="lat" title="latitude in decimal degrees">50.56384º</td>\n
        //    <td class="lon" title="longitude in decimal degrees">6.29766º</td>\n
        //    <td class="txt"><p>Parking Grünenthalstrasse</p></td>\n
        //    </tr></tbody>\n
        //    </table>\n
        // </div>\n

        $_latCells = $response->queryHTML('td[class="lat"]');
        self::assertNotEmpty($_latCells);
        // not available in "stable"
        // self::assertEquals('50.548611º', $_latCells->first()->text());
        self::assertEquals('50.548611º', $_latCells->get(0)->textContent);

        $_lonCells = $response->queryHTML('td[class="lon"]');
        self::assertNotEmpty($_lonCells);
        // not available in "stable"
        // self::assertEquals('6.29766º', $_lonCells->last()->text());
        self::assertEquals('6.29766º', $_lonCells->get(1)->textContent);
    }

    final public function test_issue34(): void {
        $request  = new TestRequest();
        $response = $request->get(array('id' => 'issue34'));
        self::assertNotNull($response);
    }

    final public function test_issue34_fixed(): void {
        $request  = new TestRequest();
        $response = $request->get(array('id' => 'issue34-fixed'));
        self::assertNotNull($response);

        $_content = $response->getContent();
        self::assertStringContainsString('issue34-fixed', $_content);
        self::assertStringContainsString('<div id="olMap_example-static" class="olStaticMap">', $_content);
        self::assertStringContainsString('<table id="olMap_example-table" class="olPOItable">', $_content);

        $_staticImage = $response->queryHTML('img[src*="olmapmaps:openstreetmap:14:cache_32:12:6533646ecb8cf2f193db46305e5f.png"]');
        self::assertNotEmpty($_staticImage);
        self::assertEquals('550', $_staticImage->attr('width'));
        self::assertEquals('450', $_staticImage->attr('height'));
        self::assertEmpty(trim($_staticImage->attr('title')));
    }
}