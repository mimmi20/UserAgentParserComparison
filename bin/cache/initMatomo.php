<?php

declare(strict_types = 1);

use DeviceDetector\Cache\PSR16Bridge;
use DeviceDetector\DeviceDetector;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;

chdir(dirname(__DIR__, 2));
/*
 * Matomo cache init
 */
require_once 'vendor/autoload.php';

echo '~~~ Prepare Cache for Matomo ~~~' . PHP_EOL;

echo '.';

/*
 * File
 */
$adapter    = new LocalFilesystemAdapter('data/cache/.tmp/matomo');
$filesystem = new Filesystem($adapter);
$cache      = new SimpleCache(
    new Flysystem($filesystem),
);

$cache->clear();

$dd = new DeviceDetector();
$dd->setCache(new PSR16Bridge($cache));
$dd->setUserAgent('test');
$dd->parse();

echo PHP_EOL;
