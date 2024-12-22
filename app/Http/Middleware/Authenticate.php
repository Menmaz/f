<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // if (! $request->expectsJson()) {
        //     // return route('login');
        // }

        // return $request->expectsJson() ? null : route('unauthorized');

        if ($request->expectsJson()) {
            abort(response()->json(['message' => 'Bạn cần đăng nhập để tiếp tục !'], 401));
        }

        return route('unauthorized');
    }
}
