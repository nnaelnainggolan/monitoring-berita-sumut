@php($data = $report->data)
<p class="meta">Tanggal berita: {{ $report->report_date->translatedFormat('d F Y') }} | Snapshot dibuat: {{ $report->generated_at->format('d/m/Y H:i') }} WIB</p>
<table class="stats"><tr><td>Artikel terkumpul<strong>{{ $data['article_count'] }}</strong></td><td>Terverifikasi<strong>{{ $data['verified_count'] }}</strong></td><td>Perlu diperiksa<strong>{{ $data['pending_count'] }}</strong></td></tr></table>
<p class="meta">{{ $data['issue_count'] }} isu terkait artikel hari ini. Sentimen berita terverifikasi: {{ $data['sentiments']['positif'] ?? 0 }} positif, {{ $data['sentiments']['netral'] ?? 0 }} netral, {{ $data['sentiments']['negatif'] ?? 0 }} negatif. {{ $data['urgent_count'] }} berita mendesak.</p>
<h2>Sorotan berita terverifikasi</h2>
<p class="meta">Maksimal lima artikel, diurutkan berdasarkan penanda mendesak lalu sentimen negatif. Ringkasan berasal dari hasil pemeriksaan analis.</p>
@forelse($data['highlights'] as $highlight)
<div class="highlight"><h3>{{ $loop->iteration }}. {{ $highlight['title'] }}</h3><p>{{ $highlight['summary'] ?: 'Ringkasan belum tersedia; baca sumber pendukung.' }}</p><p class="meta">Sumber: {{ $highlight['source'] ?: 'Tidak disebutkan' }} | Sentimen: {{ $highlight['sentiment'] }}{{ $highlight['is_urgent'] ? ' | Mendesak' : '' }}</p><p class="source"><a href="{{ $highlight['link'] }}">{{ $highlight['link'] }}</a></p></div>
@empty<p>Belum ada berita terverifikasi untuk tanggal ini. Artikel yang belum diperiksa tidak dimasukkan ke sorotan.</p>@endforelse
<h2>Penanganan saat laporan dibuat</h2>
<p>{{ $data['open_issue_count'] }} isu terbuka; {{ $data['overdue_count'] }} melewati tenggat.</p>
<p class="meta">Daftar berikut menampilkan hingga 20 isu aktif lintas tanggal, diurutkan menurut prioritas. Status ini berlaku pada waktu snapshot dibuat.</p>
@if(count($data['issues']))<table class="issue-table"><thead><tr><th>Isu / prioritas</th><th>Penanggung jawab</th><th>Status / tenggat WIB</th></tr></thead><tbody>
@foreach($data['issues'] as $issue)<tr><td><a href="{{ route('issues.show', $issue['id']) }}">#{{ $issue['id'] }} {{ $issue['title'] }}</a><br><span class="meta">{{ $issue['priority'] }}</span></td><td>{{ $issue['assignee'] }}</td><td>{{ $issue['status'] }}<br>{{ $issue['due_at'] ?? 'Tanpa tenggat' }}@if($issue['overdue'])<br><strong class="overdue">Terlambat</strong>@endif</td></tr>@endforeach
</tbody></table>@else<p>Tidak ada isu aktif pada waktu pembuatan laporan.</p>@endif
