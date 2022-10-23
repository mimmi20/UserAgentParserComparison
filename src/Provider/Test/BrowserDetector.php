<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider\Test;

use BrowserDetector\Factory\BrowserFactory;
use BrowserDetector\Factory\DeviceFactory;
use BrowserDetector\Factory\DisplayFactory;
use BrowserDetector\Factory\EngineFactory;
use BrowserDetector\Factory\PlatformFactory;
use BrowserDetector\Loader\CompanyLoader;
use BrowserDetector\Loader\CompanyLoaderFactory;
use BrowserDetector\Loader\CompanyLoaderInterface;
use BrowserDetector\Loader\NotFoundException;
use BrowserDetector\RequestBuilder;
use BrowserDetector\Version\NotNumericException;
use BrowserDetector\Version\Version;
use BrowserDetector\Version\VersionFactory;
use FilterIterator;
use Iterator;
use JsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UaBrowserType\TypeLoader;
use UaDeviceType\Unknown;
use UaResult\Browser\Browser;
use UaResult\Company\Company;
use UaResult\Device\Device;
use UaResult\Device\Display;
use UaResult\Engine\Engine;
use UaResult\Os\Os;
use UaResult\Result\Result;
use UnexpectedValueException;
use UserAgentParserComparison\Exception\NoResultFoundException;

use function array_key_exists;
use function assert;
use function bin2hex;
use function count;
use function file_get_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function sha1;
use function sprintf;

use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

/** @see https://github.com/browscap/browscap-php */
final class BrowserDetector extends AbstractTestProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'BrowserDetector';

    /**
     * Homepage of the provider
     */
    protected string $homepage = 'https://github.com/mimmi20/browser-detector';

    /**
     * Composer package name
     */
    protected string $packageName = 'mimmi20/browser-detector';

    protected string $language = 'PHP';

    /**
     * Set this in each Provider implementation
     *
     * @var array<string, array<string, bool>>
     * @phpstan-var array{browser: array{name: bool, version: bool}, renderingEngine: array{name: bool, version: bool}, operatingSystem: array{name: bool, version: bool}, device: array{model: bool, brand: bool, type: bool, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: bool, type: bool}}
     */
    protected array $detectionCapabilities = [
        'browser' => [
            'name' => true,
            'version' => true,
        ],

        'renderingEngine' => [
            'name' => true,
            'version' => true,
        ],

        'operatingSystem' => [
            'name' => true,
            'version' => true,
        ],

        'device' => [
            'model' => true,
            'brand' => true,
            'type' => true,
            'isMobile' => true,
            'isTouch' => false,
        ],

        'bot' => [
            'isBot' => true,
            'name' => true,
            'type' => true,
        ],
    ];

    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @return iterable<array<string, mixed>>
     * @phpstan-return iterable<string, array{resFilename: string, resRawResult: string, resBrowserName: string|null, resBrowserVersion: string|null, resEngineName: string|null, resEngineVersion: string|null, resOsName: string|null, resOsVersion: string|null, resDeviceModel: string|null, resDeviceBrand: string|null, resDeviceType: string|null, resDeviceIsMobile: bool|null, resDeviceIsTouch: bool|null, resBotIsBot: bool|null, resBotName: string|null, resBotType: string|null}>
     *
     * @throws NoResultFoundException
     */
    public function getTests(): iterable
    {
        $path = 'vendor/mimmi20/browser-detector/tests/data';

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $files    = new class ($iterator, 'json') extends FilterIterator {
            public function __construct(Iterator $iterator, private string $extension)
            {
                parent::__construct($iterator);
            }

            public function accept(): bool
            {
                $file = $this->getInnerIterator()->current();

                assert($file instanceof SplFileInfo);

                return $file->isFile() && $file->getExtension() === $this->extension;
            }
        };

        $companyLoaderFactory = new CompanyLoaderFactory();

        $companyLoader = $companyLoaderFactory();
        assert($companyLoader instanceof CompanyLoader, sprintf('$companyLoader should be an instance of %s, but is %s', CompanyLoader::class, $companyLoader::class));

        $resultFactory = new class ($companyLoader, $this->logger) {
            public function __construct(private CompanyLoaderInterface $companyLoader, private LoggerInterface $logger)
            {
            }

            /**
             * @param array<string, array<string, string>> $data
             * @phpstan-param array{headers?: array<string, string>, device?: (stdClass|array{deviceName?: (string|null), marketingName?: (string|null), manufacturer?: string, brand?: string, type?: (string|null), display?: (null|array{width?: (int|null), height?: (int|null), touch?: (bool|null), size?: (int|float|null)}|stdClass)}), browser?: (stdClass|array{name?: (string|null), manufacturer?: string, version?: (stdClass|string|null), type?: (string|null), bits?: (int|null), modus?: (string|null)}), os?: (stdClass|array{name?: (string|null), marketingName?: (string|null), manufacturer?: string, version?: (stdClass|string|null), bits?: (int|null)}), engine?: (stdClass|array{name?: (string|null), manufacturer?: string, version?: (stdClass|string|null)})} $data
             *
             * @throws NotFoundException
             * @throws UnexpectedValueException
             * @throws NotNumericException
             */
            public function fromArray(LoggerInterface $logger, array $data): Result | null
            {
                if (!array_key_exists('headers', $data)) {
                    return null;
                }

                $headers        = (array) $data['headers'];
                $request        = (new RequestBuilder())->buildRequest(new NullLogger(), $headers);
                $versionFactory = new VersionFactory();

                $device = new Device(
                    null,
                    null,
                    new Company('Unknown', null, null),
                    new Company('Unknown', null, null),
                    new Unknown(),
                    new Display(null, null, null, null),
                );

                if (array_key_exists('device', $data)) {
                    $deviceFactory = new DeviceFactory(
                        $this->companyLoader,
                        new \UaDeviceType\TypeLoader(),
                        new DisplayFactory(),
                        $logger,
                    );

                    $device = $deviceFactory->fromArray((array) $data['device'], $request->getDeviceUserAgent());
                }

                $browserUa = $request->getBrowserUserAgent();

                $browser = new Browser(
                    null,
                    new Company('Unknown', null, null),
                    new Version('0'),
                    new \UaBrowserType\Unknown(),
                    null,
                    null,
                );

                if (array_key_exists('browser', $data)) {
                    $browser = (new BrowserFactory($this->companyLoader, $versionFactory, new TypeLoader(), $this->logger))->fromArray((array) $data['browser'], $browserUa);
                }

                $os = new Os(
                    null,
                    null,
                    new Company('Unknown', null, null),
                    new Version('0'),
                    null,
                );

                if (array_key_exists('os', $data)) {
                    $os = (new PlatformFactory($this->companyLoader, $versionFactory, $this->logger))->fromArray((array) $data['os'], $request->getPlatformUserAgent());
                }

                $engine = new Engine(
                    null,
                    new Company('Unknown', null, null),
                    new Version('0'),
                );

                if (array_key_exists('engine', $data)) {
                    $engine = (new EngineFactory($this->companyLoader, $versionFactory, $this->logger))->fromArray((array) $data['engine'], $browserUa);
                }

                return new Result($headers, $device, $os, $browser, $engine);
            }
        };

        foreach ($files as $file) {
            assert($file instanceof SplFileInfo);

            $file = $file->getPathname();

            $content = file_get_contents($file);

            if ('' === $content || PHP_EOL === $content) {
                continue;
            }

            try {
                $allEncodedData = json_decode(
                    $content,
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException) {
                continue;
            }

            if (!is_array($allEncodedData)) {
                continue;
            }

            foreach ($allEncodedData as $encodedData) {
                $ua = $encodedData['headers']['user-agent'] ?? '';

                if ('' === $ua) {
                    continue;
                }

                $expectedResult = $resultFactory->fromArray($this->logger, $encodedData);

                $data = [
                    'resFilename' => $file,

                    'resBrowserName' => $expectedResult->getBrowser()->getName(),
                    'resBrowserVersion' => $expectedResult->getBrowser()->getVersion()->getVersion(),

                    'resEngineName' => $expectedResult->getEngine()->getName(),
                    'resEngineVersion' => $expectedResult->getEngine()->getVersion()->getVersion(),

                    'resOsName' => $expectedResult->getOs()->getName(),
                    'resOsVersion' => $expectedResult->getOs()->getVersion()->getVersion(),

                    'resDeviceModel' => $expectedResult->getDevice()->getMarketingName(),
                    'resDeviceBrand' => $expectedResult->getDevice()->getBrand()->getBrandName(),
                    'resDeviceType' => $expectedResult->getDevice()->getType()->getName(),
                    'resDeviceIsMobile' => $expectedResult->getDevice()->getType()->isMobile(),
                    'resDeviceIsTouch' => $expectedResult->getDevice()->getDisplay()->hasTouch(),

                    'resBotIsBot' => $expectedResult->getBrowser()->getType()->isBot(),
                    'resBotName' => null,
                    'resBotType' => null,
                ];

                if ($expectedResult->getBrowser()->getType()->isBot()) {
                    $data['resBotName'] = $expectedResult->getBrowser()->getName();
                    $data['resBotType'] = $expectedResult->getBrowser()->getType()->getName();
                }

                $toInsert = [
                    'uaString' => $ua,
                    'result' => $data,
                ];

                if (1 < count($encodedData['headers'])) {
                    unset($encodedData['headers']['user-agent']);

                    $toInsert['uaAdditionalHeaders'] = $encodedData['headers'];

                    $key = bin2hex(sha1($ua . ' ' . json_encode($encodedData['headers']), true));
                } else {
                    $key = bin2hex(sha1($ua, true));
                }

                yield $key => $toInsert;
            }
        }
    }
}
