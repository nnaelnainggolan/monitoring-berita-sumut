<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Services\DailyReportBuilder;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', ['reports' => DailyReport::latest('report_date')->paginate(20)]);
    }

    public function store(Request $request, DailyReportBuilder $builder)
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d|before_or_equal:today']);
        $report = $builder->generate($data['date'], $request->user()->id);

        return redirect()->route('reports.show', $report);
    }

    public function show(DailyReport $report)
    {
        return view('reports.show', compact('report'));
    }

    public function pdf(DailyReport $report)
    {
        $options = new Options(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.pdf', compact('report'))->render());
        $dompdf->setPaper('A4');
        $dompdf->render();
        $dompdf->getCanvas()->page_text(475, 805, 'Halaman {PAGE_NUM}/{PAGE_COUNT}', null, 8, [0.4, 0.4, 0.4]);

        return response($dompdf->output(), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-berita-'.$report->report_date->format('Y-m-d').'.pdf"']);
    }
}
