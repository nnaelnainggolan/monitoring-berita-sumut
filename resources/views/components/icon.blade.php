@props(['name'])
<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
    @case('grid')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
    @case('news')<path d="M5 4h14v16H5a2 2 0 0 1-2-2V8h2m0-4v14a2 2 0 0 1-2 2m5-6h7m-7 3h7"/><path d="M8 7h8v4H8z"/>@break
    @case('tasks')<rect x="4" y="4" width="16" height="17" rx="2"/><path d="M9 3h6v3H9zM8 11l1 1 2-2m2 2h3m-8 5 1 1 2-2m2 1h3"/>@break
    @case('report')<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M8 13h8m-8 4h5"/>@break
    @case('chart')<path d="M4 3v17h17M8 15v-4m5 4V7m5 8V5"/>@break
    @case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12a2 2 0 0 0 4 0"/>@break
    @case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/><circle cx="9" cy="7" r="4"/>@break
    @case('layers')<path d="m12 3 10 5-10 5L2 8zm-9 9 9 5 9-5m-18 5 9 5 9-5"/>@break
    @case('refresh')<path d="M20 7v5h-5M4 17v-5h5"/><path d="M6 6a8 8 0 0 1 13 3l1 3M4 12l1 3a8 8 0 0 0 13 3"/>@break
    @case('search')<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>@break
    @case('filter')<path d="M4 7h16M4 17h16M8 4v6m8 4v6"/>@break
    @case('download')<path d="M12 3v12m-4-4 4 4 4-4M4 16v4h16v-4"/>@break
    @case('logout')<path d="M9 4H4v16h5m5-12 4 4-4 4m-6-4h13"/>@break
    @case('arrow')<path d="M4 12h16m-6-6 6 6-6 6"/>@break
    @case('chevron')<path d="m6 9 6 6 6-6"/>@break
    @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18m-13 4h2m4 0h2"/>@break
    @case('check')<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>@break
    @case('minus')<circle cx="12" cy="12" r="9"/><path d="M8 12h8"/>@break
    @case('alert')<path d="m10.3 4.8-8 14a1.5 1.5 0 0 0 1.3 2.2h16.8a1.5 1.5 0 0 0 1.3-2.2l-8-14a2 2 0 0 0-3.4 0ZM12 9v5m0 3h.01"/>@break
    @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
    @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
    @case('close')<path d="m6 6 12 12M6 18 18 6"/>@break
    @case('archive')<path d="M4 8v12h16V8M3 3h18v5H3zm6 9h6"/>@break
    @case('pin')<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>@break
@endswitch
</svg>
