<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use App\Models\Taxonomy;
use App\Models\WorkflowRun;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsIngestController extends Controller
{
    public function store(Request $request)
    {
        $runId = $request->header('X-Run-ID');
        if ($runId !== null) {
            validator(['run_id' => $runId], ['run_id' => 'required|uuid|exists:workflow_runs,id'])->validate();
        }
        $payload = $request->all();
        $items = array_is_list($payload) ? $payload : [$payload];
        $validated = validator(['items' => $items], [
            'items' => 'required|array|min:1|max:500', 'items.*' => 'required|array',
            'items.*.title' => 'required|string|max:255', 'items.*.link' => 'required|url:http,https|max:255',
            'items.*.summary' => 'nullable|string|max:10000', 'items.*.source' => 'nullable|string|max:255',
            'items.*.published_at' => 'nullable|date', 'items.*.sentiment' => 'required|in:positif,netral,negatif',
            'items.*.is_urgent' => 'boolean', 'items.*.analysis' => 'nullable|string|max:20000',
            'items.*.archived' => 'boolean',
            'items.*.category' => 'nullable|string|max:150', 'items.*.region' => 'nullable|string|max:150',
            'items.*.agency' => 'nullable|string|max:150',
        ])->validate()['items'];
        $saved = DB::transaction(function () use ($validated, $runId) {
            $run = $runId ? WorkflowRun::lockForUpdate()->findOrFail($runId) : null;
            abort_if($run?->isTerminal(), 409, 'Eksekusi sudah selesai.');
            foreach ($validated as $data) {
                $news = NewsItem::where('link', $data['link'])->lockForUpdate()->first() ?? new NewsItem;
                $news->ai_payload = $data;
                if (! $news->exists || $news->verification_status !== 'verified') {
                    // Arsip merupakan keputusan operator; pengiriman ulang tidak membatalkannya.
                    if ($news->exists) {
                        unset($data['archived']);
                    }
                    if (! empty($data['published_at'])) {
                        $data['published_at'] = Carbon::parse($data['published_at'])->setTimezone(config('app.timezone'));
                    }
                    foreach (Taxonomy::KINDS as $kind => $label) {
                        if (! empty($data[$kind])) {
                            $data[$kind.'_id'] = Taxonomy::where('kind', $kind)->where('name', $data[$kind])->where('is_active', true)->value('id');
                        }
                        unset($data[$kind]);
                    }
                    $news->fill($data);
                }
                $news->review_version = ($news->review_version ?? 0) + 1;
                $news->save();
            }
            if ($run) {
                $run->received_count += count($validated);
                $run->heartbeat_at = now();
                $run->started_at ??= now();
                $run->status = 'running';
                $run->save();
            }

            return count($validated);
        });

        return response()->json(['message' => 'ok', 'saved' => $saved], 201);
    }
}
