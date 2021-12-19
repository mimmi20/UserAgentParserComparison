<?php

chdir(dirname(dirname(__DIR__)));

/*
 * Browscap cache init
 */
require_once 'vendor/autoload.php';

/*
 * Full
 */
echo '.';

$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter('data/cache/.tmp/browscap/full');
$filesystem = new \League\Flysystem\Filesystem($adapter);
$cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache(
    new \MatthiasMullie\Scrapbook\Adapters\Flysystem($filesystem)
);

$cache->clear();

$logger = new \Psr\Log\NullLogger();

$bc = new \BrowscapPHP\BrowscapUpdater($cache, $logger);
$bc->convertFile('data/full_php_browscap.ini');


/*
 * Lite
 */
echo '.';

$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter('data/cache/.tmp/browscap/lite');
$filesystem = new \League\Flysystem\Filesystem($adapter);
$cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache(
    new \MatthiasMullie\Scrapbook\Adapters\Flysystem($filesystem)
);

$cache->clear();

$bc = new \BrowscapPHP\BrowscapUpdater($cache, $logger);
$bc->convertFile('data/lite_php_browscap.ini');

/*
 * PHP
 */
echo '.';

$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter('data/cache/.tmp/browscap/standard');
$filesystem = new \League\Flysystem\Filesystem($adapter);
$cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache(
    new \MatthiasMullie\Scrapbook\Adapters\Flysystem($filesystem)
);

$cache->clear();

$bc = new \BrowscapPHP\BrowscapUpdater($cache, $logger);
$bc->convertFile('data/php_browscap.ini');

echo PHP_EOL;
