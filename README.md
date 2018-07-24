# Throttler

This is yet another Throttling library for PHP applications, that provides a throttling interface and a flexible API for implementing custom throttling algorithms (aka providers) and storage strategies.

## Installation

```
$ composer require tiendanube/throttler
```

## Basic Usage

```php
<?php

$storage = new TiendaNube\Throttler\Storage\InMemory();
$provider = new TiendaNube\Throttler\Provider\LeakyBucket();

$throttler = new \TiendaNube\Throttler\Throttler($provider,$storage);
if (!$throttler->throttle('client:1')) {
    // allow
} else {
    // deny
}
```

## Providers

Currently, the only available provider is the Leaky Bucket algorithm. 

If you need a different algorithm for throttling, just create your own class and implement the [ProviderInterface](src/TiendaNube/Throttler/Provider/ProviderInterface.php).

## Storage Strategies

Currently, the only available storage strategy is the InMemory adapter, that stores the information in memory, that is useful for CLI applications.

If you want to implement a custom storage (for example, a Redis database), just create your own class and implement the [StorageInterface](src/TiendaNube/Throttler/Storage/StorageInterface.php).

## Development and Tests

Feel free to contribute with bug fixing, new providers and storage strategies.

To start contributing, just make a fork of this repo, create a branch which the name explains what you are doing, code your solution and send us a Pull Request.

### Development Installation

```
$ composer install --dev
```

### Running the Tests

```
$ ./vendor/bin/phpunit
```

## Documentation

Coming soon.

## License

This library is licensed under the [MIT license](LICENSE).
