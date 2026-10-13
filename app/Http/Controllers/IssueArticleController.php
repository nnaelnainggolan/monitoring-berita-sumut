<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class IssueArticleController extends Controller
{
    public function store(Request $request, Issue $issue)
    {
        Gate::authorize('manage', $issue);
        $data = $request->validate(['news_id' => 'required|integer|exists:news_items,id']);
        DB::transaction(function () use ($issue, $request, $data) {
            $news = NewsItem::lockForUpdate()->findOrFail($data['news_id']);
            abort_if($news->issue_id && (int) $news->issue_id !== $issue->id, 409, 'Berita sudah terhubung ke isu lain. Lepaskan terlebih dahulu.');
            if ((int) $news->issue_id === $issue->id) {
                return;
            }
            $news->issue_id = $issue->id;
            $news->save();
            $issue->activities()->create(['user_id' => $request->user()->id, 'kind' => 'linked', 'note' => 'Artikel ditautkan: '.$news->title, 'changes' => ['news_id' => $news->id]]);
        });

        return back()->with('status', 'Artikel ditautkan; sumber asli tetap tersimpan.');
    }

    public function destroy(Request $request, Issue $issue, NewsItem $news)
    {
        Gate::authorize('manage', $issue);
        DB::transaction(function () use ($issue, $request, $news) {
            $news = NewsItem::lockForUpdate()->findOrFail($news->id);
            abort_unless((int) $news->issue_id === $issue->id, 404);
            $news->issue_id = null;
            $news->save();
            $issue->activities()->create(['user_id' => $request->user()->id, 'kind' => 'unlinked', 'note' => 'Artikel dilepaskan: '.$news->title, 'changes' => ['news_id' => $news->id]]);
        });

        return back()->with('status', 'Artikel dilepaskan dari isu.');
    }
}
