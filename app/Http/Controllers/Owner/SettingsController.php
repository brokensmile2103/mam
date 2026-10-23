<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        return view('settings.index', ['center' => $this->currentCenter($request, true)]);
    }

    public function updateCenter(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request, true);

        $center->update($request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]));

        return back()->with('status', 'Đã lưu thông tin nhóm trẻ.');
    }

    /** Mức học phí / tiền ăn mặc định + thông tin chuyển khoản in trên phiếu báo học phí. */
    public function updateBilling(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request, true);

        $data = $request->validate([
            'tuition_mode' => ['required', Rule::in(array_keys(config('mam.tuition_modes')))],
            'tuition_default' => 'nullable|numeric|min:0|max:1000000000',
            'daily_fee_default' => 'nullable|numeric|min:0|max:100000000',
            'meal_rate_default' => 'nullable|numeric|min:0|max:100000000',
            'bank_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'bank_account' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 ]+$/'],
            'bank_holder' => 'nullable|string|max:100',
            'bank_memo' => 'nullable|string|max:50',
            'invoice_note' => 'nullable|string|max:500',
        ], [
            'bank_code.regex' => 'Mã ngân hàng chỉ gồm chữ và số, VD: VCB, MB, TCB, ACB, BIDV.',
            'bank_account.regex' => 'Số tài khoản chỉ gồm chữ số.',
        ]);

        $data['tuition_default'] = (float) ($data['tuition_default'] ?? 0);
        $data['daily_fee_default'] = (float) ($data['daily_fee_default'] ?? 0);
        $data['meal_rate_default'] = (float) ($data['meal_rate_default'] ?? 0);
        $data['bank_code'] = isset($data['bank_code']) ? strtoupper($data['bank_code']) : null;
        $data['bank_holder'] = isset($data['bank_holder']) ? mb_strtoupper($data['bank_holder']) : null;
        // Rỗng = không điền sẵn nội dung (ngân hàng tự xử lý). Middleware đã đổi '' thành null.
        $data['bank_memo'] = isset($data['bank_memo']) ? (\App\Models\Center::cleanMemoTemplate($data['bank_memo']) ?: null) : null;

        $center->update($data);

        return back()->with('status', 'Đã lưu học phí & thông tin chuyển khoản.');
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request, true);

        $center->update($request->validate([
            'accent_color' => ['required', Rule::in(array_keys(config('mam.accent_colors')))],
            'font_scale' => 'required|in:sm,md,lg',
        ]));

        return back()->with('status', 'Đã lưu giao diện.');
    }
}
