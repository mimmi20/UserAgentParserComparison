<?php

declare(strict_types = 1);

use BrowscapPHP\BrowscapUpdater;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;
use Psr\Log\NullLogger;

chdir(dirname(__DIR__, 2));

/*
 * Browscap cache init
 */
require_once 'vendor/autoload.php';

echo '~~~ Prepare Cache for Browscap ~~~' . PHP_EOL;

/*
 * Full
 */
echo '.';

$adapter    = new LocalFilesystemAdapter('data/cache/.tmp/browscap/full');
$filesystem = new Filesystem($adapter);
$cache      = new SimpleCache(
    new Flysystem($filesystem),
);

$cache->clear();

$logger = new NullLogger();

$bc = new BrowscapUpdater($cache, $logger);
$bc->convertFile('data/full_php_browscap.ini');

/*
 * Lite
 */
echo '.';

$adapter    = new LocalFilesystemAdapter('data/cache/.tmp/browscap/lite');
$filesystem = new Filesystem($adapter);
$cache      = new SimpleCache(
    new Flysystem($filesystem),
);

$cache->clear();

$bc = new BrowscapUpdater($cache, $logger);
$bc->convertFile('data/lite_php_browscap.ini');

/*
 * PHP
 */
echo '.';

$adapter    = new LocalFilesystemAdapter('data/cache/.tmp/browscap/standard');
$filesystem = new Filesystem($adapter);
$cache      = new SimpleCache(
    new Flysystem($filesystem),
);

$cache->clear();

$bc = new BrowscapUpdater($cache, $logger);
$bc->convertFile('data/php_browscap.ini');

echo PHP_EOL;
