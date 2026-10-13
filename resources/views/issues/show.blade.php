@extends('layouts.app')
@section('content')
<a class="text-sm" href="{{ route('issues.index') }}">← Daftar isu</a>
<div class="panel mt-4 mb-5"><p class="eyebrow">ISU #{{ $issue->id }} · {{ \App\Models\Issue::STATUSES[$issue->status] }}</p>
<h1 class="font-display text-3xl mb-3">{{ $issue->title }}</h1><p class="whitespace-pre-wrap text-slate-700">{{ $issue->summary }}</p>
<div class="grid md:grid-cols-2 gap-3 text-sm text-slate-500 mt-5"><p>Prioritas: {{ \App\Models\Issue::PRIORITIES[$issue->priority] }}</p><p>Penanggung jawab: {{ $issue->assignee?->name ?? 'Belum ditugaskan' }}</p><p>Wilayah: {{ $issue->region?->name ?? '—' }} · OPD: {{ $issue->agency?->name ?? '—' }}</p><p>Tenggat: {{ $issue->due_at?->format('d/m/Y H:i') ?? 'Belum ditentukan' }} @if($issue->due_at) WIB @endif @if($issue->isOverdue()) <strong class="text-red-700">Terlambat</strong> @endif</p></div>
</div>
@can('update', $issue)
<details class="panel mb-5" @if($errors->any()) open @endif><summary class="cursor-pointer font-semibold">Perbarui penanganan</summary>
<form method="POST" action="{{ route('issues.update', $issue) }}" class="grid md:grid-cols-2 gap-4 mt-5">@csrf @method('PUT')
<input type="hidden" name="version" value="{{ old('version', $issue->version) }}">
@can('manage', $issue) @include('issues.fields') @endcan
<label>Status<select class="field" name="status">@foreach(\App\Models\Issue::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('status', $issue->status) === $key)>{{ $label }}</option>@endforeach</select></label>
<p class="text-xs text-slate-500 self-center">Urutan: Baru → Diverifikasi → Ditindaklanjuti → Selesai. Analis dapat membuka kembali isu selesai.</p>
<label class="md:col-span-2">Catatan perkembangan<textarea class="field" name="note" rows="3" required>{{ old('note') }}</textarea></label><button class="btn md:col-span-2">Simpan perkembangan</button>
</form></details>
@endcan
<div class="panel mb-5"><h2 class="font-display text-xl mb-3">Berita terkait ({{ $issue->news->count() }})</h2>
@forelse($issue->news as $news)<a class="block border-t py-3" href="{{ route('berita.show', $news) }}">{{ $news->title }}<span class="block text-xs text-slate-500 mt-1">{{ $news->source }} · {{ $news->published_at?->format('d/m/Y') }} · {{ $news->verification_status === 'verified' ? 'Terverifikasi' : 'Perlu diperiksa' }}</span></a>@can('manage', $issue)<form method="POST" action="{{ route('issues.articles.destroy', [$issue, $news]) }}" class="mb-3">@csrf @method('DELETE')<button class="text-xs text-red-700">Lepaskan dari isu</button></form>@endcan @empty<p class="text-sm text-slate-500">Belum ada artikel yang dihubungkan.</p>@endforelse
</div>
@can('manage', $issue)
<div class="panel mb-5"><h2 class="font-display text-xl mb-2">Saran artikel terkait</h2><p class="text-xs text-slate-500 mb-4">Kemiripan judul dalam rentang 7 hari dari artikel acuan, dengan kecocokan wilayah bila tersedia. Nilai kemiripan bukan tingkat kebenaran; periksa sumber sebelum menghubungkan.</p>
@forelse($suggestions as $suggestion)
<div class="border-t py-3"><a href="{{ route('berita.show', $suggestion['news']) }}" class="font-medium">{{ $suggestion['news']->title }}</a><p class="text-xs text-slate-500 mt-1">Kemiripan kata: {{ $suggestion['score'] }}% · {{ $suggestion['news']->source }}</p><form method="POST" action="{{ route('issues.articles.store', $issue) }}" class="mt-2">@csrf<input type="hidden" name="news_id" value="{{ $suggestion['news']->id }}"><button class="btn-secondary">Konfirmasi terkait isu ini</button></form></div>
@empty<p class="text-sm text-slate-500">Belum ditemukan kandidat yang cukup mirip.</p>@endforelse
<details class="mt-4"><summary class="cursor-pointer text-sm">Hubungkan artikel lain</summary><form method="POST" action="{{ route('issues.articles.store', $issue) }}" class="flex gap-3 items-end mt-3">@csrf<label class="flex-1">ID berita (terlihat pada halaman detail berita)<input class="field" type="number" min="1" name="news_id" required></label><button class="btn">Hubungkan</button></form></details>
</div>@endcan
<div class="panel"><h2 class="font-display text-xl mb-3">Riwayat & catatan</h2>
@can('update', $issue)<form method="POST" action="{{ route('issues.comment', $issue) }}" class="mb-5">@csrf<label>Tambah catatan<textarea class="field" name="note" rows="2" required maxlength="5000"></textarea></label><button class="btn mt-3">Kirim catatan</button></form>@endcan
@foreach($issue->activities as $activity)<div class="border-t py-4"><p class="text-xs text-slate-500">{{ $activity->user->name }} · {{ $activity->created_at->format('d/m/Y H:i') }} WIB</p><p class="mt-1 whitespace-pre-wrap">{{ $activity->note }}</p>@if($activity->changes)<details class="mt-2 text-xs"><summary class="cursor-pointer">Detail perubahan</summary><pre class="whitespace-pre-wrap mt-2">{{ json_encode($activity->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>@endif</div>@endforeach
</div>
@endsection
