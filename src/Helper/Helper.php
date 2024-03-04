<?php 
/**
 * Luminova Framework
 *
 * @package Luminova
 * @author Ujah Chigozie Peter
 * @copyright (c) Nanoblock Technology Ltd
 * @license See LICENSE file
 */
namespace Luminova\Psr\Cache\Helper;

use \Luminova\Psr\Cache\Exceptions\InvalidArgumentException;
use \Luminova\Time\Time;
use \DateTimeInterface;
use \DateInterval;

class Helper 
{

     /**
     * Convert DateInterval to seconds.
     * 
     * @param DateInterval $interval The DateInterval object.
     * 
     * @return int The number of seconds.
     */
    public static function intervalToSeconds(DateInterval $interval): int
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
    public static function secondsToDateInterval(int $seconds): DateInterval
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
    public static function secondsToDateTime(int $seconds): DateTimeInterface
    {
        $dateTime = (new Time())->modify('+' . $seconds . ' seconds');

        return $dateTime;
    }

     /**
     * Check if keys are valid 
     * 
     * @param iterable $keys 
     * 
     * @return void 
     * @throw InvalidArgumentException
     */
    public static function areKeysLegal(iterable $keys): void
    {
        foreach($keys as $key){
            static::isKeyLegal($key);
        }
    }

    /**
     * Check if key is valid 
     * 
     * @param bool $key key to check
     * 
     * @return void 
     * @throw InvalidArgumentException
     */
    public static function isKeyLegal(string $key): void
    {
        if($key === null || $key === ''){
            throw new InvalidArgumentException('Cache key must be a valid string');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            throw new InvalidArgumentException('Cache key contains invalid characters');
        }

        if (strlen($key) <= 2) {
            throw new InvalidArgumentException('Cache key is not long enough');
        }
    }
}