<?php declare(strict_types=1);

namespace TiendaNube\Throttler\Provider;

use PHPUnit\Framework\TestCase;
use TiendaNube\Throttler\Exception\LeakyBucketException;
use TiendaNube\Throttler\Exception\StorageNotDefinedException;
use TiendaNube\Throttler\Storage\InMemory;

/**
 * Class LeakyBucketTest
 *
 * @package TiendaNube\Throttler\Provider
 */
class LeakyBucketTest extends TestCase
{
    /**
     * Factory a provider instance using InMemory storage
     *
     * @param int $capacity
     * @param int $leakRate
     * @return LeakyBucket
     * @throws LeakyBucketException
     */
    private function getProviderWithStorage(
        $capacity = LeakyBucket::DEFAULT_CAPACITY,
        $leakRate = LeakyBucket::DEFAULT_LEAK_RATE
    ) {
        return new LeakyBucket($capacity, $leakRate, new InMemory());
    }

    /**
     * Should be able to create an instance of the provider without any argument.
     */
    public function testConstructWithoutArguments()
    {
        $provider = new LeakyBucket();
        $this->assertAttributeEquals(LeakyBucket::DEFAULT_CAPACITY,'capacity',$provider);
        $this->assertAttributeEquals(LeakyBucket::DEFAULT_LEAK_RATE,'leakRate',$provider);
        $this->assertAttributeEmpty('storage',$provider);
    }

    /**
     * Should not be able to create an instance with zeroed capacity.
     */
    public function testConstructWithZeroedCapacity()
    {
        $this->expectException(LeakyBucketException::class);
        new LeakyBucket(0);
    }

    /**
     * Should not be able to create an instance with zeroed leak rate.
     */
    public function testConstructWithZeroedLeakRate()
    {
        $this->expectException(LeakyBucketException::class);
        new LeakyBucket(1,0);
    }

    /**
     * Should not be able to get a storage without previously setting it.
     */
    public function testGetStorageWithoutStorage()
    {
        $provider = new LeakyBucket();

        $this->expectException(StorageNotDefinedException::class);
        $provider->getStorage();
    }

    /**
     * Should not be able to increment a bucket without a storage.
     */
    public function testIncrementBucketWithoutStorage()
    {
        $provider = new LeakyBucket();

        $this->expectException(StorageNotDefinedException::class);
        $provider->incrementUsage('foo');
    }

    /**
     * Should be able to increment an empty bucket.
     */
    public function testIncrementEmptyBucket()
    {
        $provider = $this->getProviderWithStorage(10,1);
        $provider->incrementUsage('foo',1);
        $this->assertEquals(1,$provider->getUsage('foo'));
    }

    /**
     * Should be able to increment a non empty bucket.
     */
    public function testIncrementNonEmptyBucket()
    {
        $provider = $this->getProviderWithStorage(10,1);
        $provider->incrementUsage('foo',1);

        $this->assertEquals(1,$provider->getUsage('foo'));

        $provider->incrementUsage('foo',2);

        $this->assertEquals(3,$provider->getUsage('foo'));
    }

    /**
     * Should not be able to increment a full bucket.
     */
    public function testIncrementFullBucket()
    {
        $provider = $this->getProviderWithStorage(10,1);
        $provider->incrementUsage('foo',10);

        $this->assertEquals(10,$provider->getUsage('foo'));

        $this->expectException(LeakyBucketException::class);
        $provider->incrementUsage('foo',1);
    }

    /**
     * Should not be able to get the usage without a storage.
     */
    public function testGetUsageWithoutStorage()
    {
        $provider = new LeakyBucket();

        $this->expectException(StorageNotDefinedException::class);
        $provider->getUsage('foo');
    }

    /**
     * Should be able to get the usage from an empty bucket.
     */
    public function testGetUsageFromEmptyBucket()
    {
        $provider = $this->getProviderWithStorage();
        $this->assertEquals(0,$provider->getUsage('foo'));
    }

    /**
     * Should be able to get the usage from a non empty bucket.
     */
    public function testGetUsageFromNonEmptyBucket()
    {
        $provider = $this->getProviderWithStorage();
        $provider->incrementUsage('foo');

        $this->assertEquals(1,$provider->getUsage('foo'));
    }

    /**
     * Should be able to get the ratio from the default bucket.
     */
    public function testGetRatioFromDefaultBucket()
    {
        $provider = new LeakyBucket();
        $this->assertEquals(1,$provider->getRatio('foo'));
    }

    /**
     * Should be able to get the bucket limit.
     */
    public function testGetBucketLimit()
    {
        $provider = new LeakyBucket(100);
        $this->assertEquals(100,$provider->getLimit('foo'));
    }

    /**
     * Should not be able to check for limit without a storage.
     */
    public function testHasLimitWithoutStorage()
    {
        $provider = new LeakyBucket();

        $this->expectException(StorageNotDefinedException::class);
        $provider->hasLimit('foo');
    }

    /**
     * Should be able to check for limit on an empty bucket.
     */
    public function testHasLimitForEmptyBucket()
    {
        $provider = $this->getProviderWithStorage();

        $this->assertTrue($provider->hasLimit('foo'));
    }

    /**
     * Should be able to check for limit on a non empty bucket.
     */
    public function testHasLimitForNonEmptyBucket()
    {
        $provider = $this->getProviderWithStorage(100);
        $provider->incrementUsage('foo',2);
        $this->assertTrue($provider->hasLimit('foo'));
    }

    /**
     * Should be able to check for limit in a full bucket.
     */
    public function testHasLimitForFullBucket()
    {
        $provider = $this->getProviderWithStorage(10);
        $provider->incrementUsage('foo',11);
        $this->assertFalse($provider->hasLimit('foo'));
    }

    /**
     * Should not be able to get the remaining number without a storage.
     */
    public function testGetRemainingWithoutStorage()
    {
        $provider = new LeakyBucket();

        $this->expectException(StorageNotDefinedException::class);
        $provider->getRemaining('foo');
    }

    /**
     * Should be able to get the remaining number from an empty bucket.
     */
    public function testGetRemainingForEmptyBucket()
    {
        $provider = $this->getProviderWithStorage();
        $this->assertGreaterThan(0,$provider->getRemaining('foo'));
    }

    /**
     * Should be able to get the remaining number from a non empty bucket.
     */
    public function testGetRemainingForNonEmptyBucket()
    {
        $provider = $this->getProviderWithStorage(10);
        $provider->incrementUsage('foo',2);
        $this->assertEquals(8,$provider->getRemaining('foo'));
    }

    /**
     * Should be able to get the remaining number from a full bucket.
     */
    public function testGetRemainingForFullBucket()
    {
        $provider = $this->getProviderWithStorage(10);

        $provider->incrementUsage('foo',10);
        $this->assertEquals(0,$provider->getRemaining('foo'));
    }

    /**
     * Should not be able to get the reset time without a storage.
     */
    public function testGetResetWithoutStorage()
    {
        $provider = new LeakyBucket();

        $this->expectException(StorageNotDefinedException::class);
        $provider->getReset('foo');
    }

    /**
     * Should be able to get the reset time from an empty bucket.
     */
    public function testGetResetForEmptyBucket()
    {
        $provider = $this->getProviderWithStorage();
        $this->assertEquals(0,$provider->getReset('foo'));
    }

    /**
     * Should be able to get the reset time from a non empty bucket.
     */
    public function testGetResetForNonEmptyBucket()
    {
        $provider = $this->getProviderWithStorage(10,1);
        $provider->incrementUsage('foo',1);
        $this->assertEquals(1000,$provider->getReset('foo'));
    }

    /**
     * Should be able to get the reset time from a full bucket.
     */
    public function testGetResetForFullBucket()
    {
        $provider = $this->getProviderWithStorage(10,1);
        $provider->incrementUsage('foo',10);
        $this->assertEquals(10000,$provider->getReset('foo'));
    }
}
