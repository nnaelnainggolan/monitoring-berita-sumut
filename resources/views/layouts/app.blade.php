<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Monitoring Berita Pemprov Sumut')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        :root {
            --ink: #2B0A0F;
            --navy: #100952;
            --navy-light: #93242F;
            --paper: #F7F5F0;
            --amber: #C9A227;
            --wire: #EFE7DA;
        }
        body { font-family: 'Inter', sans-serif; background: var(--paper); }
        .font-display { font-family: 'Fraunces', serif; }
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
        <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 md:top-[29px] bg-[var(--navy)] text-white">
            <div class="p-6 border-b border-white/10">
                <div>
                    <img src="{{ asset('logo.png') }}" class="w-11 h-11 rounded-xl object-cover mb-3">
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

            {{-- Peta Sumut --}}
            <div class="px-4 mt-2">
                <div>
                    <img src="{{ asset('images/peta-sumut.png') }}" alt="Peta Sumatera Utara"">
                </div>
                <p class="text-[10px] text-slate-400 font-mono text-center mt-2">Wilayah Cakupan Pemantauan</p>
            </div>

            <div class="mt-auto p-4 text-[11px] text-slate-400 font-mono border-t border-white/10">
                Sinkron otomatis &middot; n8n workflow
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 md:pl-64">
            {{-- Mobile top bar --}}
            <div class="md:hidden bg-[var(--navy)] text-white px-4 py-3 flex items-center justify-between">
                <p class="font-display font-semibold">Monitoring Berita Sumut</p>
                <span class="text-[10px] font-mono text-[var(--amber)]">AI NEWS DESK</span>
            </div>

            <main class="max-w-6xl mx-auto p-4 md:p-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>