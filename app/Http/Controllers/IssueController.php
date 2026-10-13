<?php
namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\NewsItem;
use App\Models\Taxonomy;
use App\Models\User;
use App\Services\IssueNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IssueController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(array_keys(Issue::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(Issue::PRIORITIES))], 'q' => 'nullable|string|max:255']);
        $query = Issue::with(['assignee', 'region'])->withCount('news')->latest();
        foreach (['status', 'priority'] as $field) if (! empty($filters[$field])) $query->where($field, $filters[$field]);
        if (! empty($filters['q'])) $query->where('title', 'like', '%'.$filters['q'].'%');
        if ($request->boolean('mine')) $query->where('assigned_to', $request->user()->id);
        if ($request->boolean('overdue')) $query->where('status', '!=', 'resolved')->where('due_at', '<', now());
        return view('issues.index', ['issues' => $query->paginate(15)->withQueryString(),
            'counts' => Issue::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Issue::class);
        $request->validate(['news_id' => 'nullable|integer|exists:news_items,id']);
        $news = $request->filled('news_id') ? NewsItem::findOrFail($request->integer('news_id')) : null;
        return view('issues.create', $this->options() + ['news' => $news]);
    }

    public function store(Request $request, IssueNotifier $notifier)
    {
        Gate::authorize('create', Issue::class);
        $data = $request->validate($this->rules() + ['news_id' => 'nullable|integer|exists:news_items,id']);
        $issue = DB::transaction(function () use ($data, $request, $notifier) {
            $issue = Issue::create(collect($data)->except('news_id')->all() + ['created_by' => $request->user()->id]);
            if (! empty($data['news_id'])) {
                $news = NewsItem::lockForUpdate()->findOrFail($data['news_id']);
                abort_if($news->issue_id, 409, 'Berita sudah terhubung dengan isu lain.');
                $news->issue_id = $issue->id;
                $news->save();
            }
            $issue->activities()->create(['user_id' => $request->user()->id, 'kind' => 'created', 'note' => 'Isu dibuat.', 'changes' => $issue->only(array_keys($this->rules()))]);
            $notifier->send($issue, 'Isu baru: '.$issue->title, $request->user()->id, true);
            return $issue;
        });
        return redirect()->route('issues.show', $issue)->with('status', 'Isu berhasil dibuat.');
    }

    public function show(Issue $issue)
    {
        return view('issues.show', $this->options() + ['issue' => $issue->load(['assignee', 'creator', 'category', 'region', 'agency', 'news', 'activities.user'])]);
    }

    public function update(Request $request, Issue $issue, IssueNotifier $notifier)
    {
        Gate::authorize('update', $issue);
        $manage = $request->user()->can('manage', $issue);
        $rules = ['status' => ['required', Rule::in(array_keys(Issue::STATUSES))], 'version' => 'required|integer|min:0', 'note' => 'required|string|max:5000'];
        $data = $request->validate($manage ? $rules + $this->rules() : $rules);
        DB::transaction(function () use ($issue, $data, $request, $manage, $notifier) {
            $issue = Issue::lockForUpdate()->findOrFail($issue->id);
            Gate::authorize('update', $issue);
            abort_if($issue->version !== (int) $data['version'], 409, 'Isu telah diperbarui. Muat ulang sebelum menyimpan.');
            $next = $data['status'];
            $allowed = ['new' => ['verified'], 'verified' => ['in_progress'], 'in_progress' => ['resolved'], 'resolved' => ['in_progress']];
            if ($next !== $issue->status && (! in_array($next, $allowed[$issue->status], true)
                || (! $manage && ($next === 'verified' || $issue->status === 'resolved')))) {
                throw ValidationException::withMessages(['status' => 'Perubahan status tidak diizinkan. Ikuti urutan pemeriksaan dan tindak lanjut.']);
            }
            $fields = array_merge(array_keys($this->rules()), ['status']);
            $before = $issue->only($fields);
            $issue->fill(collect($data)->except(['version', 'note'])->all());
            if ($next === 'in_progress' && ! $issue->assigned_to) {
                throw ValidationException::withMessages(['assigned_to' => 'Tentukan penanggung jawab sebelum menindaklanjuti isu.']);
            }
            if ($next !== $before['status']) $issue->resolved_at = $next === 'resolved' ? now() : null;
            $issue->version++;
            $issue->save();
            $issue->activities()->create(['user_id' => $request->user()->id, 'kind' => 'updated', 'note' => $data['note'], 'changes' => ['before' => $before, 'after' => $issue->only($fields)]]);
            $notifier->send($issue, $data['note'].' · Status: '.Issue::STATUSES[$issue->status], $request->user()->id);
        });
        return back()->with('status', 'Perkembangan isu disimpan.');
    }

    public function comment(Request $request, Issue $issue, IssueNotifier $notifier)
    {
        Gate::authorize('update', $issue);
        $data = $request->validate(['note' => 'required|string|max:5000']);
        $issue->activities()->create(['user_id' => $request->user()->id, 'kind' => 'comment', 'note' => $data['note']]);
        $notifier->send($issue, $data['note'], $request->user()->id);
        return back()->with('status', 'Catatan ditambahkan.');
    }

    private function rules(): array
    {
        $rules = ['title' => 'required|string|max:255', 'summary' => 'required|string|max:10000',
            'priority' => ['required', Rule::in(array_keys(Issue::PRIORITIES))], 'due_at' => 'nullable|date',
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)->whereIn('role', ['officer', 'analyst', 'admin'])]];
        foreach (Taxonomy::KINDS as $kind => $label) $rules[$kind.'_id'] = ['nullable', 'integer', Rule::exists('taxonomies', 'id')->where('kind', $kind)];
        return $rules;
    }

    private function options(): array
    {
        return ['assignees' => User::where('is_active', true)->whereIn('role', ['admin', 'analyst', 'officer'])->orderBy('name')->get(),
            'taxonomies' => Taxonomy::orderBy('name')->get()->groupBy('kind')];
    }
}
