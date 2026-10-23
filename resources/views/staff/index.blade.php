@extends('layouts.app')
@section('title', 'Cô giáo · Mầm')
@section('page-title', 'Cô giáo / bảo mẫu')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto" x-data="{ adding: {{ $errors->any() ? 'true' : 'false' }}, pw: null }">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-users"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Cô giáo / bảo mẫu</h2>
        <button type="button" @click="adding = !adding" class="px-3.5 py-2 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Thêm</button>
    </div>
    <p class="text-xs text-neutral-500 mb-5">Cô giáo đăng nhập bằng số điện thoại để điểm danh, ghi nhật ký bé, làm việc hằng ngày và xem hồ sơ bé (kể cả SĐT phụ huynh). Cô không xem được học phí, chi phí và báo cáo thu/chi.</p>

    <form method="POST" action="{{ route('staff.store') }}" x-show="adding" x-cloak class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm space-y-3 mb-4">
        @csrf
        <x-input name="name" label="Họ tên" icon="fa-user" value="{{ old('name') }}" required maxlength="100" />
        <x-input type="tel" name="phone" label="Số điện thoại (dùng để đăng nhập)" icon="fa-phone" value="{{ old('phone') }}" required maxlength="20" />
        <x-input type="text" name="password" label="Mật khẩu (từ 8 ký tự)" icon="fa-lock" required minlength="8" autocomplete="new-password" />
        <x-button icon="fa-user-plus">Tạo tài khoản</x-button>
    </form>

    <div class="space-y-2">
        @forelse($staff as $s)
            <div class="bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3 {{ $s->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-neutral-100 text-neutral-500 flex items-center justify-center font-bold shrink-0">{{ mb_strtoupper(mb_substr($s->name, 0, 1)) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-neutral-900 truncate">{{ $s->name }}</div>
                        <div class="text-xs text-neutral-500">{{ $s->phone }} @unless($s->is_active)· <span class="text-red-500 font-semibold">đã khoá</span>@endunless</div>
                    </div>
                </div>
                <div class="flex items-center gap-1 mt-2 pt-2 border-t border-neutral-100 text-xs font-semibold text-neutral-500">
                    <button type="button" @click="pw = pw === {{ $s->id }} ? null : {{ $s->id }}" class="px-2.5 py-1.5 rounded-lg hover:bg-neutral-100"><i class="fa-solid fa-key mr-1"></i>Đổi mật khẩu</button>
                    <form method="POST" action="{{ route('staff.toggle', $s->id) }}">@csrf<button class="px-2.5 py-1.5 rounded-lg hover:bg-neutral-100"><i class="fa-solid {{ $s->is_active ? 'fa-lock' : 'fa-lock-open' }} mr-1"></i>{{ $s->is_active ? 'Khoá' : 'Mở khoá' }}</button></form>
                    <form method="POST" action="{{ route('staff.remove', $s->id) }}" onsubmit="return confirm('Gỡ cô này khỏi nhóm trẻ?')" class="ml-auto">@csrf @method('DELETE')<button class="px-2.5 py-1.5 rounded-lg hover:bg-red-50 text-red-500"><i class="fa-solid fa-user-minus mr-1"></i>Gỡ</button></form>
                </div>
                <form method="POST" action="{{ route('staff.reset-password', $s->id) }}" x-show="pw === {{ $s->id }}" x-cloak class="mt-3 flex gap-2">
                    @csrf
                    <input type="text" name="password" placeholder="Mật khẩu mới (từ 8 ký tự)" required minlength="8" autocomplete="new-password" class="flex-1 rounded-xl border border-neutral-300 py-2.5 px-3 text-sm">
                    <button class="px-4 rounded-xl bg-neutral-900 text-white text-sm font-semibold">Lưu</button>
                </form>
            </div>
        @empty
            <div class="text-center py-12 text-neutral-400"><i class="fa-solid fa-users text-3xl mb-2 block"></i><p class="text-sm">Chưa có tài khoản cô giáo nào. Bạn đang dùng một mình.</p></div>
        @endforelse
    </div>
</div>
@endsection
