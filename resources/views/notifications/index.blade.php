@extends('layouts.app')
@section('content')
<h1 class="font-display text-3xl mb-6">Notifikasi</h1>
<details class="panel mb-5"><summary class="cursor-pointer font-semibold">Atur pemberitahuan</summary>
<form class="grid md:grid-cols-2 gap-4 mt-5" method="POST" action="{{ route('notifications.preferences') }}">@csrf @method('PUT')
<label><input type="checkbox" name="email" value="1" @checked($preferences['email'] ?? false)> Terima juga melalui email</label>
<label><input type="checkbox" name="watch" value="1" @checked($preferences['watch'] ?? false)> Pantau isu baru sesuai pilihan berikut</label>
<label>Prioritas minimum<select class="field" name="min_priority">@foreach(\App\Models\Issue::PRIORITIES as $key => $label)<option value="{{ $key }}" @selected(($preferences['min_priority'] ?? 'high') === $key)>{{ $label }}</option>@endforeach</select></label>
<p class="text-xs text-slate-500">Notifikasi penugasan dan tenggat tetap masuk di aplikasi. Email dikirim bila diaktifkan dan layanan email telah dikonfigurasi.</p>
@foreach(['category' => ['Kategori', 'categories'], 'region' => ['Wilayah', 'regions']] as $kind => [$label, $key])<label>{{ $label }} (kosong = semua)<select multiple class="field" name="{{ $key }}[]" size="4">@foreach($taxonomies->get($kind, collect()) as $option)<option value="{{ $option->id }}" @selected(in_array($option->id, $preferences[$key] ?? []))>{{ $option->name }}</option>@endforeach</select></label>@endforeach
<button class="btn md:col-span-2">Simpan preferensi</button></form></details>
@forelse($notifications as $notification)
<div class="panel mb-3 {{ $notification->read_at ? '' : 'border-l-4 border-l-amber-500' }}"><a class="font-semibold" href="{{ route('issues.show', $notification->data['issue_id']) }}">{{ $notification->data['title'] }}</a><p class="text-sm mt-2">{{ $notification->data['message'] }}</p><p class="text-xs text-slate-500 mt-2">{{ $notification->created_at->format('d/m/Y H:i') }} WIB</p>
@if(!$notification->read_at)<form class="mt-3" method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="btn-secondary">Tandai sudah dibaca</button></form>@endif</div>
@empty<div class="panel text-slate-500">Belum ada notifikasi.</div>@endforelse
{{ $notifications->links() }}
@endsection
