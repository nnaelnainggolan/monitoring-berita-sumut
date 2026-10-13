<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\NewsItem;
use Illuminate\Support\Collection;

class ArticleSuggestions
{
    /** Saran heuristik, selalu memerlukan konfirmasi analis. Tidak mengubah artikel. */
    public function forIssue(Issue $issue): Collection
    {
        $linked = $issue->news;
        $anchor = $linked->whereNotNull('published_at')->sortBy('published_at')->first()?->published_at ?? $issue->created_at;
        $references = $linked->pluck('title')->prepend($issue->title)->map(fn ($title) => $this->tokens($title));

        return NewsItem::whereNull('issue_id')->whereBetween('published_at', [$anchor->copy()->subDays(7)->startOfDay(), $anchor->copy()->addDays(7)->endOfDay()])
            ->latest('published_at')->limit(250)->get()->map(function ($news) use ($issue, $references) {
                if ($issue->region_id && $news->region_id && (int) $issue->region_id !== (int) $news->region_id) {
                    return null;
                }
                $words = $this->tokens($news->title);
                $score = $references->map(function ($reference) use ($words) {
                    $intersection = count(array_intersect($words, $reference));

                    return $intersection < 2 ? 0 : $intersection / max(1, count(array_unique(array_merge($words, $reference))));
                })->max() ?? 0;

                return $score >= 0.3 ? ['news' => $news, 'score' => (int) round($score * 100)] : null;
            })->filter()->sortByDesc('score')->take(10)->values();
    }

    private function tokens(string $text): array
    {
        $words = preg_split('/[^\pL\pN]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter($words, fn ($word) => mb_strlen($word) > 2
            && ! in_array($word, ['dan', 'dari', 'untuk', 'yang', 'dengan', 'oleh', 'pada', 'ini', 'itu', 'telah', 'akan', 'berita', 'pemprov', 'pemerintah', 'provinsi', 'sumatera', 'utara']))));
    }
}
