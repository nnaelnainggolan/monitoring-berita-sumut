@extends('layouts.app')
@section('content')
<h1 class="font-display text-3xl mb-2">Klasifikasi berita</h1>
<p class="text-slate-500 mb-6">Kelola kategori, wilayah, dan OPD. Data nonaktif tetap tersedia pada berita lama.</p>
<form class="panel flex flex-wrap gap-3 items-end" method="POST" action="{{ route('admin.taxonomies.store') }}">@csrf
    <label>Jenis<select class="field" name="kind">@foreach(\App\Models\Taxonomy::KINDS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
    <label class="flex-1">Nama<input class="field" name="name" value="{{ old('name') }}" required maxlength="150"></label><button class="btn">Tambah</button>
</form>
@foreach(\App\Models\Taxonomy::KINDS as $kind => $label)
<div class="panel mt-5"><h2 class="font-display text-xl mb-4">{{ $label }}</h2>
@forelse($items->where('kind', $kind) as $item)
<form method="POST" action="{{ route('admin.taxonomies.update', $item) }}" class="flex flex-wrap gap-3 py-2 items-center">@csrf @method('PUT')
    <input class="field flex-1" aria-label="Nama {{ $label }}" name="name" value="{{ $item->name }}" required maxlength="150">
    <select class="field w-auto" name="is_active" aria-label="Status"><option value="1" @selected($item->is_active)>Aktif</option><option value="0" @selected(!$item->is_active)>Nonaktif</option></select>
    <button class="btn-secondary">Simpan</button>
</form>
@empty<p class="text-slate-500">Belum ada {{ strtolower($label) }}.</p>@endforelse
</div>@endforeach
@endsection
