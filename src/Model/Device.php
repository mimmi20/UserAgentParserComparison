<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Model;

/**
 * Device model
 */
final class Device
{
    private string | null $model = null;
    private string | null $brand = null;
    private string | null $type  = null;
    private bool $isMobile       = false;
    private bool $isTouch        = false;

    /** @throws void */
    public function setModel(string | null $model): void
    {
        $this->model = $model;
    }

    /** @throws void */
    public function getModel(): string | null
    {
        return $this->model;
    }

    /** @throws void */
    public function setBrand(string | null $brand): void
    {
        $this->brand = $brand;
    }

    /** @throws void */
    public function getBrand(): string | null
    {
        return $this->brand;
    }

    /** @throws void */
    public function setType(string | null $type): void
    {
        $this->type = $type;
    }

    /** @throws void */
    public function getType(): string | null
    {
        return $this->type;
    }

    /** @throws void */
    public function setIsMobile(bool $isMobile): void
    {
        $this->isMobile = $isMobile;
    }

    /** @throws void */
    public function getIsMobile(): bool
    {
        return $this->isMobile;
    }

    /** @throws void */
    public function setIsTouch(bool $isTouch): void
    {
        $this->isTouch = $isTouch;
    }

    /** @throws void */
    public function getIsTouch(): bool
    {
        return $this->isTouch;
    }

    /**
     * @return array<bool>|array<int>|array<null>|array<string>
     * @phpstan-return array{model: string|null, brand: string|null, type: string|null, isMobile: bool, isTouch: bool}
     *
     * @throws void
     */
    public function toArray(): array
    {
        return [
            'model' => $this->getModel(),
            'brand' => $this->getBrand(),
            'type' => $this->getType(),
            'isMobile' => $this->getIsMobile(),
            'isTouch' => $this->getIsTouch(),
        ];
    }
}
