<?php
namespace Ophim\Notifications;


use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;


class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Where the route file lives, both inside the package and in the app (if overwritten).
     *
     * @var string
     */
    public $routeFilePath = '/routes/backpack/notification.php';

    public function register()
    {
        config(['plugins' => array_merge(config('plugins', []), [
            'notifications' =>
            [
                'name' => 'Thông Báo',
                'package_name' => 'notifications',
                'icon' => 'la la-cog',
                'entries' => [
                    ['name' => 'Thông Báo', 'icon' => 'la la-bell', 'url' => backpack_url('/plugin/notifications')],
                ]
            ]
        ])]);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // define the routes for the application
        $this->setupRoutes($this->app->router);

        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'notification');

    }
    /**
     * Define the routes for the application.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        // by default, use the routes file provided in vendor
        $routeFilePathInUse = __DIR__ . $this->routeFilePath;
        // but if there's a file with the same name in routes/backpack, use that one
        if (file_exists(base_path() . $this->routeFilePath)) {
            $routeFilePathInUse = base_path() . $this->routeFilePath;
        }
        $this->loadRoutesFrom($routeFilePathInUse);
    }

}