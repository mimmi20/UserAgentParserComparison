<?php

/**
 * This file is part of the mimmi20/user-agent-parser-comparison package.
 *
 * Copyright (c) 2015-2025, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace UserAgentParserComparison\Model;

use UaResult\Result\Result;

/**
 * User agent model
 */
final readonly class UserAgent
{
    /**
     * @param array<string, mixed> $rawResult
     *
     * @throws void
     */
    public function __construct(
        private string | null $providerName = null,
        private string | null $providerVersion = null,
        private array $rawResult = [],
        private Result | null $result = null,
    ) {
        // nothing to do
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
    public function isBot(): bool
    {
        return $this->result->getBrowser()->getType()->isBot();
    }

    /** @throws void */
    public function isMobile(): bool
    {
        return $this->result->getDevice()->getType()->isMobile();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws void
     */
    public function getRawResult(): array
    {
        return $this->rawResult;
    }

    /** @throws void */
    public function getResult(): Result | null
    {
        return $this->result;
    }

    /**
     * @return array<mixed>
     * @phpstan-return array{headers: array<string, string>, device: array{deviceName: string|null, marketingName: string|null, manufacturer: string, brand: string, type: string, display: array{width: int|null, height: int|null, touch: bool|null, size: float|null}}, browser: array{name: string|null, modus: string|null, version: string|null, manufacturer: string, bits: int|null, type: string}, os: array{name: string|null, marketingName: string|null, version: string|null, manufacturer: string, bits: int|null}, engine: array{name: string|null, version: string|null, manufacturer: string}, rawResult?: array<string, mixed>}
     *
     * @throws void
     */
    public function toArray(bool $includeResultRaw = false): array
    {
        $data = $this->result->toArray();

        // should be only used for debug
        if ($includeResultRaw === true) {
            $data['rawResult'] = $this->getRawResult();
        }

        return $data;
    }
}
