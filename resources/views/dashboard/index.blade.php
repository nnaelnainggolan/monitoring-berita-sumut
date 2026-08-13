@extends('layouts.app')

@section('content')

{{-- Hero: masthead + banner image slot --}}
<div class="grid md:grid-cols-[1.3fr_1fr] gap-5 mb-8 items-stretch">
    <div class="relative bg-[var(--navy)] text-white rounded-2xl p-7 flex flex-col justify-between overflow-hidden">

        {{-- Mini kalender bulan berjalan, pojok kanan atas --}}
        @php
            $today = now();
            $firstDay = $today->copy()->startOfMonth();
            $startOffset = $firstDay->dayOfWeekIso % 7; // 0 = Minggu
            $daysInMonth = $today->daysInMonth;
            $cells = collect(range(1, $startOffset))->map(fn() => null)
                ->concat(range(1, $daysInMonth));
        @endphp
        <div class="hidden sm:block absolute top-4 right-4 w-[190px] bg-white/10 rounded-lg p-3 font-mono">
            <p class="text-[11px] text-center text-[var(--amber)] mb-2 tracking-wide">{{ $today->translatedFormat('F Y') }}</p>
            <div class="grid grid-cols-7 gap-y-1 text-center text-[10px] text-slate-300">
                @foreach(['M','S','S','R','K','J','S'] as $d)
                    <span class="text-[9px] text-slate-400">{{ $d }}</span>
                @endforeach
                @foreach($cells as $day)
                    @if(is_null($day))
                        <span></span>
                    @elseif($day === $today->day)
                        <span class="bg-[var(--amber)] text-[var(--ink)] rounded-full w-6 h-6 flex items-center justify-center mx-auto font-semibold">{{ $day }}</span>
                    @else
                        <span class="text-slate-300">{{ $day }}</span>
                    @endif
                @endforeach
            </div>
        </div>

        <div>
            <p class="font-mono text-[11px] tracking-[0.15em] text-[var(--amber)] mb-2">EDISI HARI INI &middot; {{ now()->translatedFormat('d F Y') }}</p>
            <h1 class="font-display text-3xl md:text-[34px] font-semibold leading-[1.15] max-w-[70%] sm:max-w-[55%]">
                Ruang Pantau Berita<br>Pemerintah Provinsi<br>Sumatera Utara
            </h1>
        </div>
        <p class="text-sm text-slate-300 mt-6 max-w-md">
            Setiap berita dikumpulkan, dinilai sentimennya oleh AI, lalu ditandai otomatis bila memerlukan perhatian segera.
        </p>
    </div>

    {{-- Banner image slot — ganti dengan foto Kantor Gubernur / peta Sumut / dokumentasi kegiatan --}}
    <div class="rounded-2xl overflow-hidden">
        <img src="{{ asset('images/hero.jpeg') }}" class="rounded-2xl w-full h-full object-cover">
    </div>
</div>

{{-- Region yang di-refresh via AJAX --}}
<div id="dashboard-results">
    @include('dashboard.partials.results')
</div>

<script>
(function () {
    let chartInstance = null;

    function initChart() {
        const canvas = document.getElementById('trendChart');
        if (!canvas) return;
        if (chartInstance) { chartInstance.destroy(); }
        chartInstance = new Chart(canvas, {
            type: 'line',
            data: {
                labels: JSON.parse(canvas.dataset.labels),
                datasets: JSON.parse(canvas.dataset.datasets)
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
    }

    const results = document.getElementById('dashboard-results');

    async function loadUrl(url, push = true) {
        results.style.opacity = 0.5;
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            results.innerHTML = html;
            initChart();
            if (push) history.pushState({ dashboardAjax: true }, '', url);
        } catch (e) {
            window.location.href = url; // fallback kalau fetch gagal
        } finally {
            results.style.opacity = 1;
        }
    }

    // Intercept submit form filter
    document.addEventListener('submit', function (e) {
        if (e.target.id !== 'filterForm') return;
        e.preventDefault();
        const params = new URLSearchParams(new FormData(e.target));
        loadUrl(e.target.action + '?' + params.toString());
    });

    // Intercept klik link di dalam region hasil (stat card, reset, pagination) — kecuali data-no-ajax
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link || !results.contains(link)) return;
        if (link.hasAttribute('data-no-ajax')) return;
        if (link.target === '_blank') return;
        e.preventDefault();
        loadUrl(link.href);
    });

    // Dukung tombol back/forward browser
    window.addEventListener('popstate', function () {
        loadUrl(window.location.href, false);
    });

    initChart();
})();
</script>
@endsection