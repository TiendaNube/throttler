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
}
