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