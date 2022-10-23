<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use Composer\InstalledVersions;
use DateTimeImmutable;
use OutOfBoundsException;
use UserAgentParserComparison\Exception\PackageNotLoadedException;

use function array_filter;
use function array_key_exists;
use function array_merge;
use function file_get_contents;
use function json_decode;
use function reset;

/**
 * Abstraction for all providers
 */
abstract class AbstractProvider
{
    /**
     * Name of the provider
     */
    protected string $name = '';

    /**
     * Homepage of the provider
     */
    protected string $homepage = '';

    /**
     * Composer package name
     */
    protected string $packageName = '';

    protected string $language = '';

    protected bool $local = true;

    protected bool $api = false;

    /**
     * Per default the provider cannot detect anything
     * Activate them in $detectionCapabilities
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $allDetectionCapabilities = [
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
            'isMobile' => false,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => false,
            'name' => false,
            'type' => false,
        ],
    ];

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [];

    /** @var array<string, array<int|string, array<mixed>|string>> */
    protected array $defaultValues = [
        'general' => [],
    ];

    /**
     * Return the name of the provider
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the homepage
     */
    public function getHomepage(): string
    {
        return $this->homepage;
    }

    /**
     * Get the package name
     */
    public function getPackageName(): string | null
    {
        return $this->packageName;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function isApi(): bool
    {
        return $this->api;
    }

    /**
     * Return the version of the provider
     */
    public function getVersion(): string | null
    {
        try {
            return InstalledVersions::getPrettyVersion($this->getPackageName());
        } catch (OutOfBoundsException) {
            return null;
        }
    }

    /**
     * Get the last change date of the provider
     */
    public function getUpdateDate(): DateTimeImmutable | null
    {
        $installed = json_decode(file_get_contents('vendor/composer/installed.json'), true);
        $package   = $this->getPackageName();

        $filtered = array_filter(
            $installed['packages'],
            static fn (array $value): bool => array_key_exists('name', $value) && $package === $value['name'],
        );

        if ([] === $filtered) {
            return null;
        }

        $filtered = reset($filtered);

        if ([] === $filtered || !array_key_exists('time', $filtered)) {
            return null;
        }

        return new DateTimeImmutable($filtered['time']);
    }

    /**
     * What kind of capabilities this provider can detect
     *
     * @return array<string, array<string, bool>>
     * @phpstan-return array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    public function getDetectionCapabilities(): array
    {
        return array_merge($this->allDetectionCapabilities, $this->detectionCapabilities);
    }

    /** @throws PackageNotLoadedException */
    protected function checkIfInstalled(): void
    {
        $installed = json_decode(file_get_contents('vendor/composer/installed.json'), true);
        $package   = $this->getPackageName();

        $filtered = array_filter(
            $installed['packages'],
            static fn ($value): bool => array_key_exists('name', $value) && $package === $value['name'],
        );

        if ([] === $filtered) {
            throw new PackageNotLoadedException('You need to install the package ' . $package . ' to use this provider');
        }
    }
}
