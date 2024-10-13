<?php 
/**
 * Luminova Framework
 *
 * @package Luminova
 * @author Ujah Chigozie Peter
 * @copyright (c) Nanoblock Technology Ltd
 * @license See LICENSE file
 */
namespace Luminova\Psr\Cache\Exceptions;

use \Psr\Cache\CacheException as CacheExceptionInterface;
use \Luminova\Exceptions\AppException;
use \Throwable;

class CacheException extends AppException implements CacheExceptionInterface
{
    public function __construct(string $message = '', int $code = AppException::CACHE_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
