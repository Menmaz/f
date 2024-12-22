<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Ophim\Core\Models\Bookmark;
use Ophim\Core\Models\Comment;
use Ophim\Core\Models\IconRating;
use Ophim\Core\Models\Notification;
use Ophim\Core\Models\StarRating;
use Ophim\Core\Models\User as OphimUser;
use Ophim\Core\Models\View;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends OphimUser implements MustVerifyEmail,JWTSubject {

    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'description',
        'avatar',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [
            'username' => $this->username,
            // thêm các thông tin khác nếu cần
        ];
    }

//   public function bookmarks()
//   {
//     return $this->hasMany(Bookmark::class, "user_id");
//   }

//   public function reactions()
//   {
//     return $this->hasMany(CommentReaction::class, "user_id");
//   }

  public function comments()
  {
    return $this->hasMany(Comment::class, "user_id");
  }

  public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function views()
    {
        return $this->hasMany(View::class, 'user_id');
    }

    public function rating(){
        return $this->hasOne(StarRating::class, 'user_id');
    }

    public function ratingIcon(){
        return $this->hasOne(IconRating::class, 'user_id');
    }

    public function bookmarks(){
        return $this->hasMany(Bookmark::class, 'user_id');
    }
}
