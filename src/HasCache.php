<?php

namespace Vgplay\LaravelRedisModel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use \Vgplay\LaravelRedisModel\Contracts\BuilderInterface;
use Vgplay\LaravelRedisModel\Contracts\Cacheable;

trait HasCache
{
    protected static function bootHasCache()
    {
        static::created(function ($instance) {
            Cache::forget(static::getCacheKeyList());
            static::flushRelationship($instance);
        });

        static::updated(function ($instance) {
            Cache::forget(static::getCacheKey($instance->{static::primaryCacheKey()}));
            static::flushRelationship($instance);
        });

        static::deleted(function ($instance) {
            Cache::forget(static::getCacheKey($instance->{static::primaryCacheKey()}));
            Cache::forget(static::getCacheKeyList());
            static::flushRelationship($instance);
        });

        if (method_exists(statis::class, 'trashed')) {
            static::restored(function ($instance) {
                Cache::forget(static::getCacheKey($instance->{static::primaryCacheKey()}));
                static::flushRelationship($instance);
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

    protected static function flushRelationship($instance)
    {
        foreach (($instance->touches ?? []) as $relation) {
            $touch = $instance->{$relation};
            if ($touch instanceof Cacheable) {
                Cache::forget($touch->getCacheKey($touch->{$touch->primaryCacheKey()}));
            }
        }
    }
}
