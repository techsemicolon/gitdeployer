<?php

namespace Techsemicolon\Gitdeployer;

use Symfony\Component\Process\Process;
use Techsemicolon\Gitdeployer\Events\GitWebhookDeployFailed;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitService
{
    
    public function getCurrentBranch()
    {
        $process = new Process('git rev-parse --abbrev-ref HEAD', base_path());
        $process->run();
        if (!$process->isSuccessful()) {
            
            $error = (new ProcessFailedException($process))->getMessage();
            event(new GitWebhookDeployFailed($error));
            
            return;
        }

        return trim($process->getOutput());
    }


    public function getCurrentRepository()
    {
        $process = new Process('basename $(git remote get-url origin)', base_path());
        $process->run();
        if (!$process->isSuccessful()) {
            
            $error = (new ProcessFailedException($process))->getMessage();
            event(new GitWebhookDeployFailed($error));
        }

        $repo = trim($process->getOutput());

        $repo = preg_replace('/\.git$/', '', $repo);

        return $repo;
    }

    public function runBeforeScript()
    {
        $bashFile = config('git.before_script');
        
        if(!empty($bashFile)){
            return $this->runBash($bashFile);
        }
        return $this;
    }

    public function runAfterScript()
    {
        $bashFile = config('git.after_script');
        
        if(!empty($bashFile)){
            return $this->runBash($bashFile);
        }
        return $this;
    }

    private function runBash($bashFile)
    {
        $path = base_path('webhookscripts/' . $bashFile);

        if(!file_exists($path)){
            return false;
        }

        $process = new Process($path, base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            
            $error = (new ProcessFailedException($process))->getMessage();
            event(new GitWebhookDeployFailed($error));

            return $this;
        }

        return trim($process->getOutput());
    }

    public function pullTheLatestReleases()
    {
        return $this->runBash('deploy.sh');
    }

    public function checkIfCurrentBranchWasUpdated($payload){

        $branch = $this->getCurrentBranch();
        
        if(empty($branch)){
            return false;
        }

        $changes = $payload['push']['changes'] ?? [];
        if(empty($changes)){
            return false;
        }

        foreach($changes as $change){
            
            $changedBranch = $change['new']['name'] ?? '';
            if($branch == $changedBranch){
                return true;
            }
        }
        return false;
    }
    
    public function isValidRequestIp($ip)
    {
        $bitbucketIpRanges = config('git.bitbucket_ips');
        
        foreach ($bitbucketIpRanges as $range) {
            
            if($this->ipInRange($ip, $range)){
                return true;
            }
        }

        return false;
    }

    private function ipInRange($ip, $range){

        if ( strpos( $range, '/' ) == false ) {
            $range .= '/32';
        }

        list( $range, $netmask ) = explode( '/', $range, 2 );

        $range_decimal = ip2long( $range );
        $ip_decimal = ip2long( $ip );
        $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
        $netmask_decimal = ~ $wildcard_decimal;

        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}
