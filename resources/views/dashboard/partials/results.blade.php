@php
    $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));
    $sentiments = ['positif' => '#438b71', 'netral' => '#98a7bc', 'negatif' => '#bb6070'];
    $chartDatasets = collect($sentiments)->map(function ($color, $sentiment) use ($days, $trend) {
        return [
            'label' => ucfirst($sentiment), 'borderColor' => $color, 'backgroundColor' => $color,
            'tension' => 0.35, 'pointRadius' => 2, 'pointHoverRadius' => 5, 'borderWidth' => 2,
            'data' => $days->map(fn ($day) => optional(($trend[$day] ?? collect())->firstWhere('sentiment', $sentiment))->jumlah ?? 0)->values(),
        ];
    })->values();
    $metrics = [
        ['key' => 'total', 'label' => 'Total berita', 'icon' => 'news', 'class' => '', 'query' => [], 'selected' => !request()->filled('sentiment') && !request()->boolean('urgent_only')],
        ['key' => 'positif', 'label' => 'Positif', 'icon' => 'check', 'class' => 'metric-positive', 'query' => ['sentiment' => 'positif'], 'selected' => request('sentiment') === 'positif'],
        ['key' => 'netral', 'label' => 'Netral', 'icon' => 'minus', 'class' => 'metric-neutral', 'query' => ['sentiment' => 'netral'], 'selected' => request('sentiment') === 'netral'],
        ['key' => 'negatif', 'label' => 'Negatif', 'icon' => 'chart', 'class' => 'metric-negative', 'query' => ['sentiment' => 'negatif'], 'selected' => request('sentiment') === 'negatif'],
        ['key' => 'urgent', 'label' => 'Mendesak', 'icon' => 'alert', 'class' => 'metric-urgent', 'query' => ['urgent_only' => 1], 'selected' => request()->boolean('urgent_only')],
    ];
    $advancedCount = collect(['from', 'to', 'category_id', 'region_id', 'agency_id', 'verification_status', 'archived'])->filter(fn ($key) => request()->filled($key))->count() + (request('sort') === 'oldest' ? 1 : 0);
@endphp

<section aria-labelledby="overview-title">
    <div class="overview-section-label">
        <h2 id="overview-title">Ringkasan pemantauan</h2>
        <p>Seluruh data · Klik kartu untuk memfilter daftar berita</p>
    </div>
    <div class="metric-grid">
        @foreach($metrics as $metric)
        <a href="{{ route('dashboard', $metric['query']) }}" class="metric-card {{ $metric['class'] }} {{ $metric['selected'] ? 'is-selected' : '' }}">
            <div class="metric-head"><span>{{ $metric['label'] }}</span><div class="metric-icon"><x-icon :name="$metric['icon']"/></div></div>
            <strong class="metric-value">{{ number_format($stats[$metric['key']], 0, ',', '.') }}</strong>
            <span class="metric-foot">{{ $metric['key'] === 'total' ? 'Berita terkumpul' : number_format($stats['total'] ? $stats[$metric['key']] / $stats['total'] * 100 : 0, 1, ',', '.').'% dari total berita' }}<x-icon name="arrow"/></span>
        </a>
        @endforeach
    </div>
</section>

<div class="insight-grid">
    <section class="insight-card" aria-labelledby="trend-title">
        <div class="insight-heading"><div><h2 id="trend-title">Tren sentimen berita</h2><p>Pergerakan sentimen selama 14 hari terakhir</p></div><span class="insight-period">14 hari</span></div>
        <div class="chart-container">
            <canvas id="trendChart" role="img" aria-label="Grafik jumlah berita positif, netral, dan negatif per hari selama 14 hari terakhir."
                    data-labels='@json($days->map(fn ($day) => \Carbon\Carbon::parse($day)->format("d/m")))'
                    data-datasets='@json($chartDatasets)'>Grafik tren sentimen. Gunakan filter tanggal dan sentimen pada daftar berita untuk melihat data.</canvas>
        </div>
    </section>
    <section class="insight-card" aria-labelledby="weekly-title">
        <div class="insight-heading"><div><h2 id="weekly-title">Aktivitas pemberitaan</h2><p>Jumlah berita minggu ini dan minggu lalu</p></div><a class="insight-link" href="{{ route('dashboard.sources') }}" data-no-ajax>Per sumber<x-icon name="arrow"/></a></div>
        <div class="chart-container">
            <canvas id="weeklyChart" role="img" aria-label="Grafik perbandingan jumlah berita harian minggu ini dan minggu lalu."
                    data-labels='@json($weeklyComparison->pluck('label'))'
                    data-this='@json($weeklyComparison->pluck('minggu_ini'))'
                    data-last='@json($weeklyComparison->pluck('minggu_lalu'))'>Grafik aktivitas pemberitaan. Gunakan filter tanggal pada daftar berita untuk melihat data.</canvas>
        </div>
    </section>
</div>

<section class="news-panel" aria-labelledby="news-title">
    <div class="news-panel-heading">
        <div><div class="news-heading-title"><h2 id="news-title" tabindex="-1">Daftar berita</h2><span class="result-count">{{ number_format($news->total(), 0, ',', '.') }} berita</span></div><p>Telusuri, periksa, dan pantau berita yang relevan.</p></div>
        <a class="btn-secondary" href="{{ route('dashboard.export', request()->except('page')) }}" data-no-ajax><x-icon name="download"/>Ekspor CSV</a>
    </div>
    <form id="filterForm" method="GET" action="{{ route('dashboard') }}" class="news-filter" aria-label="Filter berita">
        <div class="filter-main">
            <label>Cari berita<div class="search-field"><x-icon name="search"/><input class="field" type="search" name="q" value="{{ request('q') }}" placeholder="Masukkan judul berita..."></div></label>
            <label>Sentimen<select class="field" name="sentiment"><option value="">Semua sentimen</option>@foreach(['positif', 'netral', 'negatif'] as $sentiment)<option value="{{ $sentiment }}" @selected(request('sentiment') === $sentiment)>{{ ucfirst($sentiment) }}</option>@endforeach</select></label>
            <label>Sumber<select class="field" name="source"><option value="">Semua sumber</option>@foreach($sources as $source)<option value="{{ $source }}" @selected(request('source') === $source)>{{ $source }}</option>@endforeach</select></label>
            <button class="btn" type="submit"><x-icon name="filter"/>Terapkan</button>
        </div>
        <div class="filter-bottom"><label class="urgent-checkbox"><input type="checkbox" name="urgent_only" value="1" @checked(request()->boolean('urgent_only'))>Hanya berita mendesak</label><a class="filter-reset" href="{{ route('dashboard') }}"><x-icon name="refresh"/>Reset filter</a></div>
        <details class="advanced-filters" @if($advancedCount) open @endif>
            <summary><x-icon name="filter"/>Filter lanjutan @if($advancedCount)<span class="result-count">{{ $advancedCount }} aktif</span>@endif<x-icon name="chevron"/></summary>
            <div class="advanced-fields">
                <label>Dari tanggal<input class="field" type="date" name="from" value="{{ request('from') }}"></label>
                <label>Sampai tanggal<input class="field" type="date" name="to" value="{{ request('to') }}"></label>
                <label>Status verifikasi<select class="field" name="verification_status"><option value="">Semua status</option><option value="pending" @selected(request('verification_status') === 'pending')>Perlu diperiksa</option><option value="verified" @selected(request('verification_status') === 'verified')>Terverifikasi</option></select></label>
                <label>Status arsip<select class="field" name="archived"><option value="">Semua status</option><option value="1" @selected(request('archived') === '1')>Sudah diarsipkan</option><option value="0" @selected(request('archived') === '0')>Belum diarsipkan</option></select></label>
                @foreach(\App\Models\Taxonomy::KINDS as $kind => $label)
                <label>{{ $label }}<select class="field" name="{{ $kind }}_id"><option value="">Semua {{ mb_strtolower($label) }}</option>@foreach(\App\Models\Taxonomy::where('kind', $kind)->orderBy('name')->get() as $option)<option value="{{ $option->id }}" @selected((string) request($kind.'_id') === (string) $option->id)>{{ $option->name }}</option>@endforeach</select></label>
                @endforeach
                <label>Urutan berita<select class="field" name="sort"><option value="newest" @selected(request('sort', 'newest') === 'newest')>Terbaru dahulu</option><option value="oldest" @selected(request('sort') === 'oldest')>Terlama dahulu</option></select></label>
            </div>
        </details>
    </form>
    @if($news->isEmpty())
        <div class="news-empty"><x-icon name="search"/><h3>Belum ada berita yang cocok</h3><p>Coba kata kunci atau filter lain untuk menemukan berita.</p><a href="{{ route('dashboard') }}" class="btn-secondary">Tampilkan semua berita<x-icon name="arrow"/></a></div>
    @else
        <div class="table-scroll">
            <table class="news-table">
                <caption class="sr-only">Berita yang sesuai dengan filter, beserta sumber, tanggal, sentimen, dan status pemeriksaan.</caption>
                <thead><tr><th scope="col">Judul berita</th><th scope="col">Sumber</th><th scope="col">Terbit</th><th scope="col">Sentimen</th><th scope="col">Status</th>@can('archive-news')<th scope="col"><span class="sr-only">Tindakan</span></th>@endcan</tr></thead>
                <tbody>
                @foreach($news as $item)
                    <tr>
                        <td class="news-cell-title"><a class="article-title" href="{{ route('berita.show', $item->id) }}" data-no-ajax>{{ $item->title }}</a>@if($item->summary || $item->analysis)<p class="article-summary">{{ Str::limit($item->summary ?: $item->analysis, 180) }}</p>@endif</td>
                        <td data-label="Sumber"><span class="article-source">{{ $item->source ?: 'Belum tersedia' }}</span></td>
                        <td data-label="Terbit">@if($item->published_at)<time class="article-date" datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->translatedFormat('d M Y') }}<span class="article-time">{{ $item->published_at->format('H:i') }}</span></time>@else<span class="article-date">Belum tersedia</span>@endif</td>
                        <td data-label="Sentimen"><span class="sentiment-badge sentiment-{{ $item->sentiment }}">{{ ucfirst($item->sentiment ?: 'Belum dinilai') }}</span></td>
                        <td data-label="Status"><div class="article-status"><span class="review-label {{ $item->verification_status === 'verified' ? 'is-verified' : '' }}"><x-icon :name="$item->verification_status === 'verified' ? 'check' : 'clock'"/>{{ $item->verification_status === 'verified' ? 'Terverifikasi' : 'Perlu diperiksa' }}</span>@if($item->is_urgent)<span class="urgent-label">Mendesak</span>@endif @if($item->archived)<span class="archived-label">Diarsipkan</span>@endif</div></td>
                        @can('archive-news')<td class="news-cell-actions"><button type="button" class="archive-button" data-archive-toggle data-id="{{ $item->id }}" aria-label="{{ $item->archived ? 'Batal arsip' : 'Arsipkan' }}: {{ $item->title }}" title="{{ $item->archived ? 'Batal arsip' : 'Arsipkan' }}"><x-icon name="archive"/><span class="archive-mobile-label">{{ $item->archived ? 'Batal arsip' : 'Arsipkan' }}</span></button></td>@endcan
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($news->hasPages())<div class="news-pagination">{{ $news->links('dashboard.partials.pagination') }}</div>@endif
    @endif
</section>
