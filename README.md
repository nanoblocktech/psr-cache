# PSR CACHE

PSR Cache for Luminova framework 

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

```php
// Get Item
$pool->getItem('cache_key');

// Get Items
$pool->getItems(['key1', 'key2']);

// Has Item
$pool->hasItem('cache_key');

// Save Item
$pool->save($item);

// Save Item Deferred
$pool->saveDeferred($item);

// Commit Item Deferred
$pool->commit();

// Clear a specific cache item
$pool->deleteItem('cache_key');

// Clear multiple cache items
$pool->deleteItems(['key1', 'key2']);

// Clear all cache items
$pool->clear();

```
