<?php declare(strict_types=1);

namespace TiendaNube\Throttler\Storage;
use TiendaNube\Throttler\Exception\StorageItemNotFoundException;

/**
 * Class InMemory
 *
 * @package TiendaNube\Throttler\Storage
 */
class InMemory implements StorageInterface
{
    /**
     * The storage items
     *
     * @var array
     */
    private $items = [];

    /**
     * The storage options array
     *
     * @var array
     */
    private $options = [];

    /**
     * The default storage options array
     * @var array
     */
    private $defaultOptions = [
        'ttl' => 10000, // 10 seconds in milliseconds
    ];

    /**
     * InMemory constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $options = !is_null($options) && count($options) > 0 ? $options : [];
        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $optionsDiff = array_intersect_key($options,$this->defaultOptions);
        $this->options = array_merge($this->defaultOptions,$optionsDiff);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key)
    {
        $item = $this->internalGetItem($key);
        return ($item) ? $item['value'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        return $this->internalHasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function setItem(string $key, $value): bool
    {
        $current = $this->internalGetItem($key);
        $item = $this->getItemObject($value,($current) ? $current['timestamp'] : false);
        $this->internalSetItem($key,$item);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceItem(string $key, $value): bool
    {
        $current = $this->internalGetItem($key);

        if ($current) {
            $item = $this->getItemObject($value);
            $this->internalSetItem($key,$item);

            return true;
        }

        throw new StorageItemNotFoundException('The requested item does not exists in storage');
    }

    /**
     * {@inheritdoc}
     */
    public function touchItem(string $key): bool
    {
        $current = $this->internalGetItem($key);

        if ($current) {
            return $this->replaceItem($key,$current['value']);
        }

        throw new StorageItemNotFoundException('The requested item does not exists in storage');
    }

    /**
     * {@inheritdoc}
     */
    public function removeItem(string $key): bool
    {
        if ($this->internalHasItem($key)) {
            unset($this->items[$key]);
            return true;
        }

        throw new StorageItemNotFoundException('The requested item does not exists in storage');
    }

    /**
     * Get an item from the storage array if it was not expired
     *
     * @param string $key
     * @return bool|mixed
     */
    private function internalGetItem(string $key)
    {
        if ($this->internalHasItem($key)) {
            return $this->items[$key];
        }

        return false;
    }

    /**
     * Checks if an item exists in the storage array and if it is not expired
     *
     * @param string $key
     * @return bool
     */
    private function internalHasItem(string $key): bool
    {
        if (array_key_exists($key,$this->items)) {
            $item = $this->items[$key];

            $diff = (microtime(true) - $item['timestamp']) * 1000; // time difference in milliseconds
            $ttl = $this->options['ttl'];

            if ($diff <= $ttl) {
                return true;
            } else {
                unset($this->items[$key]);
            }
        }

        return false;
    }

    /**
     * Adds an item to the storage array
     *
     * @param string $key
     * @param mixed $value
     */
    private function internalSetItem(string $key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * Create a new item "object" to be stored in the storage array.
     *
     * @param mixed $value
     * @param bool $timestamp
     * @return array
     */
    private function getItemObject($value, $timestamp = false)
    {
        return [
            'value' => $value,
            'timestamp' => $timestamp ?: microtime(true),
        ];
    }
}
