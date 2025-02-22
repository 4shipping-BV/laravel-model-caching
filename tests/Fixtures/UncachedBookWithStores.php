<?php namespace forshippingBV\LaravelModelCaching\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class UncachedBookWithStores extends Model
{
    protected $casts = [
        'price' => 'float',
        'published_at' => 'datetime',
    ];

    protected $fillable = [
        'description',
        'published_at',
        'title',
    ];

    protected $table = 'books';

    public function author() : BelongsTo
    {
        return $this->belongsTo(UncachedAuthor::class);
    }

    public function comments() : MorphMany
    {
        return $this->morphMany(Comment::class, "commentable");
    }

    public function image() : MorphOne
    {
        return $this->morphOne(Image::class, "imagable");
    }

    public function publisher() : BelongsTo
    {
        return $this->belongsTo(UncachedPublisher::class);
    }

    public function stores() : BelongsToMany
    {
        return $this->belongsToMany(Store::class, "book_store", "book_id", "store_id");
    }
}
