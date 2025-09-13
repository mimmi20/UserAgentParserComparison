<?php

declare(strict_types = 1);

use BrowscapPHP\Browscap;
use BrowserDetector\DetectorFactory;
use DeviceDetector\Cache\PSR16Bridge;
use DeviceDetector\DeviceDetector;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;
use MatthiasMullie\Scrapbook\Psr6\Pool;
use Psr\Log\NullLogger;
use UserAgentParserComparison\Provider;
use Wolfcast\BrowserDetection;

$logger = new NullLogger();

/*
 * Matomo
 */
$matomoParser = new DeviceDetector();
$matomoParser->setCache(
    new PSR16Bridge(
        new SimpleCache(
            new Flysystem(
                new Filesystem(new LocalFilesystemAdapter('data/cache/.tmp/matomo')),
            ),
        ),
    ),
);

/*
 * BrowserDetector
 */
$browserDetectorFactory  = new DetectorFactory(
    new SimpleCache(
        new Flysystem(
            new Filesystem(new LocalFilesystemAdapter('data/cache/.tmp/browser-detector')),
        ),
    ),
    $logger,
);

return new Provider\Chain([
    new Provider\BrowscapFull(
        new Browscap(
            new SimpleCache(
                new Flysystem(
                    new Filesystem(new LocalFilesystemAdapter('data/cache/.tmp/browscap/full')),
                ),
            ),
            $logger,
        ),
    ),
    new Provider\BrowscapPhp(
        new Browscap(
            new SimpleCache(
                new Flysystem(
                    new Filesystem(new LocalFilesystemAdapter('data/cache/.tmp/browscap/standard')),
                ),
            ),
            $logger,
        ),
    ),
    new Provider\BrowscapLite(
        new Browscap(
            new SimpleCache(
                new Flysystem(
                    new Filesystem(new LocalFilesystemAdapter('data/cache/.tmp/browscap/lite')),
                ),
            ),
            $logger,
        ),
    ),
    // new Provider\GetBrowser(),
    new Provider\MatomoDeviceDetector($matomoParser),
    new Provider\BrowserDetector($browserDetectorFactory()),
    new Provider\DonatjUAParser(),
    new Provider\UAParser(\UAParser\Parser::create()),
    new Provider\WhichBrowser(
        new \WhichBrowser\Parser(),
        new Pool(
            new Flysystem(
                new Filesystem(new LocalFilesystemAdapter('data/cache/.tmp/whichbrowser')),
            ),
        ),
    ),
    new Provider\Woothee(new \Woothee\Classifier()),
    new Provider\Cbschuld(new \Browser()),
    new Provider\Wolfcast(new BrowserDetection()),
    new Provider\Endorphin(new \EndorphinStudio\Detector\Detector()),
    new Provider\MobileDetect(),
    new Provider\AgentZeroDetector(),
    new Provider\ForocoDetector(),
    new Provider\FyreUseragent(),
]);
