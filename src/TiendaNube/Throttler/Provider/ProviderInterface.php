<?php declare(strict_types=1);

namespace TiendaNube\Throttler\Provider;

use TiendaNube\Throttler\Storage\StorageInterface;

/**
 * Interface ProviderInterface
 *
 * @package TiendaNube\Throttler\Provider
 */
interface ProviderInterface
{
    /**
     * Ratio multiplier constants
     */
    const RATIO_FACTOR_BY_HOUR = 3600;
    const RATIO_FACTOR_BY_MINUTE = 60;
    const RATIO_FACTOR_BY_SECOND = 1;
    const RATIO_FACTOR_BY_MILLISECOND = 0.001;

    /**
     * Sets a storage adapter.
     *
     * @param StorageInterface $storage
     */
    public function setStorage(StorageInterface $storage);

    /**
     * Get the current storage adapter.
     *
     * @return null|StorageInterface
     */
    public function getStorage();

    /**
     * Increment the request usage value and return the current usage number.
     *
     * @param string $namespace
     * @param int $count
     * @return int
     */
    public function incrementUsage(string $namespace, int $count = 1): int;

    /**
     * Get the request ratio based on a factor (in seconds or fractions of seconds).
     *
     * @param string $namespace
     * @param int $factor
     * @return int
     */
    public function getRatio(string $namespace, int $factor = self::RATIO_FACTOR_BY_SECOND): int;

    /**
     * Get the current request usage number.
     *
     * @param string $namespace
     * @return int
     */
    public function getUsage(string $namespace): int;

    /**
     * Get the request limit number.
     *
     * @param string $namespace
     * @return int
     */
    public function getLimit(string $namespace): int;

    /**
     * Checks if there is limit to perform a request.
     *
     * @param string $namespace
     * @return bool
     */
    public function hasLimit(string $namespace): bool;

    /**
     * Get the number of remaining requests before reach the limit.
     *
     * @param string $namespace
     * @return int
     */
    public function getRemaining(string $namespace): int;

    /**
     * Get the estimated time to perform the next request in milliseconds.
     *
     * @param string $namespace
     * @return int
     */
    public function getEstimate(string $namespace): int;

    /**
     * Get the time to fully reset the rate limit in milliseconds.
     *
     * @param string $namespace
     * @return int
     */
    public function getReset(string $namespace): int;
}
