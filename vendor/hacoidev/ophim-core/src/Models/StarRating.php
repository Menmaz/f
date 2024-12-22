<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Ophim\Core\Contracts\TaxonomyInterface;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Traits\ActorLog;
use Ophim\Core\Traits\HasFactory;
use Ophim\Core\Traits\HasTitle;
use Ophim\Core\Traits\Sluggable;

class StarRating extends Model
{
    use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'star_ratings';
    protected $primaryKey = 'id';
    // public $timestamps = false;
    // protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    protected $fillable = ["user_id", "manga_id", "rating"];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    // public static function boot()
    // {
    //     parent::boot();

    //     static::updating(function ($instance) {
    //         $instance->timestamps = request('timestamps') ?: false;
    //     });
    // }

    // public static function primaryCacheKey(): string
    // {
    //     $site_routes = setting('site_routes_movie', '/phim/{movie}');
    //     if (strpos($site_routes, '{movie}')) return 'slug';
    //     if (strpos($site_routes, '{id}')) return 'id';
    //     return 'slug';
    // }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function manga()
  {
    return $this->belongsTo(Manga::class, "manga_id");
  }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
