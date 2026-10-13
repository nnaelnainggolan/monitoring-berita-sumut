<?php
namespace App\Policies;
use App\Models\Issue;
use App\Models\User;
class IssuePolicy
{
    public function create(User $user): bool { return $user->is_active && in_array($user->role, ['admin', 'analyst'], true); }
    public function manage(User $user, Issue $issue): bool { return $this->create($user); }
    public function update(User $user, Issue $issue): bool
    {
        return $this->create($user) || ($user->is_active && $user->role === 'officer' && $issue->assigned_to === $user->id);
    }
}
