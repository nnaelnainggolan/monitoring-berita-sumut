<?php

namespace Tests\Feature;

use App\Models\NewsItem;
use App\Models\Taxonomy;
use App\Models\User;
use App\Services\DailyReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EditorialFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function article(array $extra = []): NewsItem
    {
        return NewsItem::create(array_merge(['title' => 'Jalan rusak di Medan', 'link' => 'https://example.com/news',
            'sentiment' => 'negatif', 'published_at' => now(), 'summary' => 'Ringkasan awal'], $extra));
    }

    public function test_login_and_logout_regenerate_access(): void
    {
        User::factory()->create(['email' => 'analis@example.com', 'password' => 'a-strong-password', 'role' => 'analyst']);
        $this->post('/login', ['email' => 'analis@example.com', 'password' => 'a-strong-password'])->assertRedirect('/');
        $this->get('/')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login_or_use_a_token(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        Sanctum::actingAs($user);
        $this->getJson('/api/news')->assertForbidden();
    }

    public function test_leader_cannot_mutate_and_admin_can_open_admin_pages(): void
    {
        $item = $this->article();
        $this->actingAs(User::factory()->create(['role' => 'leader']))->get('/')->assertOk();
        $this->post('/berita/'.$item->id.'/toggle-archive')->assertForbidden();
        $this->post('/jalankan-pencarian')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/users')->assertOk();
        $this->get('/admin/taxonomies')->assertOk();
    }

    public function test_ingest_fails_closed_and_batch_validation_is_atomic(): void
    {
        config(['services.n8n.ingest_token' => null]);
        $valid = ['title' => 'Berita', 'link' => 'https://example.com/a', 'sentiment' => 'netral'];
        $this->postJson('/api/n8n/news', $valid)->assertUnauthorized();
        config(['services.n8n.ingest_token' => 'test-key']);
        $this->withHeader('X-API-Key', 'test-key')->postJson('/api/n8n/news', [$valid, ['title' => 'Invalid']])->assertUnprocessable();
        $this->assertDatabaseCount('news_items', 0);
        $this->postJson('/api/n8n/news', $valid)->assertCreated();
        $this->postJson('/api/n8n/news', $valid)->assertCreated();
        $this->assertDatabaseCount('news_items', 1);
    }

    public function test_review_is_audited_and_verified_fields_survive_reingest(): void
    {
        $item = $this->article();
        $user = User::factory()->create(['role' => 'analyst']);
        $this->actingAs($user)->get('/berita/'.$item->id.'/review')->assertOk();
        $data = ['summary' => 'Ringkasan diperiksa', 'sentiment' => 'netral', 'is_urgent' => false,
            'verification_status' => 'verified', 'review_version' => 0, 'note' => 'Sudah membaca sumber'];
        $this->put('/berita/'.$item->id.'/review', $data)->assertRedirect('/berita/'.$item->id);
        $this->assertDatabaseHas('news_reviews', ['news_item_id' => $item->id, 'user_id' => $user->id]);
        $this->get('/berita/'.$item->id)->assertOk()->assertSee('Sudah membaca sumber');
        config(['services.n8n.ingest_token' => 'test-key']);
        $this->withHeader('X-API-Key', 'test-key')->postJson('/api/n8n/news', ['title' => 'Judul baru dari AI',
            'link' => $item->link, 'sentiment' => 'negatif', 'summary' => 'AI baru'])->assertCreated();
        $this->assertSame('Ringkasan diperiksa', $item->fresh()->summary);
        $this->assertSame('AI baru', $item->fresh()->ai_payload['summary']);
        $this->put('/berita/'.$item->id.'/review', $data)->assertConflict();
    }

    public function test_review_rejects_wrong_taxonomy_kind(): void
    {
        $item = $this->article();
        $region = Taxonomy::create(['kind' => 'region', 'name' => 'Medan']);
        $this->actingAs(User::factory()->create(['role' => 'analyst']))->putJson('/berita/'.$item->id.'/review', [
            'sentiment' => 'netral', 'is_urgent' => false, 'verification_status' => 'pending',
            'review_version' => 0, 'note' => 'Periksa', 'category_id' => $region->id,
        ])->assertUnprocessable();
    }

    public function test_api_needs_token_and_respects_role(): void
    {
        $item = $this->article();
        $this->getJson('/api/news')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['role' => 'leader']));
        $this->getJson('/api/news')->assertOk()->assertJsonPath('meta.total', 1);
        $this->postJson('/api/news/'.$item->id.'/toggle-archive')->assertForbidden();
    }

    public function test_last_admin_cannot_be_disabled_or_demoted(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->put('/admin/users/'.$user->id, ['name' => $user->name, 'email' => $user->email,
            'role' => 'leader', 'is_active' => 1])->assertSessionHasErrors('role');
        $this->assertSame('admin', $user->fresh()->role);
    }

    public function test_dashboard_can_filter_classifications_and_export(): void
    {
        $cat = Taxonomy::create(['kind' => 'category', 'name' => 'Infrastruktur']);
        $this->article(['category_id' => $cat->id]);
        $this->actingAs(User::factory()->create(['role' => 'leader']))->get('/?category_id='.$cat->id)->assertOk()->assertSee('Jalan rusak di Medan');
        $this->get('/?category_id=999')->assertOk()->assertSee('Belum ada berita yang cocok');
        $this->get('/export')->assertOk()->assertDownload();
    }

    public function test_date_only_upper_bound_and_invalid_filters_are_handled(): void
    {
        $this->article(['published_at' => '2026-10-08 09:00:00']);
        $this->actingAs(User::factory()->create())->get('/?to=2026-10-09')->assertOk()->assertSee('Jalan rusak di Medan');
        $this->getJson('/api/news?to=2026-10-09')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/news?source[]=bad')->assertUnprocessable();
        $this->getJson('/api/news?from=2026-10-09&to=2026-10-08')->assertUnprocessable();
    }

    public function test_ingest_cannot_set_editorial_permissions_or_issue_links(): void
    {
        config(['services.n8n.ingest_token' => 'test-key']);
        $this->withHeader('X-API-Key', 'test-key')->postJson('/api/n8n/news', [
            'title' => 'Artikel belum diperiksa', 'link' => 'https://example.com/untrusted', 'sentiment' => 'netral',
            'verification_status' => 'verified', 'verified_by' => 99, 'issue_id' => 99,
        ])->assertCreated();
        $news = NewsItem::firstOrFail();
        $this->assertSame('pending', $news->verification_status);
        $this->assertNull($news->verified_by);
        $this->assertNull($news->issue_id);
    }

    public function test_login_throttling_and_api_token_revocation(): void
    {
        $user = User::factory()->create(['password' => 'valid-long-password']);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'wrong', 'device_name' => 'Test'])->assertUnprocessable();
        }
        $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'valid-long-password', 'device_name' => 'Test'])->assertTooManyRequests();
        $this->travel(61)->seconds();
        $response = $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'valid-long-password', 'device_name' => 'Test'])->assertOk();
        $this->withToken($response->json('token'))->getJson('/api/news')->assertOk();
        $this->deleteJson('/api/auth/token')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_ingest_normalizes_utc_dates_for_local_daily_reports(): void
    {
        config(['services.n8n.ingest_token' => 'test-key', 'app.timezone' => 'Asia/Jakarta']);
        $this->withHeader('X-API-Key', 'test-key')->postJson('/api/n8n/news', [
            'title' => 'Berita lewat tengah malam WIB', 'link' => 'https://example.com/timezone',
            'sentiment' => 'netral', 'published_at' => '2026-10-11T18:30:00Z',
        ])->assertCreated();
        $this->assertDatabaseHas('news_items', ['published_at' => '2026-10-12 01:30:00']);
        $news = NewsItem::firstOrFail();
        $this->assertSame('2026-10-11T18:30:00Z', $news->ai_payload['published_at']);
        $report = app(DailyReportBuilder::class)->generate('2026-10-12');
        $this->assertSame(1, $report->data['article_count']);
    }
}
