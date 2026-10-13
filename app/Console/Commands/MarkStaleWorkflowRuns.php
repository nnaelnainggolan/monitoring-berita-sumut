<?php

namespace App\Console\Commands;

use App\Models\WorkflowRun;
use Illuminate\Console\Command;

class MarkStaleWorkflowRuns extends Command
{
    protected $signature = 'workflow:mark-stale';

    protected $description = 'Menandai eksekusi tanpa kabar selama 15 menit, tanpa menganggapnya berhasil atau gagal';

    public function handle(): int
    {
        $count = WorkflowRun::whereIn('status', ['requested', 'accepted', 'running'])->where('heartbeat_at', '<', now()->subMinutes(15))
            ->update(['status' => 'unknown', 'message' => 'Tidak ada konfirmasi selama 15 menit. Periksa riwayat eksekusi n8n.']);
        $this->info($count.' status diperbarui.');

        return self::SUCCESS;
    }
}
