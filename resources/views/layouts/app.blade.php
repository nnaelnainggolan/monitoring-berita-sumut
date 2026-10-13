<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Monitoring Berita Pemprov Sumut')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased @auth authenticated @else guest-page @endauth">
    <a class="skip-link" href="#main-content">Lewati ke konten</a>
    @auth
    @php
        $unreadCount = auth()->user()->unreadNotifications()->count();
        $pageLabel = match (true) {
            request()->routeIs('issues.*') => 'Isu & tindak lanjut',
            request()->routeIs('reports.*') => 'Laporan harian',
            request()->routeIs('notifications.*') => 'Notifikasi',
            request()->routeIs('admin.users.*') => 'Pengguna',
            request()->routeIs('admin.taxonomies.*') => 'Klasifikasi',
            request()->routeIs('workflow.*') => 'Sinkronisasi',
            request()->routeIs('dashboard.sources') => 'Statistik sumber',
            default => 'Ruang pantau',
        };
    @endphp
    <aside class="desk-sidebar" aria-label="Navigasi desktop">
        <a href="{{ route('dashboard') }}" class="desk-brand">
            <span class="brand-logo"><img src="{{ asset('images/logo.png') }}" alt="Logo Kominfo" width="36" height="36"></span>
            <strong>Berita Sumut<span>Monitoring & analisis</span></strong>
        </a>
        @include('layouts.navigation')
        <div class="sidebar-bottom">
            <div class="coverage-card">
                <img src="{{ asset('images/peta-sumut.png') }}" alt="Peta Sumatera Utara" width="42" height="62">
                <div><span>Cakupan wilayah</span><strong>Sumatera Utara</strong><small>Pemantauan pemberitaan</small></div>
            </div>
            <div class="sidebar-account">
                <span class="user-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div><strong>{{ auth()->user()->name }}</strong><span>{{ \App\Models\User::ROLES[auth()->user()->role] }}</span></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-logout" aria-label="Keluar" title="Keluar"><x-icon name="logout"/></button></form>
            </div>
        </div>
    </aside>
    <dialog id="mobile-navigation" class="mobile-navigation" aria-label="Menu navigasi">
        <div class="mobile-nav-heading"><strong>Berita Sumut</strong><button type="button" class="icon-button" data-close-navigation aria-label="Tutup menu"><x-icon name="close"/></button></div>
        @include('layouts.navigation')
        <div class="mobile-account"><span>{{ auth()->user()->name }} · {{ \App\Models\User::ROLES[auth()->user()->role] }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="nav-link"><x-icon name="logout"/>Keluar</button></form></div>
    </dialog>
    @endauth
    <div class="app-workspace">
        @auth
        <header class="desk-topbar">
            <div class="topbar-location"><button type="button" class="icon-button mobile-menu-toggle" data-open-navigation aria-controls="mobile-navigation" aria-haspopup="dialog" aria-label="Buka menu"><x-icon name="menu"/></button><span class="topbar-context">Pusat informasi</span><span class="topbar-divider">/</span><strong>{{ $pageLabel }}</strong></div>
            <div class="topbar-tools"><span class="topbar-date"><x-icon name="calendar"/>{{ now()->translatedFormat('d M Y') }}</span><a class="icon-button notification-link" href="{{ route('notifications.index') }}" aria-label="Notifikasi, {{ $unreadCount }} belum dibaca"><x-icon name="bell"/>@if($unreadCount)<span class="notification-dot"></span>@endif</a><span class="topbar-avatar" title="{{ auth()->user()->name }}">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span></div>
        </header>
        @else
        <header class="guest-brand"><img src="{{ asset('images/logo.png') }}" alt="Logo Kominfo" width="36" height="36"><span>Monitoring Berita Sumut</span></header>
        @endauth
        <main id="main-content" class="workspace-content" tabindex="-1">
            @if(session('status'))<div role="status" class="flash-message flash-success"><x-icon name="check"/><span>{{ session('status') }}</span></div>@endif
            @if($errors->any())<div role="alert" class="flash-message flash-error"><x-icon name="alert"/><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
            @auth<footer class="workspace-footer"><span>Monitoring Berita Sumut</span><span>Pemerintah Provinsi Sumatera Utara</span></footer>@endauth
        </main>
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
