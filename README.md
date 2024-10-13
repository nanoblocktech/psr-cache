# Luminova PSR Cache Interface

The PSR Cache implementation for the [Luminova Framework](https://luminova.ng), [Luminova Framework GitHub](https://github.com/luminovang/luminova/) providing `CachePool` and `SimpleCache` class. 
This library enables the use of both file-based and memory-based (Memcached) caching systems with an easy-to-use API.

For more information read the official [documentation](https://luminova.ng/docs/3.3.0/cache/psr).

---

### Installation

Via Composer:

```bash
composer require nanoblocktech/psr-cache
```

---

## Cache Pool Class

The `CachePool` class provides an interface to manage cache items. 
It supports multiple cache storage driver, such as file-based or memory-based (Memcached) caching.

---

### Usage Example

```php
<?php
use \Luminova\Psr\Cache\CachePool;
use \Luminova\Psr\Cache\CacheItem;

$pool = CachePool::withFileCache('my_cache', 'my_cache_folder');

// Set a cache item
$item = $pool->getItem('cache_key');
$item->set('This is my cache data');
$pool->save($item);

// Get a cache item
$data = $pool->getItem('cache_key')->get();

// Check if the cache item exists
if (!$item->isHit()) {
    $data = databaseLoadData();
    $item->expiresAfter(new DateInterval('PT1H')); 
    $item->set($data);
    $pool->save($item);
} else {
    // Data exists in the cache, use it directly
    $data = $item->get();
}
```

---

### CachePool Class Methods

#### Constructor

```php
$pool = new CachePool(
    string|null $storage = 'psr_cache_storage', 
    string|null $subfolderOrId = 'psr', 
    string|null $driver = CachePool::FILECACHE
);
```

- **$storage**: Cache storage name to differentiate cache spaces (defaults to `'psr_cache_storage'`).
- **$subfolderOrId**: Optional subfolder for file-based cache or Memcached persistent ID (defaults to `'psr'`).
- **$driver**: Optional cache driver, either `CachePool::FILECACHE` (default) or `CachePool::MEMCACHED`.

---

#### Methods

```php
// Retrieve a cache item by key.
$pool->getItem('cache_key'): CacheItem;

// Retrieve multiple cache items.
$pool->getItems(['key1', 'key2']): iterable<key,CacheItem>;

// Check if a cache item exists.
$pool->hasItem('cache_key'): bool;

// Save a cache item.
$pool->save(CacheItemInterface $item): bool;

// Save a deferred cache item.
$pool->saveDeferred(CacheItemInterface $item): bool;

// Commit deferred cache items.
$pool->commit(): bool;

// Rollback deferred cache items.
$pool->rollback(): bool;

// Delete a cache item by key.
$pool->deleteItem('cache_key'): bool;

// Delete multiple cache items.
$pool->deleteItems(array ['key1', 'key2']): bool;

// Clear all cached entries.
$pool->clear(): bool;
```

---

## Simple Cache Class

The `SimpleCache` class provides a simplified interface for interacting with the cache. 
It offers basic operations for storing and retrieving cached data, it supports multiple cache storage driver, such as file-based or memory-based (Memcached) caching.

---

### Usage Example

```php
<?php
use \Luminova\Psr\Cache\SimpleCache;

$simple = SimpleCache::withFileCache('my_cache', 'my_cache_folder_name');

// Set a cache item
$data = $simple->get('cache_key', 'NO_DATA');
if($item === 'NO_DATA'){
    $data = 'This is my cache data';
    $simple->set('cache_key', $data);
}
```

---

### SimpleCache Class Methods

#### Constructor

```php
$simple = new SimpleCache(
    string|null $storage = 'psr_cache_storage', 
    string|null $subfolderOrId = 'psr', 
    string|null $driver = SimpleCache::FILECACHE
);
```

---

#### Methods

```php
// Retrieve a cache item by key, with a default fallback value.
$simple->get('cache_key', 'default_value'): mixed;

// Retrieve multiple cache items.
$simple->getMultiple(array ['key1', 'key2'], 'default_value'): iterable<key, mixed>;

// Check if a cache item exists.
$simple->has('cache_key'): bool;

// Save a cache item with an optional TTL.
$simple->set('cache_key', 'data_to_save', int|DateInterval|null $ttl = 60): bool;

// Save multiple cache items with an optional TTL.
$simple->setMultiple(array ['key1' => 'data1', 'key2' => 'data2'], int|DateInterval|null $ttl = 60): bool;

// Delete a cache item.
$simple->delete('cache_key'): bool;

// Delete multiple cache items.
$simple->deleteMultiple(array ['key1', 'key2']): bool;

// Clear all cache entries.
$simple->clear(): bool;
```

---

## Cache Item Class

The `CacheItem` class represents an individual cache entry.
It includes methods to manipulate and manage the cached data when working with `CachePool` class.

---

### CacheItem Class Methods

#### Constructor

```php
$item = new CacheItem(string $key, mixed $content = null, ?bool $isHit = null);
```

---

#### Methods

```php
// Retrieve the key of the cache item.
$item->getKey(): string;

// Retrieve the value of the cache item.
$item->get(): mixed;

// Check if the cache item is a hit.
$item->isHit(): bool;

// Set the value of the cache item.
$item->set(mixed $value): static;

// Set the expiration time of the cache item.
$item->expiresAt(?DateTimeInterface $expiration): static;

// Set the expiration time relative to the current time.
$item->expiresAfter(int|DateInterval|null $time): static;
```
