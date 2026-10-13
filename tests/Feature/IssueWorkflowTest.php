<?php
namespace Tests\Feature;

use App\Models\Issue;
use App\Models\NewsItem;
use App\Models\User;
use App\Notifications\IssueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IssueWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeIssue(User $analyst, User $officer): Issue
    {
        return Issue::create(['title' => 'Jalan Medan', 'summary' => 'Periksa kerusakan jalan.', 'created_by' => $analyst->id,
            'assigned_to' => $officer->id, 'priority' => 'high', 'due_at' => now()->subDay()]);
    }

    public function test_create_issue_attaches_source_and_notifies_assignee(): void
    {
        Notification::fake();
        $analyst = User::factory()->create(['role' => 'analyst']);
        $officer = User::factory()->create(['role' => 'officer']);
        $news = NewsItem::create(['title' => 'Sumber', 'link' => 'https://example.com/1', 'sentiment' => 'negatif']);
        $this->actingAs($analyst)->get('/issues/create?news_id='.$news->id)->assertOk();
        $this->post('/issues', ['title' => 'Isu baru', 'summary' => 'Ringkasan isu', 'priority' => 'high',
            'assigned_to' => (string)$officer->id, 'news_id' => $news->id])->assertRedirect();
        $issue = Issue::firstOrFail();
        $this->assertEquals($issue->id, $news->fresh()->issue_id);
        Notification::assertSentTo($officer, IssueNotification::class);
        $this->get('/issues')->assertOk()->assertSee('Isu baru');
        $this->get('/issues/'.$issue->id)->assertOk()->assertSee('Ringkasan isu');
        $this->postJson('/issues', ['title' => 'Duplikat', 'summary' => 'Tes', 'priority' => 'normal', 'news_id' => $news->id])->assertConflict();
        $this->assertDatabaseCount('issues', 1);
    }

    public function test_only_assigned_officer_can_progress_verified_issue(): void
    {
        Notification::fake();
        $analyst = User::factory()->create(['role' => 'analyst']);
        $officer = User::factory()->create(['role' => 'officer']);
        $issue = $this->makeIssue($analyst, $officer);
        $this->actingAs(User::factory()->create(['role' => 'officer']))->putJson('/issues/'.$issue->id, [])->assertForbidden();
        $this->actingAs($officer)->putJson('/issues/'.$issue->id, ['status' => 'resolved', 'version' => 0, 'note' => 'Melewati alur'])->assertUnprocessable();
        $this->actingAs($analyst)->put('/issues/'.$issue->id, ['title' => $issue->title, 'summary' => $issue->summary, 'priority' => 'high',
            'assigned_to' => $officer->id, 'status' => 'verified', 'version' => 0, 'note' => 'Sumber sudah diverifikasi'])->assertRedirect();
        $this->actingAs($officer)->put('/issues/'.$issue->id, ['status' => 'in_progress', 'version' => 1, 'note' => 'Tim turun ke lapangan', 'assigned_to' => $analyst->id])->assertRedirect();
        $this->assertEquals($officer->id, $issue->fresh()->assigned_to);
        $this->put('/issues/'.$issue->id, ['status' => 'resolved', 'version' => 2, 'note' => 'Perbaikan selesai dan diperiksa'])->assertRedirect();
        $this->assertNotNull($issue->fresh()->resolved_at);
        $this->assertDatabaseCount('issue_activities', 3);
        $this->putJson('/issues/'.$issue->id, ['status' => 'resolved', 'version' => 2, 'note' => 'Usang'])->assertConflict();
        $this->putJson('/issues/'.$issue->id, ['status' => 'in_progress', 'version' => 3, 'note' => 'Buka lagi'])->assertUnprocessable();
    }

    public function test_leader_is_read_only_and_activity_text_is_escaped(): void
    {
        $analyst = User::factory()->create(['role' => 'analyst']);
        $officer = User::factory()->create(['role' => 'officer']);
        $issue = $this->makeIssue($analyst, $officer);
        $this->actingAs(User::factory()->create(['role' => 'leader']))->get('/issues/'.$issue->id)->assertOk();
        $this->post('/issues/'.$issue->id.'/comments', ['note' => 'Komentar'])->assertForbidden();
        $this->get('/issues/create')->assertForbidden();
        Notification::fake();
        $this->actingAs($officer)->post('/issues/'.$issue->id.'/comments', ['note' => '<script>alert(1)</script>'])->assertRedirect();
        $this->get('/issues/'.$issue->id)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_overdue_reminders_are_daily_and_skip_resolved(): void
    {
        Notification::fake();
        $analyst = User::factory()->create(['role' => 'analyst']);
        $officer = User::factory()->create(['role' => 'officer']);
        $this->makeIssue($analyst, $officer);
        $this->makeIssue($analyst, $officer)->update(['status' => 'resolved']);
        $this->artisan('issues:remind')->assertSuccessful();
        $this->artisan('issues:remind')->assertSuccessful();
        $this->assertDatabaseCount('issue_reminders', 1);
        Notification::assertSentToTimes($officer, IssueNotification::class, 1);
        $this->travel(1)->days();
        $this->artisan('issues:remind')->assertSuccessful();
        Notification::assertSentToTimes($officer, IssueNotification::class, 2);
    }

    public function test_notification_preferences_and_ownership(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = $other->notifications()->create(['id' => (string)\Illuminate\Support\Str::uuid(), 'type' => IssueNotification::class,
            'data' => ['issue_id' => 1, 'title' => 'Private', 'message' => 'Test']]);
        $this->actingAs($user)->get('/notifications')->assertOk();
        $this->post('/notifications/'.$notification->id.'/read')->assertNotFound();
        $this->put('/notifications/preferences', ['email' => 1, 'watch' => 1, 'min_priority' => 'critical'])->assertRedirect();
        $this->assertTrue($user->fresh()->notification_preferences['email']);
    }
}
