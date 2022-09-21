<?php

chdir(dirname(dirname(__DIR__)));
/*
 * BrowserDetector cache init
 */
require_once 'vendor/autoload.php';

echo '~~~ Prepare Cache for WhichBrowser ~~~' . PHP_EOL;

echo '.';

/*
 * File
 */
$whichbrowserAdapter = new \League\Flysystem\Local\LocalFilesystemAdapter('data/cache/.tmp/whichbrowser');
$whichbrowserCache   = new \MatthiasMullie\Scrapbook\Psr6\Pool(
    new \MatthiasMullie\Scrapbook\Adapters\Flysystem(
        new \League\Flysystem\Filesystem($whichbrowserAdapter)
    )
);

$whichbrowserCache->clear();

echo PHP_EOL;
