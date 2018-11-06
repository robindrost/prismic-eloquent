<?php

namespace RobinDrost\PrismicEloquent;

use \Illuminate\Support\Facades\Cache as LaravelCache;
use \Prismic\Cache\CacheInterface;

class Cache implements CacheInterface
{
    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return LaravelCache::has($this->prefix($key));
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        return LaravelCache::get($this->prefix($key));
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = 0)
    {
        if ($ttl == 0) {
            return LaravelCache::forever($this->prefix($key), $value);
        } else {
            return LaravelCache::add($this->prefix($key), $value, $ttl);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        return LaravelCache::forget($this->prefix($key));
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return LaravelCache::flush(config('prismiceloquent.cache.prefix'));
    }

    /**
     * Prefix the given key with a prefix string from the configuration.
     *
     * @param string $key
     *
     * @return string
     */
    protected function prefix($key)
    {
        return config('prismiceloquent.cache.prefix') . '.' . $key;
    }
}
