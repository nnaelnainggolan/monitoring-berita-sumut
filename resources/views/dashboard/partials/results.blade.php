@php
    $days = collect(range(13, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));
    $sentiments = ['positif' => '#16A34A', 'netral' => '#94A3B8', 'negatif' => '#DC2626'];
    $chartDatasets = collect($sentiments)->map(function ($color, $s) use ($days, $trend) {
        return [
            'label' => ucfirst($s),
            'borderColor' => $color,
            'backgroundColor' => $color,
            'tension' => 0.35,
            'pointRadius' => 2,
            'data' => $days->map(fn($d) => optional(($trend[$d] ?? collect())->firstWhere('sentiment', $s))->jumlah ?? 0)->values(),
        ];
    })->values();
@endphp

{{-- Stat cards (klik untuk memfilter) --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <a href="{{ route('dashboard') }}"
       class="block text-left bg-white rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border p-4 transition hover:-translate-y-0.5 hover:shadow-md {{ !request()->hasAny(['sentiment','urgent_only']) ? 'border-[var(--navy)] ring-1 ring-[var(--navy)]' : 'border-slate-200/70' }}">
        <p class="text-[11px] font-mono uppercase tracking-wide text-slate-400">Total</p>
        <p class="font-display text-3xl font-semibold text-[var(--navy)] mt-1">{{ $stats['total'] }}</p>
    </a>
    <a href="{{ route('dashboard', ['sentiment' => 'positif']) }}"
       class="block text-left bg-white rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border p-4 transition hover:-translate-y-0.5 hover:shadow-md {{ request('sentiment') === 'positif' ? 'border-emerald-600 ring-1 ring-emerald-600' : 'border-slate-200/70' }}">
        <p class="text-[11px] font-mono uppercase tracking-wide text-slate-400">Positif</p>
        <p class="font-display text-3xl font-semibold text-emerald-600 mt-1">{{ $stats['positif'] }}</p>
    </a>
    <a href="{{ route('dashboard', ['sentiment' => 'netral']) }}"
       class="block text-left bg-white rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border p-4 transition hover:-translate-y-0.5 hover:shadow-md {{ request('sentiment') === 'netral' ? 'border-slate-500 ring-1 ring-slate-500' : 'border-slate-200/70' }}">
        <p class="text-[11px] font-mono uppercase tracking-wide text-slate-400">Netral</p>
        <p class="font-display text-3xl font-semibold text-slate-500 mt-1">{{ $stats['netral'] }}</p>
    </a>
    <a href="{{ route('dashboard', ['sentiment' => 'negatif']) }}"
       class="block text-left bg-white rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border p-4 transition hover:-translate-y-0.5 hover:shadow-md {{ request('sentiment') === 'negatif' ? 'border-red-600 ring-1 ring-red-600' : 'border-slate-200/70' }}">
        <p class="text-[11px] font-mono uppercase tracking-wide text-slate-400">Negatif</p>
        <p class="font-display text-3xl font-semibold text-red-600 mt-1">{{ $stats['negatif'] }}</p>
    </a>
    <a href="{{ route('dashboard', ['urgent_only' => 1]) }}"
       class="block text-left bg-[var(--ink)] rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] p-4 transition hover:-translate-y-0.5 hover:shadow-md {{ request()->boolean('urgent_only') ? 'ring-2 ring-[var(--amber)]' : '' }}">
        <p class="text-[11px] font-mono uppercase tracking-wide text-[var(--amber)]">Mendesak</p>
        <p class="font-display text-3xl font-semibold text-white mt-1">{{ $stats['urgent'] }}</p>
    </a>
</div>

{{-- Trend chart & Perbandingan mingguan (berdampingan) --}}
<div class="grid md:grid-cols-2 gap-5 mb-6">
    <div class="relative rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border border-slate-200/70 p-5 overflow-hidden bg-white">
        <p class="font-display text-sm font-semibold text-[var(--navy)] mb-3">Tren Sentimen &middot; 14 Hari Terakhir</p>
        <canvas id="trendChart" height="140"
                data-labels='@json($days->map(fn($d) => \Carbon\Carbon::parse($d)->format("d/m")))'
                data-datasets='@json($chartDatasets)'></canvas>
    </div>

    <div class="relative rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border border-slate-200/70 p-5 overflow-hidden bg-white">
        <p class="font-display text-sm font-semibold text-[var(--navy)] mb-3">Minggu Ini vs Minggu Lalu</p>
        <canvas id="weeklyChart" height="140"
                data-labels='@json($weeklyComparison->pluck('label'))'
                data-this='@json($weeklyComparison->pluck('minggu_ini'))'
                data-last='@json($weeklyComparison->pluck('minggu_lalu'))'></canvas>
    </div>
</div>

{{-- Link ke halaman statistik per sumber --}}
<div class="mb-6">
    <a href="{{ route('dashboard.sources') }}" data-no-ajax
       class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border border-[var(--navy)]/20 bg-white hover:bg-[var(--navy)]/5 transition-colors">
        📊 Lihat Statistik per Sumber Berita &rarr;
    </a>
</div>

{{-- Filters --}}
<form id="filterForm" method="GET" action="{{ route('dashboard') }}" class="bg-[var(--navy)]/5 rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border border-[var(--navy)]/15 p-5 mb-5">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul berita..."
               class="md:col-span-2 border border-[var(--navy)]/20 bg-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--navy)]/30">

        <select name="sentiment" class="border border-[var(--navy)]/20 bg-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--navy)]/30">
            <option value="">Semua Sentimen</option>
            @foreach(['positif','netral','negatif'] as $s)
                <option value="{{ $s }}" @selected(request('sentiment') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>

        <select name="source" class="border border-[var(--navy)]/20 bg-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--navy)]/30">
            <option value="">Semua Sumber</option>
            @foreach($sources as $src)
                <option value="{{ $src }}" @selected(request('source') === $src)>{{ $src }}</option>
            @endforeach
        </select>

        <input type="date" name="from" value="{{ request('from') }}" class="border border-[var(--navy)]/20 bg-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--navy)]/30">
    </div>

    <div class="grid md:grid-cols-4 gap-3 mt-4">
        @foreach(\App\Models\Taxonomy::KINDS as $kind => $label)
        <label>{{ $label }}<select class="field" name="{{ $kind }}_id"><option value="">Semua {{ $label }}</option>
        @foreach(\App\Models\Taxonomy::where('kind', $kind)->orderBy('name')->get() as $option)<option value="{{ $option->id }}" @selected((string)request($kind.'_id') === (string)$option->id)>{{ $option->name }}</option>@endforeach
        </select></label>@endforeach
        <label>Verifikasi<select class="field" name="verification_status"><option value="">Semua status</option><option value="pending" @selected(request('verification_status') === 'pending')>Perlu diperiksa</option><option value="verified" @selected(request('verification_status') === 'verified')>Terverifikasi</option></select></label>
        <label>Sampai tanggal<input class="field" type="date" name="to" value="{{ request('to') }}"></label>
    </div>
    <div class="flex flex-wrap items-center gap-4 mt-4">
        <label class="text-sm flex items-center gap-2">
            <input type="checkbox" name="urgent_only" value="1" @checked(request('urgent_only'))>
            Hanya mendesak
        </label>
        <select name="archived" class="border border-[var(--navy)]/20 bg-white rounded-lg px-2 py-1.5 text-sm">
            <option value="">Semua status arsip</option>
            <option value="1" @selected(request('archived') === '1')>Sudah diarsipkan</option>
            <option value="0" @selected(request('archived') === '0')>Belum diarsipkan</option>
        </select>
        <select name="sort" class="border border-[var(--navy)]/20 bg-white rounded-lg px-2 py-1.5 text-sm">
            <option value="newest" @selected(request('sort', 'newest') === 'newest')>Terbaru</option>
            <option value="oldest" @selected(request('sort') === 'oldest')>Terlama</option>
        </select>

        <div class="md:ml-auto flex gap-2 w-full md:w-auto">
            <button type="submit" class="flex-1 md:flex-none bg-[var(--navy)] text-white text-sm px-4 py-2 rounded-lg hover:bg-[var(--navy-light)] transition-colors">Terapkan</button>
            <a href="{{ route('dashboard') }}" class="flex-1 md:flex-none text-center text-sm px-4 py-2 rounded-lg border border-[var(--navy)]/20 bg-white hover:bg-[var(--navy)]/5 transition-colors">Reset</a>
            <a href="{{ route('dashboard.export', request()->query()) }}" data-no-ajax class="flex-1 md:flex-none text-center text-sm px-4 py-2 rounded-lg bg-[var(--amber)] text-[var(--ink)] font-medium hover:brightness-95 transition">Export CSV</a>
        </div>
    </div>
</form>

{{-- Table / empty state --}}
@if($news->isEmpty())
    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-14 text-center">
        <div class="img-slot w-24 h-24 rounded-2xl mx-auto mb-4 flex items-center justify-center text-2xl">📰</div>
        <p class="font-display text-lg text-[var(--navy)] mb-1">Belum ada berita yang cocok</p>
        <p class="text-sm text-slate-500">Coba ubah filter, atau tunggu workflow n8n mengirim data berikutnya.</p>
    </div>
@else
<div class="bg-white rounded-2xl shadow-[0_1px_3px_rgba(17,20,43,0.06)] border border-slate-200/70 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-400 text-[11px] font-mono uppercase tracking-wide border-b border-slate-200">
            <tr>
                <th class="p-3">Judul</th>
                <th class="p-3">Sumber</th>
                <th class="p-3">Tanggal</th>
                <th class="p-3">Sentimen</th>
                <th class="p-3">Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($news as $item)
            <tr class="border-t border-slate-100 hover:bg-slate-50/80 transition-colors {{ $item->is_urgent ? 'bg-amber-50/50' : '' }}">
                <td class="p-3 align-top">
                    <a href="{{ route('berita.show', $item->id) }}" data-no-ajax class="font-medium text-[var(--navy)] hover:underline">
                        {{ $item->title }}
                    </a>
                    @if($item->summary)
                        <p class="text-xs text-slate-500 mt-1">{{ Str::limit($item->summary, 130) }}</p>
                    @elseif($item->analysis)
                        <p class="text-xs text-slate-500 mt-1">{{ Str::limit($item->analysis, 130) }}</p>
                    @endif
                </td>
                <td class="p-3 align-top text-slate-500 whitespace-nowrap font-mono text-xs">{{ $item->source }}</td>
                <td class="p-3 align-top text-slate-500 whitespace-nowrap font-mono text-xs">{{ optional($item->published_at)->format('d/m/y H:i') }}</td>
                <td class="p-3 align-top">
                    <span @class([
                        'px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap',
                        'bg-emerald-100 text-emerald-700' => $item->sentiment === 'positif',
                        'bg-slate-100 text-slate-600'      => $item->sentiment === 'netral',
                        'bg-red-100 text-red-700'          => $item->sentiment === 'negatif',
                    ])>{{ ucfirst($item->sentiment) }}</span>
                </td>
                <td class="p-3 align-top space-y-1">
                    <span class="block text-xs text-slate-500">{{ $item->verification_status === 'verified' ? '✓ Terverifikasi' : 'Perlu diperiksa' }}</span>
                    @if($item->is_urgent)
                        <span class="block w-fit px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Mendesak</span>
                    @endif
                    @if($item->archived)
                        <span class="block w-fit px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Diarsipkan</span>
                    @endif
                    @can('archive-news')
                    <button type="button" data-archive-toggle data-id="{{ $item->id }}"
                            class="block w-fit px-2.5 py-1 rounded-full text-xs font-medium border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
                        {{ $item->archived ? 'Batal arsip' : 'Arsipkan' }}
                    </button>
                    @endcan
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $news->links() }}</div>
@endif
