<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsItemResource;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewsApiController extends Controller
{
    /**
     * GET /api/news
     * Query params: q, sentiment, source, urgent_only, archived, sort, page
     */
    public function index(Request $request): JsonResponse
    {
        $query = NewsItem::query();

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->input('q') . '%');
        }
        if ($request->filled('sentiment')) {
            $query->where('sentiment', $request->input('sentiment'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }
        if ($request->boolean('urgent_only')) {
            $query->where('is_urgent', true);
        }
        if ($request->filled('archived')) {
            $query->where('archived', $request->input('archived') === '1');
        }

        $sort = $request->input('sort', 'newest');
        $query->orderBy('published_at', $sort === 'oldest' ? 'asc' : 'desc');

        $news = $query->paginate(20);

        return response()->json([
            'data' => NewsItemResource::collection($news->items()),
            'meta' => [
                'current_page' => $news->currentPage(),
                'last_page'    => $news->lastPage(),
                'total'        => $news->total(),
            ],
        ]);
    }

    /**
     * GET /api/news/{news}
     */
    public function show(NewsItem $news): NewsItemResource
    {
        return new NewsItemResource($news);
    }

    /**
     * POST /api/news/{news}/toggle-archive
     */
    public function toggleArchive(NewsItem $news): NewsItemResource
    {
        $news->update(['archived' => ! $news->archived]);
        return new NewsItemResource($news);
    }

    /**
     * GET /api/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total'   => NewsItem::count(),
            'positif' => NewsItem::where('sentiment', 'positif')->count(),
            'netral'  => NewsItem::where('sentiment', 'netral')->count(),
            'negatif' => NewsItem::where('sentiment', 'negatif')->count(),
            'urgent'  => NewsItem::where('is_urgent', true)->count(),
        ]);
    }

    /**
     * GET /api/sources
     */
    public function sources(): JsonResponse
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

        return response()->json($sourceStats);
    }

    /**
     * GET /api/trend — tren sentimen 14 hari terakhir
     */
    public function trend(): JsonResponse
    {
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $rows = NewsItem::selectRaw('DATE(published_at) as tanggal, sentiment, COUNT(*) as jumlah')
            ->where('published_at', '>=', now()->subDays(14))
            ->groupBy('tanggal', 'sentiment')
            ->get()
            ->groupBy('tanggal');

        $result = $days->map(function ($date) use ($rows) {
            $dayRows = $rows[$date] ?? collect();
            return [
                'date'    => $date,
                'label'   => \Carbon\Carbon::parse($date)->format('d/m'),
                'positif' => (int) optional($dayRows->firstWhere('sentiment', 'positif'))->jumlah,
                'netral'  => (int) optional($dayRows->firstWhere('sentiment', 'netral'))->jumlah,
                'negatif' => (int) optional($dayRows->firstWhere('sentiment', 'negatif'))->jumlah,
            ];
        });

        return response()->json($result->values());
    }

    /**
     * GET /api/weekly — perbandingan minggu ini vs minggu lalu, per hari
     */
    public function weekly(): JsonResponse
    {
        $thisWeekStart = now()->startOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();

        $thisWeek = NewsItem::selectRaw('DATE(published_at) as tanggal, COUNT(*) as jumlah')
            ->whereBetween('published_at', [$thisWeekStart, $thisWeekStart->copy()->endOfWeek()])
            ->groupBy('tanggal')->pluck('jumlah', 'tanggal');

        $lastWeek = NewsItem::selectRaw('DATE(published_at) as tanggal, COUNT(*) as jumlah')
            ->whereBetween('published_at', [$lastWeekStart, $lastWeekStart->copy()->endOfWeek()])
            ->groupBy('tanggal')->pluck('jumlah', 'tanggal');

        $result = collect(range(0, 6))->map(function ($i) use ($thisWeekStart, $lastWeekStart, $thisWeek, $lastWeek) {
            $thisDate = $thisWeekStart->copy()->addDays($i)->format('Y-m-d');
            $lastDate = $lastWeekStart->copy()->addDays($i)->format('Y-m-d');
            return [
                'label'       => $thisWeekStart->copy()->addDays($i)->translatedFormat('D'),
                'minggu_ini'  => (int) ($thisWeek[$thisDate] ?? 0),
                'minggu_lalu' => (int) ($lastWeek[$lastDate] ?? 0),
            ];
        });

        return response()->json($result);
    }
}
