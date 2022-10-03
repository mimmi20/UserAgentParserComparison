<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception\NoResultFoundException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for donatj/PhpUserAgent
 *
 * @see https://github.com/donatj/PhpUserAgent
 */
final class DonatjUAParser extends AbstractParseProvider
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

    private string $functionName = '\parse_user_agent';

    /** @throws PackageNotLoadedException */
    public function __construct()
    {
        $this->checkIfInstalled();
    }

    /**
     * @throws NoResultFoundException
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        $functionName = $this->functionName;

        $resultRaw = $functionName($userAgent);

        if (true !== $this->hasResult($resultRaw)) {
            throw new NoResultFoundException('No result found for user agent: ' . $userAgent);
        }

        /*
         * Hydrate the model
         */
        $result = new Model\UserAgent($this->getName(), $this->getVersion());
        $result->setProviderResultRaw($resultRaw);

        /*
         * Bot detection - is currently not possible!
         */

        /*
         * hydrate the result
         */
        $this->hydrateBrowser($result->getBrowser(), $resultRaw);
        // renderingEngine not available
        // os is mixed with device informations
        // device is mixed with os

        return $result;
    }

    /** @param array $resultRaw */
    private function hasResult(array $resultRaw): bool
    {
        return $this->isRealResult($resultRaw['browser']);
    }

    /** @param array $resultRaw */
    private function hydrateBrowser(Model\Browser $browser, array $resultRaw): void
    {
        $browser->setName($this->getRealResult($resultRaw['browser']));
        $browser->getVersion()->setComplete($this->getRealResult($resultRaw['version']));
    }
}
