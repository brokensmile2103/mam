<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** Cô giáo / bảo mẫu của nhóm trẻ: điểm danh, ghi nhật ký, làm việc, xem hồ sơ bé; không thấy tiền, không sửa cài đặt. */
class StaffController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request, true);

        return view('staff.index', [
            'center' => $center,
            'staff' => $center->staff()->where('role', 'staff')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request, true);

        $request->merge(['phone' => normalize_phone($request->input('phone'))]);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?\d{8,15}$/', 'unique:users,phone'],
            'password' => 'required|string|min:8',
        ], [
            'phone.regex' => 'Số điện thoại không hợp lệ (VD: 0912345678).',
            'phone.unique' => 'Số điện thoại này đã có tài khoản Mầm.',
        ]);

        $staff = User::forceCreate([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $center->staff()->attach($staff->id);

        return back()->with('status', 'Đã thêm cô "'.$staff->name.'".');
    }

    public function toggleActive(Request $request, int $user): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $staff = $center->staff()->where('role', 'staff')->findOrFail($user);
        $staff->forceFill(['is_active' => ! $staff->is_active])->save();

        return back()->with('status', $staff->is_active ? 'Đã kích hoạt lại tài khoản.' : 'Đã khoá tài khoản.');
    }

    public function resetPassword(Request $request, int $user): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $staff = $center->staff()->where('role', 'staff')->findOrFail($user);

        $data = $request->validate(['password' => 'required|string|min:8']);
        $staff->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', 'Đã đổi mật khẩu cho '.$staff->name.'.');
    }

    public function remove(Request $request, int $user): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $staff = $center->staff()->where('role', 'staff')->findOrFail($user);
        $center->staff()->detach($staff->id);

        return back()->with('status', 'Đã gỡ cô khỏi nhóm trẻ này.');
    }
}
