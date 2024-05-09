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
use \Luminova\Time\Timestamp;
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
     * {@inheritdoc}
    */
    public function get(string $key, mixed $default = null): mixed
    {
        Helper::assertLegalKey($key);

        $content = $this->engine->getItem($key);

        return $content ?? $default;
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
        $items = [];
        foreach ($keys as $key) {
            Helper::assertLegalKey($key);
            $items[$key] = $this->engine->getItem($key) ?? $default;
        }

        return $items;
    }

    /**
     * {@inheritdoc}
    */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
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
        
        if($expiration === null){
            $expiration = 24 * 60 * 60;
        }elseif($expiration instanceof DateInterval){
            $expiration = Timestamp::ttlToSeconds($expiration);
        }

        return $this->engine->setItem($key, $content, $expiration, null, true);
    }
}
