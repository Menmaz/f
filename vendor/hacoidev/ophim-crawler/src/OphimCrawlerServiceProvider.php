<?php

namespace Ophim\Crawler\OphimCrawler;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as SP;
use Ophim\Crawler\OphimCrawler\Console\CrawlerScheduleCommand;
use Ophim\Crawler\OphimCrawler\Option;
use Ophim\Core\Policies\CrawlSchedulePolicy;

class OphimCrawlerServiceProvider extends SP
{
    protected $middleware = ['your-middleware'];
    /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    public function policies()
    {
        // return [];
        return [
            CrawlSchedule::class => CrawlSchedulePolicy::class
        ];
    }

    public function register()
    {
        config(['plugins' => array_merge(config('plugins', []), [
            'hacoidev/ophim-crawler' =>
            [
                'name' => 'Tải dữ liệu',
                'package_name' => 'hacoidev/ophim-crawler',
                'icon' => 'la la-hand-grab-o',
                'entries' => [
                    ['name' => 'Tải dữ liệu', 'icon' => 'la la-hand-grab-o', 'url' => backpack_url('/plugin/ophim-crawler')],
                    // ['name' => 'Option', 'icon' => 'la la-cog', 'url' => backpack_url('/plugin/ophim-crawler/options')],
                ],
            ]
        ])]);

        config(['logging.channels' => array_merge(config('logging.channels', []), [
            'ophim-crawler' => [
                'driver' => 'daily',
                'path' => storage_path('logs/hacoidev/ophim-crawler.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                // 'days' => 7,
                // 'permission' => 777,
            ],
        ])]);

        config(['logging.channels' => array_merge(config('logging.channels', []), [
            'chapter-crawler' => [
                'driver' => 'daily',
                'path' => storage_path('logs/hacoidev/chapter-crawler.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                // 'days' => 7,
                // 'permission' => 777,
            ],
        ])]);

        config(['ophim.updaters' => array_merge(config('ophim.updaters', []), [
            [
                'name' => 'Ophim Crawler',
                'handler' => 'Ophim\Crawler\OphimCrawler\Crawler'
            ]
        ])]);
    }

    public function boot()
    {
        $this->commands([
            CrawlerScheduleCommand::class,
        ]);

        $this->app->booted(function () {
            $this->loadScheduler();
        });

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ophim-crawler');
    }

    protected function loadScheduler()
    {
        $schedule = $this->app->make(Schedule::class);
        // $schedule->command('ophim:plugins:ophim-crawler:schedule')->cron(Option::get('crawler_schedule_cron_config', '*/10 * * * *'))->withoutOverlapping();
        $schedule->command('ophim:plugins:ophim-crawler:schedule')->cron(Option::get('crawler_schedule_cron_config', '*/10 * * * *'))->withoutOverlapping()->runInBackground();
    }
}
