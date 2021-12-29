<?php

namespace Vgplay\LaravelRedisModel\Contracts;

use Illuminate\Support\Collection;
use Closure;

interface BuilderInterface
{
    public function find($id);
    public function get($ids): Collection;
    public function all(): Collection;
    public function when($condition, Closure $callback);
}
