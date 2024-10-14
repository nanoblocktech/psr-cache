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
     * Cache key.
     * 
     * @var string $key
     */
    private string $key = '';

    /**
     * Cache content.
     * 
     * @var mixed $content
     */
    private mixed $content = null;

    /**
     * Cache hit.
     * 
     * @var bool $isHit
     */
    private bool $isHit = false;

    /**
     * Cache expiration.
     * 
     * @var ?DateTimeInterface $expiration
     */
    private ?DateTimeInterface $expiration = null;

    /**
     * Cache expiration after.
     * 
     * @var DateInterval|int|null $expireAfter
     */
    private DateInterval|int|null $expireAfter = null;

    /**
     * CacheItem Constructor.
     *
     * @param string $key The unique identifier for the cache item.
     * @param mixed $content The value to be stored in the cache item (default: null).
     * @param bool|null $isHit Determines whether the cache item is considered a "hit" (i.e., the item exists in the cache). 
     *                      If null, the hit status is automatically determined based on whether the content is null or not.
     */
    public function __construct(string $key, mixed $content = null, ?bool $isHit = null)
    {
        $this->key = $key;
        $this->content = $content;
        $this->isHit = ($isHit === null) ? $content !== null : $isHit;
    }

    /**
     * Gets the absolute expiration time for this cache item.
     * 
     * @return DateTimeInterface|null Returns the exact expiration time as a DateTimeInterface object, or null if the item has no expiration set.
     */
    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiration;
    }

    /**
     * Gets the relative expiration time for this cache item.
     * 
     * @return DateInterval|int|null Returns the expiration time relative to the current time. It may return:
     * - a `DateInterval` object for specific intervals,
     * - an integer representing seconds,
     * - or null if no expiration is set.
     */
    public function getExpiresAfter(): DateInterval|int|null
    {
        return $this->expireAfter;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): mixed
    {
        return $this->isHit ? $this->content : null;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * {@inheritdoc}
     */
    public function set(mixed $value): static
    {
        $this->content = $value;
        $this->isHit = $value !== null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;
        $this->expireAfter = null;
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter(DateInterval|int|null $time): static
    {
        $this->expireAfter = $time;

        return $this;
    }
}
