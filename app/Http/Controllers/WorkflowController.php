<?php

namespace App\Http\Controllers;

use App\Models\WorkflowRun;

class WorkflowController extends Controller
{
    public function index()
    {
        return view('workflow.index', ['runs' => WorkflowRun::with('user')->latest()->paginate(20),
            'lastSuccess' => WorkflowRun::where('status', 'succeeded')->latest('finished_at')->first(),
            'configured' => (bool) (config('services.n8n.trigger_webhook_url') && config('services.n8n.trigger_token'))]);
    }
}
