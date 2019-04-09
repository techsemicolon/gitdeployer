# Laravel Git Deployer

Git webhook deployer package for laravel currently integrated with bitbucket.

## Introduction : 

When you push new releases or hotfixes to your production branch, it's a hassle to everytime login to the live server and do git pull manually. That is why webhook is a crutial part. There are many tools available which gives you CICD options and automated git deployments. However, I have opinion that not all projects need that level of criticality and complications. I had a very simple application with only 1 server running. I was looking for a very plug and play type of webhook package for laravel, I did not find one with my expectations and hence this is the one I built.

## How it works : 

Currently it integrates just with Bitbucket and not github(will add that as well soon). This package once installed gives you a url `www.your-url.com/git/webhook` which you can add into the bitbucket webhook configurations. Then whenever the webhook is triggered, this url gets the webhook payload via POST request to your server.

Once the server gets the payload it does following checks : 

1. Validating request IP :

Before taking any actions on webhooks, we need to make sure the request payload is actually coming from bitbucket and not falsely sent by any other unknwon servers. The package whitelists bitbucket's IP addresses virtually so that only valid payloads from bitbucket are handled.

2. Checking repository :

If the repository in the bitbucket's webhook payload is the one which is currently active in the laravel project, then only take further steps. Otherwise do nothing.

3. Checking if the current active branch is updated : 

If you have `production` branch active on your live instance and the payload has changes in some `hotfix` branch, we do not need to run webhook scripts on the live server. The package only takes further actions if the payload has any new changes for the currently active branch.

4. Running the webhook actions :

If the all the above checks are passed, webhooks actions are taken in form of scripts running one after another. The package is flexible to give you entire control of the scripts and commands which will be run once webhook is triggered.

## Prerequisites : 

1. The bitbucket repo origin set on the live server has to be using `ssh` so that it does not require password to be entered when you do git pull.

2. The user and group running as web server and the file permissions of laravel files should to be same. For example, if you have `www-data` running as web server and the laravel files are having permissions `ubuntu.ubuntu`, its going to create permission problems.

## Installation :

To install the package using composer : 

~~~bash
composer require techsemicolon/gitdeployer
~~~

Once installed, you can add service provider in `config/app.php` file for laravel version <= 5.2. For later versions the service provider will be automatically included.
~~~php
Techsemicolon\Gitdeployer\GitdeployerServiceProvider::class,
~~~

Now, you can publish the vendor files for the package :

~~~bash
php artisan vendor:publish --provider="Techsemicolon\Gitdeployer\GitdeployerServiceProvider"
~~~

This will add a config file `git.php` and a folder for scripts in app root called `webhookscripts`.

Importantly, you need to add the `/git/webhook` url which route to avoid csrf verification so bitbucket's post requests are not rejected. For that, you need to add following inside `App\Http\Middleware\VerifyCsrfToken.php` file : 

~~~php
/**
 * The URIs that should be excluded from CSRF verification.
 *
 * @var array
    */
protected $except = [
    '/git/webhook'
];
~~~

## Workflow : 

Apart from the 4 steps mentioned in `how it works` section, the package follows a simplw workflow. The `webhookscripts` folder is the key of this package.

When you run vendor publish, it will automatically create a file `deploy.sh` for you. This contains the main deployment bash script. Anytime webhook is triggered then this bash script will be run.

Additionally and optionally, the package gives you ability yo specify 2 more script files with the name you like, which will run before and after the main `deploy.sh` bash script. This is to give you control over what to do prior to pulling the latest release and what to do after it.

You can have those files stored in the `webhookscripts` folder with the name you want. That name you can specify in the `config/git.php` file. More configuration options are mentioned below.


## Configurations : 

The file `config/git.php` has following configurations : 

~~~php
<?php

return [

    // Specify full absolute path of git dir
    // This will help if your .git folder is not inside the working directory
    // The value of this will be passed like `git pull --git-dir=/your/path/repo.git`
    // This is optional
    'dir' => env('GIT_DIR', null),

    // This the name of script to run before running main deployment script
    // The script files should be inside webhookscripts folder
    // This is optional
    'before_script' => env('GIT_BEFORE_SCRIPT', null),
    
    // This the name of script to run after running main deployment script
    // The script files should be inside webhookscripts folder
    // This is optional
    'after_script' => env('GIT_AFTER_SCRIPT', null),
    
    // These are the official bitbucket IP addresses
    // which needs to be whitelisted
    // This is required
    'bitbucket_ips' => [
        '18.205.93.0/25',
        '18.234.32.128/25',
        '13.52.5.0/25'
    ],
];
~~~
## Events to get the deployment output : 

It's crutial to know what happened when script ran, what was the output from the server console. The package emits events which you can listen to. The events have time, console output and errors.

Success Event : 

~~~php
<?php

namespace Techsemicolon\Gitdeployer\Events;

class GitWebhookWasDeployed
{
    public $time;

    public $output;

    /**
     * Create a new event instance.
     *
     * @param  integer $time
     * @param  string  $output
     * 
     * @return void
     */
    public function __construct($time, $output)
    {
        $this->time = $time;
        $this->output = $output;
    }
}
~~~

Failed Event : 

~~~php
<?php

namespace Techsemicolon\Gitdeployer\Events;

class GitWebhookDeployFailed
{
    public $error;

    /**
     * Create a new event instance.
     *
     * @param  string  $error
     * 
     * @return void
     */
    public function __construct($error)
    {
        $this->error = $error;
    }
}
~~~
## Main deployment script : 

Below is the default `deploy.sh` script. You can definitely change it as per your needs. 

~~~bash
#!/bin/sh

gitdir=${1:-''}

# activate maintenance mode
php artisan down

# update source code
git pull $gitdir

# update PHP dependencies
export COMPOSER_HOME='/tmp/composer'
composer install --no-interaction --no-dev --prefer-dist
# --no-interaction	Do not ask any interactive question
# --no-dev		Disables installation of require-dev packages.
# --prefer-dist		Forces installation from package dist even for dev versions.

# clear cache
php artisan cache:clear

# clear config cache
php artisan config:clear

# cache config 
php artisan config:cache

# restart queues 
php artisan -v queue:restart

# update database
php artisan migrate --force
# --force		Required to run when in production.

# stop maintenance mode
php artisan up
~~~

Note : the bash variable `gitdir` is required to make sure it uses git-dir configuration if you specify it in config file.

## Known problems : 

1. The package runs bash scripts using `symfony/process`. So make sure the bash scripts inside `webhookscripts` directory have required permissions to make them executables.

2. When you setup the `ssh-key` and add it to github, make sure the ssh-keys are generated for the right user who is running the web-server and also has the right permissions to the laravel files.

3. Once the ssh-keys are saved to the bitbucket, run `ssh -T git@bitbucket.com`. This is to add the You should be prompted to add bitbucket to your known hosts. If this is not done, the script will throw error.

## License : 

This package is open-sourced software licensed under the MIT license