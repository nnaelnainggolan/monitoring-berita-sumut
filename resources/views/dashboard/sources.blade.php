@extends('layouts.app')

@section('content')

<a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-[var(--navy)] mb-5">
    &larr; Kembali ke Ruang Pantau
</a>

<div class="mb-6">
    <p class="font-mono text-[11px] tracking-[0.15em] text-[var(--amber)] mb-1">ANALISIS PER MEDIA</p>
    <h1 class="font-display text-2xl md:text-3xl font-semibold text-[var(--navy)]">Statistik per Sumber Berita</h1>
    <p class="text-sm text-slate-500 mt-1">Diurutkan dari sumber dengan berita negatif terbanyak.</p>
</div>

@if($sourceStats->isEmpty())
    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-14 text-center">
        <p class="font-display text-lg text-[var(--navy)] mb-1">Belum ada data</p>
        <p class="text-sm text-slate-500">Statistik akan muncul setelah ada berita yang tersimpan.</p>
    </div>
@else
<div class="bg-white rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border border-slate-200/70 overflow-hidden mb-6">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-[11px] font-mono uppercase tracking-wide border-b border-slate-200">
            <tr>
                <th class="p-3">Sumber</th>
                <th class="p-3 text-center">Total</th>
                <th class="p-3 text-center">Positif</th>
                <th class="p-3 text-center">Netral</th>
                <th class="p-3 text-center">Negatif</th>
                <th class="p-3">Proporsi Sentimen</th>
            </tr>
        </thead>
        <tbody>
        @foreach($sourceStats as $src)
            <tr class="border-t border-slate-100">
                <td class="p-3 font-medium text-[var(--navy)] whitespace-nowrap">{{ $src->source }}</td>
                <td class="p-3 text-center font-mono">{{ $src->total }}</td>
                <td class="p-3 text-center font-mono text-emerald-600">{{ $src->positif }}</td>
                <td class="p-3 text-center font-mono text-slate-500">{{ $src->netral }}</td>
                <td class="p-3 text-center font-mono text-red-600">{{ $src->negatif }}</td>
                <td class="p-3">
                    <div class="flex h-3 w-full rounded-full overflow-hidden bg-slate-100 min-w-[120px]">
                        @php $t = max($src->total, 1); @endphp
                        <div style="width: {{ $src->positif / $t * 100 }}%" class="bg-emerald-500"></div>
                        <div style="width: {{ $src->netral / $t * 100 }}%" class="bg-slate-400"></div>
                        <div style="width: {{ $src->negatif / $t * 100 }}%" class="bg-red-500"></div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="bg-white rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border border-slate-200/70 p-5">
    <p class="font-display text-base font-semibold text-[var(--navy)] mb-3">Perbandingan Antar Sumber</p>
    <canvas id="sourceChart" height="90"
            data-labels='@json($sourceStats->pluck('source'))'
            data-positif='@json($sourceStats->pluck('positif'))'
            data-netral='@json($sourceStats->pluck('netral'))'
            data-negatif='@json($sourceStats->pluck('negatif'))'></canvas>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
if (typeof Chart === 'undefined') return;
new Chart(document.getElementById('sourceChart'), {
    type: 'bar',
    data: {
        labels: JSON.parse(document.getElementById('sourceChart').dataset.labels),
        datasets: [
            { label: 'Positif', data: JSON.parse(document.getElementById('sourceChart').dataset.positif), backgroundColor: '#16A34A', borderRadius: 4 },
            { label: 'Netral', data: JSON.parse(document.getElementById('sourceChart').dataset.netral), backgroundColor: '#94A3B8', borderRadius: 4 },
            { label: 'Negatif', data: JSON.parse(document.getElementById('sourceChart').dataset.negatif), backgroundColor: '#DC2626', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true } } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#F0E9DC' } },
            x: { grid: { display: false } }
        }
    }
});
}, { once: true });
</script>
@endif

@endsection
