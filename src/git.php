<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Git dir absolute path
    |--------------------------------------------------------------------------
    |
    | Specify full absolute path of git dir
    | This will help if your .git folder is not inside the working directory
    | The value of this will be passed like `git pull --git-dir=/your/path/repo.git`
    | This is optional
    |
    */

    'dir' => env('GIT_DIR', null),

    /*
    |--------------------------------------------------------------------------
    | Before script name
    |--------------------------------------------------------------------------
    |
    | This the name of script to run before running main deployment script
    | The script files should be inside webhookscripts folder
    | This is optional
    |
    */

    'before_script' => env('GIT_BEFORE_SCRIPT', null),
    
    /*
    |--------------------------------------------------------------------------
    | After script name
    |--------------------------------------------------------------------------
    |
    | This the name of script to run after running main deployment script
    | The script files should be inside webhookscripts folder
    | This is optional
    |
    */
    'after_script' => env('GIT_AFTER_SCRIPT', null),

    /*
    |--------------------------------------------------------------------------
    | Bitbucket official IP ranges
    |--------------------------------------------------------------------------
    |
    | These are the official bitbucket IP addresses which needs to be whitelisted
    | This is required
    |
    */
    'bitbucket_ips' => [
        '18.205.93.0/25',
        '18.234.32.128/25',
        '13.52.5.0/25'
    ],
];
