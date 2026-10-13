<?php

namespace App\Support;

use App\Models\NewsItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class NewsFilters
{
    public static function query(Request $request): Builder
    {
        $request->validate([
            'q' => 'nullable|string|max:255', 'source' => 'nullable|string|max:255',
            'sentiment' => 'nullable|in:positif,netral,negatif',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'category_id' => 'nullable|integer|min:1', 'region_id' => 'nullable|integer|min:1', 'agency_id' => 'nullable|integer|min:1',
            'verification_status' => 'nullable|in:pending,verified', 'sort' => 'nullable|in:newest,oldest',
            'urgent_only' => 'nullable|boolean', 'archived' => 'nullable|in:0,1', 'page' => 'nullable|integer|min:1',
        ]);
        $query = NewsItem::with(['category', 'region', 'agency']);
        foreach (['source', 'sentiment', 'category_id', 'region_id', 'agency_id', 'verification_status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->input('q').'%');
        }
        if ($request->boolean('urgent_only')) {
            $query->where('is_urgent', true);
        }
        if ($request->filled('archived')) {
            $query->where('archived', $request->boolean('archived'));
        }
        if ($request->filled('from')) {
            $query->whereDate('published_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('published_at', '<=', $request->input('to'));
        }

        return $query;
    }
}
