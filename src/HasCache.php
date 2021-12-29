<?php

namespace Vgplay\LaravelRedisModel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use \Vgplay\LaravelRedisModel\Contracts\BuilderInterface;

trait HasCache
{
    protected static function bootHasCache()
    {
        static::created(function ($instance) {
            Cache::forget(static::getCacheKeyList());
        });

        static::updated(function ($instance) {
            Cache::forget(static::getCacheKey($instance->{static::primaryCacheKey()}));
        });

        static::deleted(function ($instance) {
            Cache::forget(static::getCacheKey($instance->{static::primaryCacheKey()}));
            Cache::forget(static::getCacheKeyList());
        });

        if (method_exists(statis::class, 'trashed')) {
            static::restored(function ($instance) {
                Cache::forget(static::getCacheKey($instance->{static::primaryCacheKey()}));
            });
        }
    }

    public static function primaryCacheKey(): string
    {
        return 'id';
    }

    public static function getCacheKey($id): string
    {
        return md5(sprintf("%s%s", Str::slug(__CLASS__), $id));
    }

    public static function getCacheKeyList(): string
    {
        return md5(sprintf('all_%s_cached_keys', Str::slug(__CLASS__) . '.'));
    }

    public static function cacheTimeout(): int
    {
        return (int) config('cache.ttl.id', 24 * 3600);
    }

    public function scopeCacheWithRelation($query)
    {
        return $query;
    }

    final public static function fromCache(): BuilderInterface
    {
        return new CacheQueryBuilder(static::class);
    }
}
