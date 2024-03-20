# PSR CACHE

PSR Cache for [Luminova Framework](https://github.com/luminovang/luminova/) `CachePool`, `SimpleCache`.
To use this library you need to install Luminova Framework first.


### Installation 

Via Composer 

```bash 
composer require nanoblocktech/psr-cache
```

### Usage 
```php
use \Luminova\Psr\Cache\CachePool;
use \Luminova\Psr\Cache\CacheItem;

$pool = new CachePool('my_cache', 'my_cache_folder_name');

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

### CachePool Methods 

Initialize the class  with `storage` location name and `folder` subfolder name.
```php 
$pool = new CachePool(string $storage = 'psr_cache_storage', string $folder = 'psr');
```

```php
// Retrieves an item from the cache.
$pool->getItem('cache_key'): CacheItem;

// Retrieves multiple cache items at once.
$pool->getItems(array ['key1', 'key2']): iterable<key, CacheItem>;

// Determines whether an item exists in the cache.
$pool->hasItem(string 'cache_key'): bool;

// Persists a cache item immediately.
$pool->save(CacheItemInterface $item): bool;

// Save a deferred cache item.
$pool->saveDeferred(CacheItemInterface $item): bool;

// Commits any deferred cache items.
$pool->commit(): bool;

// Rollback If any deferred commit failed to save, if you prefer not to recommit
$pool->rollback(): bool;

// Deletes an item from the cache.
$pool->deleteItem(string 'cache_key'): bool;

// Deletes multiple items from the cache.
$pool->deleteItems(array ['key1', 'key2']): bool;

// Clear all cached entries 
$pool->clear(): bool;
```

```php
use \Luminova\Psr\Cache\SimpleCache;

$simple = new SimpleCache(string 'my_cache', string 'my_cache_folder_name');

// Set a cache item
$data = $simple->get('cache_key', 'NO_DATA');
if($item === 'NO_DATA'){
    $data = 'This is my cache data';
    $simple->set('cache_key', $data);
}
```

### SimpleCache Methods 

Initialize the class  with `storage` location name and `folder` subfolder name.

```php
$simple = new ‎SimpleCache‎(string $storage = 'psr_cache_storage', string $folder = 'psr');
```

```php
// Retrieves an item from the cache.
$simple->get(string 'cache_key', mixed 'default value'): mixed;

// Retrieves multiple cache items at once.
$simple->getMultiple(array ['key1', 'key2'], 'default on empty key value'): iterable<key, mixed>;

// Determines whether an item exists in the cache.
$simple->has(string 'cache_key'): bool;

// Persists a cache item immediately with an optional TTL.
$simple->set(string 'cache_key', mixed 'data to save', null|int|DateInterval 60): bool;

// Persists a set of key => value pairs in the cache, with an optional TTL.
$simple->setMultiple(array ['key1' => 'data 1', 'key2' => 'data 2'], null|int|DateInterval 60): bool;

// Deletes an item from the cache.
$simple->delete(string 'cache_key'): bool;

// Deletes multiple items from the cache.
$simple->deleteMultiple(array ['key1', 'key2']): bool;

// Clears all cache entries.
$simple->clear(): bool;
```

### CacheItem Methods 

Initialize the class with `key`, `content` to save and specify the `hit` state optionally.

```php
$item = new CacheItem(string $key, mixed $content = null, ?bool $isHit = null);
```

```php
//Retrieves the key of the cache item.
$item->getKey(): string;

//Retrieves the value of the cache item.
$item->get(): mixed;

//Check if the cache item is a hit.
$item->isHit(): bool;

//Sets the value of the cache item.
$item->set(mixed $value): static;

//Sets the expiration time of the cache item.
$item->expiresAt(?DateTimeInterface $expiration): static;

//Sets the expiration time of the cache item relative to the current time.
$item->expiresAfter(int|DateInterval|null $time): static;
```
