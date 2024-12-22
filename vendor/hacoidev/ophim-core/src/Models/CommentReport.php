<?php

namespace Ophim\Core\Models;

use App\Models\User;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;


class CommentReport extends Model 
{
    use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'comment_reports';
    protected $primaryKey = 'id';
    // public $timestamps = false;
    // protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    protected $fillable = ["comment_id", "manga_id", "reporter_id", "report_message", "created_at", "updated_at"];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user()
  {
    return $this->hasOneThrough(User::class, Comment::class, 'id', 'id', 'comment_id', 'user_id');
  }

  public function manga()
  {
    return $this->hasOneThrough(Manga::class, Comment::class, 'id', 'id', 'comment_id', 'commentable_id');
  }

  public function comment()
  {
    return $this->belongsTo(Comment::class, 'comment_id');
  }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
