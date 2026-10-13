@extends('layouts.app')
@section('content')
<div class="flex justify-between gap-3 mb-5"><a class="btn-secondary" href="{{ route('reports.index') }}">← Daftar laporan</a><a class="btn" href="{{ route('reports.pdf', $report) }}">Unduh PDF</a></div>
<div class="panel report"><p class="eyebrow">MONITORING BERITA SUMUT</p><h1 class="font-display text-3xl mb-3">Ringkasan harian</h1>@include('reports.body')</div>
<style>.report h2{font-size:20px;font-weight:600;margin:28px 0 12px}.report h3{font-weight:600;margin-bottom:8px}.report .meta{font-size:12px;color:#64748b;margin:10px 0}.stats{width:100%;margin:24px 0}.stats td{width:33%;padding:16px;background:#f7f5f0}.stats strong{display:block;font-size:28px}.highlight{border-bottom:1px solid #e2e8f0;padding:16px 0}.source{word-break:break-all;font-size:12px;color:#7a1b24}.issue-table{width:100%;font-size:13px}.issue-table td,.issue-table th{padding:12px 8px;text-align:left;border-bottom:1px solid #e2e8f0}.overdue{color:#b91c1c}</style>
@endsection
