<?php
namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Exception\PackageNotLoadedException;
use UserAgentParserComparison\Model;

/**
 * Abstraction for all providers
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 */
abstract class AbstractProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name;

    /**
     * Homepage of the provider
     *
     * @var string
     */
    protected $homepage;

    /**
     * Composer package name
     *
     * @var string
     */
    protected $packageName;

    /**
     * Per default the provider cannot detect anything
     * Activate them in $detectionCapabilities
     *
     * @var array
     */
    protected $allDetectionCapabilities = [
        'browser' => [
            'name'    => false,
            'version' => false,
        ],

        'renderingEngine' => [
            'name'    => false,
            'version' => false,
        ],

        'operatingSystem' => [
            'name'    => false,
            'version' => false,
        ],

        'device' => [
            'model'    => false,
            'brand'    => false,
            'type'     => false,
            'isMobile' => false,
            'isTouch'  => false,
        ],

        'bot' => [
            'isBot' => false,
            'name'  => false,
            'type'  => false,
        ],
    ];

    /**
     * Set this in each Provider implementation
     *
     * @var array
     */
    protected $detectionCapabilities = [];

    protected $defaultValues = [
        'general' => [],
    ];

    /**
     * Return the name of the provider
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the homepage
     *
     * @return string
     */
    public function getHomepage(): string
    {
        return $this->homepage;
    }

    /**
     * Get the package name
     *
     * @return string|null
     */
    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

    /**
     * Return the version of the provider
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        try {
            return \Composer\InstalledVersions::getPrettyVersion($this->getPackageName());
        } catch (\OutOfBoundsException $ex) {
            return null;
        }
    }

    /**
     * Get the last change date of the provider
     *
     * @return \DateTimeImmutable|null
     */
    public function getUpdateDate(): ?\DateTimeImmutable
    {
        $installed = json_decode(file_get_contents('vendor/composer/installed.json'), true);
        $package   = $this->getPackageName();

        $filtered = array_filter(
            $installed['packages'],
            function (array $value) use ($package): bool {
                return array_key_exists('name', $value) && $package === $value['name'];
            }
        );

        if ([] === $filtered) {
            return null;
        }

        $filtered = reset($filtered);

        if ([] === $filtered || !array_key_exists('time', $filtered)) {
            return null;
        }

        return new \DateTimeImmutable($filtered['time']);
    }

    /**
     * What kind of capabilities this provider can detect
     *
     * @return array
     */
    public function getDetectionCapabilities(): array
    {
        return array_merge($this->allDetectionCapabilities, $this->detectionCapabilities);
    }

    /**
     *
     * @throws PackageNotLoadedException
     */
    protected function checkIfInstalled(): void
    {
        $installed = json_decode(file_get_contents('vendor/composer/installed.json'), true);
        $package   = $this->getPackageName();

        $filtered = array_filter(
            $installed['packages'],
            function ($value) use ($package): bool {
                return array_key_exists('name', $value) && $package === $value['name'];
            }
        );

        if ([] === $filtered) {
            throw new PackageNotLoadedException('You need to install the package ' . $package . ' to use this provider');
        }
    }
}
