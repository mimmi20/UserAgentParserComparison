<?php

chdir(dirname(dirname(__DIR__)));
/*
 * Matomo cache init
 */
require_once 'vendor/autoload.php';

echo '~~~ Prepare Cache for Matomo ~~~' . PHP_EOL;

echo '.';

/*
 * File
 */
$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter('data/cache/.tmp/matomo');
$filesystem = new \League\Flysystem\Filesystem($adapter);
$cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache(
    new \MatthiasMullie\Scrapbook\Adapters\Flysystem($filesystem)
);

$cache->clear();

$dd = new \DeviceDetector\DeviceDetector();
$dd->setCache(new \DeviceDetector\Cache\PSR16Bridge($cache));
$dd->setUserAgent('test');
$dd->parse();

echo PHP_EOL;
