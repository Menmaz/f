<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Traits\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\URL as LARURL;

class Taxonomy extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'taxonomies';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function genres()
    {
        return  $this->where('type', 'genre');
    } 

//     public function taxables()
//   {
//     return $this->morphedByMany(Taxable::class, "taxable");
//   }

public function taxables(): HasOne
{
    return $this->hasOne(Taxable::class, 'taxable_id');
}

  public function parent()
  {
    return $this->belongsTo(Taxonomy::class, "parent_id");
  }

  public function children()
  {
    return $this->hasMany(Taxonomy::class, "parent_id");
  }

  public function mangas(): HasManyThrough
    {
        return $this->hasManyThrough(Manga::class, Taxable::class, 'taxonomy_id', 'id', 'id', 'taxable_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    //new version
    public function getUrl()
    {
        return LARURL::to('/') . "/the-loai/$this->slug";
    }

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
