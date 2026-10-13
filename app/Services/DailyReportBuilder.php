<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Issue;
use App\Models\NewsItem;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DailyReportBuilder
{
    public function generate(string $date, ?int $userId = null): DailyReport
    {
        $existing = DailyReport::whereDate('report_date', $date)->first();
        if ($existing) {
            return $existing;
        } // Snapshot tidak berubah saat dibuka atau saat scheduler diulang.
        $day = Carbon::parse($date)->startOfDay();
        $all = NewsItem::whereBetween('published_at', [$day, $day->copy()->endOfDay()])->get();
        $verified = $all->where('verification_status', 'verified');
        $highlights = $verified->sortByDesc(fn ($news) => ($news->is_urgent ? 2 : 0) + ($news->sentiment === 'negatif' ? 1 : 0))->take(5)
            ->map(fn ($news) => ['id' => $news->id, 'title' => $news->title, 'summary' => Str::limit($news->summary ?? '', 800),
                'source' => $news->source, 'link' => $news->link, 'sentiment' => $news->sentiment, 'is_urgent' => $news->is_urgent])->values();
        // Status penanganan adalah snapshot pada waktu pembuatan, bukan rekonstruksi status historis.
        $issues = Issue::with('assignee')->where('status', '!=', 'resolved')->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderBy('due_at')->get();
        $data = ['article_count' => $all->count(), 'verified_count' => $verified->count(), 'pending_count' => $all->count() - $verified->count(),
            'sentiments' => $verified->countBy('sentiment')->all(), 'urgent_count' => $verified->where('is_urgent', true)->count(),
            'highlights' => $highlights->all(), 'issue_count' => $all->pluck('issue_id')->filter()->unique()->count(),
            'open_issue_count' => $issues->count(), 'overdue_count' => $issues->filter->isOverdue()->count(),
            'issues' => $issues->take(20)->map(fn ($issue) => ['id' => $issue->id, 'title' => $issue->title, 'priority' => Issue::PRIORITIES[$issue->priority],
                'status' => Issue::STATUSES[$issue->status], 'assignee' => $issue->assignee?->name ?? 'Belum ditugaskan',
                'due_at' => $issue->due_at?->format('d/m/Y H:i'), 'overdue' => $issue->isOverdue()])->all()];

        return DailyReport::firstOrCreate(['report_date' => $day->toDateString()], ['data' => $data, 'generated_by' => $userId, 'generated_at' => now()]);
    }
}
