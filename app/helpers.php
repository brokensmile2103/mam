<?php

if (! function_exists('money')) {
    /**
     * Định dạng số tiền theo chuẩn Việt Nam — dấu CHẤM ngăn cách hàng nghìn,
     * KHÔNG dùng dấu phẩy như number_format() mặc định của PHP (kiểu Mỹ).
     * Dùng hàm này thay cho number_format() ở MỌI nơi hiển thị tiền trong app.
     */
    function money($value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }
}

if (! function_exists('quantity')) {
    /**
     * Định dạng SỐ LƯỢNG (kg, g, ml...) theo chuẩn Việt Nam — TỰ BỎ số 0 thừa
     * ở phần thập phân thay vì luôn ép đủ N chữ số như number_format() thường
     * làm (VD: 1920 hiện "1.920" thay vì "1.920,00"; 1920.5 vẫn hiện đúng
     * "1.920,5"). Khác với money() — tiền luôn muốn số nguyên, còn số lượng
     * nguyên liệu nhiều lúc cần phần thập phân thật (0.5g, 1.25kg...).
     */
    function quantity($value, int $maxDecimals = 2): string
    {
        $rounded = round((float) $value, $maxDecimals);
        $trimmed = rtrim(rtrim(number_format($rounded, $maxDecimals, '.', ''), '0'), '.');

        [$intPart, $decPart] = array_pad(explode('.', $trimmed), 2, null);
        $intFormatted = number_format((float) $intPart, 0, '', '.');

        return $decPart !== null ? $intFormatted.','.$decPart : $intFormatted;
    }
}

if (! function_exists('money_compact')) {
    /**
     * Số tiền RÚT GỌN cho những ô rất hẹp (VD: ô heatmap giờ cao điểm trên điện
     * thoại): 125.000 → "125k", 1.250.000 → "1,3tr", 12.000.000 → "12tr". CHỈ để
     * hiển thị gọn — chỗ nào cần số tiền chính xác vẫn dùng money().
     */
    function money_compact($value): string
    {
        $amount = (float) $value;

        // >= 999.500 làm tròn theo nghìn sẽ ra "1.000k" — chuyển sang đơn vị triệu cho gọn.
        if (abs($amount) >= 999_500) {
            $millions = round($amount / 1_000_000, 1);

            return rtrim(rtrim(number_format($millions, 1, ',', '.'), '0'), ',').'tr';
        }

        if (abs($amount) >= 1_000) {
            return number_format(round($amount / 1_000), 0, ',', '.').'k';
        }

        return number_format(round($amount), 0, ',', '.');
    }
}

if (! function_exists('color_badge')) {
    /**
     * Class Tailwind (liệt kê ĐẦY ĐỦ chuỗi để Tailwind quét được) cho huy hiệu/biểu tượng theo màu trong
     * config/mam.php. Không nối chuỗi kiểu "bg-{$color}-100" vì Tailwind sẽ không sinh CSS cho nó.
     */
    function color_badge(?string $color): string
    {
        return [
            'amber' => 'bg-amber-100 text-amber-700',
            'rose' => 'bg-rose-100 text-rose-700',
            'blue' => 'bg-blue-100 text-blue-700',
            'violet' => 'bg-violet-100 text-violet-700',
            'emerald' => 'bg-emerald-100 text-emerald-700',
            'sky' => 'bg-sky-100 text-sky-700',
            'orange' => 'bg-orange-100 text-orange-700',
            'red' => 'bg-red-100 text-red-700',
        ][$color] ?? 'bg-neutral-100 text-neutral-600';
    }
}

if (! function_exists('day_label')) {
    /** "Hôm nay" / "Hôm qua" / "Thứ ba, 06/10/2026" — dùng làm tiêu đề nhóm trong timeline. */
    function day_label(\Illuminate\Support\Carbon|\DateTimeInterface $date): string
    {
        $date = \Illuminate\Support\Carbon::instance($date)->startOfDay();

        if ($date->isToday()) {
            return 'Hôm nay';
        }

        if ($date->isYesterday()) {
            return 'Hôm qua';
        }

        $weekdays = [1 => 'Thứ hai', 2 => 'Thứ ba', 3 => 'Thứ tư', 4 => 'Thứ năm', 5 => 'Thứ sáu', 6 => 'Thứ bảy', 7 => 'Chủ nhật'];

        return $weekdays[$date->dayOfWeekIso].', '.$date->format('d/m/Y');
    }
}

if (! function_exists('ago')) {
    /** "2 ngày trước" / "hôm nay" — mức độ gần đây của 1 mốc thời gian. */
    function ago($date): string
    {
        if (! $date) {
            return 'chưa có';
        }

        $date = \Illuminate\Support\Carbon::parse($date);
        $days = (int) $date->copy()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::today());

        return match (true) {
            $days <= 0 => 'hôm nay',
            $days === 1 => 'hôm qua',
            default => $days.' ngày trước',
        };
    }
}

if (! function_exists('signed_money')) {
    /** Số tiền có dấu: +1.200.000 / −350.000 (dùng cho lãi/lỗ). */
    function signed_money($value): string
    {
        $v = (float) $value;

        return ($v > 0 ? '+' : ($v < 0 ? '−' : '')).money(abs($v));
    }
}

if (! function_exists('input_number')) {
    /**
     * Giá trị để ĐIỀN SẴN vào <input type="number">: số "sạch" kiểu máy (dấu chấm thập phân,
     * không phân cách nghìn, bỏ số 0 thừa). KHÔNG dùng quantity()/money() cho value của ô số —
     * "1.500" (1500 kiểu VN) sẽ bị trình duyệt hiểu thành 1,5 và "2,5" bị coi là không hợp lệ.
     */
    function input_number($value, int $maxDecimals = 3): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $s = number_format(round((float) $value, $maxDecimals), $maxDecimals, '.', '');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}

if (! function_exists('parse_date')) {
    /**
     * Đọc ngày từ query string (Y-m-d) an toàn: chuỗi rác/sai định dạng trả về $default
     * thay vì ném exception (lỗi 500) như Carbon::parse().
     */
    function parse_date($value, ?\Illuminate\Support\Carbon $default = null): ?\Illuminate\Support\Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $default;
        }

        try {
            $date = \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return $default;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : $default;
    }
}

if (! function_exists('normalize_phone')) {
    /**
     * Chuẩn hoá số điện thoại dùng để ĐĂNG NHẬP: bỏ khoảng trắng, dấu chấm, gạch, ngoặc; "+84" → "0".
     * "0912 345 678", "0912.345.678", "+84912345678" đều thành "0912345678" — tránh 1 người có 2 tài khoản
     * hoặc gõ khác lúc đăng ký là không đăng nhập được.
     */
    function normalize_phone(?string $phone): string
    {
        $phone = trim((string) $phone);
        $plus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if ($plus && str_starts_with($digits, '84')) {
            return '0'.substr($digits, 2);
        }

        return ($plus ? '+' : '').$digits;
    }
}

if (! function_exists('csv_safe')) {
    /** Chống "CSV injection": ô bắt đầu bằng = + - @ bị Excel hiểu là công thức. */
    function csv_safe($value)
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }
}

if (! function_exists('task_meta')) {
    /** Nhãn/icon/màu của 1 loại công việc: dùng chung với loại nhật ký, riêng "dọn dẹp" có icon riêng. */
    function task_meta(?string $type): array
    {
        $entry = config('mam.entry_types.'.$type);
        $extra = config('mam.task_icons.'.$type, []);
        $label = config('mam.task_types.'.$type, $entry['label'] ?? 'Việc khác');

        return [
            'label' => $label,
            'icon' => $extra['icon'] ?? $entry['icon'] ?? 'fa-list-check',
            'color' => $extra['color'] ?? $entry['color'] ?? 'neutral',
            'is_entry' => $entry !== null,
        ];
    }
}

if (! function_exists('duration_label')) {
    /** 95 phút → "1 giờ 35 phút". */
    function duration_label(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return trim(($h ? $h.' giờ ' : '').($m || ! $h ? $m.' phút' : ''));
    }
}

if (! function_exists('parse_month')) {
    /** Đọc tháng dạng YYYY-MM từ query string an toàn (sai định dạng → $default). */
    function parse_month($value, ?\Illuminate\Support\Carbon $default = null): \Illuminate\Support\Carbon
    {
        $default ??= \Illuminate\Support\Carbon::today()->startOfMonth();

        if (! is_string($value) || ! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $value)) {
            return $default;
        }

        return \Illuminate\Support\Carbon::createFromFormat('!Y-m', $value)->startOfMonth();
    }
}

if (! function_exists('vietqr_url')) {
    /** Ảnh mã VietQR (dịch vụ công khai img.vietqr.io) — phụ huynh quét bằng app ngân hàng là điền sẵn số tiền + nội dung. */
    function vietqr_url(?string $bankCode, ?string $account, float $amount = 0, string $memo = '', ?string $holder = null): ?string
    {
        if (! $bankCode || ! $account) {
            return null;
        }

        $query = array_filter([
            'amount' => $amount > 0 ? (int) round($amount) : null,
            'addInfo' => $memo ?: null,
            'accountName' => $holder ?: null,
        ]);

        return 'https://img.vietqr.io/image/'.rawurlencode(trim($bankCode)).'-'.rawurlencode(preg_replace('/\s+/', '', $account)).'-compact2.png'
            .($query ? '?'.http_build_query($query) : '');
    }
}

if (! function_exists('daily_report')) {
    /**
     * Báo cáo 1 ngày của bé dạng văn bản (để cô copy gửi Zalo cho phụ huynh / hiện ở trang phụ huynh).
     *
     * @param  iterable<\App\Models\DiaryEntry>  $entries  các ghi nhận TRONG NGÀY, đã sắp theo giờ
     */
    function daily_report(\App\Models\Child $child, \Illuminate\Support\Carbon $day, iterable $entries, ?\App\Models\Attendance $att): string
    {
        $entries = collect($entries);
        $time = fn ($e) => $e->occurred_at->format('H:i');
        $lines = ['🌱 Nhật ký '.$child->displayName().' — '.day_label($day).($day->isToday() || $day->isYesterday() ? ' ('.$day->format('d/m').')' : '')];

        if ($att) {
            $lines[] = $att->isPresent()
                ? '• Đến lớp: '.($att->checkInLabel() ?? '—').($att->checkOutLabel() ? ' · Về: '.$att->checkOutLabel().($att->picked_up_by ? ' ('.$att->picked_up_by.' đón)' : '') : '')
                : '• '.$att->meta()['label'];
        }

        $meals = $entries->where('type', 'an')->map(fn ($e) => trim(($e->meal ? config('mam.meals.'.$e->meal) : 'Bữa ăn').' '.($e->level ? mb_strtolower(config('mam.eat_levels.'.$e->level.'.label')) : '')));
        if ($meals->isNotEmpty()) {
            $lines[] = '• Ăn: '.$meals->implode('; ');
        }

        $milk = $entries->where('type', 'sua');
        if ($milk->isNotEmpty()) {
            $ml = $milk->sum('amount_ml');
            $lines[] = '• Sữa: '.$milk->count().' lần'.($ml ? ', tổng '.$ml.' ml' : '');
        }

        $sleep = $entries->where('type', 'ngu');
        if ($sleep->isNotEmpty()) {
            $lines[] = '• Ngủ: '.$sleep->map(fn ($e) => $e->summary(false) ?: $time($e))->implode('; ');
        }

        $toilet = $entries->where('type', 've_sinh');
        if ($toilet->isNotEmpty()) {
            $lines[] = '• Vệ sinh: '.$toilet->groupBy(fn ($e) => $e->level ?: 'khac')
                ->map(fn ($g, $k) => mb_strtolower(config('mam.toilet_kinds.'.$k, 'vệ sinh')).' '.$g->count().' lần')->implode(', ');
        }

        foreach ($entries->where('type', 'suc_khoe') as $e) {
            $lines[] = '• Nhiệt độ '.$time($e).': '.$e->summary(false);
        }

        foreach ($entries->where('type', 'can_do') as $e) {
            $lines[] = '• Cân đo: '.$e->summary(false);
        }

        foreach (['thuoc' => 'Uống thuốc', 'hoat_dong' => 'Hoạt động', 'su_co' => '⚠️ Sự cố', 'khac' => 'Cô ghi chú'] as $type => $label) {
            foreach ($entries->where('type', $type) as $e) {
                $lines[] = '• '.$label.' '.$time($e).': '.trim(($e->note ?? '').($e->item && $type === 'thuoc' ? ' ('.$e->item->name.')' : '')) ;
            }
        }

        foreach ($entries->whereIn('type', ['an', 'sua', 'ngu', 've_sinh', 'suc_khoe', 'can_do'])->filter(fn ($e) => filled($e->note)) as $e) {
            $lines[] = '  ↳ '.$e->meta()['label'].' '.$time($e).': '.$e->note;
        }

        if (count($lines) === 1) {
            $lines[] = '• Chưa có ghi nhận nào trong ngày.';
        }

        return implode("\n", $lines);
    }
}

if (! function_exists('invoice_text')) {
    /** Phiếu báo học phí dạng văn bản để gửi Zalo/SMS cho phụ huynh. */
    function invoice_text(\App\Models\Center $center, \App\Models\Invoice $invoice): string
    {
        $child = $invoice->child;
        $lines = [
            '📋 '.$center->name.' — Phiếu báo học phí '.mb_strtolower($invoice->monthLabel()),
            'Bé: '.$child?->full_name.($child?->classroom ? ' ('.$child->classroom->name.')' : ''),
        ];

        if ($invoice->isDaily()) {
            $lines[] = '• Học phí: '.(int) $invoice->tuition_days.' ngày × '.money($invoice->tuition_rate).'đ = '.money($invoice->tuition_amount).'đ';
        } elseif ((float) $invoice->tuition_amount > 0) {
            $lines[] = '• Học phí: '.money($invoice->tuition_amount).'đ';
        }
        if ((float) $invoice->meal_amount > 0) {
            $lines[] = '• Tiền ăn: '.$invoice->meal_days.' ngày × '.money($invoice->meal_rate).'đ = '.money($invoice->meal_amount).'đ';
        }
        if ((float) $invoice->other_amount > 0) {
            $lines[] = '• '.($invoice->other_label ?: 'Khoản khác').': '.money($invoice->other_amount).'đ';
        }
        if ((float) $invoice->discount_amount > 0) {
            $lines[] = '• Giảm trừ: −'.money($invoice->discount_amount).'đ';
        }

        $lines[] = 'Tổng cộng: '.money($invoice->total_amount).'đ';

        if ((float) $invoice->paid_amount > 0) {
            $lines[] = 'Đã đóng: '.money($invoice->paid_amount).'đ — Còn lại: '.money($invoice->balance()).'đ';
        }

        if ($invoice->balance() > 0 && $center->bank_account) {
            $lines[] = 'Chuyển khoản: '.trim(($center->bank_code ? $center->bank_code.' · ' : '').$center->bank_account.($center->bank_holder ? ' · '.$center->bank_holder : ''));
            if ($invoice->transferMemo() !== '') {
                $lines[] = 'Nội dung: '.$invoice->transferMemo();
            }
        }

        if ($invoice->note) {
            $lines[] = 'Ghi chú: '.$invoice->note;
        }
        if ($center->invoice_note) {
            $lines[] = $center->invoice_note;
        }

        return implode("\n", $lines);
    }
}
