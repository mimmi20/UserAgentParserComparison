<?php

declare(strict_types = 1);

namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Model;

/**
 * A chain provider, to use multiple providers at the same time
 */
final class Chain extends AbstractParseProvider
{
    /**
     * Name of the provider
     */
    protected string $name = 'Chain';

    /**
     * @param array<AbstractProvider> $providers
     *
     * @throws void
     */
    public function __construct(private readonly array $providers = [])
    {
    }

    /**
     * @throws void
     */
    public function isActive(): bool
    {
        return true;
    }

    /**
     * @return array<AbstractProvider>
     *
     * @throws void
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @param array<string, string> $headers
     * @throws Exception\NoResultFoundException
     */
    public function parse(array $headers = []): Model\UserAgent
    {
        foreach ($this->getProviders() as $provider) {
            if (!$provider instanceof AbstractParseProvider || !$provider->isActive()) {
                continue;
            }

            try {
                return $provider->parse($headers);
            } catch (Exception\NoResultFoundException) {
                // just catch this and continue to the next provider
            }
        }

        throw new Exception\NoResultFoundException('No result found for user agent: ' . $headers['user-agent'] ?? '');
    }
}
