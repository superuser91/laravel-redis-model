<?php

namespace Vgplay\LaravelRedisModel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait HasCache
{
    public static function primaryCacheKey(): string
    {
        return 'id';
    }

    public static function getCacheKey($id): string
    {
        return md5(sprintf("%s%s",Str::slug(__CLASS__) , $id));
    }

    public static function getCacheKeyList(): string
    {
        return md5(sprintf('all_%s_cached_keys', Str::slug(__CLASS__) . '.'));
    }

    final public static function findList($ids): Collection
    {
        $ids = is_array($ids) ? $ids : [$ids];

        $available = collect(static::availableFromCache($ids));

        $missing = collect(static::loadMissingItems($available, $ids));

        return $available->merge($missing);
    }

    protected static function availableFromCache(array $ids)
    {
        $keys = array_map(function ($id) {
            return static::getCacheKey($id);
        }, $ids);

        return Cache::many($keys);
    }

    public static function retrieveFromCache($id)
    {
        return Cache::remember(static::getCacheKey($id), config('cache.ttl.id'), function () use ($id) {
            return static::cacheWithRelation()->where(static::primaryCacheKey(), $id)->first();
        });
    }

    private static function loadMissingItems($items, $ids): Collection
    {
        $missingIds = static::missingIds($items, $ids);

        if (empty($missingIds)) return collect([]);

        $missingItems = static::cacheWithRelation($missingIds)
            ->whereIn(static::primaryCacheKey(), $missingIds)
            ->get();

        foreach ($missingItems as $item) {
            Cache::put(static::getCacheKey($item->{static::primaryCacheKey()}), $item, static::cacheTimeout());
        }

        return $missingItems->mapWithKeys(function ($item) {
            return [static::getCacheKey($item->{static::primaryCacheKey()}) => $item];
        });
    }

    protected static function cacheTimeout() {
        return config('cache.ttl.id');
    }

    private static function missingIds($items, $ids): array
    {
        $missingIds = [];
        foreach ($ids as $id) {
            if (Cache::missing(static::getCacheKey($id))) {
                $missingIds[] = $id;
            }
        }
        return $missingIds;
    }

    public function scopeCacheWithRelation($query)
    {
        return $query;
    }
}
