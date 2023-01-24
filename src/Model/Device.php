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
    private string | null $type = null;
    private bool $isMobile = false;
    private bool $isTouch = false;

    public function setModel(string | null $model): void
    {
        $this->model = $model;
    }

    public function getModel(): string | null
    {
        return $this->model;
    }

    public function setBrand(string | null $brand): void
    {
        $this->brand = $brand;
    }

    public function getBrand(): string | null
    {
        return $this->brand;
    }

    public function setType(string | null $type): void
    {
        $this->type = $type;
    }

    public function getType(): string | null
    {
        return $this->type;
    }

    public function setIsMobile(bool $isMobile): void
    {
        $this->isMobile = $isMobile;
    }

    public function getIsMobile(): bool
    {
        return $this->isMobile;
    }

    public function setIsTouch(bool $isTouch): void
    {
        $this->isTouch = $isTouch;
    }

    public function getIsTouch(): bool
    {
        return $this->isTouch;
    }

    /**
     * @return bool[]|int[]|null[]|string[]
     * @phpstan-return array{model: string|null, brand: string|null, type: string|null, isMobile: bool, isTouch: bool}
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
