<?php declare(strict_types=1);

namespace TiendaNube\Throttler;

use TiendaNube\Throttler\Exception\ProviderException;
use TiendaNube\Throttler\Provider\ProviderInterface;
use TiendaNube\Throttler\Storage\StorageInterface;

/**
 * Class Throttler
 *
 * @package TiendaNube\Throttler
 */
class Throttler
{
    /**
     * The current provider instance
     *
     * @var ProviderInterface
     */
    private $provider;

    /**
     * Throttler constructor.
     *
     * @param ProviderInterface $provider
     * @param StorageInterface|null $storage
     */
    public function __construct(ProviderInterface $provider, StorageInterface $storage = null)
    {
        $this->provider = $provider;

        if (!is_null($storage)) {
            $this->provider->setStorage($storage);
        }
    }

    /**
     * Throttle a request
     *
     * @param string $namespace
     * @param bool $sleep
     * @param int $increment
     * @throws ProviderException
     * @return bool
     */
    public function throttle(string $namespace, bool $sleep = false, int $increment = 1): bool
    {
        if ($this->provider->hasLimit($namespace)) {
            $limit = $this->provider->getLimit($namespace);
            $usage = $this->provider->getUsage($namespace);

            if (($usage + $increment) <= $limit) {
                $this->provider->incrementUsage($namespace,$increment);
                return false;
            }
        }

        if ($sleep) {
            $time = $this->provider->getEstimate($namespace);
            usleep($time * 1000);

            return $this->throttle($namespace,$sleep,$increment);
        }

        return true;
    }

    /**
     * Get the current ratio.
     *
     * @param string $namespace
     * @param int $factor
     * @return int
     */
    public function getRatio(string $namespace, int $factor = ProviderInterface::RATIO_FACTOR_BY_SECOND): int
    {
        return $this->provider->getRatio($namespace,$factor);
    }

    /**
     * Get the current usage.
     *
     * @param string $namespace
     * @throws ProviderException
     * @return int
     */
    public function getUsage(string $namespace): int
    {
        return $this->provider->getUsage($namespace);
    }

    /**
     * Get the current limit.
     *
     * @param string $namespace
     * @throws ProviderException
     * @return int
     */
    public function getLimit(string $namespace): int
    {
        return $this->provider->getLimit($namespace);
    }

    /**
     * Check if the current provider has limit.
     *
     * @param string $namespace
     * @throws ProviderException
     * @return bool
     */
    public function hasLimit(string $namespace): bool
    {
        return $this->provider->hasLimit($namespace);
    }

    /**
     * Get the number of remaining requests available.
     *
     * @param string $namespace
     * @throws ProviderException
     * @return int
     */
    public function getRemaining(string $namespace): int
    {
        return $this->provider->getRemaining($namespace);
    }

    /**
     * Get the estimated time (in milliseconds) to perform the next request.
     *
     * @param string $namespace
     * @throws ProviderException
     * @return int
     */
    public function getEstimate(string $namespace): int
    {
        return $this->provider->getEstimate($namespace);
    }

    /**
     * Get the estimated time (in milliseconds) to fully reset the bucket.
     *
     * @param string $namespace
     * @throws ProviderException
     * @return int
     */
    public function getReset(string $namespace): int
    {
        return $this->provider->getReset($namespace);
    }
}
