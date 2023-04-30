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
    protected string $language    = 'PHP';

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [
        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'brand' => false,
            'isMobile' => false,
            'isTouch' => false,
            'model' => false,
            'type' => false,
        ],

        'operatingSystem' => [
            'name' => false,
            'version' => false,
        ],

        'renderingEngine' => [
            'name' => false,
            'version' => false,
        ],
    ];

    /** @phpstan-var callable-string */
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
        $resultRaw = ($this->functionName)($userAgent);

        if ($this->hasResult($resultRaw) !== true) {
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
        // os is mixed with device information
        // device is mixed with os

        return $result;
    }

    /** @throws void */
    private function hasResult(array $resultRaw): bool
    {
        return $this->isRealResult($resultRaw['browser']);
    }

    /** @throws void */
    private function hydrateBrowser(Model\Browser $browser, array $resultRaw): void
    {
        $browser->setName($this->getRealResult($resultRaw['browser']));
        $browser->getVersion()->setComplete($this->getRealResult($resultRaw['version']));
    }
}
