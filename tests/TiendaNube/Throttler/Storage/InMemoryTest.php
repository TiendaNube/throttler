<?php declare(strict_types=1);

namespace TiendaNube\Throttler\Storage;

use PHPUnit\Framework\TestCase;
use TiendaNube\Throttler\Exception\StorageItemNotFoundException;

/**
 * Class InMemoryTest
 *
 * @package TiendaNube\Throttler\Storage
 */
class InMemoryTest extends TestCase
{
    /**
     * Should be able to create an instance of InMemory without arguments in constructor
     * AND the default options must be setted.
     */
    public function testConstructWithoutOptions()
    {
        $storage = new InMemory();
        $options = $storage->getOptions();

        $this->assertGreaterThan(0,count($options));
        $this->assertArrayHasKey('ttl',$options);
    }

    /**
     * Should be able to create an instance of InMemory with custom options in constructor
     * AND the custom options should override the default ones.
     */
    public function testConstructWithOptions()
    {
        $storageDefault = new InMemory();
        $defaultOptions = $storageDefault->getOptions();

        $storageCustom = new InMemory(['ttl' => -1]);
        $customOptions = $storageCustom->getOptions();

        $this->assertGreaterThan(0,count($defaultOptions));
        $this->assertGreaterThan(0,count($customOptions));
        $this->assertArrayHasKey('ttl',$defaultOptions);
        $this->assertArrayHasKey('ttl',$customOptions);
        $this->assertNotEquals($defaultOptions['ttl'],$customOptions['ttl']);
    }

    /**
     * Should be able to create an instance of InMemory with custom invalid options in constructor
     * AND the invalid should not be present in the options array.
     */
    public function testConstructWithInvalidOptions()
    {
        $storage = new InMemory(['foo' => 'bar']);
        $options = $storage->getOptions();

        $this->assertGreaterThan(0,count($options));
        $this->assertArrayNotHasKey('foo',$options);
    }

    /**
     * Should be able to set a new item in the storage
     * AND it should have to be present in the items array.
     */
    public function testSetNewItem()
    {
        $storage = new InMemory();

        $this->assertTrue($storage->setItem('foo',1));
        $this->assertAttributeCount(1,'items',$storage);
    }

    /**
     * Should be able to set an existing item in the storage
     * AND when try to set the item again, it should change the value but not the TTL.
     */
    public function testSetExistentItem()
    {
        $storage = new InMemory();

        // using reflection to get the internal ttl from the persisted value
        $reflector = new \ReflectionClass($storage);
        $property = $reflector->getProperty('items');
        $property->setAccessible(true);

        $this->assertTrue($storage->setItem('foo',1));
        $this->assertAttributeCount(1,'items',$storage);
        $this->assertEquals(1,$storage->getItem('foo'));

        $firstSet = $property->getValue($storage);

        $this->assertTrue($storage->setItem('foo',2));
        $this->assertAttributeCount(1,'items',$storage);
        $this->assertEquals(2,$storage->getItem('foo'));

        $secondSet = $property->getValue($storage);

        $this->assertEquals($firstSet['foo']['timestamp'],$secondSet['foo']['timestamp']);
    }

    /**
     * Should be able to set an item
     * AND check if it exists.
     */
    public function testSetAndHasItem()
    {
        $storage = new InMemory();
        $this->assertTrue($storage->setItem('foo',1));
        $this->assertTrue($storage->hasItem('foo'));
    }

    /**
     * Should be able to set an item
     * AND get it from the storage.
     */
    public function testSetAndGetItem()
    {
        $storage = new InMemory();

        $this->assertTrue($storage->setItem('foo',1));
        $this->assertEquals(1,$storage->getItem('foo'));
    }

    /**
     * Should be able to replace an existing item
     * AND the item timestamp should be refreshed during the replace.
     */
    public function testReplaceExistingItem()
    {
        $storage = new InMemory();

        // using reflection to get the internal ttl from the persisted value
        $reflector = new \ReflectionClass($storage);
        $property = $reflector->getProperty('items');
        $property->setAccessible(true);

        $storage->setItem('foo',1);
        $this->assertEquals(1,$storage->getItem('foo'));

        $firstSet = $property->getValue($storage);

        $this->assertTrue($storage->replaceItem('foo',2));
        $this->assertEquals(2,$storage->getItem('foo'));

        $secondSet = $property->getValue($storage);

        $this->assertNotEquals($firstSet['foo']['timestamp'],$secondSet['foo']['timestamp']);
    }

    /**
     * Should not be able to replace a non existent item.
     */
    public function testReplaceNonExistentItem()
    {
        $storage = new InMemory();

        $this->expectException(StorageItemNotFoundException::class);
        $storage->replaceItem('foo',1);
    }

    /**
     * Should be able to set an item
     * AND touch it to refresh the ttl.
     */
    public function testTouchExistingItem()
    {
        $storage = new InMemory();

        // using reflection to get the internal ttl from the persisted value
        $reflector = new \ReflectionClass($storage);
        $property = $reflector->getProperty('items');
        $property->setAccessible(true);

        $storage->setItem('foo',1);

        $firstSet = $property->getValue($storage);

        $this->assertTrue($storage->touchItem('foo'));

        $secondSet = $property->getValue($storage);

        $this->assertNotEquals($firstSet['foo']['timestamp'],$secondSet['foo']['timestamp']);
    }

    /**
     * Should not be able to touch a non existent item.
     */
    public function testTouchNonExistentItem()
    {
        $storage = new InMemory();

        $this->expectException(StorageItemNotFoundException::class);
        $storage->touchItem('foo');
    }

    /**
     * Should be able to remove an existing item from the storage.
     */
    public function testRemoveExistingItem()
    {
        $storage = new InMemory();
        $storage->setItem('foo',1);

        $this->assertTrue($storage->removeItem('foo'));
        $this->assertAttributeCount(0,'items',$storage);
    }

    /**
     * Should not be able to remove a non existing item from the storage.
     */
    public function testRemoveNonExistentItem()
    {
        $storage = new InMemory();

        $this->expectException(StorageItemNotFoundException::class);
        $storage->removeItem('foo');
    }

    /**
     * Should not report an existing but expired item.
     */
    public function testHasExpiredItem()
    {
        $storage = new InMemory(['ttl' => 100]); // 0.1 seconds in milli
        $storage->setItem('foo',1);

        usleep(500000); // 0.5 seconds wait in microseconds
        $this->assertFalse($storage->hasItem('foo'));
    }

    /**
     * Should not be able to get an existing but expired item.
     */
    public function testGetExpiredItem()
    {
        $storage = new InMemory(['ttl' => 100]);
        $storage->setItem('foo',1);

        usleep(500000);
        $this->assertFalse($storage->getItem('foo'));
    }
}
