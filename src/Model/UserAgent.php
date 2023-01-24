<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Model;

/**
 * User agent model
 */
final class UserAgent
{
    private string | null $providerName;
    private string | null $providerVersion;
    private Browser $browser;
    private RenderingEngine $renderingEngine;
    private OperatingSystem $operatingSystem;
    private Device $device;
    private Bot $bot;

    /** @var mixed */
    private $providerResultRaw;

    public function __construct(string | null $providerName = null, string | null $providerVersion = null)
    {
        $this->providerName    = $providerName;
        $this->providerVersion = $providerVersion;

        $this->browser         = new Browser();
        $this->renderingEngine = new RenderingEngine();
        $this->operatingSystem = new OperatingSystem();
        $this->device          = new Device();
        $this->bot             = new Bot();
    }

    public function getProviderName(): string | null
    {
        return $this->providerName;
    }

    public function getProviderVersion(): string | null
    {
        return $this->providerVersion;
    }

    public function setBrowser(Browser $browser): void
    {
        $this->browser = $browser;
    }

    public function getBrowser(): Browser
    {
        return $this->browser;
    }

    public function setRenderingEngine(RenderingEngine $renderingEngine): void
    {
        $this->renderingEngine = $renderingEngine;
    }

    public function getRenderingEngine(): RenderingEngine
    {
        return $this->renderingEngine;
    }

    public function setOperatingSystem(OperatingSystem $operatingSystem): void
    {
        $this->operatingSystem = $operatingSystem;
    }

    public function getOperatingSystem(): OperatingSystem
    {
        return $this->operatingSystem;
    }

    public function setDevice(Device $device): void
    {
        $this->device = $device;
    }

    public function getDevice(): Device
    {
        return $this->device;
    }

    public function setBot(Bot $bot): void
    {
        $this->bot = $bot;
    }

    public function getBot(): Bot
    {
        return $this->bot;
    }

    public function isBot(): bool
    {
        return $this->getBot()->getIsBot();
    }

    public function isMobile(): bool
    {
        return $this->getDevice()->getIsMobile();
    }

    /** @param mixed $providerResultRaw */
    public function setProviderResultRaw($providerResultRaw): void
    {
        $this->providerResultRaw = $providerResultRaw;
    }

    /** @return mixed */
    public function getProviderResultRaw()
    {
        return $this->providerResultRaw;
    }

    /**
     * @return mixed[]
     * @phpstan-return array{browser: array{name: string|null, version: array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}}, renderingEngine: array{name: string|null, version: array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}}, operatingSystem: array{name: string|null, version: array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}}, device: array{model: string|null, brand: string|null, type: string|null, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: string|null, type: string|null}, providerResultRaw?: mixed}
     */
    public function toArray(bool $includeResultRaw = false): array
    {
        $data = [
            'browser' => $this->getBrowser()->toArray(),
            'renderingEngine' => $this->getRenderingEngine()->toArray(),
            'operatingSystem' => $this->getOperatingSystem()->toArray(),
            'device' => $this->getDevice()->toArray(),
            'bot' => $this->getBot()->toArray(),
        ];

        // should be only used for debug
        if (true === $includeResultRaw) {
            $data['providerResultRaw'] = $this->getProviderResultRaw();
        }

        return $data;
    }
}
