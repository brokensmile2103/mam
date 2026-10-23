<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Báo cáo thu/chi & chuyên cần theo tháng. */
class ReportController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request, ReportService $reports): View
    {
        $center = $this->currentCenter($request, true);
        $month = parse_month($request->query('thang'));

        // 6 tháng gần nhất để vẽ biểu đồ cột thu/chi.
        $trend = collect(range(5, 0))->map(function ($i) use ($center, $month, $reports) {
            $m = $month->copy()->subMonthsNoOverflow($i);
            $r = $reports->month($center, $m);

            return ['label' => $m->format('m/Y'), 'in' => $r['collected'], 'out' => $r['cost_total']];
        });

        return view('reports.index', [
            'center' => $center,
            'month' => $month,
            'r' => $reports->month($center, $month),
            'trend' => $trend,
        ]);
    }

    /** Danh sách công nợ học phí (mọi tháng) — CSV mở bằng Excel. */
    public function exportDebts(Request $request): StreamedResponse
    {
        $center = $this->currentCenter($request, true);

        $rows = $center->invoices()->with('child.classroom')->whereColumn('paid_amount', '<', 'total_amount')
            ->orderBy('month')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tháng', 'Mã bé', 'Họ tên', 'Lớp', 'Phụ huynh', 'SĐT', 'Tổng phiếu', 'Đã thu', 'Còn nợ']);

            foreach ($rows as $inv) {
                fputcsv($out, [
                    $inv->month, csv_safe($inv->child?->code), csv_safe($inv->child?->full_name), csv_safe($inv->child?->classroom?->name),
                    csv_safe($inv->child?->parent_name), csv_safe($inv->child?->parent_phone),
                    round((float) $inv->total_amount), round((float) $inv->paid_amount), round($inv->balance()),
                ]);
            }

            fclose($out);
        }, 'mam-cong-no-hoc-phi-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
