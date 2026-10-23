<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Học phí theo tháng: tạo phiếu hàng loạt, sửa phiếu, thu tiền nhiều lần, in/gửi phiếu báo. */
class InvoiceController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request, InvoiceService $service, AttendanceService $attendance): View
    {
        $center = $this->currentCenter($request, true);
        $month = parse_month($request->query('thang'));
        $filter = in_array($request->query('loc'), ['unpaid', 'paid'], true) ? $request->query('loc') : 'all';

        $invoices = $center->invoices()->with(['child.classroom', 'payments'])->where('month', $month->format('Y-m'))
            ->when($filter === 'unpaid', fn ($q) => $q->whereColumn('paid_amount', '<', 'total_amount'))
            ->when($filter === 'paid', fn ($q) => $q->whereColumn('paid_amount', '>=', 'total_amount'))
            ->get()->sortBy(fn ($i) => $i->child?->full_name)->values();

        $all = $center->invoices()->where('month', $month->format('Y-m'));

        return view('invoices.index', [
            'center' => $center,
            'month' => $month,
            'filter' => $filter,
            'invoices' => $invoices,
            'totals' => [
                'count' => (clone $all)->count(),
                'total' => (float) (clone $all)->sum('total_amount'),
                'paid' => (float) (clone $all)->sum('paid_amount'),
            ],
            'missing' => $this->missingCount($center, $month, $service, $attendance),
            'olderDebt' => (float) $center->invoices()->where('month', '<', $month->format('Y-m'))->whereColumn('paid_amount', '<', 'total_amount')
                ->selectRaw('SUM(total_amount - paid_amount) as due')->value('due'),
        ]);
    }

    public function generate(Request $request, InvoiceService $service): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $data = $request->validate(['month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']]);
        $month = parse_month($data['month']);

        $result = $service->generateMonth($center, $month, $request->user());

        $message = "Tạo mới {$result['created']} phiếu, cập nhật {$result['updated']} phiếu theo điểm danh hiện tại.";
        if ($result['kept'] > 0) {
            $message .= " Giữ nguyên {$result['kept']} phiếu đã thu tiền hoặc đã sửa tay.";
        }

        return redirect()->route('invoices.index', ['thang' => $month->format('Y-m')])->with('status', $message);
    }

    public function show(Request $request, int $invoice): View
    {
        $center = $this->currentCenter($request, true);
        $invoice = $center->invoices()->with(['child.classroom', 'payments.creator'])->findOrFail($invoice);

        return view('invoices.show', [
            'center' => $center,
            'invoice' => $invoice,
            'qr' => vietqr_url($center->bank_code, $center->bank_account, $invoice->balance(), $invoice->transferMemo(), $center->bank_holder),
            'text' => invoice_text($center, $invoice),
        ]);
    }

    public function update(Request $request, InvoiceService $service, int $invoice): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $invoice = $center->invoices()->findOrFail($invoice);

        // Form cũ / client không gửi cách tính → giữ cách tính hiện tại của phiếu.
        $request->mergeIfMissing(['tuition_mode' => $invoice->tuition_mode]);

        $data = $request->validate([
            'tuition_mode' => ['required', Rule::in(array_keys(config('mam.tuition_modes')))],
            'tuition_amount' => 'exclude_if:tuition_mode,daily|required|numeric|min:0|max:1000000000',
            'tuition_days' => 'exclude_unless:tuition_mode,daily|required|integer|min:0|max:31',
            'tuition_rate' => 'exclude_unless:tuition_mode,daily|required|numeric|min:0|max:100000000',
            'meal_days' => 'required|integer|min:0|max:31',
            'meal_rate' => 'required|numeric|min:0|max:100000000',
            'other_label' => 'nullable|string|max:120',
            'other_amount' => 'nullable|numeric|min:0|max:1000000000',
            'discount_amount' => 'nullable|numeric|min:0|max:1000000000',
            'note' => 'nullable|string|max:500',
        ]);

        $service->update($invoice, $data);

        return back()->with('status', 'Đã cập nhật phiếu học phí.');
    }

    /** Tính lại 1 phiếu theo điểm danh + mức học phí hiện tại của bé (bỏ các số đã sửa tay). */
    public function recalculate(Request $request, InvoiceService $service, int $invoice): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $invoice = $center->invoices()->findOrFail($invoice);

        $service->recalculate($invoice);

        return back()->with('status', 'Đã tính lại phiếu theo điểm danh và mức học phí hiện tại.');
    }

    public function destroy(Request $request, int $invoice): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $invoice = $center->invoices()->findOrFail($invoice);

        if ($invoice->payments()->exists()) {
            return back()->with('warning', 'Phiếu đã có khoản thu — hãy xoá các lần thu trước khi xoá phiếu.');
        }

        $month = $invoice->month;
        $invoice->delete();

        return redirect()->route('invoices.index', ['thang' => $month])->with('status', 'Đã xoá phiếu học phí.');
    }

    public function pay(Request $request, InvoiceService $service, int $invoice): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $invoice = $center->invoices()->with('child')->findOrFail($invoice);

        $data = $request->validate([
            'amount' => 'required|numeric|gt:0|max:1000000000',
            'paid_on' => 'required|date|before_or_equal:today',
            'method' => ['required', Rule::in(array_keys(config('mam.payment_methods')))],
            'note' => 'nullable|string|max:255',
        ]);

        $service->addPayment($invoice, (float) $data['amount'], Carbon::parse($data['paid_on']), $data['method'], $data['note'] ?? null, $request->user());

        return back()->with('status', 'Đã thu '.money($data['amount']).'đ học phí của '.$invoice->child->displayName().'.');
    }

    /** Số bé cần phiếu tháng này mà chưa có (bé gửi ngày chưa đi ngày nào thì không tính). */
    private function missingCount(\App\Models\Center $center, Carbon $month, InvoiceService $service, AttendanceService $attendance): int
    {
        $billable = $service->billableChildren($center, $month);
        $have = $center->invoices()->where('month', $month->format('Y-m'))->pluck('child_id')->map(fn ($id) => (int) $id)->all();
        $days = $attendance->presentDays($center, $month);

        return $billable->filter(fn ($c) => ! in_array($c->id, $have, true) && $service->needsInvoice($c, $days[$c->id] ?? 0))->count();
    }

    public function destroyPayment(Request $request, InvoiceService $service, int $payment): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $service->deletePayment($center->payments()->findOrFail($payment));

        return back()->with('status', 'Đã xoá lần thu tiền.');
    }
}
