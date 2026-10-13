<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkflowRun;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WorkflowTrigger
{
    public function start(User $user): array
    {
        $url = config('services.n8n.trigger_webhook_url');
        $token = config('services.n8n.trigger_token');
        if (! $url || ! $token) {
            return ['code' => 503, 'message' => 'URL atau token pemicu n8n belum dikonfigurasi.'];
        }
        $run = WorkflowRun::create(['triggered_by' => $user->id, 'status' => 'requested', 'heartbeat_at' => now()]);
        try {
            $response = Http::withHeaders(['X-Trigger-Key' => $token])->timeout(10)->post($url, [
                'run_id' => $run->id, 'callback_url' => route('n8n.runs.update', $run),
            ]);
            if ($response->successful()) {
                // Callback bisa datang lebih cepat daripada respons webhook. Jangan menimpa status tersebut.
                WorkflowRun::whereKey($run->id)->where('status', 'requested')->update(['status' => 'accepted', 'heartbeat_at' => now()]);

                return ['code' => 202, 'run_id' => $run->id, 'message' => 'Webhook diterima. Status pengumpulan menunggu konfirmasi n8n; lihat menu Sinkronisasi.'];
            }
            WorkflowRun::whereKey($run->id)->where('status', 'requested')->update(['status' => 'failed', 'finished_at' => now(), 'message' => 'Webhook merespons HTTP '.$response->status().'.']);

            return ['code' => 502, 'run_id' => $run->id, 'message' => 'Pemicu n8n gagal, HTTP '.$response->status().'. Periksa konfigurasi workflow.'];
        } catch (ConnectionException $e) {
            WorkflowRun::whereKey($run->id)->where('status', 'requested')->update(['status' => 'unknown', 'message' => 'Koneksi terputus atau melewati batas waktu. Eksekusi mungkin tetap berjalan.']);

            return ['code' => 502, 'run_id' => $run->id, 'message' => 'Belum dapat memastikan status n8n. Periksa eksekusinya sebelum memicu ulang.'];
        }
    }
}
