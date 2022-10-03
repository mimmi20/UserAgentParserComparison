<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use Jenssegers\Agent\Agent;
use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for jenssegers/agent
 *
 * @see https://github.com/jenssegers/agent
 */
final class JenssegersAgent extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'JenssegersAgent';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/jenssegers/agent';

    /**
     * Composer package name
     */
    protected string $packageName = 'jenssegers/agent';

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
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => false,
            'brand' => false,
            'type' => false,
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => false,
        ],
    ];

    protected array $defaultValues = [
        'general' => [],

        'browser' => [
            'name' => ['/^GenericBrowser$/i'],
        ],
    ];

    /**
     * Used for unitTests mocking
     */
    private Agent | null $parser = null;

    /** @throws PackageNotLoadedException */
    public function __construct()
    {
        $this->checkIfInstalled();
    }

    public function getParser(): Agent
    {
        if (null === $this->parser) {
            $this->parser = new Agent();
        }

        return $this->parser;
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $parser = $this->getParser();
        $parser->setHttpHeaders($headers);
        $parser->setUserAgent($userAgent);

        /*
         * Since Mobile_Detect to a regex comparison on every call
         * We cache it here for all checks and hydration
         */
        $browserName = $parser->browser();
        $osName      = $parser->platform();

        $resultCache = [
            'browserName' => $browserName,
            'browserVersion' => $parser->version($browserName),

            'osName' => $osName,
            'osVersion' => $parser->version($osName),

            'deviceModel' => $parser->device(),
            'isMobile' => $parser->isMobile(),

            'isRobot' => $parser->isRobot(),
            'botName' => $parser->robot(),
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
         * Bot detection
         */
        if (true === $parser->isRobot()) {
            $this->hydrateBot($result->getBot(), $resultCache);

            return $result;
        }

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultCache);
        $this->hydrateOperatingSystem($result->getOperatingSystem(), $resultCache);
        $this->hydrateDevice($result->getDevice(), $resultCache);

        return $result;
    }

    /** @param array $resultRaw */
    private function hasResult(array $resultRaw): bool
    {
        if (true === $resultRaw['isMobile'] || true === $resultRaw['isRobot']) {
            return true;
        }

        return true === $this->isRealResult($resultRaw['browserName'], 'browser', 'name') || true === $this->isRealResult($resultRaw['osName']) || true === $this->isRealResult($resultRaw['botName']);
    }

    /** @param array $resultRaw */
    private function hydrateBot(Model\Bot $bot, array $resultRaw): void
    {
        $bot->setIsBot(true);
        $bot->setName($this->getRealResult($resultRaw['botName']));
    }

    /** @param array $resultRaw */
    private function hydrateBrowser(Model\Browser $browser, array $resultRaw): void
    {
        if (true !== $this->isRealResult($resultRaw['browserName'], 'browser', 'name')) {
            return;
        }

        $browser->setName($resultRaw['browserName']);
        $browser->getVersion()->setComplete($this->getRealResult($resultRaw['browserVersion']));
    }

    /** @param array $resultRaw */
    private function hydrateOperatingSystem(Model\OperatingSystem $os, array $resultRaw): void
    {
        if (true !== $this->isRealResult($resultRaw['osName'])) {
            return;
        }

        $os->setName($resultRaw['osName']);
        $os->getVersion()->setComplete($this->getRealResult($resultRaw['osVersion']));
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
