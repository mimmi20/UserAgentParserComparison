<?php

declare(strict_types = 1);

use BrowserDetector\DetectorFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;
use Psr\Log\NullLogger;

chdir(dirname(__DIR__, 2));
/*
 * BrowserDetector cache init
 */
require_once 'vendor/autoload.php';

echo '~~~ Prepare Cache for BrowserDetector ~~~' . PHP_EOL;

echo '.';

/*
 * File
 */
$detectorAdapter = new LocalFilesystemAdapter('data/cache/.tmp/browser-detector');
$detectorCache   = new SimpleCache(
    new Flysystem(
        new Filesystem($detectorAdapter),
    ),
);

$detectorCache->clear();

$logger = new NullLogger();

$browserDetectorFactory = new DetectorFactory($detectorCache, $logger);
$browserDetector        = $browserDetectorFactory();
$browserDetector('test');

echo PHP_EOL;
