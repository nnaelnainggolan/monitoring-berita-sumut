<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filtered($request);

        $sort = $request->string('sort', 'newest');
        $query->orderBy('published_at', $sort === 'oldest' ? 'asc' : 'desc');

        $news = $query->paginate(15)->withQueryString();

        $stats = [
            'total'   => NewsItem::count(),
            'positif' => NewsItem::where('sentiment', 'positif')->count(),
            'netral'  => NewsItem::where('sentiment', 'netral')->count(),
            'negatif' => NewsItem::where('sentiment', 'negatif')->count(),
            'urgent'  => NewsItem::where('is_urgent', true)->count(),
        ];

        $sources = NewsItem::whereNotNull('source')->distinct()->orderBy('source')->pluck('source');

        $trend = NewsItem::selectRaw('DATE(published_at) as tanggal, sentiment, COUNT(*) as jumlah')
            ->where('published_at', '>=', now()->subDays(14))
            ->groupBy('tanggal', 'sentiment')
            ->orderBy('tanggal')
            ->get()
            ->groupBy('tanggal');

        $tickerItems = NewsItem::latest('published_at')
            ->limit(10)
            ->pluck('title');

        // Request AJAX (filter live) cukup dikirim partial-nya saja, tanpa layout penuh
        if ($request->ajax()) {
            return view('dashboard.partials.results', compact('news', 'stats', 'sources', 'trend'));
        }

        return view('dashboard.index', compact('news', 'stats', 'sources', 'trend', 'tickerItems'));
    }

    public function export(Request $request): StreamedResponse
    {
        $items = $this->filtered($request)->orderBy('published_at', 'desc')->get();

        $filename = 'berita-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Judul', 'Sumber', 'Tanggal', 'Sentimen', 'Mendesak', 'Analisis', 'Link']);
            foreach ($items as $item) {
                fputcsv($out, [
                    $item->title,
                    $item->source,
                    optional($item->published_at)->format('Y-m-d H:i'),
                    $item->sentiment,
                    $item->is_urgent ? 'Ya' : 'Tidak',
                    $item->analysis,
                    $item->link,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filtered(Request $request)
    {
        $query = NewsItem::query();

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->string('q') . '%');
        }
        if ($request->filled('sentiment')) {
            $query->where('sentiment', $request->string('sentiment'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }
        if ($request->boolean('urgent_only')) {
            $query->where('is_urgent', true);
        }
        if ($request->filled('archived')) {
            $query->where('archived', $request->string('archived') === '1');
        }
        if ($request->filled('from')) {
            $query->whereDate('published_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('published_at', '<=', $request->date('to'));
        }

        return $query;
    }
}
