<?php

namespace Techsemicolon\Gitdeployer;

use Symfony\Component\Process\Process;
use Techsemicolon\Gitdeployer\Events\GitWebhookDeployFailed;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitService
{
    /**
     * Get current active branch
     *
     * @return string
     */
    public function getCurrentBranch()
    {
        $gitDir = $this->getGitDir();

        $process = new Process('git ' . $gitDir .'rev-parse --abbrev-ref HEAD', base_path());
        $process->run();
        if (!$process->isSuccessful()) {
            
            $error = (new ProcessFailedException($process))->getMessage();

            // Emit event got failed git webhook deployment
            event(new GitWebhookDeployFailed($error));
            
            return;
        }

        return trim($process->getOutput());
    }

    /**
     * Specify git dir
     * 
     * @return string
     */
    private function getGitDir()
    {
        return empty(config('git.dir')) ? '' : '--git-dir=' . config('git.dir');
    }
    /**
     * Get current git repository
     *
     * @return string
     */
    public function getCurrentRepository()
    {
        $gitDir = $this->getGitDir();

        $process = new Process('basename $(git ' . $gitDir . ' remote get-url origin)', base_path());
        $process->run();
        if (!$process->isSuccessful()) {
            
            $error = (new ProcessFailedException($process))->getMessage();
            event(new GitWebhookDeployFailed($error));
        }

        $repo = trim($process->getOutput());

        $repo = preg_replace('/\.git$/', '', $repo);

        return $repo;
    }

    /**
     * Run webhook before script
     *
     * @return string|void
     */
    public function runBeforeScript()
    {
        $bashFile = config('git.before_script');
        
        if(!empty($bashFile)){
            return $this->runBash($bashFile);
        }
        return $this;
    }

    /**
     * Run webhook after script
     *
     * @return string|void
     */
    public function runAfterScript()
    {
        $bashFile = config('git.after_script');
        
        if(!empty($bashFile)){
            return $this->runBash($bashFile);
        }
        return $this;
    }

    /**
     * Run bash
     *
     * @return string|void
     */
    private function runBash($bashFile)
    {
        $gitDir = $this->getGitDir();
        
        $path = base_path('webhookscripts/' . $bashFile);

        if(!file_exists($path)){
            return false;
        }

        $command = $path;

        if($gitDir){
            $command .= ' ' . $gitDir;
        }
        
        $process = new Process($command, base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            
            $error = (new ProcessFailedException($process))->getMessage();

            // Emit event got failed git webhook deployment
            event(new GitWebhookDeployFailed($error));

            return $this;
        }

        return trim($process->getOutput());
    }

    /**
     * Run webhook pull script
     *
     * @return string|void
     */
    public function pullTheLatestReleases()
    {
        return $this->runBash('deploy.sh');
    }

    /**
     * Check if the current branch was updated
     *
     * @return bool
     */
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
    
    /**
     * Check if the request is coming from
     * valid IP addresses
     *
     * @return bool
     */
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

    /**
     * Check if IP belongs to IP range
     *
     * @return string|void
     */
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
