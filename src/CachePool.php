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

use \Psr\Cache\CacheItemPoolInterface;
use \Luminova\Cache\FileCache;
use \Luminova\Psr\Cache\CacheItem;
use \Psr\Cache\CacheItemInterface;
use \Luminova\Psr\Cache\Helper\Helper;

class CachePool implements CacheItemPoolInterface
{
    /**
     * Engin instance
     * 
     * @var FileCache|null $engine
    */
    public ?FileCache $engine = null;

    /**
     * @var array<string, mixed> $deferred Deferred cache items
    */
    private array $deferred = [];

    /**
     * @var array $passed Deferred passed transactions cache keys
    */
    private array $passed = [];

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
     * 
     * @return CacheItem The cache item.
     * 
     * @throws InvalidArgumentException If the key is not a legal value.
    */
    public function getItem(string $key): CacheItem
    {
        Helper::isKeyLegal($key);

        $data = $this->engine->getItem($key, false);
        $content = $data['data'] ?? null;
        $isHit = $data === null ? false : !$this->engine->hasExpired($key);

        $item = new CacheItem($key, $content, $isHit);
        $item->expiresAt(Helper::secondsToDateTime($data['expiration'] ?? 0));
        $item->expiresAfter($data['expireAfter'] ?? null);

        return $item;
    }

    /**
     * Retrieves multiple cache items at once.
     * 
     * @param array $keys The array keys of the items to retrieve.
     * 
     * @return iterable An array of items keyed by the cache keys.
     * @throws InvalidArgumentException
    */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            Helper::isKeyLegal($key);

            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * Determines whether an item exists in the cache.
     * 
     * @param string $key The cache item key.
     * 
     * @return bool True if item exists, false otherwise.
     * @throws InvalidArgumentException
    */
    public function hasItem(string $key): bool
    {
        Helper::isKeyLegal($key);

        return $this->engine->hasItem($key);
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
     * Deletes an item from the cache.
     * 
     * @param string $key The cache item key.
     * 
     * @return bool True if item was deleted, false otherwise.
     * @throws InvalidArgumentException
    */
    public function deleteItem(string $key): bool
    {
        Helper::isKeyLegal($key);

        return $this->engine->deleteItem($key);
    }

    /**
     * Deletes multiple items from the cache.
     * 
     * @param array $keys The cache item keys to delete.
     * 
     * @return bool True if items were deleted, false otherwise.
     * @throws InvalidArgumentException
    */
    public function deleteItems(array $keys): bool
    {
        Helper::areKeysLegal($keys);

        return $this->engine->deleteItems($keys);
    }

    /**
     * Persists a cache item immediately.
     * 
     * @param CacheItemInterface $item The cache item to save.
     * 
     * @return bool True if saved successfully, false otherwise.
    */
    public function save(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $expired = $item->get() === null || $this->engine->hasExpired($key);

        return $this->saveItem($item, $expired);
    }

    /**
     * Save a deferred cache item.
     * 
     * @param CacheItemInterface $item The cache item to save.
     * 
     * @return bool False if the item could not be queued or if a commit was attempted and failed. True otherwise.
    */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        if(isset($this->deferred[$key]) || $item->get() === null || $this->engine->hasExpired($key)){

            return false;

        }

        $this->deferred[$key] = clone $item;

        return true;
    }

    /**
     * Commits any deferred cache items.
     * 
     * @return bool True if all not-yet-saved items were successfully saved or there were none. False otherwise.
    */
    public function commit(): bool
    {
        if ($this->deferred === []) {
            return true;
        }

        $this->passed = [];
        $failed = [];

        foreach ($this->deferred as $key => $item) {
            if ($this->saveItem($item, true)) {
                $this->passed[] = $key;
            }else{
                $failed[$key] = $item;
            }
        }

        if($failed === []){
            $this->passed = [];

            return true;
        }
        
        $this->deferred = $failed;
        
        return false;
    }

    /**
     * Rollback deferred transaction if any was failed 
     * 
     * @return bool if rollback was successful 
    */
    public function rollback(): bool
    {
        if ($this->passed === []) {
            return true;
        }

        if($this->deleteItems( $this->passed )){
            $this->passed = [];

            return true;
        }

        return false;
    }

    /**
     * Save an item to the cache.
     * 
     * @param CacheItemInterface $item The cache item to save.
     * @param bool $expired Whether the item is expired.
     * 
     * @return bool True if item was saved, false otherwise.
    */
    private function saveItem(CacheItemInterface $item, bool $expired = true): bool
    {
        if ($expired){
            $value = $item->get();
            if($value !== null){
                return $this->engine->setItem($item->getKey(), $value, $item->expiration, $item->expireAfter, true);
            }

            return false;
        }
        
        return true;
    }
}