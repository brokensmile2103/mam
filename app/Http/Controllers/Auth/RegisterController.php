<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\User;
use App\Services\FormGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * CLOUD — đăng ký công khai: tạo cùng lúc tài khoản chủ nhóm (role=owner) + nhóm trẻ đầu tiên.
 * Giai đoạn đầu KHÔNG giới hạn tính năng theo gói cước.
 */
class RegisterController extends Controller
{
    private const WELCOME = 'Chào mừng đến với Mầm! Bắt đầu bằng việc thêm hồ sơ các bé nhé.';

    public function show(): Response
    {
        // Trang chứa token FormGuard phải luôn mới — không để bất kỳ lớp cache nào lưu lại.
        return response()
            ->view('auth.register')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isBotOrThrottled($request, 'register')) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['center_name' => $this->guardMessage($request, 'register')]);
        }

        $request->merge(['phone' => normalize_phone($request->input('phone'))]);

        $data = $request->validate([
            'center_name' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?\d{8,15}$/', 'unique:users,phone'],
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'Bạn cần đồng ý với Điều khoản sử dụng và Chính sách bảo mật để tiếp tục đăng ký.',
            'phone.regex' => 'Số điện thoại không hợp lệ (VD: 0912345678).',
        ]);

        [$owner, $center] = $this->createOwnerWithCenter($data['name'], $data['phone'], Hash::make($data['password']), $data['center_name']);

        return $this->finish($request, $owner, $center);
    }

    /** Bước "Hoàn tất đăng ký" sau khi đăng nhập Google lần đầu — bảng users bắt buộc có số điện thoại. */
    public function showGooglePhone(Request $request): RedirectResponse|Response
    {
        $pending = $request->session()->get('google_pending');

        if (! $pending) {
            return redirect()->route('register');
        }

        return response()
            ->view('auth.google-phone', ['pending' => $pending])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function storeGooglePhone(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('google_pending');

        if (! $pending) {
            return redirect()->route('register');
        }

        if ($this->isBotOrThrottled($request, 'register-google-phone')) {
            return back()->withErrors(['center_name' => $this->guardMessage($request, 'register-google-phone')]);
        }

        $request->merge(['phone' => normalize_phone($request->input('phone'))]);

        $data = $request->validate([
            'center_name' => 'required|string|max:100',
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?\d{8,15}$/', 'unique:users,phone'],
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'Bạn cần đồng ý với Điều khoản sử dụng và Chính sách bảo mật để tiếp tục đăng ký.',
            'phone.regex' => 'Số điện thoại không hợp lệ (VD: 0912345678).',
        ]);

        [$owner, $center] = $this->createOwnerWithCenter(
            $pending['name'],
            $data['phone'],
            // Đăng nhập Google không dùng mật khẩu này — cột password NOT NULL nên dùng chuỗi ngẫu nhiên.
            Hash::make(Str::random(40)),
            $data['center_name'],
            [
                'email' => $pending['email'],
                'email_verified_at' => $pending['email'] ? now() : null,
                'google_id' => $pending['google_id'],
                'avatar_url' => $pending['avatar_url'],
            ],
        );

        $request->session()->forget('google_pending');

        return $this->finish($request, $owner, $center);
    }

    // ---------------------------------------------------------------------

    /** @return array{0: User, 1: Center} */
    private function createOwnerWithCenter(string $name, string $phone, string $passwordHash, string $centerName, array $extra = []): array
    {
        return DB::transaction(function () use ($name, $phone, $passwordHash, $centerName, $extra) {
            $owner = User::forceCreate([
                'name' => $name,
                'phone' => $phone,
                'password' => $passwordHash,
                'role' => 'owner',
                'is_active' => true,
            ] + $extra);

            $center = Center::forceCreate([
                'owner_id' => $owner->id,
                'name' => $centerName,
                'is_active' => true,
            ]);

            $center->staff()->attach($owner->id);

            return [$owner, $center];
        });
    }

    private function finish(Request $request, User $owner, Center $center): RedirectResponse
    {
        // Đăng nhập SAU KHI transaction đã commit — tránh session trỏ tới user đã bị rollback.
        Auth::login($owner);
        $request->session()->regenerate();
        $request->session()->put('current_center_id', $center->id);

        return redirect()->route('today')->with('status', self::WELCOME);
    }

    /** Honeypot + token thời gian ký HMAC, rồi giới hạn tần suất theo IP. true = chặn. */
    private function isBotOrThrottled(Request $request, string $formName): bool
    {
        $isBot = ! FormGuard::verify($request, $formName, $request->input('form_ts'), $request->input('form_sig'), $request->input('hp_key'));

        if ($isBot) {
            return true;
        }

        $throttleKey = 'register:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            return true;
        }

        RateLimiter::hit($throttleKey, 3600);

        return false;
    }

    private function guardMessage(Request $request, string $formName): string
    {
        $throttleKey = 'register:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return "Bạn thao tác quá nhiều lần. Vui lòng thử lại sau khoảng {$minutes} phút.";
        }

        return 'Phiên đăng ký đã hết hạn hoặc có lỗi xảy ra. Vui lòng tải lại trang và thử lại.';
    }
}
