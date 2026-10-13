<?php
namespace App\Console\Commands;

use App\Models\Issue;
use App\Services\IssueNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemindOverdueIssues extends Command
{
    protected $signature = 'issues:remind';
    protected $description = 'Mengantrekan pengingat isu terlambat, maksimal sekali per isu per hari';
    public function handle(IssueNotifier $notifier): int
    {
        $count = 0;
        Issue::where('status', '!=', 'resolved')->where('due_at', '<', now())->chunkById(100, function ($issues) use ($notifier, &$count) {
            foreach ($issues as $issue) {
                DB::transaction(function () use ($issue, $notifier, &$count) {
                    $inserted = DB::table('issue_reminders')->insertOrIgnore(['issue_id' => $issue->id, 'reminder_date' => today()->toDateString()]);
                    if ($inserted) {
                        $notifier->send($issue, 'Tenggat penanganan telah lewat: '.$issue->due_at->format('d/m/Y H:i').' WIB.');
                        $count++;
                    }
                });
            }
        });
        $this->info($count.' pengingat diantrekan.');
        return self::SUCCESS;
    }
}
