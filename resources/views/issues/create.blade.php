@extends('layouts.app')
@section('content')
<a class="text-sm" href="{{ route('issues.index') }}">← Daftar isu</a>
<h1 class="font-display text-3xl my-4">Buat isu pemantauan</h1>
@if($news)<p class="mb-5 text-sm text-slate-500">Artikel sumber: {{ $news->title }}</p>@endif
<form method="POST" action="{{ route('issues.store') }}" class="panel grid md:grid-cols-2 gap-4">@csrf
@if($news)<input type="hidden" name="news_id" value="{{ $news->id }}">@endif
@include('issues.fields')
<p class="text-xs text-slate-500 md:col-span-2">Prioritas ditentukan dari dampak layanan publik, waktu kejadian, dan hasil pemeriksaan analis.</p>
<button class="btn md:col-span-2">Buat isu</button>
</form>
@endsection
