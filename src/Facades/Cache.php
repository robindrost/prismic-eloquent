<?php

namespace RobinDrost\PrismicEloquent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool has(string $key)
 * @method static mixed get(string $key)
 * @method static mixed set(string $key, mixed $value, int $ttl = 0)
 * @method static bool delete(string $key)
 * @method static bool clear()
 *
 * @see \RobinDrost\PrismicEloquent\Cache
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'prismiceloquent.cache';
    }
}
