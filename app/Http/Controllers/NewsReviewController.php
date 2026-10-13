<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NewsReviewController extends Controller
{
    public const FIELDS = ['summary', 'sentiment', 'is_urgent', 'analysis', 'category_id', 'region_id', 'agency_id', 'verification_status'];

    public function edit(NewsItem $berita)
    {
        return view('news.review', ['item' => $berita, 'taxonomies' => Taxonomy::orderBy('name')->get()->groupBy('kind')]);
    }

    public function update(Request $request, NewsItem $berita)
    {
        $rules = [
            'summary' => 'nullable|string|max:10000', 'analysis' => 'nullable|string|max:20000',
            'sentiment' => 'required|in:positif,netral,negatif', 'is_urgent' => 'required|boolean',
            'verification_status' => 'required|in:pending,verified', 'note' => 'required|string|max:2000',
            'review_version' => 'required|integer|min:0',
        ];
        foreach (Taxonomy::KINDS as $kind => $label) {
            $rules[$kind.'_id'] = ['nullable', 'integer', Rule::exists('taxonomies', 'id')->where('kind', $kind)];
        }
        $data = $request->validate($rules);
        DB::transaction(function () use ($berita, $request, $data) {
            $news = NewsItem::lockForUpdate()->findOrFail($berita->id);
            abort_if($news->review_version !== (int) $data['review_version'], 409, 'Berita telah diperbarui. Muat ulang sebelum menyimpan.');
            $before = $news->only(self::FIELDS);
            // Snapshot pertama menjaga hasil AI asli dari berita sebelum fitur review tersedia.
            if ($news->ai_payload === null) $news->ai_payload = $news->only(['summary', 'sentiment', 'is_urgent', 'analysis']);
            $news->fill(collect($data)->only(self::FIELDS)->all());
            $news->verified_by = $data['verification_status'] === 'verified' ? $request->user()->id : null;
            $news->verified_at = $data['verification_status'] === 'verified' ? now() : null;
            $news->review_version++;
            $news->save();
            $news->reviews()->create(['user_id' => $request->user()->id, 'before' => $before, 'after' => $news->only(self::FIELDS), 'note' => $data['note']]);
        });
        return redirect()->route('berita.show', $berita)->with('status', 'Pemeriksaan berita disimpan.');
    }
}
