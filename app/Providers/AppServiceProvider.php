<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        if ($this->app->environment('local')) {

        }

        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('frontend-web.home.components.header', function ($view) {
            $categories = Cache::remember('categories', now()->addHours(8), function () {
                return DB::table('taxonomies')->where("type", 'genre')->orWhere('type', 'type')
                ->orderBy('name', 'asc')
                ->get(["name", "slug"]);
            });
            $view->with('categories', $categories);
        });
    }
}
