<?php

declare(strict_types = 1);

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr6\Pool;

chdir(dirname(__DIR__, 2));
/*
 * BrowserDetector cache init
 */
require_once 'vendor/autoload.php';

echo '~~~ Prepare Cache for WhichBrowser ~~~' . PHP_EOL;

echo '.';

/*
 * File
 */
$whichbrowserAdapter = new LocalFilesystemAdapter('data/cache/.tmp/whichbrowser');
$whichbrowserCache   = new Pool(
    new Flysystem(
        new Filesystem($whichbrowserAdapter),
    ),
);

$whichbrowserCache->clear();

echo PHP_EOL;
