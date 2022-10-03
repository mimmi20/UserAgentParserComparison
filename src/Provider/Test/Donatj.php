<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use UserAgentParserComparison\Exception\NoResultFoundException;

use function bin2hex;
use function file_exists;
use function file_get_contents;
use function filesize;
use function json_decode;
use function sha1;

/** @see https://github.com/browscap/browscap-php */
final class Donatj extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'DonatjUAParser';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/donatj/PhpUserAgent';

    /**
     * Composer package name
     */
    protected string $packageName = 'donatj/phpuseragentparser';

    protected string $language = 'PHP';

    protected array $detectionCapabilities = [
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => false,
            'version' => false,
        ],

        'operatingSystem' => [
            'name' => false,
            'version' => false,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => false,
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
    ];

    /** @throws NoResultFoundException */
    public function getTests(): iterable
    {
        if (
            file_exists('vendor/donatj/phpuseragentparser/tests/user_agents.json')
            && 0 < filesize('vendor/donatj/phpuseragentparser/tests/user_agents.json')
        ) {
            $file = 'vendor/donatj/phpuseragentparser/tests/user_agents.json';
        } else {
            $file = 'vendor/donatj/phpuseragentparser/tests/user_agents.dist.json';
        }

        $content = file_get_contents($file);

        $json = json_decode($content, true);

        foreach ($json as $ua => $row) {
            $data = [
                'resFilename' => $file,

                'resBrowserName' => $row['browser'] ?? null,
                'resBrowserVersion' => $row['version'] ?? null,

                'resEngineName' => null,
                'resEngineVersion' => null,

                'resOsName' => $row['platform'] ?? null,
                'resOsVersion' => null,

                'resDeviceModel' => null,
                'resDeviceBrand' => null,
                'resDeviceType' => null,
                'resDeviceIsMobile' => null,
                'resDeviceIsTouch' => null,

                'resBotIsBot' => null,
                'resBotName' => null,
                'resBotType' => null,
            ];

            $key      = bin2hex(sha1($ua, true));
            $toInsert = [
                'uaString' => $ua,
                'result' => $data,
            ];

            yield $key => $toInsert;
        }
    }
}
