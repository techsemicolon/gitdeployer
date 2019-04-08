<?php

namespace Techsemicolon\Gitdeployer;

use Illuminate\Support\ServiceProvider;

class GitdeployerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->publishes([
            __DIR__.'/webhookscripts/deploy.sh' => base_path('webhookscripts/deploy.sh'),
            __DIR__.'/git.php' => config_path('git.php'),
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {}
}
