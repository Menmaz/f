<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MangaService;

class MangaServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Đăng ký MangaService trong container
        $this->app->singleton(MangaService::class, function ($app) {
            return new MangaService();
        });
    }

    public function boot()
    {
        //
    }
}

