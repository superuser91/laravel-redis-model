<?php

namespace Vgplay\LaravelRedisModel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use \Vgplay\LaravelRedisModel\Contracts\BuilderInterface;
use Closure;
use \Vgplay\LaravelRedisModel\Contracts\Cacheable;
use \Vgplay\LaravelRedisModel\Exceptions\UnsupportedModelException;

class CacheQueryBuilder implements BuilderInterface
{
    protected $model;

    /**
     * @throws UnsupportedModelException
     */
    public function __construct(string $model)
    {
        if (!in_array(Cacheable::class, class_implements($model))) {
            throw new UnsupportedModelException();
        }

        $this->model = $model;
    }

    public function find($id)
    {
        return Cache::remember($this->model::getCacheKey($id), $this->model::cacheTimeout(), function () use ($id) {
            return $this->model::cacheWithRelation()->where($this->model::primaryCacheKey(), $id)->first();
        });
    }

    public function get($ids): Collection
    {
        $ids = is_array($ids) ? $ids : [$ids];

        $available = collect($this->availableFromCache($ids));

        $missing = collect($this->loadMissingItems($ids));

        return $available->merge($missing);
    }

    public function all(): Collection
    {
        $ids = Cache::remember($this->model::getCacheKeyList(), $this->model::cacheTimeout(), function () {
            return $this->model::pluck($this->model::primaryCacheKey())->toArray();
        });

        return $this->get($ids);
    }

    public function when($condition, Closure $callback): CacheQueryBuilder
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    protected function availableFromCache(array $ids)
    {
        $keys = array_map(function ($id) {
            return $this->model::getCacheKey($id);
        }, $ids);

        return Cache::many($keys);
    }

    protected function loadMissingItems($ids): Collection
    {
        $missingIds = $this->missingIds($ids);

        if (empty($missingIds)) return collect([]);

        $missingItems = $this->model::cacheWithRelation($missingIds)
            ->whereIn($this->model::primaryCacheKey(), $missingIds)
            ->get();

        foreach ($missingItems as $item) {
            Cache::put($this->model::getCacheKey($item->{$this->model::primaryCacheKey()}), $item, $this->model::cacheTimeout());
        }

        return $missingItems->mapWithKeys(function ($item) {
            return [$this->model::getCacheKey($item->{$this->model::primaryCacheKey()}) => $item];
        });
    }

    protected function missingIds($ids): array
    {
        return collect($ids)->filter(function ($id) {
            return Cache::missing($this->model::getCacheKey($id));
        })->toArray();
    }
}
