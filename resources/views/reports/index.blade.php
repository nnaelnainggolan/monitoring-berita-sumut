@extends('layouts.app')
@section('content')
<p class="eyebrow">LAPORAN EKSEKUTIF</p><h1 class="font-display text-3xl mb-3">Ringkasan harian</h1>
<p class="text-slate-500 mb-5">Laporan menyimpan snapshot berita pada tanggal terbit yang dipilih, beserta status penanganan saat laporan dibuat. Sorotan diambil dari berita terverifikasi.</p>
@can('review-news')<form class="panel flex flex-wrap gap-3 items-end mb-5" method="POST" action="{{ route('reports.store') }}">@csrf<label>Tanggal berita (WIB)<input class="field" type="date" name="date" value="{{ old('date', today()->subDay()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label><button class="btn">Buat / buka laporan</button></form>@endcan
@forelse($reports as $report)<a class="panel block mb-3" href="{{ route('reports.show', $report) }}"><strong>{{ $report->report_date->translatedFormat('d F Y') }}</strong><p class="text-sm text-slate-500 mt-2">{{ $report->data['article_count'] }} artikel · {{ $report->data['verified_count'] }} terverifikasi · Dibuat {{ $report->generated_at->format('d/m/Y H:i') }} WIB</p></a>@empty<div class="panel text-slate-500">Belum ada laporan. Analis dapat membuat laporan setelah pemeriksaan berita selesai.</div>@endforelse
{{ $reports->links() }}
@endsection
