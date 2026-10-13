@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-6 gap-4"><div><p class="eyebrow">TINDAK LANJUT</p><h1 class="font-display text-3xl">Isu & penugasan</h1></div>@can('create', \App\Models\Issue::class)<a class="btn" href="{{ route('issues.create') }}">+ Buat isu</a>@endcan</div>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">@foreach(\App\Models\Issue::STATUSES as $key => $label)<a class="panel" href="{{ route('issues.index', ['status' => $key]) }}"><p class="text-sm text-slate-500">{{ $label }}</p><p class="text-3xl font-display">{{ $counts[$key] ?? 0 }}</p></a>@endforeach</div>
<form class="panel flex flex-wrap items-end gap-3 mb-5" method="GET">
<label class="flex-1">Cari isu<input class="field" name="q" value="{{ request('q') }}" placeholder="Judul isu..."></label>
<label>Status<select class="field" name="status"><option value="">Semua</option>@foreach(\App\Models\Issue::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></label>
<label>Prioritas<select class="field" name="priority"><option value="">Semua</option>@foreach(\App\Models\Issue::PRIORITIES as $key => $label)<option value="{{ $key }}" @selected(request('priority') === $key)>{{ $label }}</option>@endforeach</select></label>
<label><input type="checkbox" name="mine" value="1" @checked(request('mine'))> Tugas saya</label><label><input type="checkbox" name="overdue" value="1" @checked(request('overdue'))> Terlambat</label><button class="btn">Terapkan</button><a class="btn-secondary" href="{{ route('issues.index') }}">Reset</a>
</form>
@forelse($issues as $issue)
<a class="panel block mb-3 hover:border-amber-500" href="{{ route('issues.show', $issue) }}"><div class="flex justify-between gap-3"><h2 class="font-semibold text-lg">{{ $issue->title }}</h2><span class="text-sm">{{ \App\Models\Issue::PRIORITIES[$issue->priority] }}</span></div>
<p class="text-sm text-slate-500 mt-2">{{ \App\Models\Issue::STATUSES[$issue->status] }} · {{ $issue->assignee?->name ?? 'Belum ditugaskan' }} · {{ $issue->news_count }} artikel · {{ $issue->region?->name ?? 'Wilayah belum ditentukan' }}</p>
@if($issue->due_at)<p class="text-xs mt-2 {{ $issue->isOverdue() ? 'text-red-700' : 'text-slate-500' }}">{{ $issue->isOverdue() ? 'Terlambat · ' : 'Tenggat · ' }}{{ $issue->due_at->format('d/m/Y H:i') }} WIB</p>@endif</a>
@empty<div class="panel text-center text-slate-500">Belum ada isu yang cocok.</div>@endforelse
{{ $issues->links() }}
@endsection
