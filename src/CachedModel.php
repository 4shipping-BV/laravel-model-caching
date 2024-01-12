<?php namespace forshippingBV\LaravelModelCaching;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Model;

abstract class CachedModel extends Model
{
    use Cachable;
}
