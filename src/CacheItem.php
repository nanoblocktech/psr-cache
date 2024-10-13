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
    public ?DateTimeInterface $expiration = null;

    /**
     * Cache expiration after.
     * 
     * @var DateInterval|int|null $expireAfter
     */
    public DateInterval|int|null $expireAfter = null;

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
        $this->isHit = ($isHit === null) ? $content !== null : $isHit;
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
    public function expiresAfter(int|DateInterval|null $time): static
    {
        $this->expireAfter = $time;

        return $this;
    }
}
