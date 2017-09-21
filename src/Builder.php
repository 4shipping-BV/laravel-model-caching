<?php namespace GeneaLabs\LaravelModelCaching;

use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class Builder extends EloquentBuilder
{
    protected function eagerLoadRelation(array $models, $name, Closure $constraints)
    {
        $relation = $this->getRelation($name);
        $relation->addEagerConstraints($models);
        $constraints($relation);

        return $relation->match(
            $relation->initRelation($models, $name),
            $this->cacheResults($relation, $models),
            $name
        );
    }

    protected function cacheResults(Relation $relation, array $models) : Collection
    {
        $parentIds = implode('_', collect($models)->pluck('id')->toArray());
        $parentName = str_slug(get_class($relation->getParent()));
        $childName = str_slug(get_class($relation->getRelated()));
        $cache = cache();

        if (is_subclass_of($cache->getStore(), TaggableStore::class)) {
            $cache->tags([$parentName, $childName]);
        }

        return cache()->tags([$parentName, $childName])
            ->rememberForever("{$parentName}_{$parentIds}-{$childName}s", function () use ($relation) {
                return $relation->getEager();
            });
    }
}
