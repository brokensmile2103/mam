<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Hồ sơ 1 bé. */
class Child extends Model
{
    protected $table = 'children';

    protected $fillable = [
        'classroom_id', 'full_name', 'nickname', 'gender', 'birth_date', 'enrolled_on',
        'parent_name', 'parent_relation', 'parent_phone', 'parent2_name', 'parent2_phone', 'address',
        'allergies', 'health_note', 'pickup_note', 'tuition_mode', 'tuition_fee', 'daily_fee', 'meal_rate', 'note',
    ];

    protected $hidden = ['parent_token'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrolled_on' => 'date',
            'left_on' => 'date',
            'tuition_fee' => 'decimal:2',
            'daily_fee' => 'decimal:2',
            'meal_rate' => 'decimal:2',
        ];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function entries()
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Bé "có tên trong lớp" vào ngày $date: đã nhập học, và đang học HOẶC nghỉ học từ ngày đó trở về sau.
     * Dùng cho điểm danh — bé nghỉ giữa tháng vẫn điểm danh bù được những ngày trước khi nghỉ
     * (quan trọng với bé gửi ngày: học phí tính theo số ngày có mặt).
     */
    public function scopeOnRollAt($query, Carbon|string $date)
    {
        $day = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('enrolled_on', '<=', $day)
            ->where(fn ($q) => $q->where('status', 'active')
                ->orWhere(fn ($q2) => $q2->where('status', 'left')->where('left_on', '>=', $day)));
    }

    /** Ngày $date có nằm trong thời gian bé theo học không (từ ngày nhập học tới ngày nghỉ, nếu có). */
    public function isOnRollAt(Carbon $date): bool
    {
        $day = $date->copy()->startOfDay();

        if ($this->enrolled_on && $day->lt($this->enrolled_on->copy()->startOfDay())) {
            return false;
        }

        return $this->isActive() || ($this->left_on && $day->lte($this->left_on->copy()->startOfDay()));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Tên gọi hằng ngày: "Bé Na" nếu có tên ở nhà, ngược lại là tên (chữ cuối) trong họ tên. */
    public function displayName(): string
    {
        return 'Bé '.($this->nickname ?: Str::of($this->full_name)->explode(' ')->last());
    }

    public function initials(): string
    {
        $name = $this->nickname ?: Str::of($this->full_name)->explode(' ')->last();

        return mb_strtoupper(mb_substr((string) $name, 0, 2));
    }

    /** Tuổi tính theo tháng (nhóm trẻ thường nói "bé 18 tháng"). */
    public function ageMonths(?Carbon $at = null): int
    {
        return max(0, (int) floor($this->birth_date->diffInMonths($at ?? Carbon::today())));
    }

    public function ageLabel(?Carbon $at = null): string
    {
        $months = $this->ageMonths($at);

        if ($months < 1) {
            return max(0, (int) $this->birth_date->diffInDays($at ?? Carbon::today())).' ngày';
        }

        if ($months < 36) {
            return $months.' tháng';
        }

        $years = intdiv($months, 12);
        $rest = $months % 12;

        return $years.' tuổi'.($rest ? ' '.$rest.' tháng' : '');
    }

    public function genderLabel(): ?string
    {
        return ['nam' => 'Bé trai', 'nu' => 'Bé gái'][$this->gender] ?? null;
    }

    public function hasAllergy(): bool
    {
        return filled($this->allergies);
    }

    /** Sinh nhật tiếp theo (tính từ hôm nay). */
    public function nextBirthday(): Carbon
    {
        $today = Carbon::today();
        $next = $this->birth_date->copy()->setYear($today->year);

        if ($next->lt($today)) {
            $next->addYear();
        }

        return $next;
    }

    /** Cách tính học phí áp dụng cho bé: mức riêng của bé, không có thì theo mặc định của nhóm. */
    public function effectiveTuitionMode(): string
    {
        $mode = $this->tuition_mode ?: $this->center?->tuitionMode();

        return $mode === 'daily' ? 'daily' : 'monthly';
    }

    public function isDailyTuition(): bool
    {
        return $this->effectiveTuitionMode() === 'daily';
    }

    /** Học phí THÁNG (đ/tháng) — dùng khi bé gửi theo tháng. */
    public function effectiveTuition(): float
    {
        return (float) ($this->tuition_fee ?? $this->center?->tuition_default ?? 0);
    }

    /** Đơn giá học phí NGÀY (đ/ngày có mặt) — dùng khi bé gửi theo ngày. */
    public function effectiveDailyFee(): float
    {
        return (float) ($this->daily_fee ?? $this->center?->daily_fee_default ?? 0);
    }

    /** Có đặt mức học phí riêng (khác mức chung của nhóm) không. */
    public function hasCustomFee(): bool
    {
        return $this->tuition_mode !== null || $this->tuition_fee !== null || $this->daily_fee !== null || $this->meal_rate !== null;
    }

    public function effectiveMealRate(): float
    {
        return (float) ($this->meal_rate ?? $this->center?->meal_rate_default ?? 0);
    }

    public function telLink(?string $phone = null): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', (string) ($phone ?? $this->parent_phone));

        return $phone ? 'tel:'.$phone : null;
    }
}
