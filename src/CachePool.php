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
use \Luminova\Base\BaseCache;
use \Luminova\Cache\FileCache;
use \Luminova\Cache\MemoryCache;
use \Luminova\Psr\Cache\CacheItem;
use \Psr\Cache\CacheItemInterface;
use \Luminova\Psr\Cache\Helper\Helper;

class CachePool implements CacheItemPoolInterface
{
    /**
     * Memcached driver.
     * 
     * @var string MEMCACHED
     */
    public const MEMCACHED = 'memcached';

    /**
     * Filesystem cache driver.
     * 
     * @var string FILECACHE
     */
    public const FILECACHE = 'filesystem';

    /**
     * Engin instance.
     * 
     * @var BaseCache|null $engine
     */
    public ?BaseCache $engine = null;

    /**
     * Cache instance.
     * 
     * @var self|null $instance
     */
    private static ?self $instance = null;

    /**
     * Deferred cache items. 
     * 
     * @var array<string,mixed> $deferred
     */
    private array $deferred = [];

    /**
     * Deferred passed transactions cache keys.
     * 
     * @var array $passed
     */
    private array $passed = [];

    /**
     * Initializes psr cache pool instance using either a file-based cache, a memory-based cache (Memcached), 
     * or the driver specified by the `preferred.cache.driver` configuration if no driver is provided.
     *
     * @param string|null $storage The cache storage name used to distinguish different cache pools or spaces.
     * @param string|null $subfolderOrId Optional subfolder for file-based cache or persistent ID for Memcached.
     * @param string|null $driver Optional cache driver. Defaults to `SimpleCache::FILECACHE` if not provided.
     *                            Accepts `SimpleCache::FILECACHE` or `SimpleCache::MEMCACHED`.
     */
    public function __construct(
        private string|null $storage = 'psr_cache_storage', 
        private string|null $subfolderOrId = 'psr',
        private string|null $driver = self::FILECACHE
    ) {
        $this->driver ??= env('preferred.cache.driver', 'filesystem');
        $this->engine = ($this->driver === self::MEMCACHED) 
            ? new MemoryCache($this->storage, $this->subfolderOrId)
            : new FileCache($this->storage, $this->subfolderOrId);
    }

    /**
     * Creates or returns a singleton instance of the class using file-based caching.
     *
     * @param string|null $storage Optional storage name to be used in the file cache.
     * @param string|null $subfolder Optional subfolder name for file-based storage.
     * 
     * @return static Returns a singleton instance of the class with a file-based cache engine.
     */
    public static function withFileCache(?string $storage = null, ?string $subfolder = null): static 
    {
        if (!self::$instance instanceof self) {
            self::$instance = new static($storage, $subfolder, self::FILECACHE);
        }

        return self::$instance;
    }

    /**
     * Creates or returns a singleton instance of the class using Memcached.
     *
     * @param string|null $storage Optional storage name to be used in the Memcached engine.
     * @param string|null $persistent_id Optional persistent ID for Memcached sessions.
     * 
     * @return static Returns a singleton instance of the class with a memory-based cache engine.
     */
    public static function withMemCache(?string $storage = null, ?string $persistent_id = null): static 
    {
        if (!self::$instance instanceof self) {
            self::$instance = new static($storage, $persistent_id, self::MEMCACHED);
        }

        return self::$instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItem
    {
        Helper::assertLegalKey($key);

        $data = $this->engine->getItem($key, false);
        $content = $data['data'] ?? null;
        $isHit = ($data === null) 
            ? false 
            : !$this->engine->hasExpired($key);

        $item = new CacheItem($key, $content, $isHit);
        $item->expiresAt(Helper::secondsToDateTime($data['expiration'] ?? 0));
        $item->expiresAfter($data['expireAfter'] ?? null);

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->getItem($key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        Helper::assertLegalKey($key);
        return $this->engine->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->engine->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        Helper::assertLegalKey($key);
        return $this->engine->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        Helper::assertLegalKeys($keys);
        return $this->engine->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        if(!$item instanceof CacheItem){
            return false;
        }

        $key = $item->getKey();
        $expired = $item->get() === null || $this->engine->hasExpired($key);

        return $this->saveItem($item, $expired);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        if(!$item instanceof CacheItem){
            return false;
        }

        $key = $item->getKey();
        if(isset($this->deferred[$key]) || $item->get() === null || $this->engine->hasExpired($key)){
            return false;
        }

        $this->deferred[$key] = clone $item;

        return true;
    }

    /**
     * {@inheritdoc}
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
     * @return bool Return true if rollback was successful, otherwise false.
     */
    public function rollback(): bool
    {
        if ($this->passed === []) {
            return true;
        }

        if($this->deleteItems($this->passed)){
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
     * @return bool Return true if item was saved, false otherwise.
     */
    private function saveItem(CacheItemInterface $item, bool $expired = true): bool
    {
        if ($expired){
            $value = $item->get();
            return ($value !== null) 
                ? $this->engine->setItem($item->getKey(), $value, $item->expiration, $item->expireAfter, true)
                : false;
        }
        
        return true;
    }
}
