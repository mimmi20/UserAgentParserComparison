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

    /** @param AbstractProvider[] $providers */
    public function __construct(private readonly array $providers = [])
    {
    }

    /** @return AbstractProvider[] */
    public function getProviders(): array
    {
        return $this->providers;
    }

    public function parse(string $userAgent, array $headers = []): Model\UserAgent
    {
        foreach ($this->getProviders() as $provider) {
            if (!$provider instanceof AbstractParseProvider) {
                continue;
            }

            try {
                return $provider->parse($userAgent, $headers);
            } catch (Exception\NoResultFoundException) {
                // just catch this and continue to the next provider
            }
        }

        throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
    }
}
