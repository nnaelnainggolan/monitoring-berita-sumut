@extends('layouts.app')
@section('content')
<h1 class="font-display text-3xl mb-2">Pengguna dan hak akses</h1>
<p class="text-slate-500 mb-6">Admin mengelola sistem; analis memverifikasi berita; petugas menangani penugasan; pimpinan membaca laporan.</p>
<details class="panel mb-5" @if($errors->any()) open @endif><summary class="cursor-pointer font-semibold">Tambah pengguna</summary>
<form class="grid md:grid-cols-2 gap-4 mt-5" method="POST" action="{{ route('admin.users.store') }}">@csrf
<label>Nama<input class="field" name="name" value="{{ old('name') }}" required></label>
<label>Email<input class="field" type="email" name="email" value="{{ old('email') }}" required></label>
<label>Kata sandi<input class="field" type="password" name="password" minlength="12" required autocomplete="new-password"></label>
<label>Ulangi kata sandi<input class="field" type="password" name="password_confirmation" required autocomplete="new-password"></label>
<label>Peran<select class="field" name="role">@foreach(\App\Models\User::ROLES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
<input type="hidden" name="is_active" value="1"><button class="btn self-end">Buat akun</button>
</form></details>
@foreach($users as $user)
<details class="panel mb-3"><summary class="cursor-pointer"><strong>{{ $user->name }}</strong> · {{ \App\Models\User::ROLES[$user->role] }} · {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</summary>
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid md:grid-cols-2 gap-4 mt-5">@csrf @method('PUT')
<label>Nama<input class="field" name="name" value="{{ $user->name }}" required></label><label>Email<input class="field" name="email" type="email" value="{{ $user->email }}" required></label>
<label>Peran<select class="field" name="role">@foreach(\App\Models\User::ROLES as $key => $label)<option value="{{ $key }}" @selected($user->role === $key)>{{ $label }}</option>@endforeach</select></label>
<label>Status<select class="field" name="is_active"><option value="1" @selected($user->is_active)>Aktif</option><option value="0" @selected(!$user->is_active)>Nonaktif</option></select></label>
<label>Kata sandi baru (opsional)<input class="field" name="password" type="password" minlength="12" autocomplete="new-password"></label><label>Ulangi kata sandi baru<input class="field" name="password_confirmation" type="password" autocomplete="new-password"></label>
<button class="btn">Simpan perubahan</button></form>
</details>@endforeach
{{ $users->links() }}
@endsection
