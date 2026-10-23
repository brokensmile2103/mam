<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Phiếu học phí 1 tháng của 1 bé. paid_amount là cache, luôn đồng bộ bằng syncPaid(). */
class Invoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tuition_amount' => 'decimal:2',
            'tuition_rate' => 'decimal:2',
            'tuition_days' => 'integer',
            'meal_rate' => 'decimal:2',
            'meal_amount' => 'decimal:2',
            'other_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'meal_days' => 'integer',
            'edited_at' => 'datetime',
        ];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function balance(): float
    {
        return round((float) $this->total_amount - (float) $this->paid_amount, 2);
    }

    public function status(): string
    {
        if ((float) $this->total_amount <= 0 || $this->balance() <= 0) {
            return 'paid';
        }

        return (float) $this->paid_amount > 0 ? 'partial' : 'unpaid';
    }

    public function statusLabel(): string
    {
        return ['paid' => 'Đã thu đủ', 'partial' => 'Thu một phần', 'unpaid' => 'Chưa thu'][$this->status()];
    }

    public function monthLabel(): string
    {
        return 'Tháng '.Carbon::createFromFormat('!Y-m', $this->month)->format('m/Y');
    }

    /** Phiếu tính học phí theo ngày (gửi ngày) — học phí = số ngày học × đơn giá/ngày. */
    public function isDaily(): bool
    {
        return $this->tuition_mode === 'daily';
    }

    /** Nhãn dòng học phí: "Học phí" hoặc "Học phí (12 ngày × 150.000đ)". */
    public function tuitionLabel(): string
    {
        return $this->isDaily()
            ? 'Học phí ('.(int) $this->tuition_days.' ngày × '.money($this->tuition_rate).'đ)'
            : 'Học phí';
    }

    /** Mô tả ngắn số ngày dùng trong danh sách: "Gửi ngày · 12 ngày học" / "8 ngày ăn". */
    public function daysSummary(): string
    {
        return $this->isDaily()
            ? 'Gửi ngày · '.(int) $this->tuition_days.' ngày học'
            : (int) $this->meal_days.' ngày ăn';
    }

    /** Tính lại học phí (nếu gửi ngày), tiền ăn + tổng tiền từ các thành phần. */
    public function recompute(): void
    {
        if ($this->isDaily()) {
            $this->tuition_amount = round((int) $this->tuition_days * (float) $this->tuition_rate, 2);
        } else {
            $this->tuition_mode = 'monthly';
            $this->tuition_days = 0;
            $this->tuition_rate = 0;
        }

        $this->meal_amount = round((int) $this->meal_days * (float) $this->meal_rate, 2);
        $this->total_amount = max(0, round(
            (float) $this->tuition_amount + (float) $this->meal_amount + (float) $this->other_amount - (float) $this->discount_amount,
            2,
        ));
    }

    public function syncPaid(): void
    {
        $this->paid_amount = round((float) $this->payments()->sum('amount'), 2);
        $this->save();
    }

    /** Nội dung chuyển khoản gợi ý — ngắn, không dấu để mọi app ngân hàng nhận đúng. */
    public function transferMemo(): string
    {
        $name = \Illuminate\Support\Str::of($this->child?->full_name ?? '')->ascii()->upper()->replaceMatches('/[^A-Z0-9 ]/', '')->squish();

        $template = $this->center?->bankMemoTemplate() ?? Center::DEFAULT_BANK_MEMO;

        return Center::renderMemo($template, [
            'month' => str_replace('-', '', substr($this->month, 2)),
            'child' => (string) $name,
        ]);
    }
}
