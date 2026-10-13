@extends('layouts.app')

@section('content')
<div class="dashboard-heading">
    <div><h1>Ruang pantau berita</h1><p>Pantau pemberitaan, pahami sentimen, dan tentukan tindak lanjut.</p></div>
    <div class="dashboard-actions">
        <button id="enableNotif" type="button" class="btn-secondary" aria-label="Aktifkan notifikasi berita mendesak"><x-icon name="bell"/><span data-button-label>Notifikasi</span></button>
        @can('review-news')<button id="runWorkflow" type="button" class="btn"><x-icon name="refresh"/><span data-button-label>Cari berita terbaru</span></button>@endcan
    </div>
</div>
<div class="action-feedback" role="status"><span id="notifStatus"></span>@can('review-news')<span id="runWorkflowStatus"></span>@endcan</div>
<section class="overview-banner" aria-label="Pemantauan Sumatera Utara">
    <img class="banner-photo" src="{{ asset('images/hero.png') }}" alt="" fetchpriority="high">
    <div class="banner-content"><span class="banner-eyebrow"><x-icon name="pin"/>Pemerintah Provinsi Sumatera Utara</span><h2>Informasi terkini, keputusan lebih terarah.</h2><p>Satu ruang untuk memantau berita dan isu yang membutuhkan perhatian.</p></div>
    <span class="banner-caption">Sumatera Utara</span>
</section>
@if($tickerItems->isNotEmpty())
<div class="news-wire" aria-label="Sorotan berita terbaru">
    <span class="news-wire-label">Sorotan</span>
    <div class="news-wire-window"><div class="ticker-track"><div class="ticker-group">@foreach($tickerItems as $headline)<span>{{ $headline }}</span>@endforeach</div><div class="ticker-group" aria-hidden="true">@foreach($tickerItems as $headline)<span>{{ $headline }}</span>@endforeach</div></div></div>
    <button type="button" class="ticker-toggle" aria-label="Jeda sorotan berita" aria-pressed="false">Jeda</button>
</div>
@endif
<div id="dashboard-results">@include('dashboard.partials.results')</div>
<p id="results-status" class="sr-only" role="status"></p>

<script>
(function () {
    let chartInstance = null;
    let weeklyChartInstance = null;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    function destroyCharts() {
        chartInstance?.destroy();
        weeklyChartInstance?.destroy();
        chartInstance = weeklyChartInstance = null;
    }
    function chartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion.matches ? false : { duration: 400 },
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'bottom', align: 'start', labels: { color: '#65748a', boxWidth: 7, boxHeight: 7, usePointStyle: true, padding: 20, font: { size: 10, family: 'Inter Variable, sans-serif' } } },
                tooltip: { backgroundColor: '#253247', padding: 11, cornerRadius: 7, titleFont: { size: 11 }, bodyFont: { size: 11 } }
            },
            scales: {
                y: { beginAtZero: true, border: { display: false }, ticks: { precision: 0, maxTicksLimit: 5, color: '#8794a6', padding: 8, font: { size: 10 } }, grid: { color: '#edf0f5', drawTicks: false } },
                x: { border: { display: false }, ticks: { color: '#8794a6', maxRotation: 0, maxTicksLimit: 7, padding: 9, font: { size: 10 } }, grid: { display: false } }
            }
        };
    }
    function initChart() {
        if (typeof Chart === 'undefined') return;
        destroyCharts();
        const canvas = document.getElementById('trendChart');
        if (canvas) chartInstance = new Chart(canvas, {
            type: 'line',
            data: { labels: JSON.parse(canvas.dataset.labels), datasets: JSON.parse(canvas.dataset.datasets) },
            options: chartOptions()
        });
        const weeklyCanvas = document.getElementById('weeklyChart');
        if (weeklyCanvas) weeklyChartInstance = new Chart(weeklyCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(weeklyCanvas.dataset.labels),
                datasets: [
                    { label: 'Minggu ini', data: JSON.parse(weeklyCanvas.dataset.this), backgroundColor: '#98485d', borderRadius: 4, maxBarThickness: 14 },
                    { label: 'Minggu lalu', data: JSON.parse(weeklyCanvas.dataset.last), backgroundColor: '#dfc49d', borderRadius: 4, maxBarThickness: 14 }
                ]
            },
            options: chartOptions()
        });
    }

    const tickerToggle = document.querySelector('.ticker-toggle');
    tickerToggle?.addEventListener('click', () => {
        const paused = tickerToggle.getAttribute('aria-pressed') !== 'true';
        tickerToggle.setAttribute('aria-pressed', String(paused));
        tickerToggle.setAttribute('aria-label', paused ? 'Lanjutkan sorotan berita' : 'Jeda sorotan berita');
        tickerToggle.textContent = paused ? 'Lanjut' : 'Jeda';
        document.querySelector('.ticker-track').style.animationPlayState = paused ? 'paused' : '';
    });

    const results = document.getElementById('dashboard-results');
    let activeRequest;
    async function loadUrl(url, push = true, focusTarget = null) {
        activeRequest?.abort();
        const request = activeRequest = new AbortController();
        const advancedOpen = results.querySelector('.advanced-filters')?.open;
        results.style.opacity = '0.55';
        results.setAttribute('aria-busy', 'true');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: request.signal });
            if (res.redirected) { window.location.href = res.url; return; }
            if (!res.ok) { window.location.href = url; return; }
            const html = await res.text();
            if (request.signal.aborted) return;
            destroyCharts();
            results.innerHTML = html;
            if (advancedOpen && focusTarget === '#filterForm button[type="submit"]') results.querySelector('.advanced-filters').open = true;
            initChart();
            if (push) history.pushState({ dashboardAjax: true }, '', url);
            if (focusTarget) results.querySelector(focusTarget)?.focus({ preventScroll: true });
            if (focusTarget === '#news-title') results.querySelector('.news-panel').scrollIntoView({ block: 'start' });
            document.getElementById('results-status').textContent = results.querySelector('.result-count')?.textContent.trim() + ' ditampilkan sesuai filter.';
        } catch (error) {
            if (error.name !== 'AbortError') window.location.href = url;
        } finally {
            if (activeRequest === request) {
                results.style.opacity = '1';
                results.removeAttribute('aria-busy');
            }
        }
    }
    document.addEventListener('submit', function (event) {
        if (event.target.id !== 'filterForm') return;
        event.preventDefault();
        const params = new URLSearchParams(new FormData(event.target));
        for (const [key, value] of [...params]) if (!value) params.delete(key);
        loadUrl(event.target.action + '?' + params.toString(), true, '#filterForm button[type="submit"]');
    });
    document.addEventListener('click', function (event) {
        const link = event.target.closest('a');
        if (!link || !results.contains(link) || link.hasAttribute('data-no-ajax') || link.target === '_blank') return;
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (new URL(link.href).origin !== window.location.origin) return;
        event.preventDefault();
        loadUrl(link.href, true, link.closest('.news-pagination') ? '#news-title' : null);
    });
    window.addEventListener('popstate', () => loadUrl(window.location.href, false));
    initChart();
    document.addEventListener('DOMContentLoaded', initChart, { once: true });
    window.__reloadDashboardResults = () => loadUrl(window.location.href, false);

    const NOTIF_URL = @json(route('dashboard.check-urgent'));
    const notifBtn = document.getElementById('enableNotif');
    const notifStatus = document.getElementById('notifStatus');
    let lastSeenId = {{ $latestId ?? 0 }};
    try { lastSeenId = Number(localStorage.getItem('lastSeenUrgentId')) || lastSeenId; } catch (_) {}
    function updateNotifStatus() {
        if (!('Notification' in window)) {
            notifStatus.textContent = 'Browser ini tidak mendukung notifikasi.';
            notifBtn.disabled = true;
            return;
        }
        if (Notification.permission === 'granted') {
            notifBtn.querySelector('[data-button-label]').textContent = 'Notifikasi aktif';
            notifBtn.setAttribute('aria-label', 'Notifikasi berita mendesak aktif');
        } else if (Notification.permission === 'denied') {
            notifStatus.textContent = 'Izinkan notifikasi melalui pengaturan browser.';
        }
    }
    notifBtn.addEventListener('click', async function () {
        if (!('Notification' in window)) return;
        try {
            const permission = await Notification.requestPermission();
            updateNotifStatus();
            if (permission === 'granted') new Notification('Notifikasi aktif', { body: 'Anda akan diberi tahu saat ada berita mendesak baru.' });
        } catch (_) { notifStatus.textContent = 'Notifikasi belum dapat diaktifkan pada browser ini.'; }
    });
    updateNotifStatus();

    const runBtn = document.getElementById('runWorkflow');
    const runStatus = document.getElementById('runWorkflowStatus');
    runBtn?.addEventListener('click', async function () {
        const label = runBtn.querySelector('[data-button-label]');
        const originalLabel = label.textContent;
        runBtn.disabled = true;
        label.textContent = 'Memulai pencarian...';
        runStatus.textContent = '';
        const restoreButton = () => { runBtn.disabled = false; label.textContent = originalLabel; };
        try {
            const res = await fetch(@json(route('dashboard.run-workflow')), {
                method: 'POST', headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok) {
                runStatus.textContent = data.message || 'Pencarian berita sedang diproses.';
                label.textContent = 'Sedang diproses...';
                let attempts = 0;
                const refreshInterval = setInterval(() => {
                    window.__reloadDashboardResults();
                    if (++attempts >= 6) clearInterval(refreshInterval);
                }, 30000);
                setTimeout(restoreButton, 60000);
            } else {
                runStatus.textContent = data.message || 'Gagal memulai pencarian berita.';
                restoreButton();
            }
        } catch (_) { runStatus.textContent = 'Gagal menghubungi server.'; restoreButton(); }
    });
    async function pollUrgent() {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        try {
            const res = await fetch(`${NOTIF_URL}?since_id=${lastSeenId}`);
            if (!res.ok) return;
            const data = await res.json();
            (data.items || []).forEach(item => new Notification('Berita mendesak', { body: item.title + (item.source ? ' — ' + item.source : '') }));
            if (data.latestId) {
                lastSeenId = data.latestId;
                try { localStorage.setItem('lastSeenUrgentId', lastSeenId); } catch (_) {}
            }
        } catch (_) { /* Coba lagi pada pemeriksaan berikutnya. */ }
    }
    setInterval(pollUrgent, 30000);
})();
</script>
@endsection
