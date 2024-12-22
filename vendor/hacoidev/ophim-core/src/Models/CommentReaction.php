<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Illuminate\Database\Eloquent\Model;

class CommentReaction extends Model
{
    use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'comments_reactions';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    // protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    protected $fillable = ['user_id', 'comment_id', 'type'];

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
        return $this->belongsTo(User::class);
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id')->with('commentable');
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
