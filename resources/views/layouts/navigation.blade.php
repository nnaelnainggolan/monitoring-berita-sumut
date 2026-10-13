@php
    $navigation = [
        ['label' => 'Ruang pantau', 'route' => 'dashboard', 'icon' => 'grid', 'active' => request()->routeIs('dashboard', 'berita.*', 'news.review*')],
        ['label' => 'Isu & tindak lanjut', 'route' => 'issues.index', 'icon' => 'tasks', 'active' => request()->routeIs('issues.*')],
        ['label' => 'Laporan harian', 'route' => 'reports.index', 'icon' => 'report', 'active' => request()->routeIs('reports.*')],
        ['label' => 'Statistik sumber', 'route' => 'dashboard.sources', 'icon' => 'chart', 'active' => request()->routeIs('dashboard.sources')],
        ['label' => 'Notifikasi', 'route' => 'notifications.index', 'icon' => 'bell', 'active' => request()->routeIs('notifications.*')],
    ];
@endphp
<nav class="desk-navigation" aria-label="Menu utama">
    <p class="nav-caption">Pemantauan</p>
    @foreach($navigation as $link)
        <a href="{{ route($link['route']) }}" @class(['nav-link', 'is-active' => $link['active']]) @if($link['active']) aria-current="page" @endif>
            <x-icon :name="$link['icon']"/><span>{{ $link['label'] }}</span>
            @if($link['route'] === 'notifications.index' && $unreadCount > 0)<span class="nav-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
        </a>
    @endforeach
    @can('review-news')
        <p class="nav-caption nav-caption-secondary">Pengelolaan</p>
        <a href="{{ route('workflow.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('workflow.*')]) @if(request()->routeIs('workflow.*')) aria-current="page" @endif><x-icon name="refresh"/><span>Sinkronisasi</span></a>
    @endcan
    @can('administer')
        <a href="{{ route('admin.users.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.users.*')]) @if(request()->routeIs('admin.users.*')) aria-current="page" @endif><x-icon name="users"/><span>Pengguna</span></a>
        <a href="{{ route('admin.taxonomies.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.taxonomies.*')]) @if(request()->routeIs('admin.taxonomies.*')) aria-current="page" @endif><x-icon name="layers"/><span>Klasifikasi</span></a>
    @endcan
</nav>
