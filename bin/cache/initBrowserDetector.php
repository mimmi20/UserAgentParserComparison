<?php

chdir(dirname(dirname(__DIR__)));
/*
 * BrowserDetector cache init
 */
require_once 'vendor/autoload.php';

/*
 * File
 */
$detectorAdapter = new \League\Flysystem\Local\LocalFilesystemAdapter('data/cache/.tmp/browser-detector');
$detectorCache   = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache(
    new \MatthiasMullie\Scrapbook\Adapters\Flysystem(
        new \League\Flysystem\Filesystem($detectorAdapter)
    )
);

$detectorCache->clear();

$logger = new \Psr\Log\NullLogger();

$browserDetectorFactory  = new \BrowserDetector\DetectorFactory($detectorCache, $logger);
$browserDetector = $browserDetectorFactory();
$browserDetector('test');

echo PHP_EOL;
