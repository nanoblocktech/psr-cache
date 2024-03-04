# PSR CACHE

PSR Cache for Luminova framework `CachePool`, `SimpleCache`.


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

// Rollback If any deferred commit failed to save, if you prefer not to recommit
$pool->rollback();

// Clear a specific cache item
$pool->deleteItem('cache_key');

// Clear multiple cache items
$pool->deleteItems(['key1', 'key2']);

// Clear all cache items
$pool->clear();
```

```php
use \Luminova\Psr\Cache\SimpleCache;

$simple = new SimpleCache('my_cache', 'my_cache_folder_name');

// Set a cache item
$data = $simple->get('cache_key', 'NO_DATA');
if($item === 'NO_DATA'){
    $data = 'This is my cache data';
    $simple->set('cache_key', $data);
}
```

### SimpleCache Methods 


```php
// Get Item
$simple->get('cache_key', 'default value');

// Get Items
$simple->getMultiple(['key1', 'key2'], 'default on empty key value');

// Has Item
$simple->has('cache_key');

// Save Item
$simple->set('cache_key', 'data to save', 60)

// Save Multiple items 
$simple->setMultiple(['key1' => 'data 1', 'key2' => 'data 2'], 60);

// Clear a specific cache item
$simple->delete('cache_key');

// Clear multiple cache items
$simple->deleteMultiple(['key1', 'key2']);

// Clear all cache items
$simple->clear();
```
