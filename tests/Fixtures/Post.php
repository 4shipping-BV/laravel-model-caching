<?php namespace forshippingBV\LaravelModelCaching\Tests\Fixtures;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Post extends Model
{
    use Cachable;

    protected $fillable = [
        "title",
        "body",
    ];

    public function comments() : MorphMany
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    public function tags() : MorphToMany
    {
        return $this->morphToMany(Tag::class, "taggable");
    }
}
