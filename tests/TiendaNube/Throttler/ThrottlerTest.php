<?php declare(strict_types=1);

namespace TiendaNube\Throttler;

use PHPUnit\Framework\TestCase;
use TiendaNube\Throttler\Exception\StorageNotDefinedException;
use TiendaNube\Throttler\Provider\LeakyBucket;
use TiendaNube\Throttler\Storage\InMemory;

class ThrottlerTest extends TestCase
{
    /**
     * Factory a Throttler instance with LeakyBucket algo and InMemory storage.
     *
     * @param int $capacity
     * @param int $leakRate
     * @return Throttler
     * @throws Exception\LeakyBucketException
     */
    private function getThrottlerWithProviderAndStorage(
        int $capacity = LeakyBucket::DEFAULT_CAPACITY,
        int $leakRate = LeakyBucket::DEFAULT_LEAK_RATE
    ) {
        return new Throttler(new LeakyBucket($capacity,$leakRate), new InMemory());
    }

    /**
     * Should be able to create an instance without a storage.
     */
    public function testConstructWithoutStorage()
    {
        $throttler = new Throttler(new LeakyBucket());
        $this->assertInstanceOf(Throttler::class,$throttler);
    }

    /**
     * Should not be able to throttle without a storage.
     */
    public function testThrottleWithoutStorage()
    {
        $throttler = new Throttler(new LeakyBucket());

        $this->expectException(StorageNotDefinedException::class);
        $throttler->throttle('foo');
    }

    /**
     * Should be able to allow a request if has limit.
     */
    public function testThrottleRequestWithAvailableLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage();
        $this->assertFalse($throttler->throttle('foo'));
    }

    /**
     * Should not be able to allow a request if there is no limit.
     */
    public function testThrottleRequestWithoutLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(1);

        $this->assertFalse($throttler->throttle('foo'));
        $this->assertTrue($throttler->throttle('foo'));
    }

    /**
     * Should be able to allow a request after the leak time.
     */
    public function testThrottleRequestAfterLeakTime()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(1);

        $this->assertFalse($throttler->throttle('foo'));
        usleep(500000);
        $this->assertFalse($throttler->throttle('foo'));
    }

    /**
     * Should be able to allow a request after the leak time using the internal scheduler.
     */
    public function testThrottlerRequestWithInternalScheduler()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(1);
        $this->assertFalse($throttler->throttle('foo'));
        $this->assertFalse($throttler->throttle('foo',true));
    }

    /**
     * Should be able to get the ratio
     */
    public function testGetRatio()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage();
        $this->assertEquals(1,$throttler->getRatio('foo'));
    }

    /**
     * Should be able to get the usage of an empty storage
     */
    public function testGetUsageOfEmptyBucket()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage();
        $this->assertEquals(0,$throttler->getUsage('foo'));
    }

    /**
     * Should be able to get the usage of a non-empty storage
     */
    public function testGetUsageOfNonEmptyBucket()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage();
        $throttler->throttle('foo');
        $this->assertNotEquals(0,$throttler->getUsage('foo'));
    }

    /**
     * Should be able to get the throttling limit
     */
    public function testGetLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(10);
        $this->assertEquals(10,$throttler->getLimit('foo'));
    }

    /**
     * Should be able to check if has limit with available limit
     */
    public function testHasLimitWithAvailableLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(10);
        $this->assertTrue($throttler->hasLimit('foo'));
        $throttler->throttle('foo');
        $this->assertTrue($throttler->hasLimit('foo'));
    }

    /**
     * Should be able to check if has limit without available limit
     */
    public function testHasLimitWithoutAvailableLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(1);
        $this->assertTrue($throttler->hasLimit('foo'));
        $throttler->throttle('foo');
        $this->assertFalse($throttler->hasLimit('foo'));
    }

    /**
     * Should be able to get the remaining limit
     */
    public function testGetRemaining()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(10);
        $this->assertEquals(10,$throttler->getRemaining('foo'));
        $throttler->throttle('foo');
        $this->assertEquals(9,$throttler->getRemaining('foo'));
    }

    /**
     * Should be able to get the estimate with available limit
     */
    public function testGetEstimateWithAvailableLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(10,1);
        $this->assertEquals(0,$throttler->getEstimate('foo'));
        $throttler->throttle('foo');
        $this->assertEquals(0,$throttler->getEstimate('foo'));
    }

    /**
     * Should be able to get the estimate without available limit
     */
    public function testGetEstimateWithoutAvailableLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(1,1);
        $throttler->throttle('foo');
        $this->assertEquals(1000,$throttler->getEstimate('foo'));
    }

    /**
     * Should be able to get the reset time in milliseconds without throttling
     */
    public function testGetResetWithoutThrottling()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage();
        $this->assertEquals(0,$throttler->getReset('foo'));
    }

    /**
     * Should be able to get the reset time in milliseconds, throttling with available limit
     */
    public function testGetResetWithThrottlingWithAvailableLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(10,1);
        $throttler->throttle('foo');
        $this->assertEquals(1000,$throttler->getReset('foo'));
    }

    /**
     * Should be able to get the reset time, throttling, without available limit
     */
    public function testGetResetWithoutAvailableLimit()
    {
        $throttler = $this->getThrottlerWithProviderAndStorage(10,1);
        $throttler->throttle('foo',false,10);
        $this->assertEquals(10000,$throttler->getReset('foo'));
    }
}
