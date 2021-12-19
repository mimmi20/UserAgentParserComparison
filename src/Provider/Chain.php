<?php
namespace UserAgentParserComparison\Provider;

use UserAgentParserComparison\Exception;
use UserAgentParserComparison\Model;

/**
 * A chain provider, to use multiple providers at the same time
 *
 * @author Martin Keckeis <martin.keckeis1@gmail.com>
 * @license MIT
 */
class Chain extends AbstractParseProvider
{
    /**
     * Name of the provider
     *
     * @var string
     */
    protected $name = 'Chain';

    /**
     *
     * @var AbstractProvider[]
     */
    private $providers = [];

    /**
     *
     * @param AbstractProvider[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     *
     * @return AbstractProvider[]
     */
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

            /* @var $provider AbstractParseProvider */

            try {
                return $provider->parse($userAgent, $headers);
            } catch (Exception\NoResultFoundException $ex) {
                // just catch this and continue to the next provider
            }
        }

        throw new Exception\NoResultFoundException('No result found for user agent: ' . $userAgent);
    }
}
