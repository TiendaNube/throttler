<?php declare(strict_types=1);

namespace TiendaNube\Throttler\Storage;

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
        'ttl' => 300000, // 5 minutes in milliseconds
    ];

    /**
     * InMemory constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (!is_null($options) && count($options) > 0) {
            $this->setOptions($options);
        }
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
        if ($this->hasItem($key)) {
            $current = $this->items[$key];
            return $current['value'];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        if (array_key_exists($key,$this->items)) {
            $current = $this->items[$key];

            $diff = microtime(true) - $current['timestamp'];
            $ttlInMicroseconds = $this->options['ttl'] * 1000;

            if ($diff > $ttlInMicroseconds) {
                return true;
            } else {
                unset($this->items[$key]);
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setItem(string $key, $value): bool
    {
        $current = $this->getItem($key);
        $new = $this->getItemObject($value,($current) ? $current['timestamp'] : false);

        $this->items[$key] = $new;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceItem(string $key, $value): bool
    {
        $current = $this->getItem($key);

        if ($current) {
            $new = $this->getItemObject($value);
            $this->items[$key] = $new;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function touchItem(string $key): bool
    {
        $current = $this->getItem($key);

        if ($current) {
            return $this->replaceItem($key,$current['value']);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function removeItem(string $key): bool
    {
        if ($this->hasItem($key)) {
            unset($this->items[$key]);
            return true;
        }

        return false;
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
