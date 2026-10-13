<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Monitoring Berita Pemprov Sumut')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ink: #2B0A0F;
            --navy: #7A1B24;
            --navy-light: #93242F;
            --paper: #F7F5F0;
            --amber: #C9A227;
            --wire: #EFE7DA;
        }
        body { font-family: 'Inter Variable', sans-serif; background: var(--paper); }
        .font-display { font-family: 'Fraunces Variable', serif; }
        .font-mono { font-family: 'IBM Plex Mono', monospace; }

        /* Signature element: live wire ticker */
        .ticker-track {
            display: inline-flex;
            white-space: nowrap;
            animation: ticker 32s linear infinite;
        }
        @media (prefers-reduced-motion: reduce) {
            .ticker-track { animation: none; }
        }
        @keyframes ticker {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .img-slot {
            border: 1.5px dashed #D8CBB8;
            background: repeating-linear-gradient(135deg, #F0E9DC 0 10px, #F7F5F0 10px 20px);
        }
        .panel { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; }
        .field { display: block; width: 100%; background: white; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; margin-top: 5px; color: #2B0A0F; }
        .field:focus { outline: 2px solid #C9A227; outline-offset: 1px; }
        .btn, .btn-secondary { display: inline-block; border-radius: 8px; padding: 10px 16px; font-size: 14px; cursor: pointer; text-align: center; }
        .btn { background: #7A1B24; color: white; } .btn-secondary { border: 1px solid #cbd5e1; background: white; }
        .eyebrow { color: #7A1B24; font: 11px 'IBM Plex Mono', monospace; letter-spacing: .12em; margin-bottom: 12px; }
        label { font-size: 14px; } summary:focus-visible { outline: 2px solid #C9A227; }
    </style>
</head>
<body class="text-[var(--ink)] antialiased">

    {{-- Live wire ticker: signature element carrying alert headlines --}}
    @if(($tickerItems ?? collect())->isNotEmpty())
    <div class="sticky top-0 z-50 bg-[var(--ink)] text-[var(--amber)] font-mono text-xs overflow-hidden border-b border-black/20">
        <div class="ticker-track py-2">
            @for($i = 0; $i < 2; $i++)
                @foreach($tickerItems as $t)
                    <span class="px-6 inline-flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--amber)]"></span>
                        {{ $t }}
                    </span>
                @endforeach
            @endfor
        </div>
    </div>
    @endif

    <div class="md:flex">
        {{-- Sidebar (desktop) --}}
        @auth
        <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 {{ ($tickerItems ?? collect())->isNotEmpty() ? 'md:top-[29px]' : '' }} bg-[var(--navy)] text-white overflow-y-auto">
            <div class="p-6 border-b border-white/10">
                <div>
                    <img src="{{ asset('images/logo.png') }}" class="w-11 h-11 rounded-xl object-cover mb-3">
                </div>
                <p class="font-display text-lg font-semibold leading-snug">Monitoring<br>Berita Sumut</p>
                <p class="text-[11px] text-[var(--amber)] font-mono mt-1 tracking-wide">AI NEWS DESK</p>
            </div>
            <nav class="p-4 space-y-1 text-sm">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Ruang Pantau
                </a>
                <a href="{{ route('dashboard.export', request()->query()) }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-300 hover:bg-white/5">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Export CSV
                </a>
            </nav>

            {{-- Peta Sumut — ganti src dengan gambar peta kamu --}}
            <div>
                <div>
                    <img src="{{ asset('images/peta-sumut.png') }}" alt="Peta Sumatera Utara" class="w-full h-auto object-contain bg-white/5 p-2">
                </div>
                <p class="text-[10px] text-slate-400 font-mono text-center mt-2">Wilayah Cakupan Pemantauan</p>
            </div>

            <div class="mt-auto p-4 text-[11px] text-slate-400 font-mono border-t border-white/10">
                Sinkron otomatis &middot; n8n workflow
            </div>
        </aside>
        @endauth

        {{-- Main --}}
        <div class="flex-1 @auth md:pl-64 @endauth">
            {{-- Mobile top bar --}}
            <div class="md:hidden bg-[var(--navy)] text-white px-4 py-3 flex items-center justify-between">
                <p class="font-display font-semibold">Monitoring Berita Sumut</p>
                <span class="text-[10px] font-mono text-[var(--amber)]">AI NEWS DESK</span>
            </div>

            @auth
            <header class="bg-white border-b px-4 md:px-8 py-3 flex flex-wrap gap-3 items-center text-sm">
                <a href="{{ route('dashboard') }}">Berita</a>
                @if(Route::has('issues.index'))<a href="{{ route('issues.index') }}">Isu & tugas</a>@endif
                @if(Route::has('reports.index'))<a href="{{ route('reports.index') }}">Laporan</a>@endif
                @if(Route::has('notifications.index'))<a href="{{ route('notifications.index') }}">Notifikasi ({{ auth()->user()->unreadNotifications()->count() }})</a>@endif
                @can('administer')<a href="{{ route('admin.users.index') }}">Pengguna</a><a href="{{ route('admin.taxonomies.index') }}">Klasifikasi</a>@endcan
                @if(Route::has('workflow.index'))@can('review-news')<a href="{{ route('workflow.index') }}">Sinkronisasi</a>@endcan @endif
                <span class="md:ml-auto text-slate-500">{{ auth()->user()->name }} · {{ \App\Models\User::ROLES[auth()->user()->role] }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-red-700">Keluar</button></form>
            </header>
            @endauth
            <main class="max-w-6xl mx-auto p-4 md:p-8">
                @if(session('status'))<div role="status" class="mb-5 rounded-lg p-4 bg-emerald-50 text-emerald-800">{{ session('status') }}</div>@endif
                @if($errors->any())<div role="alert" class="mb-5 rounded-lg p-4 bg-red-50 text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    // Toggle arsip berita — berlaku global di semua halaman (tabel dashboard & halaman detail)
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-archive-toggle]');
        if (!btn) return;
        btn.disabled = true;
        try {
            const res = await fetch(`/berita/${btn.dataset.id}/toggle-archive`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            });
            if (!res.ok) throw new Error('Gagal menyimpan perubahan arsip.');
            await res.json();
            if (btn.dataset.redirect) {
                window.location.reload();
                return;
            }
            const results = document.getElementById('dashboard-results');
            if (results && results.contains(btn) && typeof window.__reloadDashboardResults === 'function') {
                window.__reloadDashboardResults();
            } else {
                window.location.reload();
            }
        } catch (err) {
            btn.disabled = false;
            window.alert('Perubahan arsip belum tersimpan. Muat ulang halaman dan coba lagi.');
        }
    });
    </script>
</body>
</html>
