<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Services\WorkflowTrigger;
use App\Support\NewsFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filtered($request);

        $sort = $request->input('sort', 'newest');
        $query->orderBy('published_at', $sort === 'oldest' ? 'asc' : 'desc');

        $news = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => NewsItem::count(),
            'positif' => NewsItem::where('sentiment', 'positif')->count(),
            'netral' => NewsItem::where('sentiment', 'netral')->count(),
            'negatif' => NewsItem::where('sentiment', 'negatif')->count(),
            'urgent' => NewsItem::where('is_urgent', true)->count(),
        ];

        $sources = NewsItem::whereNotNull('source')->distinct()->orderBy('source')->pluck('source');

        // Tren 14 hari terakhir untuk grafik
        $trend = NewsItem::selectRaw('DATE(published_at) as tanggal, sentiment, COUNT(*) as jumlah')
            ->where('published_at', '>=', now()->subDays(14))
            ->groupBy('tanggal', 'sentiment')
            ->orderBy('tanggal')
            ->get()
            ->groupBy('tanggal');

        // Perbandingan minggu ini vs minggu lalu, per hari (Senin-Minggu)
        $thisWeekStart = now()->startOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();

        $thisWeek = NewsItem::selectRaw('DATE(published_at) as tanggal, COUNT(*) as jumlah')
            ->whereBetween('published_at', [$thisWeekStart, $thisWeekStart->copy()->endOfWeek()])
            ->groupBy('tanggal')->pluck('jumlah', 'tanggal');

        $lastWeek = NewsItem::selectRaw('DATE(published_at) as tanggal, COUNT(*) as jumlah')
            ->whereBetween('published_at', [$lastWeekStart, $lastWeekStart->copy()->endOfWeek()])
            ->groupBy('tanggal')->pluck('jumlah', 'tanggal');

        $weeklyComparison = collect(range(0, 6))->map(function ($i) use ($thisWeekStart, $lastWeekStart, $thisWeek, $lastWeek) {
            $dayLabel = $thisWeekStart->copy()->addDays($i)->translatedFormat('D');
            $thisDate = $thisWeekStart->copy()->addDays($i)->format('Y-m-d');
            $lastDate = $lastWeekStart->copy()->addDays($i)->format('Y-m-d');

            return [
                'label' => $dayLabel,
                'minggu_ini' => (int) ($thisWeek[$thisDate] ?? 0),
                'minggu_lalu' => (int) ($lastWeek[$lastDate] ?? 0),
            ];
        });

        // Headline untuk ticker: semua berita terbaru
        $tickerItems = NewsItem::latest('published_at')
            ->limit(10)
            ->pluck('title');

        $latestId = (int) NewsItem::max('id');

        // Request AJAX (filter live) cukup dikirim partial-nya saja, tanpa layout penuh
        if ($request->ajax()) {
            return view('dashboard.partials.results', compact('news', 'stats', 'sources', 'trend', 'weeklyComparison'));
        }

        return view('dashboard.index', compact('news', 'stats', 'sources', 'trend', 'tickerItems', 'weeklyComparison', 'latestId'));
    }

    public function sources()
    {
        $sourceStats = NewsItem::selectRaw("source,
                COUNT(*) as total,
                SUM(CASE WHEN sentiment = 'positif' THEN 1 ELSE 0 END) as positif,
                SUM(CASE WHEN sentiment = 'netral' THEN 1 ELSE 0 END) as netral,
                SUM(CASE WHEN sentiment = 'negatif' THEN 1 ELSE 0 END) as negatif")
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('negatif')
            ->get();

        return view('dashboard.sources', compact('sourceStats'));
    }

    public function show(NewsItem $berita)
    {
        return view('dashboard.show', ['item' => $berita->load(['category', 'region', 'agency', 'verifier', 'reviews.user'])]);
    }

    public function toggleArchive(Request $request, NewsItem $berita)
    {
        $berita->update(['archived' => ! $berita->archived]);

        if ($request->ajax()) {
            return response()->json(['archived' => $berita->archived]);
        }

        return back();
    }

    /**
     * Dipoll berkala oleh JS di browser untuk notifikasi push.
     * Mengembalikan berita mendesak yang id-nya lebih baru dari $sinceId.
     */
    public function checkUrgent(Request $request): JsonResponse
    {
        $sinceId = (int) $request->query('since_id', 0);

        $items = NewsItem::where('is_urgent', true)
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->get(['id', 'title', 'source']);

        return response()->json([
            'items' => $items,
            'latestId' => max($sinceId, (int) NewsItem::max('id')),
        ]);
    }

    /**
     * Dipanggil oleh tombol "Cari Berita Terbaru" di dashboard.
     * Memicu webhook n8n supaya workflow pencarian & analisis berita
     * langsung berjalan (di luar jadwal harian jam 07:00).
     */
    public function runWorkflow(): JsonResponse
    {
        $result = app(WorkflowTrigger::class)->start(request()->user());
        $status = $result['code'];
        unset($result['code']);

        return response()->json($result, $status);
    }

    public function export(Request $request): StreamedResponse
    {
        $items = $this->filtered($request)->orderBy('published_at', 'desc')->get();

        $filename = 'berita-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 supaya karakter Indonesia (é, ü, dsb) & Excel terbaca benar
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'No',
                'Judul Berita',
                'Ringkasan',
                'Sumber',
                'Tanggal Terbit',
                'Sentimen',
                'Status Mendesak',
                'Status Arsip',
                'Ringkasan Analisis',
                'Link Berita',
            ], ';');

            foreach ($items as $i => $item) {
                fputcsv($out, [
                    $i + 1,
                    $item->title,
                    $item->summary ?: '-',
                    $item->source ?: '-',
                    optional($item->published_at)->translatedFormat('d F Y H:i') ?: '-',
                    ucfirst($item->sentiment),
                    $item->is_urgent ? 'Ya' : 'Tidak',
                    $item->archived ? 'Sudah diarsipkan' : 'Belum diarsipkan',
                    $item->analysis ?: '-',
                    $item->link,
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        return NewsFilters::query($request);
    }
}
