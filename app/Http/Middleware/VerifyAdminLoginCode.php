<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyAdminLoginCode
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->route()->named('verify.code') || $request->route()->named('verify.code.post')) {
            return $next($request);
        }

        if ($request->is(config('backpack.base.route_prefix') . '/login') && !$request->session()->has('verified_login_code')) {
            return redirect()->route('verify.code');
        }

        return $next($request);
    }
}
