<?php

namespace App\Providers;

use Backpack\Settings\app\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    // public function boot()
    // {
    //     $contaboDriverConfig = [
    //         'driver' => 's3',
    //         'key' => Setting::where('key', 'contabo_access_key_id')->value('value'),
    //         'secret' => Setting::where('key', 'contabo_secret_access_key')->value('value'),
    //         'region' => Setting::where('key', 'contabo_default_region')->value('value'),
    //         'bucket' => Setting::where('key', 'contabo_bucket')->value('value'),
    //         'url' => Setting::where('key', 'contabo_url')->value('value'),
    //         'endpoint' => Setting::where('key', 'contabo_endpoint')->value('value'),
    //         'use_path_style_endpoint' => env('CONTABO_USE_PATH_STYLE_ENDPOINT', false),
    //         'throw' => false,
    //     ];

    //     $backblazeDriverConfig = [
    //         'driver' => 's3',
    //         'key' => Setting::where('key', 'backblaze_access_key_id')->value('value'),
    //         'secret' => Setting::where('key', 'backblaze_secret_access_key')->value('value'),
    //         'region' => Setting::where('key', 'backblaze_default_region')->value('value'),
    //         'bucket' => Setting::where('key', 'backblaze_bucket')->value('value'),
    //         'url' => Setting::where('key', 'backblaze_url')->value('value'),
    //         'endpoint' => Setting::where('key', 'backblaze_endpoint')->value('value'),
    //         'use_path_style_endpoint' => env('BACKBLAZE_USE_PATH_STYLE_ENDPOINT', false),
    //         'throw' => false,
    //     ];

    //     // Set configuration values
    //     Config::set('filesystems.disks.contabo', $contaboDriverConfig);
    //     Config::set('filesystems.disks.backblaze', $backblazeDriverConfig);
    // }

    public function boot()
{
    $baseDriverConfig = function ($prefix) {
        return [
            'driver' => 's3',
            'key' => Setting::where('key', "{$prefix}_access_key_id")->value('value'),
            'secret' => Setting::where('key', "{$prefix}_secret_access_key")->value('value'),
            'region' => Setting::where('key', "{$prefix}_default_region")->value('value'),
            'bucket' => Setting::where('key', "{$prefix}_bucket")->value('value'),
            'url' => Setting::where('key', "{$prefix}_url")->value('value'),
            'endpoint' => Setting::where('key', "{$prefix}_endpoint")->value('value'),
            'use_path_style_endpoint' => env(strtoupper("{$prefix}_USE_PATH_STYLE_ENDPOINT"), false),
            'throw' => false,
        ];
    };

    Config::set('filesystems.disks.contabo', $baseDriverConfig('contabo'));
    Config::set('filesystems.disks.backblaze', $baseDriverConfig('backblaze'));
}
}
