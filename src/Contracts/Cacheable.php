<?php

namespace Vgplay\LaravelRedisModel\Contracts;

interface Cacheable
{
    public static function primaryCacheKey(): string;

    public static function getCacheKey($id): string;

    public static function getCacheKeyList(): string;

    public static function findList(array $ids);

    public static function retrieveFromCache($id);

    public function scopeCacheWithRelation($query);
}
