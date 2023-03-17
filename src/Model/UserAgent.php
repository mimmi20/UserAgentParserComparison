<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Model;

/**
 * User agent model
 */
final class UserAgent
{
    private Browser $browser;
    private RenderingEngine $renderingEngine;
    private OperatingSystem $operatingSystem;
    private Device $device;
    private Bot $bot;

    /** @var mixed */
    private $providerResultRaw;

    /** @throws void */
    public function __construct(
        private readonly string | null $providerName = null,
        private readonly string | null $providerVersion = null,
    ) {
        $this->browser         = new Browser();
        $this->renderingEngine = new RenderingEngine();
        $this->operatingSystem = new OperatingSystem();
        $this->device          = new Device();
        $this->bot             = new Bot();
    }

    /** @throws void */
    public function getProviderName(): string | null
    {
        return $this->providerName;
    }

    /** @throws void */
    public function getProviderVersion(): string | null
    {
        return $this->providerVersion;
    }

    /** @throws void */
    public function setBrowser(Browser $browser): void
    {
        $this->browser = $browser;
    }

    /** @throws void */
    public function getBrowser(): Browser
    {
        return $this->browser;
    }

    /** @throws void */
    public function setRenderingEngine(RenderingEngine $renderingEngine): void
    {
        $this->renderingEngine = $renderingEngine;
    }

    /** @throws void */
    public function getRenderingEngine(): RenderingEngine
    {
        return $this->renderingEngine;
    }

    /** @throws void */
    public function setOperatingSystem(OperatingSystem $operatingSystem): void
    {
        $this->operatingSystem = $operatingSystem;
    }

    /** @throws void */
    public function getOperatingSystem(): OperatingSystem
    {
        return $this->operatingSystem;
    }

    /** @throws void */
    public function setDevice(Device $device): void
    {
        $this->device = $device;
    }

    /** @throws void */
    public function getDevice(): Device
    {
        return $this->device;
    }

    /** @throws void */
    public function setBot(Bot $bot): void
    {
        $this->bot = $bot;
    }

    /** @throws void */
    public function getBot(): Bot
    {
        return $this->bot;
    }

    /** @throws void */
    public function isBot(): bool
    {
        return $this->getBot()->getIsBot();
    }

    /** @throws void */
    public function isMobile(): bool
    {
        return $this->getDevice()->getIsMobile();
    }

    /** @throws void */
    public function setProviderResultRaw(mixed $providerResultRaw): void
    {
        $this->providerResultRaw = $providerResultRaw;
    }

    /**
     * @return mixed
     *
     * @throws void
     */
    public function getProviderResultRaw()
    {
        return $this->providerResultRaw;
    }

    /**
     * @return array<mixed>
     * @phpstan-return array{browser: array{name: string|null, version: array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}}, renderingEngine: array{name: string|null, version: array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}}, operatingSystem: array{name: string|null, version: array{major: int|string|null, minor: int|string|null, patch: int|string|null, alias: string|null, complete: string|null}}, device: array{model: string|null, brand: string|null, type: string|null, isMobile: bool, isTouch: bool}, bot: array{isBot: bool, name: string|null, type: string|null}, providerResultRaw?: mixed}
     *
     * @throws void
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
