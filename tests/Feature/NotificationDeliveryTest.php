<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use App\Services\IssueNotifier;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use DatabaseMigrations;

    public function test_database_notification_is_dispatched_after_commit_and_read_by_owner(): void
    {
        $analyst = User::factory()->create(['role' => 'analyst']);
        $officer = User::factory()->create(['role' => 'officer']);
        DB::transaction(function () use ($analyst, $officer) {
            $issue = Issue::create(['title' => 'Uji notifikasi', 'summary' => 'Tindak lanjut', 'created_by' => $analyst->id, 'assigned_to' => $officer->id]);
            app(IssueNotifier::class)->send($issue, 'Tugas baru untuk Anda.', $analyst->id);
            $this->assertDatabaseCount('notifications', 0);
        });
        $this->assertDatabaseCount('notifications', 1);
        $notification = $officer->notifications()->firstOrFail();
        $this->actingAs($officer)->get('/notifications')->assertOk()->assertSee('Tugas baru untuk Anda.');
        $this->post('/notifications/'.$notification->id.'/read')->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
