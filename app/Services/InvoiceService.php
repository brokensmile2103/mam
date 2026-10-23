<?php

namespace App\Services;

use App\Models\Center;
use App\Models\Child;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Học phí theo tháng, 2 cách tính học phí:
 *  - Gửi tháng (monthly): học phí cố định/tháng.
 *  - Gửi ngày (daily):    học phí = số ngày CÓ MẶT (lấy từ điểm danh) × đơn giá/ngày.
 * Cộng tiền ăn theo SỐ NGÀY CÓ MẶT + khoản khác − giảm trừ.
 * Thu nhiều lần được (payments); invoices.paid_amount luôn đồng bộ từ payments.
 */
class InvoiceService
{
    public function __construct(private AttendanceService $attendance) {}

    /**
     * Các bé cần có phiếu tháng $month: đang học, hoặc đã nghỉ TRONG/SAU tháng đó (vẫn phải trả những ngày đã học),
     * và đã nhập học trước khi tháng kết thúc.
     *
     * @return Collection<int, Child>
     */
    public function billableChildren(Center $center, Carbon $month): Collection
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        return $center->children()->with('center')
            ->where(fn ($q) => $q->where('status', 'active')
                ->orWhere(fn ($q2) => $q2->where('status', 'left')->where('left_on', '>=', $start)))
            ->where('enrolled_on', '<=', $end)
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Bé gửi ngày mà cả tháng không đi ngày nào thì không cần phiếu 0đ (tránh rác danh sách "Đã thu đủ").
     */
    public function needsInvoice(Child $child, int $presentDays): bool
    {
        return ! $child->isDailyTuition() || $presentDays > 0;
    }

    /**
     * Tạo phiếu cho các bé chưa có phiếu tháng này, và CẬP NHẬT LẠI theo điểm danh + mức học phí hiện tại
     * cho các phiếu CHƯA THU đồng nào và CHƯA SỬA TAY. Phiếu đã thu giữ nguyên để không lệch với tiền đã nhận;
     * phiếu đã sửa tay giữ nguyên để không mất số chủ nhóm đã chỉnh (mở phiếu → "Tính lại theo điểm danh" nếu muốn).
     *
     * @return array{created: int, updated: int, kept: int}
     */
    public function generateMonth(Center $center, Carbon $month, ?User $user): array
    {
        $key = $month->format('Y-m');

        // Bấm "Tạo phiếu" 2 lần / 2 người cùng bấm: chạy lần lượt, không đụng unique (child_id, month) gây lỗi 500.
        try {
            return Cache::lock('mam:invoices:'.$center->id.':'.$key, 120)
                ->block(20, fn () => $this->generateMonthLocked($center, $month, $key, $user));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['month' => 'Đang có người tạo phiếu tháng này, vui lòng thử lại sau ít giây.']);
        }
    }

    /** @return array{created: int, updated: int, kept: int} */
    private function generateMonthLocked(Center $center, Carbon $month, string $key, ?User $user): array
    {
        $days = $this->attendance->presentDays($center, $month);
        $children = $this->billableChildren($center, $month);
        $created = $updated = $kept = 0;

        DB::transaction(function () use ($center, $children, $key, $days, $user, &$created, &$updated, &$kept) {
            foreach ($children as $child) {
                $present = $days[$child->id] ?? 0;
                $invoice = Invoice::query()->where('child_id', $child->id)->where('month', $key)->lockForUpdate()->first();

                if ($invoice && ((float) $invoice->paid_amount > 0 || $invoice->edited_at)) {
                    $kept++;

                    continue;
                }

                if (! $invoice && ! $this->needsInvoice($child, $present)) {
                    continue;
                }

                $invoice ??= new Invoice(['center_id' => $center->id, 'child_id' => $child->id, 'month' => $key, 'created_by' => $user?->id]);
                $isNew = ! $invoice->exists;

                $this->applyRates($invoice, $child, $present);
                $invoice->save();

                $isNew ? $created++ : $updated++;
            }
        });

        return ['created' => $created, 'updated' => $updated, 'kept' => $kept];
    }

    /**
     * Tính lại 1 phiếu theo điểm danh + mức học phí HIỆN TẠI của bé (giữ khoản khác, giảm trừ, ghi chú).
     * Bỏ đánh dấu "đã sửa tay". Không cho tổng mới nhỏ hơn số đã thu.
     */
    public function recalculate(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $locked = Invoice::query()->with('child.center')->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->child, 404);

            $present = $this->attendance->presentDays($locked->child->center, Carbon::createFromFormat('!Y-m', $locked->month))[$locked->child_id] ?? 0;

            $this->applyRates($locked, $locked->child, $present);
            $locked->edited_at = null;
            $this->assertNotBelowPaid($locked);
            $locked->save();

            $invoice->setRawAttributes($locked->getAttributes(), true);

            return $invoice;
        });
    }

    /** Sửa tay 1 phiếu (cách tính, số ngày, đơn giá, khoản khác, giảm trừ...). Không cho tổng < số đã thu. */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $mode = ($data['tuition_mode'] ?? $invoice->tuition_mode) === 'daily' ? 'daily' : 'monthly';

        $invoice->fill([
            'tuition_mode' => $mode,
            'meal_days' => (int) ($data['meal_days'] ?? $invoice->meal_days),
            'meal_rate' => (float) ($data['meal_rate'] ?? $invoice->meal_rate),
            'other_label' => $data['other_label'] ?? null,
            'other_amount' => (float) ($data['other_amount'] ?? 0),
            'discount_amount' => (float) ($data['discount_amount'] ?? 0),
            'note' => $data['note'] ?? null,
        ]);

        if ($mode === 'daily') {
            $invoice->tuition_days = (int) ($data['tuition_days'] ?? $invoice->tuition_days);
            $invoice->tuition_rate = (float) ($data['tuition_rate'] ?? $invoice->tuition_rate);
        } else {
            $invoice->tuition_amount = (float) ($data['tuition_amount'] ?? $invoice->tuition_amount);
        }

        $invoice->recompute();
        $this->assertNotBelowPaid($invoice);

        if ($invoice->isDirty()) {
            $invoice->edited_at = now();
        }

        $invoice->save();

        return $invoice;
    }

    public function addPayment(Invoice $invoice, float $amount, Carbon $paidOn, string $method, ?string $note, ?User $user): Payment
    {
        return DB::transaction(function () use ($invoice, $amount, $paidOn, $method, $note, $user) {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Số tiền phải lớn hơn 0.']);
            }

            if ($amount > $locked->balance() + 0.001) {
                throw ValidationException::withMessages(['amount' => 'Số tiền thu ('.money($amount).'đ) lớn hơn số còn phải thu ('.money($locked->balance()).'đ).']);
            }

            $payment = Payment::create([
                'center_id' => $locked->center_id,
                'invoice_id' => $locked->id,
                'amount' => round($amount, 2),
                'paid_on' => $paidOn->toDateString(),
                'method' => $method,
                'note' => $note,
                'created_by' => $user?->id,
            ]);

            $locked->syncPaid();
            $invoice->setRawAttributes($locked->getAttributes(), true);

            return $payment;
        });
    }

    public function deletePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $invoice = Invoice::query()->whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
            $payment->delete();
            $invoice->syncPaid();
        });
    }

    /** Ước tính phiếu của 1 bé cho 1 tháng (chưa lưu) — dùng để xem trước. */
    public function preview(Child $child, Carbon $month): Invoice
    {
        $days = $this->attendance->presentDays($child->center, $month)[$child->id] ?? 0;

        $invoice = new Invoice(['center_id' => $child->center_id, 'child_id' => $child->id, 'month' => $month->format('Y-m')]);
        $this->applyRates($invoice, $child, $days);

        return $invoice;
    }

    // ---------------------------------------------------------------------

    /** Điền cách tính, đơn giá, số ngày theo mức HIỆN TẠI của bé rồi tính lại tổng (không đụng khoản khác/giảm trừ). */
    private function applyRates(Invoice $invoice, Child $child, int $presentDays): void
    {
        if ($child->isDailyTuition()) {
            $invoice->tuition_mode = 'daily';
            $invoice->tuition_days = $presentDays;
            $invoice->tuition_rate = $child->effectiveDailyFee();
        } else {
            $invoice->tuition_mode = 'monthly';
            $invoice->tuition_amount = $child->effectiveTuition();
        }

        $invoice->meal_rate = $child->effectiveMealRate();
        $invoice->meal_days = $presentDays;
        $invoice->recompute();
    }

    private function assertNotBelowPaid(Invoice $invoice): void
    {
        if ((float) $invoice->total_amount < (float) $invoice->paid_amount) {
            throw ValidationException::withMessages(['total' => 'Tổng phiếu ('.money($invoice->total_amount).'đ) không được nhỏ hơn số tiền đã thu ('.money($invoice->paid_amount).'đ). Hãy xoá bớt lần thu trước.']);
        }
    }
}
