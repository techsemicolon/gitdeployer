<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    | Supported: "apc", "array", "database", "file", "memcached", "redis"
    |
    */

    'dir' => env('GIT_DIR', null),

    'before_script' => env('GIT_BEFORE_SCRIPT', null),
    
    'after_script' => env('GIT_AFTER_SCRIPT', null),

    'bitbucket_ips' => [
        '18.205.93.0/25',
        '18.234.32.128/25',
        '13.52.5.0/25'
    ],

    

];
