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
     * Run the webhook
     *
     * @return \Illuminate\Http\Response
     */
    public function pull()
    {
        $service = new GitService();

        $requestIp = \Request::ip();
        
        if(!$service->isValidRequestIp($requestIp)){
            return response('Error | Unauthorized', 403)->header('Content-Type', 'text/plain');
        }

        $payload = \Request::all();
        $payloadRepository = $payload['repository']['name'] ?? '';
        
        if($service->getCurrentRepository() != $payloadRepository)
        {
            return response('Webhook received, invalid repository!', 200)->header('Content-Type', 'text/plain');
        }

        if(!$service->checkIfCurrentBranchWasUpdated($payload)){
            return response('Webhook received, changes not in the active branch. Hence, no changes in the active codebase!', 200)->header('Content-Type', 'text/plain');
        }

        $startTime = Carbon::now();

        $output  = '';
        $output .= $service->runBeforeScript();
        $output .= $service->pullTheLatestReleases();
        $output .= $service->runAfterScript();

        $time = Carbon::now()->diffInMinutes($startTime);

        event(new GitWebhookWasDeployed($time, $output));

	    return response('Webhook received, successfully pulled latest releases!', 200)->header('Content-Type', 'text/plain');
    }
}

