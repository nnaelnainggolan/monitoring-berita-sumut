<?php
namespace App\Services;
use App\Models\Issue;
use App\Models\User;
use App\Notifications\IssueNotification;

class IssueNotifier
{
    public function send(Issue $issue, string $message, ?int $actorId = null, bool $includeWatchers = false): void
    {
        User::where('is_active', true)->chunkById(100, function ($users) use ($issue, $message, $actorId, $includeWatchers) {
            foreach ($users as $user) {
                if ($user->id === $actorId) continue;
                $direct = in_array($user->id, [$issue->assigned_to, $issue->created_by], true);
                $prefs = $user->notification_preferences ?? [];
                $watch = $includeWatchers && ($prefs['watch'] ?? false)
                    && Issue::PRIORITY_RANK[$issue->priority] >= Issue::PRIORITY_RANK[$prefs['min_priority'] ?? 'high']
                    && (empty($prefs['categories']) || in_array($issue->category_id, $prefs['categories']))
                    && (empty($prefs['regions']) || in_array($issue->region_id, $prefs['regions']));
                if ($direct || $watch) $user->notify(new IssueNotification($issue->id, $issue->title, $message));
            }
        });
    }
}
