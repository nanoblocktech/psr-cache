<?php 
/**
 * Luminova Framework
 *
 * @package Luminova
 * @author Ujah Chigozie Peter
 * @copyright (c) Nanoblock Technology Ltd
 * @license See LICENSE file
 */
namespace Luminova\Psr\Cache;

use \Psr\SimpleCache\CacheInterface;
use \Luminova\Cache\FileCache;
use \DateInterval;
use \Luminova\Psr\Cache\Helper\Helper;

class SimpleCache implements CacheInterface
{
    /**
     * Engin instance
     * 
     * @var FileCache|null $engine
    */
    public ?FileCache $engine = null;

    /**
     * FileCache constructor.
     * 
     * @param string $storage The storage location and configuration.
     * @param string $folder subfolder path
    */
    public function __construct(string $storage = 'psr_cache_storage', string $folder = 'psr')
    {
        $this->engine = new FileCache($storage, $folder);
        $this->engine->create();
    }

    /**
     * Retrieves an item from the cache.
     * 
     * @param string $key The key for the item to retrieve.
     * @param mixed $default Default value 
     * 
     * @return mixed The cache item.
     * 
     * @throws InvalidArgumentException If the key is not a legal value.
    */
    public function get(string $key, mixed $default = null): mixed
    {
        Helper::isKeyLegal($key);

        $content = $this->engine->getItem($key);

        return $content ?? $default;
    }

    /**
     * Persists a cache item immediately.
     * 
     * @param string $key Cache key
     * @param string $value The cache value to save.
     * 
     * @return bool True if saved successfully, false otherwise.
    */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        Helper::isKeyLegal($key);

        return $this->setItem($key, $value, $ttl);
    }

     /**
     * Determines whether an item exists in the cache.
     * 
     * @param string $key The cache item key.
     * 
     * @return bool True if item exists, false otherwise.
     * @throws InvalidArgumentException
    */
    public function has(string $key): bool
    {
        Helper::isKeyLegal($key);

        return $this->engine->hasItem($key);
    }

    /**
     * Deletes an item from the cache.
     * 
     * @param string $key The cache item key.
     * 
     * @return bool True if item was deleted, false otherwise.
     * @throws InvalidArgumentException
    */
    public function delete(string $key): bool
    {
        Helper::isKeyLegal($key);

        return $this->engine->deleteItem($key);
    }

    /**
     * Clears the entire cache.
     * 
     * @return bool True on success, false on failure.
    */
    public function clear(): bool
    {
        return $this->engine->clear();
    }

    /**
     * Retrieves multiple cache items at once.
     * 
     * @param iterable<string> $keys The array keys of the items to retrieve.
     * 
     * @return iterable An array of items keyed by the cache keys.
     * @throws InvalidArgumentException
    */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            Helper::isKeyLegal($key);

            $items[$key] = $this->engine->getItem($key) ?? $default;
        }

        return $items;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     * 
     * @param iterable<string, mixed> $values The cache item to save key => value
     * @param null|int|DateInterval $ttl Expiration
     * 
     * @return bool True if saved successfully, false otherwise.
    */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {

        $count = 0;

        foreach($values as $key => $value){
            Helper::isKeyLegal($key);

            if($this->setItem($key, $value, $ttl)){
                $count++;
            }
        }

        return $count !== 0;
    }

    /**
     * Deletes multiple items from the cache.
     * 
     * @param iterable<string> $keys The cache item keys to delete. [key, key2, ..., key]
     * 
     * @return bool True if items were deleted, false otherwise.
     * @throws InvalidArgumentException
    */
    public function deleteMultiple(iterable $keys): bool
    {
        Helper::areKeysLegal($keys);

        return $this->engine->deleteItems($keys);
    }

    /**
     * Save an item to the cache.
     * 
     * @param string $key The cache item to save.
     * @param mixed $content content to save 
     * @param null|int|DateInterval $expiration Expiration
     * 
     * @return bool True if item was saved, false otherwise.
    */
    private function setItem(string $key, mixed $content, null|int|DateInterval $expiration = null): bool
    {
  
        if($content === null || $content === '' || $content === []){
            return false;
        }
        
        if($expiration === null){
            $expiration = 24 * 60 * 60;
        }else if($expiration instanceof DateInterval){
            $expiration = FileCache::ttlToSeconds($expiration);
        }

        return $this->engine->setItem($key, $$content, $expiration, null, true);
    }
}
