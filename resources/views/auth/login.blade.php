@extends('layouts.app')
@section('title', 'Masuk · Monitoring Berita Sumut')
@section('content')
<div class="panel max-w-md mx-auto mt-12">
    <p class="eyebrow">MONITORING BERITA SUMUT</p>
    <h1 class="font-display text-3xl mb-3">Selamat datang kembali</h1>
    <p class="text-sm text-slate-500 mb-6">Masuk untuk memantau berita dan menindaklanjuti isu.</p>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">@csrf
        <label class="block">Email<input class="field" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
        <label class="block">Kata sandi<input class="field" type="password" name="password" required autocomplete="current-password"></label>
        <label class="flex gap-2"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
        <button class="btn w-full">Masuk</button>
    </form>
    <p class="text-xs text-slate-500 mt-6">Hubungi admin untuk pembuatan akun atau penggantian kata sandi.</p>
</div>
@endsection
