<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Issue;
use App\Models\NewsItem;
use App\Models\Taxonomy;
use App\Models\User;
use App\Models\WorkflowRun;
use App\Services\ArticleSuggestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportsAndMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function news(string $link, array $fields = []): NewsItem
    {
        return NewsItem::create(array_merge(['title' => 'Kerusakan jalan Medan menyebabkan kemacetan', 'link' => $link,
            'sentiment' => 'negatif', 'published_at' => today(), 'summary' => 'Petugas memeriksa jalan dan mengatur arus lalu lintas.'], $fields));
    }

    public function test_suggestions_require_confirmation_and_preserve_articles(): void
    {
        $analyst = User::factory()->create(['role' => 'analyst']);
        $region = Taxonomy::create(['kind' => 'region', 'name' => 'Medan']);
        $other = Taxonomy::create(['kind' => 'region', 'name' => 'Binjai']);
        $issue = Issue::create(['title' => 'Kerusakan jalan Medan', 'summary' => 'Tinjauan jalan', 'created_by' => $analyst->id, 'region_id' => $region->id]);
        $similar = $this->news('https://example.com/similar', ['region_id' => $region->id]);
        $this->news('https://example.com/wrong-region', ['region_id' => $other->id]);
        $this->news('https://example.com/old', ['published_at' => today()->subDays(30)]);
        $candidates = app(ArticleSuggestions::class)->forIssue($issue);
        $this->assertCount(1, $candidates);
        $this->assertNull($similar->fresh()->issue_id);
        $this->actingAs($analyst)->post('/issues/'.$issue->id.'/articles', ['news_id' => $similar->id])->assertRedirect();
        $this->get('/issues/'.$issue->id)->assertOk()->assertSee('Berita terkait (1)');
        $this->delete('/issues/'.$issue->id.'/articles/'.$similar->id)->assertRedirect();
        $this->assertDatabaseCount('news_items', 3);
        $this->assertNull($similar->fresh()->issue_id);
        $this->assertDatabaseCount('issue_activities', 2);
    }

    public function test_daily_report_filters_verified_news_and_exports_real_pdf(): void
    {
        $analyst = User::factory()->create(['role' => 'analyst']);
        $item = $this->news('https://example.com/verified', ['verification_status' => 'verified', 'source' => 'Media contoh', 'is_urgent' => true]);
        $this->news('https://example.com/pending', ['title' => 'Artikel belum diperiksa']);
        Issue::create(['title' => 'Perbaikan jalan lingkungan', 'summary' => 'Menunggu laporan petugas.', 'created_by' => $analyst->id,
            'assigned_to' => $analyst->id, 'priority' => 'high', 'due_at' => now()->subHours(3)]);
        $this->actingAs($analyst)->post('/reports', ['date' => today()->toDateString()])->assertRedirect();
        $report = DailyReport::firstOrFail();
        $this->assertSame(2, $report->data['article_count']);
        $this->assertSame(1, $report->data['verified_count']);
        $this->assertCount(1, $report->data['highlights']);
        $this->get('/reports')->assertOk();
        $this->get('/reports/'.$report->id)->assertOk()->assertSee('https://example.com/verified');
        $pdf = $this->get('/reports/'.$report->id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        if ($path = getenv('REPORT_QA_PATH')) {
            file_put_contents($path, $pdf->getContent());
        }
        $item->update(['summary' => 'Diperbarui setelah snapshot']);
        $this->post('/reports', ['date' => today()->toDateString()])->assertRedirect();
        $this->assertDatabaseCount('daily_reports', 1);
        $this->assertStringNotContainsString('Diperbarui', $report->fresh()->data['highlights'][0]['summary']);
        $this->actingAs(User::factory()->create(['role' => 'leader']))->post('/reports', ['date' => today()->toDateString()])->assertForbidden();
        $this->get('/reports/'.$report->id.'/pdf')->assertOk();
    }

    public function test_webhook_acceptance_requires_callback_before_success(): void
    {
        Http::fake(['https://n8n.example/*' => Http::response(['accepted' => true], 200)]);
        config(['services.n8n.trigger_webhook_url' => 'https://n8n.example/trigger', 'services.n8n.trigger_token' => 'trigger-key', 'services.n8n.ingest_token' => 'ingest-key']);
        $this->actingAs(User::factory()->create(['role' => 'analyst']))->postJson('/jalankan-pencarian')->assertStatus(202);
        $run = WorkflowRun::firstOrFail();
        $this->assertSame('accepted', $run->status);
        Http::assertSent(fn ($request) => $request['run_id'] === $run->id && $request->hasHeader('X-Trigger-Key', 'trigger-key'));
        $this->get('/workflow')->assertOk()->assertSee('Webhook diterima');
        $this->withHeaders(['X-API-Key' => 'ingest-key', 'X-Run-ID' => $run->id])->postJson('/api/n8n/news', [
            'title' => 'Artikel baru', 'link' => 'https://example.com/n8n', 'sentiment' => 'netral'])->assertCreated();
        $this->assertSame(1, $run->fresh()->received_count);
        $this->patchJson('/api/n8n/runs/'.$run->id, ['status' => 'succeeded', 'execution_id' => '123'])->assertOk();
        $this->assertSame('succeeded', $run->fresh()->status);
        $finished = $run->fresh()->finished_at;
        $this->travel(1)->minutes();
        $this->patchJson('/api/n8n/runs/'.$run->id, ['status' => 'succeeded'])->assertOk();
        $this->assertTrue($finished->equalTo($run->fresh()->finished_at));
        $this->patchJson('/api/n8n/runs/'.$run->id, ['status' => 'running'])->assertConflict();
    }

    public function test_early_callback_is_not_overwritten_and_stale_status_is_unknown(): void
    {
        config(['services.n8n.trigger_webhook_url' => 'https://n8n.example/trigger', 'services.n8n.trigger_token' => 'trigger-key']);
        Http::fake(function ($request) {
            WorkflowRun::whereKey($request['run_id'])->update(['status' => 'succeeded', 'finished_at' => now()]);

            return Http::response([], 200);
        });
        $this->actingAs(User::factory()->create(['role' => 'analyst']))->postJson('/jalankan-pencarian')->assertStatus(202);
        $this->assertSame('succeeded', WorkflowRun::first()->status);
        $stale = WorkflowRun::create(['status' => 'running', 'heartbeat_at' => now()->subMinutes(16)]);
        $this->artisan('workflow:mark-stale')->assertSuccessful();
        $this->assertSame('unknown', $stale->fresh()->status);
    }

    public function test_missing_trigger_configuration_and_bad_callback_token_are_rejected(): void
    {
        Http::fake();
        config(['services.n8n.trigger_webhook_url' => null, 'services.n8n.ingest_token' => 'key']);
        $this->actingAs(User::factory()->create(['role' => 'analyst']))->postJson('/jalankan-pencarian')->assertStatus(503);
        Http::assertNothingSent();
        $this->postJson('/api/n8n/runs', [])->assertUnauthorized();
        $this->withHeader('X-API-Key', 'key')->postJson('/api/n8n/runs', ['execution_id' => 'daily'])->assertCreated();
    }
}
