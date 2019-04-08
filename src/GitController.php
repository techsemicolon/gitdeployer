<?php

namespace Techsemicolon\Gitdeployer;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Techsemicolon\Gitdeployer\GitService;
use Techsemicolon\Gitdeployer\Events\GitWebhookWasDeployed;

class GitController extends Controller
{
    /**
     * Run the git webhook
     *
     * @return \Illuminate\Http\Response
     */
    public function pull()
    {
        $service = new GitService();

        $requestIp = \Request::ip();
        
        // Check if the request is really coming from bitbucket's servers
        if(!$service->isValidRequestIp($requestIp)){
            return response('Error | Unauthorized', 403)->header('Content-Type', 'text/plain');
        }

        $payload = \Request::all();
        $payloadRepository = $payload['repository']['name'] ?? '';
        
        // Check if the repository mentioned in the webhook matches with the one
        // present in the project
        if($service->getCurrentRepository() != $payloadRepository)
        {
            return response('Webhook received, invalid repository!', 200)->header('Content-Type', 'text/plain');
        }
        // Check if the webhook has any updated in the current active branch
        // If not then there is no need to run any webhook scripts
        if(!$service->checkIfCurrentBranchWasUpdated($payload)){
            return response('Webhook received, changes not in the active branch. Hence, no changes in the active codebase!', 200)->header('Content-Type', 'text/plain');
        }

        $startTime = Carbon::now();

        $output  = '';
        
        // Run any scripts added before the actual pull of latest releases
        $output .= $service->runBeforeScript();

        // Pull latest releases and run webhook scripts
        $output .= $service->pullTheLatestReleases();

        // Run any scripts added after the actual pull of latest releases
        $output .= $service->runAfterScript();

        $time = Carbon::now()->diffInMinutes($startTime);

        // Emit event got successful git webhook deployment
        event(new GitWebhookWasDeployed($time, $output));

	    return response('Webhook received, successfully pulled latest releases!', 200)->header('Content-Type', 'text/plain');
    }
}

