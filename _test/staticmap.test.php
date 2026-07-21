<?php
/*
 * Copyright (c) 2026 Mark C. Prins <mprins@users.sf.net>
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

use dokuwiki\plugin\openlayersmap\StaticMap;

/**
 * Tests for StaticMap::fetchTile using DokuHTTPClient.
 *
 * @group plugin_openlayersmap
 * @group plugins
 */
class staticmap_plugin_openlayersmap_test extends DokuWikiTest
{
    protected $pluginsEnabled = ['openlayersmap', 'geophp'];

    /** @var StaticMap */
    private StaticMap $map;

    /** @var string */
    private string $tileCacheDir;

    public function setUp(): void
    {
        parent::setUp();
        $this->tileCacheDir = TMP_DIR . '/olmaptilecache_' . uniqid('', true);
        $this->map = new StaticMap(
            51.5,
            -0.1,
            10,
            256,
            256,
            'openstreetmap',
            [],
            '',
            '',
            '',
            TMP_DIR,
            $this->tileCacheDir
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        // clean up tile cache
        if (is_dir($this->tileCacheDir)) {
            $this->rrmdir($this->tileCacheDir);
        }
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Test that fetchTile returns false for a URL that does not point to a valid image.
     * This exercises the DokuHTTPClient code path without requiring network access to
     * a real tile server; an unreachable/invalid URL should result in false.
     */
    final public function test_fetchTile_returns_false_for_invalid_url(): void
    {
        // A syntactically valid but unreachable URL; DokuHTTPClient will fail and
        // fetchTile must return false rather than throwing.
        $result = $this->map->fetchTile('http://localhost:19999/nonexistent_tile.png');
        self::assertFalse(
            $result,
            'fetchTile should return false when the tile cannot be retrieved or is not a valid image'
        );
    }

    /**
     * Test that fetchTile returns cached content when a valid cached tile exists,
     * bypassing the HTTP client entirely.
     */
    final public function test_fetchTile_returns_cached_tile(): void
    {
        // Create a minimal 1×1 valid PNG image to store in the cache
        $img = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();

        $tileUrl = 'https://tile.openstreetmap.org/10/512/341.png';

        // Write into cache directly
        $this->map->writeTileToCache($tileUrl, $pngData);

        // fetchTile should now return the cached content
        $result = $this->map->fetchTile($tileUrl);
        self::assertNotFalse($result, 'fetchTile should return cached tile data');
        self::assertEquals($pngData, $result, 'fetchTile should return the exact cached tile data');
    }

    /**
     * Test that tileUrlToFilename produces a non-empty path within the cache base directory.
     */
    final public function test_tileUrlToFilename(): void
    {
        $url      = 'https://tile.openstreetmap.org/10/512/341.png';
        $filename = $this->map->tileUrlToFilename($url);
        self::assertNotEmpty($filename);
        self::assertStringContainsString('olmaptiles', $filename);
    }
}
