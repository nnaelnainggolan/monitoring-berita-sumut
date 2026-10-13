@extends('layouts.app')

@section('content')

<a href="{{ url()->previous() }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-[var(--navy)] mb-5">
    &larr; Kembali ke Ruang Pantau
</a>

<div class="bg-white rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border border-slate-200/70 p-7">
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span @class([
            'px-2.5 py-1 rounded-full text-xs font-medium',
            'bg-emerald-100 text-emerald-700' => $item->sentiment === 'positif',
            'bg-slate-100 text-slate-600'      => $item->sentiment === 'netral',
            'bg-red-100 text-red-700'          => $item->sentiment === 'negatif',
        ])>{{ ucfirst($item->sentiment) }}</span>
        @if($item->is_urgent)
            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Mendesak</span>
        @endif
        @if($item->archived)
            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Diarsipkan</span>
        @endif
        <span class="text-xs text-slate-400 font-mono ml-auto">{{ optional($item->published_at)->translatedFormat('d F Y, H:i') }}</span>
    </div>

    <h1 class="font-display text-2xl md:text-3xl font-semibold text-[var(--navy)] leading-snug mb-2">
        {{ $item->title }}
    </h1>
    <p class="text-sm text-slate-500 font-mono mb-6">{{ $item->source }}</p>

    @if($item->summary)
        <div class="bg-[var(--navy)]/5 border border-[var(--navy)]/10 rounded-xl p-5 mb-6">
            <p class="text-xs font-mono uppercase tracking-wide text-[var(--navy)]/70 mb-2">Ringkasan</p>
            <p class="text-slate-700 leading-relaxed">{{ $item->summary }}</p>
        </div>
    @endif

    @if($item->analysis)
        <div class="mb-6">
            <p class="text-xs font-mono uppercase tracking-wide text-slate-400 mb-2">Analisis AI</p>
            <p class="text-slate-700 leading-relaxed">{{ $item->analysis }}</p>
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ $item->link }}" target="_blank" data-no-ajax
           class="inline-flex items-center gap-2 bg-[var(--navy)] text-white text-sm px-5 py-2.5 rounded-lg hover:bg-[var(--navy-light)] transition-colors">
            Baca sumber asli &rarr;
        </a>
        @can('archive-news')
        <button type="button" data-archive-toggle data-id="{{ $item->id }}" data-redirect="{{ route('berita.show', $item->id) }}"
                class="inline-flex items-center gap-2 border border-slate-300 text-slate-600 text-sm px-5 py-2.5 rounded-lg hover:bg-slate-50 transition-colors">
            {{ $item->archived ? '↩ Batalkan arsip' : '🗄 Arsipkan berita ini' }}
        </button>
        @endcan
        @if($item->issue_id)<a class="btn-secondary" href="{{ route('issues.show', $item->issue_id) }}">Lihat isu terkait</a>
        @else @can('create', \App\Models\Issue::class)<a class="btn-secondary" href="{{ route('issues.create', ['news_id' => $item->id]) }}">Buat isu dari berita</a>@endcan @endif
        @can('review-news')<a class="btn" href="{{ route('news.review', $item) }}">Periksa analisis</a>@endcan
    </div>
</div>

<div class="panel mt-5"><h2 class="font-display text-xl mb-3">Klasifikasi & verifikasi</h2>
<p class="text-sm">Kategori: {{ $item->category?->name ?? 'Belum ditentukan' }} · Wilayah: {{ $item->region?->name ?? 'Belum ditentukan' }} · OPD: {{ $item->agency?->name ?? 'Belum ditentukan' }}</p>
<p class="text-sm mt-2">{{ $item->verification_status === 'verified' ? 'Terverifikasi' : 'Perlu diperiksa' }}@if($item->verified_at) oleh {{ $item->verifier?->name }} · {{ $item->verified_at->format('d/m/Y H:i') }} WIB @endif</p>
</div>
<div class="panel mt-5"><h2 class="font-display text-xl mb-3">Riwayat pemeriksaan</h2>
@forelse($item->reviews as $review)
<details class="border-t py-3"><summary class="cursor-pointer">{{ $review->user->name }} · {{ $review->created_at->format('d/m/Y H:i') }} · {{ $review->note }}</summary>
<div class="overflow-x-auto mt-3"><table class="w-full text-sm"><thead><tr><th class="text-left">Kolom</th><th class="text-left">Sebelum</th><th class="text-left">Sesudah</th></tr></thead><tbody>
@foreach($review->after as $field => $value)<tr class="border-t"><td class="p-2">{{ $field }}</td><td class="p-2 whitespace-pre-wrap">{{ is_bool($review->before[$field] ?? null) ? (($review->before[$field] ?? false) ? 'Ya' : 'Tidak') : ($review->before[$field] ?? '—') }}</td><td class="p-2 whitespace-pre-wrap">{{ is_bool($value) ? ($value ? 'Ya' : 'Tidak') : ($value ?? '—') }}</td></tr>@endforeach
</tbody></table></div></details>
@empty<p class="text-sm text-slate-500">Belum ada pemeriksaan oleh analis.</p>@endforelse
</div>
@endsection
