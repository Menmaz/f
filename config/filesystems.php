<?php

use Backpack\Settings\app\Models\Setting;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        // 'contabo' => [
        //     'driver' => 's3',
        //     'key' => env('CONTABO_ACCESS_KEY_ID'),
        //     'secret' => env('CONTABO_SECRET_ACCESS_KEY'),
        //     'region' => env('CONTABO_DEFAULT_REGION'),
        //     'bucket' => env('CONTABO_BUCKET'),
        //     'url' => env('CONTABO_URL'),
        //     'endpoint' => env('CONTABO_ENDPOINT'),
        //     'use_path_style_endpoint' => env('CONTABO_USE_PATH_STYLE_ENDPOINT', false),
        //     'throw' => false
        // ],

        // 'backblaze' => [
        //     'driver' => 's3',
        //     'key' => env('BLACKBLAZE_ACCESS_KEY_ID'),
        //     'secret' => env('BLACKBLAZE_SECRET_ACCESS_KEY'),
        //     'region' => env('BLACKBLAZE_DEFAULT_REGION'),
        //     'bucket' => env('BLACKBLAZE_BUCKET'),
        //     'url' => env('BLACKBLAZE_URL'),
        //     'endpoint' => env('BLACKBLAZE_ENDPOINT'),
        //     'use_path_style_endpoint' => env('BLACKBLAZE_USE_PATH_STYLE_ENDPOINT', false),
        //     'throw' => false
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

