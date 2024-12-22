<?php

namespace Ophim\Core\Models;

use App\Models\User;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Illuminate\Database\Eloquent\Model;


class Notification  extends Model 
{
    use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = ['user_id', 'message', 'type', 'status', 'manga_id', 'comment_id', 'related_user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manga(){
        return $this->belongsTo(Manga::class, 'manga_id');
    }

    public function comment(){
        return $this->belongsTo(Comment::class, 'comment_id');
    }

    public function markAsRead()
    {
        $this->update(['status' => 1]); // Đặt `status` là 1 (đã đọc)
    }

    public function relatedUser() {
        return $this->belongsTo(User::class, 'related_user_id');
    }


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

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
