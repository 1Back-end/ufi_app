<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'browser_shot' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL'),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'exportclient' => [
            'driver' => 'local',
            'root' => storage_path('app/export-client'),
            'url' => env('APP_URL') . '/export-client',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'exportconsultant' => [
            'driver' => 'local',
            'root' => storage_path('app/export-consultant'),
            'url' => env('APP_URL') . '/export-consultant',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'exportassureurs' => [
            'driver' => 'local',
            'root' => public_path('export-assureurs'),
            'url' => env('APP_URL') . '/export-assureurs',
            'visibility' => 'public',
        ],
        'exportfournisseurs' => [
            'driver' => 'local',
            'root' => public_path('export-fournisseurs'),
            'url' => env('APP_URL') . '/export-fournisseurs',
            'visibility' => 'public',
        ],
        'exportprisesencharges' => [
            'driver' => 'local',
            'root' => public_path('export-export-prises-en-charges'),
            'url' => env('APP_URL') . '/export-export-prises-en-charges',
            'visibility' => 'public',
        ],
        'exportconsultants' => [
            'driver' => 'local',
            'root' => public_path('export-consultants'),
            'url' => env('APP_URL') . '/export-consultants',
            'visibility' => 'public',
        ],
        'exportproducts' => [
            'driver' => 'local',
            'root' => public_path('export-products'),
            'url' => env('APP_URL') . '/export-products',
            'visibility' => 'public',
        ],
        'exportrendezvous' => [
            'driver' => 'local',
            'root' => public_path('export-rendezvous'),
            'url' => env('APP_URL') . '/export-rendezvous',
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
            'throw' => false,
            'report' => false,
        ],

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
        public_path('export-assureurs') => storage_path('app/export-assureurs'),
        public_path('export-consultant') => storage_path('app/export-consultant'),
        public_path('storage') => storage_path('app/public'),
        public_path('export-client') => storage_path('app/export-client'),
        public_path('export-rendezvous') => storage_path('app/export-rendezvous'),
    ],

];
