<?php

namespace Ophim\Core\Models;

use App\Models\User;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Illuminate\Database\Eloquent\Model;


class Comment extends Model 
{
    use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'comments';
    protected $primaryKey = 'id';
    // public $timestamps = false;
    // protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    protected $fillable = ["user_id", "commentable_id", "parent_id", "commentable_type", "content"];

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
      return $this->belongsTo(User::class, 'user_id');
    }

  public function commentable()
  {
    return $this->morphTo();
  }

  public function replies()
  {
    return $this->hasMany(Comment::class, "parent_id");
  }

  public function getDirectReplies()
    {
        return $this->replies()->orderByDesc('created_at'); // Sắp xếp theo thứ tự thời gian, lấy các phản hồi trực tiếp
    }

  public function parentComment()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

  public function getTotalReplies()
  {
    return $this->replies()->count();
  }

  public function parent()
  {
    return $this->belongsTo(Comment::class, "parent_id");
  }

  public function manga()
  {
    return $this->belongsTo(Manga::class, "commentable_id");
  }

  public function reactions()
  {
    return $this->hasMany(CommentReaction::class);
  }

  public function likes()
  {
    return $this->hasMany(CommentReaction::class)->where("type", 1);
  }

  public function getTotalLikes(){
    return $this->likes()->count();
  }

  public function dislikes()
  {
    return $this->hasMany(CommentReaction::class)->where("type", 0);
  }

  public function getTotalDislikes(){
    return $this->dislikes()->count();
  }

  public function likesSum()
  {
    if (!isset($this->likes_count)) {
      $this->loadCount(["likes", "dislikes"]);
    }

    return $this->likes_count - $this->dislikes_count;
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

    public function totalViews()
    {
        return $this->views()->where("model", 'App\Models\Manga')->sum('views');
    }

    public function lastestChapter()
    {
        return $this->chapters()->orderByDesc('id')->count();
    }

    public function getStatus()
    {
        $statuses = [
            'trailer' => __('Sắp chiếu'),
            'ongoing' => __('Đang chiếu'),
            'completed' => __('Hoàn thành')
        ];
        return $statuses[$this->status];
    }

    protected function titlePattern(): string
    {
        return Setting::get('site_movie_title', '');
    }
    


    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
