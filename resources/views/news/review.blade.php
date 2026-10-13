@extends('layouts.app')
@section('content')
<a class="text-sm" href="{{ route('berita.show', $item) }}">← Kembali ke berita</a>
<h1 class="font-display text-3xl my-4">Periksa analisis berita</h1>
<div class="panel mb-5"><h2 class="font-semibold">{{ $item->title }}</h2><a class="underline text-sm" href="{{ $item->link }}" target="_blank" rel="noopener noreferrer">Buka sumber berita</a>
@if($item->ai_payload)<details class="mt-3"><summary class="cursor-pointer">Hasil kiriman AI terakhir</summary><pre class="text-xs whitespace-pre-wrap mt-3">{{ json_encode($item->ai_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>@endif</div>
<form method="POST" action="{{ route('news.review.update', $item) }}" class="panel grid md:grid-cols-2 gap-4">@csrf @method('PUT')
<input type="hidden" name="review_version" value="{{ old('review_version', $item->review_version) }}">
<label class="md:col-span-2">Ringkasan<textarea class="field" name="summary" rows="4">{{ old('summary', $item->summary) }}</textarea></label>
<label class="md:col-span-2">Analisis<textarea class="field" name="analysis" rows="5">{{ old('analysis', $item->analysis) }}</textarea></label>
<label>Sentimen<select class="field" name="sentiment">@foreach(['positif', 'netral', 'negatif'] as $value)<option @selected(old('sentiment', $item->sentiment) === $value)>{{ $value }}</option>@endforeach</select></label>
<label>Urgensi<select class="field" name="is_urgent"><option value="0" @selected(!old('is_urgent', $item->is_urgent))>Biasa</option><option value="1" @selected(old('is_urgent', $item->is_urgent))>Mendesak</option></select></label>
@foreach(\App\Models\Taxonomy::KINDS as $kind => $label)
<label>{{ $label }}<select class="field" name="{{ $kind }}_id"><option value="">Belum ditentukan</option>@foreach($taxonomies->get($kind, collect()) as $option)
<option value="{{ $option->id }}" @selected((string)old($kind.'_id', $item->{$kind.'_id'}) === (string)$option->id)>{{ $option->name }}{{ $option->is_active ? '' : ' (nonaktif)' }}</option>
@endforeach</select></label>@endforeach
<label>Status verifikasi<select class="field" name="verification_status"><option value="pending" @selected(old('verification_status', $item->verification_status) === 'pending')>Perlu diperiksa</option><option value="verified" @selected(old('verification_status', $item->verification_status) === 'verified')>Terverifikasi</option></select></label>
<label class="md:col-span-2">Catatan pemeriksaan<textarea class="field" name="note" rows="2" required maxlength="2000" placeholder="Jelaskan pemeriksaan atau koreksi yang dilakukan.">{{ old('note') }}</textarea></label>
<p class="text-xs text-slate-500 md:col-span-2">Data terverifikasi tidak ditimpa kiriman ulang n8n. Mengembalikan status ke “Perlu diperiksa” mengizinkan pembaruan otomatis berikutnya.</p>
<button class="btn md:col-span-2">Simpan pemeriksaan</button>
</form>
@endsection
