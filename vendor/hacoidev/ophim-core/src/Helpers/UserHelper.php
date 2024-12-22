<?php

namespace Ophim\Core\Helpers;

use Illuminate\Support\Facades\Auth;

class UserHelper
{
    // public static function isAdmin()
    // {
    //     return Auth::check() && Auth::user()->hasRole('Admin');
    // }

    public static function checkAdminPermissions()
    {
        $user = backpack_user();
        if($user && $user->hasRole('Admin')){
            return true;
        } else {
            abort(403);
        }
    }
}
