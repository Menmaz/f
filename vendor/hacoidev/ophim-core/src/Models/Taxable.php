<?php

namespace Ophim\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Scopes\TaxableScope;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Taxable extends Model
{
  use CrudTrait;
  use HasFactory;

  protected $table = "taxables";
  // protected $hidden = ["pivot"];

  protected $fillable = ["taxonomy_id", "taxable_type", "taxable_id"];

  protected static function boot()
  {
    parent::boot();
  }

  public function taxonomy()
  {
    return $this->morphToMany(Taxonomy::class, "taxable");
  }
  // public function manga()
  // {
  //     return $this->morphTo();
  // }
  public function manga()
    {
        return $this->belongsTo(Manga::class);
    }
}
