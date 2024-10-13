<?php 
/**
 * Luminova Framework PSR Simple cache.
 *
 * @package Luminova
 * @author Ujah Chigozie Peter
 * @copyright (c) Nanoblock Technology Ltd
 * @license See LICENSE file
 */
namespace Luminova\Psr\Cache;

use \Psr\SimpleCache\CacheInterface;
use \Luminova\Base\BaseCache;
use \Luminova\Cache\FileCache;
use \Luminova\Cache\MemoryCache;
use \Luminova\Time\Timestamp;
use \DateInterval;
use \Luminova\Psr\Cache\Helper\Helper;

class SimpleCache implements CacheInterface
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
     * Initializes psr simple cache instance using either a file-based cache, a memory-based cache (Memcached), 
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
    public function get(string $key, mixed $default = null): mixed
    {
        Helper::assertLegalKey($key);
        return $this->engine->getItem($key) ?? $default;
    }

    /**
     * {@inheritdoc}
    */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        Helper::assertLegalKey($key);
        return $this->setItem($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        Helper::assertLegalKey($key);
        return $this->engine->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        Helper::assertLegalKey($key);
        return $this->engine->deleteItem($key);
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
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            Helper::assertLegalKey($key);
            yield $key => $this->engine->getItem($key) ?? $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $count = 0;

        foreach($values as $key => $value){
            Helper::assertLegalKey($key);
            if($this->setItem($key, $value, $ttl)){
                $count++;
            }
        }

        return $count !== 0;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        Helper::assertLegalKeys($keys);
        return $this->engine->deleteItems($keys);
    }

    /**
     * Save an item to the cache.
     * 
     * @param string $key The cache item to save.
     * @param mixed $content content to save 
     * @param DateInterval|int|null $expiration Expiration
     * 
     * @return bool Return true if the item was saved, false otherwise.
     */
    private function setItem(string $key, mixed $content, DateInterval|int|null $expiration = null): bool
    {
        if(empty($content)){
            return false;
        }
        
        $expiration = ($expiration instanceof DateInterval) 
            ? Timestamp::ttlToSeconds($expiration)
            : ($expiration ?? 24 * 60 * 60);

        return $this->engine->setItem($key, $content, $expiration);
    }
}
