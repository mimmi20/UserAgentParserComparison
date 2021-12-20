<?php
namespace UserAgentParserComparison\Model;

/**
 * Device model
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 */
final class Device
{
    private ?string $model = null;

    private ?string $brand = null;

    private ?string $type = null;

    private bool $isMobile = false;

    private bool $isTouch = false;

    public function setModel(?string $model): void
    {
        $this->model = $model;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setBrand(?string $brand): void
    {
        $this->brand = $brand;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getType(): ?string
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
     *
     * @return string[]|int[]|bool[]|null[]
     * @phpstan-return array{model: string|null, brand: string|null, type: string|null, isMobile: bool, isTouch: bool}
     */
    public function toArray(): array
    {
        return [
            'model'    => $this->getModel(),
            'brand'    => $this->getBrand(),
            'type'     => $this->getType(),
            'isMobile' => $this->getIsMobile(),
            'isTouch'  => $this->getIsTouch(),
        ];
    }
}
