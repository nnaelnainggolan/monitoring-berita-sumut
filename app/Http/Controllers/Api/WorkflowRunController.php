<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkflowRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowRunController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['execution_id' => 'nullable|string|max:255']);
        $run = WorkflowRun::create($data + ['status' => 'running', 'started_at' => now(), 'heartbeat_at' => now()]);

        return response()->json(['run_id' => $run->id, 'callback_url' => route('n8n.runs.update', $run)], 201);
    }

    public function update(Request $request, WorkflowRun $run)
    {
        $data = $request->validate(['status' => 'required|in:running,succeeded,failed', 'execution_id' => 'nullable|string|max:255', 'message' => 'nullable|string|max:2000']);
        DB::transaction(function () use ($run, $data) {
            $run = WorkflowRun::lockForUpdate()->findOrFail($run->id);
            if ($run->isTerminal()) {
                abort_if($data['status'] !== $run->status, 409, 'Eksekusi sudah selesai dengan status berbeda.');

                return; // Callback ulang tidak mengubah waktu selesai atau data sebelumnya.
            }
            $run->fill($data);
            $run->started_at ??= now();
            $run->heartbeat_at = now();
            if ($run->isTerminal()) {
                $run->finished_at = now();
            }
            $run->save();
        });

        return response()->json(['run_id' => $run->id, 'status' => $run->fresh()->status]);
    }
}
