<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use Mobile_Detect;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Model;

/** @see https://github.com/browscap/browscap-php */
final class MobileDetect extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'MobileDetect';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/serbanghita/Mobile-Detect';

    /**
     * Composer package name
     */
    protected string $packageName = 'mobiledetect/mobiledetectlib';

    protected string $language = 'PHP';

    protected array $detectionCapabilities = [
        'browser' => [
            'name' => false,
            'version' => false,
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
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
    ];

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $parser = new Mobile_Detect();
        $parser->setHttpHeaders($headers);
        $parser->setUserAgent($userAgent);

        /*
         * Since Mobile_Detect to a regex comparison on every call
         * We cache it here for all checks and hydration
         */
        $resultCache = [
            'isMobile' => $parser->isMobile(),
        ];

        /*
         * No result found?
         */
        if (true !== $this->hasResult($resultCache)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultCache);

        /*
         * hydrate the result
         */
        $this->hydrateDevice($result->getDevice(), $resultCache);

        return $result;
    }

    /** @param array $resultRaw */
    private function hasResult(array $resultRaw): bool
    {
        return true === $resultRaw['isMobile'];
    }

    /** @param array $resultRaw */
    private function hydrateDevice(Model\Device $device, array $resultRaw): void
    {
        if (true !== $resultRaw['isMobile']) {
            return;
        }

        $device->setIsMobile(true);
    }
}
