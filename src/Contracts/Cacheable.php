<?php

namespace Vgplay\LaravelRedisModel\Contracts;

use \Vgplay\LaravelRedisModel\Contracts\BuilderInterface;

interface Cacheable
{
    public static function primaryCacheKey(): string;

    public static function getCacheKey($id): string;

    public static function getCacheKeyList(): string;

    public static function cacheTimeout(): int;

    public function scopeCacheWithRelation($query);
}
