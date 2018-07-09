<?php declare(strict_types=1);

namespace TiendaNube\Throttler\Storage;

interface StorageInterface
{
    /**
     * Sets the storage options.
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options);

    /**
     * Get the current storage options.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Get an item from storage or false if the item does not exists.
     *
     * @param string $key
     * @return bool|mixed Returns the item value or false if the item does not exists
     */
    public function getItem(string $key);

    /**
     * Check if an item exists in storage.
     *
     * @param string $key
     * @return bool
     */
    public function hasItem(string $key): bool;

    /**
     * Store an item in the storage.
     *
     * @param string $key
     * @param mixed $value
     * @return bool Returns true if the item was successfully stored otherwise false
     */
    public function setItem(string $key, $value): bool;

    /**
     * Replace an item in the storage resetting its TTL.
     *
     * @param string $key
     * @param mixed $value
     * @return bool Returns true if the item was successfully replaced otherwise false
     */
    public function replaceItem(string $key, $value): bool;

    /**
     * Resets an item TTL in the storage.
     *
     * @param string $key
     * @return bool Returns tru if the item timestamp was successfully touched otherwise false
     */
    public function touchItem(string $key): bool;

    /**
     * Remove an item from the storage.
     *
     * @param string $key
     * @return bool Returns true if the item was successfully removed from the storage otherwise false
     */
    public function removeItem(string $key): bool;
}
