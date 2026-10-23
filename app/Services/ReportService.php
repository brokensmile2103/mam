<?php

namespace App\Services;

use App\Models\Center;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Báo cáo thu/chi theo tháng (theo dòng tiền thực tế — dễ hiểu với chủ nhóm trẻ):
 *  - Thu: tiền học phí đã thu trong tháng (theo ngày thu).
 *  - Chi: chi phí đã ghi (thuê nhà, lương, đi chợ...) + tiền nhập kho (sữa, tã, thực phẩm...) trong tháng.
 * Kèm phải thu của phiếu tháng đó, công nợ tồn và tỉ lệ chuyên cần.
 */
class ReportService
{
    public function month(Center $center, Carbon $month): array
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $range = [$from->toDateString(), $to->toDateString()];

        $collected = (float) $center->payments()->whereBetween('paid_on', $range)->sum('amount');
        $byMethod = $center->payments()->whereBetween('paid_on', $range)
            ->selectRaw('method, SUM(amount) as total')->groupBy('method')->pluck('total', 'method')->map(fn ($v) => (float) $v)->all();

        $expenseByCat = $center->expenses()->whereBetween('occurred_on', $range)
            ->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category')->map(fn ($v) => (float) $v)->all();

        $stockIn = (float) DB::table('stock_movements')->where('center_id', $center->id)->where('type', 'in')
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('total_cost');

        $costs = [];
        foreach (config('mam.expense_categories') as $key => $label) {
            $costs[$key] = ['label' => $label, 'amount' => $expenseByCat[$key] ?? 0.0];
        }
        $costs['nhap_kho'] = ['label' => 'Nhập kho (sữa, tã, thực phẩm…)', 'amount' => $stockIn];
        $costTotal = array_sum(array_column($costs, 'amount'));

        $invoiceRow = $center->invoices()->where('month', $month->format('Y-m'))
            ->selectRaw('COUNT(*) as n, SUM(total_amount) as total, SUM(paid_amount) as paid')->first();

        $outstanding = (float) $center->invoices()->whereColumn('paid_amount', '<', 'total_amount')
            ->selectRaw('SUM(total_amount - paid_amount) as due')->value('due');

        // Chuyên cần: số lượt có mặt / tổng lượt điểm danh trong tháng.
        $att = $center->attendances()->whereBetween('date', $range)
            ->selectRaw("SUM(CASE WHEN status = 'co_mat' THEN 1 ELSE 0 END) as present, COUNT(*) as total")->first();

        return [
            'collected' => $collected,
            'by_method' => $byMethod,
            'costs' => $costs,
            'cost_total' => $costTotal,
            'profit' => $collected - $costTotal,
            'invoice_count' => (int) ($invoiceRow->n ?? 0),
            'invoice_total' => (float) ($invoiceRow->total ?? 0),
            'invoice_paid' => (float) ($invoiceRow->paid ?? 0),
            'outstanding' => $outstanding,
            'attendance_rate' => ($att->total ?? 0) > 0 ? (int) $att->present / (int) $att->total * 100 : null,
            'present_count' => (int) ($att->present ?? 0),
            'active_children' => $center->children()->where('status', 'active')->count(),
            'enrolled' => $center->children()->whereBetween('enrolled_on', $range)->count(),
            'left' => $center->children()->where('status', 'left')->whereBetween('left_on', $range)->count(),
        ];
    }
}
