<?php

namespace App\Console\Commands;

use App\Services\DailyReportBuilder;
use Illuminate\Console\Command;

class GenerateDailyReport extends Command
{
    protected $signature = 'reports:daily {date? : YYYY-MM-DD, bawaan kemarin}';

    protected $description = 'Membuat snapshot laporan harian dari berita terverifikasi';

    public function handle(DailyReportBuilder $builder): int
    {
        $date = $this->argument('date') ?? today()->subDay()->toDateString();
        $validator = validator(['date' => $date], ['date' => 'required|date_format:Y-m-d|before_or_equal:today']);
        if ($validator->fails()) {
            $this->error('Tanggal tidak valid atau masih di masa depan.');

            return self::FAILURE;
        }
        $report = $builder->generate($date);
        $this->info('Laporan #'.$report->id.' tersedia.');

        return self::SUCCESS;
    }
}
