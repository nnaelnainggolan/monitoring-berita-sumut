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
    <div>
        <img src="{{ asset('images/hero.png') }}" class="rounded-2xl w-full h-full object-cover">
    </div>
</div>

{{-- Tombol aktifkan notifikasi & tombol jalankan pencarian berita terbaru --}}
<div class="mb-6 flex flex-wrap items-center gap-2">
    <button id="enableNotif" type="button"
            class="text-sm px-4 py-2 rounded-lg border border-[var(--navy)]/20 bg-white hover:bg-[var(--navy)]/5 transition-colors">
        🔔 Aktifkan notifikasi berita mendesak
    </button>
    <span id="notifStatus" class="text-xs text-slate-400 ml-2"></span>

    <button id="runWorkflow" type="button"
            class="text-sm px-4 py-2 rounded-lg bg-[var(--amber)] text-[var(--ink)] font-medium hover:brightness-95 transition disabled:opacity-60 disabled:cursor-not-allowed">
        🔄 Cari Berita Terbaru
    </button>
    <span id="runWorkflowStatus" class="text-xs text-slate-400"></span>
</div>

{{-- Region yang di-refresh via AJAX --}}
<div id="dashboard-results">
    @include('dashboard.partials.results')
</div>

<script>
(function () {
    let chartInstance = null;
    let weeklyChartInstance = null;

    function initChart() {
        const canvas = document.getElementById('trendChart');
        if (canvas) {
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

        const weeklyCanvas = document.getElementById('weeklyChart');
        if (weeklyCanvas) {
            if (weeklyChartInstance) { weeklyChartInstance.destroy(); }
            weeklyChartInstance = new Chart(weeklyCanvas, {
                type: 'bar',
                data: {
                    labels: JSON.parse(weeklyCanvas.dataset.labels),
                    datasets: [
                        { label: 'Minggu Ini', data: JSON.parse(weeklyCanvas.dataset.this), backgroundColor: '#7A1B24', borderRadius: 4 },
                        { label: 'Minggu Lalu', data: JSON.parse(weeklyCanvas.dataset.last), backgroundColor: '#C9A227', borderRadius: 4 }
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
        }
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

    // Toggle arsip (tabel di dashboard / tombol di halaman detail)
    initChart();
    window.__reloadDashboardResults = () => loadUrl(window.location.href, false);

    // ===== Notifikasi browser untuk berita mendesak =====
    const NOTIF_URL = @json(route('dashboard.check-urgent'));
    const notifBtn = document.getElementById('enableNotif');
    const notifStatus = document.getElementById('notifStatus');
    let lastSeenId = parseInt(localStorage.getItem('lastSeenUrgentId') || '{{ $latestId ?? 0 }}', 10);

    function updateNotifStatus() {
        if (!('Notification' in window)) {
            notifStatus.textContent = 'Browser tidak mendukung notifikasi';
            notifBtn.disabled = true;
            return;
        }
        if (Notification.permission === 'granted') {
            notifStatus.textContent = 'Notifikasi aktif';
            notifBtn.textContent = '🔔 Notifikasi aktif';
        } else if (Notification.permission === 'denied') {
            notifStatus.textContent = 'Notifikasi diblokir di pengaturan browser';
        }
    }

    notifBtn?.addEventListener('click', async function () {
        if (!('Notification' in window)) return;
        const perm = await Notification.requestPermission();
        updateNotifStatus();
        if (perm === 'granted') {
            new Notification('Notifikasi aktif', { body: 'Kamu akan diberi tahu saat ada berita mendesak baru.' });
        }
    });

    updateNotifStatus();

    // ===== Tombol "Cari Berita Terbaru" (memicu webhook n8n) =====
    const RUN_WORKFLOW_URL = @json(route('dashboard.run-workflow'));
    const runBtn = document.getElementById('runWorkflow');
    const runStatus = document.getElementById('runWorkflowStatus');
    const csrfToken = @json(csrf_token());

    runBtn?.addEventListener('click', async function () {
        runBtn.disabled = true;
        const originalLabel = runBtn.textContent;
        runBtn.textContent = '⏳ Memulai pencarian...';
        runStatus.textContent = '';

        try {
            const res = await fetch(RUN_WORKFLOW_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            if (res.ok) {
                runStatus.textContent = data.message || 'Pencarian berita sedang diproses.';
                runBtn.textContent = '✅ Sedang diproses...';

                // Coba muat ulang hasil beberapa kali selama ~3 menit,
                // karena analisis AI di n8n berjalan di background.
                let attempts = 0;
                const refreshInterval = setInterval(() => {
                    attempts++;
                    window.__reloadDashboardResults();
                    if (attempts >= 6) clearInterval(refreshInterval);
                }, 30000);

                // Aktifkan lagi tombolnya setelah 60 detik (hindari spam trigger)
                setTimeout(() => {
                    runBtn.disabled = false;
                    runBtn.textContent = originalLabel;
                }, 60000);
            } else {
                runStatus.textContent = data.message || 'Gagal memulai pencarian berita.';
                runBtn.disabled = false;
                runBtn.textContent = originalLabel;
            }
        } catch (e) {
            runStatus.textContent = 'Gagal menghubungi server.';
            runBtn.disabled = false;
            runBtn.textContent = originalLabel;
        }
    });

    async function pollUrgent() {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        try {
            const res = await fetch(`${NOTIF_URL}?since_id=${lastSeenId}`);
            const data = await res.json();
            (data.items || []).forEach(item => {
                new Notification('🚨 Berita Mendesak', {
                    body: item.title + (item.source ? ' — ' + item.source : ''),
                });
            });
            if (data.latestId) {
                lastSeenId = data.latestId;
                localStorage.setItem('lastSeenUrgentId', lastSeenId);
            }
        } catch (e) { /* diam saja kalau gagal, coba lagi di interval berikutnya */ }
    }

    setInterval(pollUrgent, 30000); // cek tiap 30 detik
})();
</script>
@endsection
