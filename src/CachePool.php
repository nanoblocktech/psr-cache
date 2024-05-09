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
     * {@inheritdoc}
    */
    public function getItem(string $key): CacheItem
    {
        Helper::assertLegalKey($key);

        $data = $this->engine->getItem($key, false);
        $content = $data['data'] ?? null;
        $isHit = $data === null ? false : !$this->engine->hasExpired($key);

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
        $items = [];
        foreach ($keys as $key) {
            Helper::assertLegalKey($key);

            $items[$key] = $this->getItem($key);
        }

        return $items;
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
