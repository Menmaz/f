<?php

namespace Ophim\Core\Models;

use App\Models\User;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Traits\HasFactory;

class Bookmark extends Model 
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'bookmarks';
    protected $primaryKey = 'id';
    // public $timestamps = false;
    // protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    protected $fillable = ["user_id", "bookmarkable_type", "bookmarkable_id", "collection_id", "status"];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function bookmarkable()
  {
    return $this->morphTo();
  }

  public function manga()
  {
    return $this->belongsTo(Manga::class, 'bookmarkable_id');
  }
}
