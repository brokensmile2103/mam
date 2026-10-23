<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nhóm trẻ / cơ sở = 1 tenant. Mọi dữ liệu nghiệp vụ gắn center_id. */
class Center extends Model
{
    /** 'owner_id' và 'is_active' cố tình không fillable (xem User). */
    protected $fillable = [
        'name', 'address', 'phone', 'font_scale', 'accent_color',
        'tuition_mode', 'tuition_default', 'daily_fee_default', 'meal_rate_default', 'bank_code', 'bank_account', 'bank_holder', 'bank_memo', 'invoice_note',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tuition_default' => 'decimal:2',
            'daily_fee_default' => 'decimal:2',
            'meal_rate_default' => 'decimal:2',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function staff()
    {
        return $this->belongsToMany(User::class);
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }

    public function children()
    {
        return $this->hasMany(Child::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function entries()
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function taskTemplates()
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /** Cách tính học phí mặc định của nhóm: monthly | daily. */
    public function tuitionMode(): string
    {
        return $this->tuition_mode === 'daily' ? 'daily' : 'monthly';
    }

    public const DEFAULT_BANK_MEMO = 'HP {month} {child}';

    /** Mẫu nội dung chuyển khoản điền sẵn vào VietQR ({month} = tháng YYMM, {child} = tên bé). Rỗng = không điền sẵn. */
    public function bankMemoTemplate(): string
    {
        return array_key_exists('bank_memo', $this->attributes)
            ? (string) $this->attributes['bank_memo']
            : self::DEFAULT_BANK_MEMO;
    }

    /** Làm sạch mẫu khi lưu: bỏ dấu, chỉ giữ chữ, số, khoảng trắng, gạch ngang và {biến}. */
    public static function cleanMemoTemplate(string $template): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9 \-{}]/', '', \Illuminate\Support\Str::ascii($template))));
    }

    /** Thay biến, bỏ dấu và ký tự lạ (nhiều app ngân hàng từ chối nội dung có dấu/ký tự đặc biệt). */
    public static function renderMemo(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{'.$key.'}', (string) $value, $template);
        }

        $template = preg_replace('/\{[^}]*\}/', '', $template);

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9 \-]/', '', \Illuminate\Support\Str::ascii($template))));
    }

    /** Có đủ thông tin để hiện mã VietQR chuyển khoản trên phiếu học phí không. */
    public function hasVietQr(): bool
    {
        return filled($this->bank_code) && filled($this->bank_account);
    }
}
