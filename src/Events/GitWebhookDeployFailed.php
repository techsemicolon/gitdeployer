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