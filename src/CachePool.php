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
use \Luminova\Cache\FileCache as SystemFileCache;
use \Luminova\Psr\Cache\CacheItem;
use \Luminova\Psr\Cache\Exceptions\InvalidArgumentException;
use \Psr\Cache\CacheItemInterface;
use \Luminova\Time\Time;
use \DateTimeInterface;
use \DateInterval;

class CachePool implements CacheItemPoolInterface
{
    /**
     * Engin instance
     * 
     * @var SystemFileCache|null $engine
     */
    public ?SystemFileCache $engine = null;

    /**
     * @var array Deferred cache items
     */
    private array $deferred = [];

    /**
     * FileCache constructor.
     * 
     * @param string $storage The storage location and configuration.
     * @param string $folder subfolder path
     */
    public function __construct(string $storage = 'psr_cache_storage', string $folder = 'psr')
    {
        $this->engine = new SystemFileCache($storage, $folder);
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
        if ($key === '') {
            throw new InvalidArgumentException('Cache key must be a valid string');
        }

        $data = $this->engine->getItem($key, false);
        $content = $data['data'] ?? null;
        $isHit = $data === null ? false : !$this->engine->hasExpired($key);

        $item = new CacheItem($key, $content, $isHit);
        $item->expiresAt(static::secondsToDateTime($data['expiration'] ?? 0));
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
            if ($key === '') {
                throw new InvalidArgumentException('Cache key must be a valid string');
            }
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
        if ($key === '') {
            throw new InvalidArgumentException('Cache key must be a valid string');
        }
        return $this->engine->hasItem($key);
    }

    /**
     * Clears the entire cache.
     * 
     * @return bool True on success, false on failure.
     */
    public function clear(): bool
    {
        $this->engine->clear();
        return true;
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
        if ($key === '') {
            throw new InvalidArgumentException('Cache key must be a valid string');
        }

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
        if ($keys === []) {
            throw new InvalidArgumentException('Cache key must be a valid string');
        }
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
     * @return bool Always returns true.
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $this->deferred[$key] = $item;

        return true;
    }

    /**
     * Commits any deferred cache items.
     * 
     * @return bool True if any deferred items were saved, false otherwise.
     */
    public function commit(): bool
    {
        $count = 0;
        foreach ($this->deferred as $key => $item) {
            $isHit = $item->get() !== null && !$this->engine->hasExpired($key);
            if ($isHit) {
                $this->saveItem($item, true);
                $count++;
            }
        }

        $this->deferred = [];

        return $count !== 0;
    }

    /**
     * Convert DateInterval to seconds.
     * 
     * @param DateInterval $interval The DateInterval object.
     * 
     * @return int The number of seconds.
     */
    private function intervalToSeconds(DateInterval $interval): int
    {
        $reference = new Time();
        $endTime = $reference->add($interval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }

     /**
     * Convert seconds to a DateInterval object.
     *
     * @param int $seconds The number of seconds.
     * @return DateInterval The DateInterval object representing the duration in seconds.
     */
    private static function secondsToDateInterval(int $seconds): DateInterval
    {
        $intervalString = "PT{$seconds}S";
        return DateInterval::createFromDateString($intervalString);
    }

    /**
     * Convert Seconds to DateTimeInterface.
     * 
     * @param int $seconds seconds
     * 
     * @return DateTimeInterface $dateTime
     */
    private static function secondsToDateTime(int $seconds): DateTimeInterface
    {
        $dateTime = (new Time())->modify('+' . $seconds . ' seconds');

        return $dateTime;
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
