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

use \Psr\Cache\CacheItemInterface;
use \DateTimeInterface;
use \DateInterval;

class CacheItem implements CacheItemInterface
{
    /**
     * @var string $key cache key
    */
    private string $key;

    /**
     * @var mixed $content cache content
    */
    private mixed $content;

    /**
     * @var bool $isHit cache hit
    */
    private bool $isHit;

    /**
     * @var  ?DateTimeInterface $expiration cache expiration
    */
    public ?DateTimeInterface $expiration = null;

    /**
     * @var int|DateInterval|null $expireAfter cache expiration after
    */
    public int|DateInterval|null $expireAfter = null;

    /**
     * Constructor.
     *
     * @param string $key The cache item key.
     * @param mixed $value The cache item value.
     * @param bool $isHit Indicates if the cache item was a hit.
     */
    public function __construct(string $key, mixed $content = null, ?bool $isHit = null)
    {
        $this->key = $key;
        $this->content = $content;
        $this->isHit = $isHit === null ? $content !== null : $isHit;
    }

    /**
     * Retrieves the key of the cache item.
     *
     * @return string The cache item key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Retrieves the value of the cache item.
     *
     * @return mixed|null The value of the cache item, or null if not found.
     */
    public function get(): mixed
    {
        return $this->isHit ? $this->content : null;
    }

    /**
     * Checks if the cache item is a hit.
     *
     * @return bool True if the cache item is a hit, otherwise false.
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * Sets the value of the cache item.
     *
     * @param mixed $value The value to set.
     * @return static The current instance.
     */
    public function set(mixed $value): static
    {
        $this->content = $value;
        $this->isHit = $value !== null;

        return $this;
    }

    /**
     * Sets the expiration time of the cache item.
     *
     * @param DateTimeInterface|null $expiration The expiration time of the cache item.
     * @return static The current instance.
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;
        $this->expireAfter = null;
        return $this;
    }

     /**
     * Sets the expiration time of the cache item relative to the current time.
     *
     * @param int|DateInterval|null $time The expiration time in seconds or as a DateInterval.
     * @return static The current instance.
     */
    public function expiresAfter(int|DateInterval|null $time): static
    {
        $this->expireAfter = $time;

        return $this;
    }
}