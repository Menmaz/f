<?php

return [
    'version' => explode('@', \PackageVersions\Versions::getVersion('hacoidev/ophim-core') ?? 0)[0],
    'episodes' => [
        'types' => [
            'embed' => 'Nhúng',
            'mp4' => 'MP4',
            'm3u8' => 'M3U8'
        ]
    ],
    //ophim version
    // 'ckfinder' => [
    //     'loadRoutes' => false,
    //     'backends' => [
    //         'name'         => 'default',
    //         'adapter'      => 'local',
    //         'baseUrl'      => '/storage/',
    //         'root'         => public_path('/storage/'),
    //         'chmodFiles'   => 0777,
    //         'chmodFolders' => 0755,
    //         'filesystemEncoding' => 'UTF-8'
    //     ]
    // ]

    //10truyen version
    'ckfinder' => [
        'loadRoutes' => false,
        'backends' => [
            'name'         => 'default',
            'adapter'      => 'local',
            'baseUrl'      => '/storage/',
            'root'         => storage_path('app/public'),
            'chmodFiles'   => 0777,
            'chmodFolders' => 0755,
            'filesystemEncoding' => 'UTF-8'
        ]
    ],
    
    'frontend_domain' => 'hihitruyen.com',
];
