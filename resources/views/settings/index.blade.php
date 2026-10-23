@extends('layouts.app')
@section('title', 'Cài đặt · Mầm')
@section('page-title', 'Cài đặt')
@section('content')
@php $hex = ['pink' => '#db2777', 'emerald' => '#059669', 'amber' => '#d97706', 'orange' => '#ea580c', 'blue' => '#2563eb', 'rose' => '#e11d48', 'violet' => '#7c3aed']; @endphp
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto space-y-5">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-gear"></i></div>
        <h2 class="text-lg font-bold text-neutral-900">Cài đặt nhóm trẻ</h2>
    </div>

    <form method="POST" action="{{ route('settings.center') }}" class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
        @csrf @method('PUT')
        <p class="text-sm font-bold text-neutral-900">Thông tin nhóm trẻ</p>
        <x-input name="name" label="Tên nhóm trẻ" icon="fa-house-chimney" value="{{ old('name', $center->name) }}" required maxlength="100" />
        <x-input name="address" label="Địa chỉ" icon="fa-location-dot" value="{{ old('address', $center->address) }}" maxlength="255" />
        <x-input type="tel" name="phone" label="Số điện thoại liên hệ" icon="fa-phone" value="{{ old('phone', $center->phone) }}" maxlength="20" />
        <x-button icon="fa-floppy-disk">Lưu</x-button>
    </form>

    @php $billingMode = old('tuition_mode', $center->tuitionMode()); @endphp
    <form method="POST" action="{{ route('settings.billing') }}" class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4" x-data="{ mode: @js($billingMode) }">
        @csrf @method('PUT')
        <div>
            <p class="text-sm font-bold text-neutral-900">Học phí & chuyển khoản</p>
            <p class="text-xs text-neutral-500 mt-0.5">Mức chung cho mọi bé — từng bé có thể đặt cách tính và mức riêng ở hồ sơ.</p>
        </div>
        @if($errors->hasAny(['tuition_mode', 'tuition_default', 'daily_fee_default', 'meal_rate_default', 'bank_code', 'bank_account', 'bank_holder', 'bank_memo']))
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-3 py-2 text-xs">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
        @endif
        <div>
            <p class="text-sm font-medium text-neutral-700 mb-2">Cách tính học phí</p>
            <div class="grid grid-cols-2 gap-2">
                @foreach(config('mam.tuition_modes') as $k => $m)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="tuition_mode" value="{{ $k }}" x-model="mode" class="peer sr-only" @checked($billingMode === $k)>
                        <span class="block h-full rounded-xl border-2 border-neutral-200 px-3 py-2.5 transition peer-checked:border-[var(--accent)] peer-checked:bg-[var(--accent-light)] peer-focus-visible:ring-2 peer-focus-visible:ring-[var(--accent-ring)]">
                            <span class="flex items-center gap-1.5 text-sm font-bold text-neutral-900"><i class="fa-solid {{ $k === 'daily' ? 'fa-calendar-day' : 'fa-calendar' }} text-[var(--accent-text)]"></i>{{ $m['label'] }}</span>
                            <span class="block text-xs text-neutral-500 mt-0.5 leading-snug">{{ $m['hint'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="transition" :class="mode === 'daily' && 'opacity-60'">
                <x-input type="text" inputmode="numeric" class="money-input" name="tuition_default" label="Học phí / tháng (đ)" value="{{ old('tuition_default', (int) $center->tuition_default ?: '') }}" placeholder="VD: 2.500.000" />
            </div>
            <div class="transition" :class="mode === 'monthly' && 'opacity-60'">
                <x-input type="text" inputmode="numeric" class="money-input" name="daily_fee_default" label="Học phí / ngày (đ)" value="{{ old('daily_fee_default', (int) $center->daily_fee_default ?: '') }}" placeholder="VD: 150.000" />
            </div>
        </div>
        <p class="text-xs text-neutral-400 -mt-2" x-text="mode === 'daily'
            ? 'Gửi ngày: học phí = số ngày bé có mặt trong tháng (theo điểm danh) × học phí/ngày. Mức tháng chỉ dùng cho bé đặt riêng kiểu gửi tháng.'
            : 'Gửi tháng: học phí cố định mỗi tháng. Mức ngày chỉ dùng cho bé đặt riêng kiểu gửi ngày (VD: bé gửi theo buổi).'"></p>
        <x-input type="text" inputmode="numeric" class="money-input" name="meal_rate_default" label="Tiền ăn / ngày (đ)" value="{{ old('meal_rate_default', (int) $center->meal_rate_default ?: '') }}" placeholder="VD: 35.000" />
        <p class="text-xs text-neutral-400 -mt-2">Tiền ăn tính theo số ngày bé có mặt trong tháng. Để trống nếu học phí đã gồm tiền ăn.</p>
        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-1"><x-input name="bank_code" label="Ngân hàng" placeholder="VCB, MB…" value="{{ old('bank_code', $center->bank_code) }}" maxlength="20" /></div>
            <div class="col-span-2"><x-input name="bank_account" label="Số tài khoản" inputmode="numeric" value="{{ old('bank_account', $center->bank_account) }}" maxlength="40" /></div>
        </div>
        <x-input name="bank_holder" label="Tên chủ tài khoản" placeholder="NGUYEN THI HOA" value="{{ old('bank_holder', $center->bank_holder) }}" maxlength="100" />
        <x-input name="bank_memo" label="Nội dung chuyển khoản" placeholder="HP {month} {child}" value="{{ old('bank_memo', $center->bankMemoTemplate()) }}" maxlength="50" />
        <p class="text-xs text-neutral-400 -mt-2">Điền sẵn vào mã QR. Dùng <code>{month}</code> (tháng, VD 2610) và <code>{child}</code> (tên bé không dấu) — VD <code>HP {month} {child}</code> → HP 2610 TRAN GIA BAO. Để trống nếu muốn phụ huynh tự nhập hoặc ngân hàng tự điền.</p>
        <p class="text-xs text-neutral-400 -mt-2">Nhập mã ngân hàng viết tắt (VCB, MB, TCB, ACB, BIDV, VPB, TPB…) để phiếu học phí hiện mã QR — phụ huynh quét là điền sẵn số tiền và nội dung.</p>
        <x-input name="invoice_note" label="Lời nhắn cuối phiếu học phí" placeholder="VD: Bố mẹ vui lòng đóng trước ngày 05 hằng tháng. Cảm ơn ạ!" value="{{ old('invoice_note', $center->invoice_note) }}" maxlength="500" />
        <x-button icon="fa-floppy-disk">Lưu học phí</x-button>
    </form>

    <form method="POST" action="{{ route('settings.appearance') }}" class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
        @csrf @method('PUT')
        <p class="text-sm font-bold text-neutral-900">Giao diện</p>
        <div>
            <p class="text-sm font-medium text-neutral-700 mb-2">Màu chủ đạo</p>
            <div class="flex flex-wrap gap-3">
                @foreach(config('mam.accent_colors') as $k => $label)
                    <label class="relative cursor-pointer text-center">
                        <input type="radio" name="accent_color" value="{{ $k }}" class="peer sr-only" @checked(old('accent_color', $center->accent_color ?: 'pink') === $k)>
                        <span class="block w-11 h-11 rounded-full ring-2 ring-transparent ring-offset-2 peer-checked:ring-neutral-900 transition" style="background: {{ $hex[$k] ?? '#db2777' }}"></span>
                        <span class="block text-[0.65rem] text-neutral-500 mt-1">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <p class="text-sm font-medium text-neutral-700 mb-2">Cỡ chữ</p>
            <div class="grid grid-cols-3 gap-1 bg-neutral-100 rounded-xl p-1">
                @foreach(['sm' => 'Nhỏ', 'md' => 'Vừa', 'lg' => 'Lớn'] as $k => $l)
                    <label class="relative cursor-pointer"><input type="radio" name="font_scale" value="{{ $k }}" class="peer sr-only" @checked(old('font_scale', $center->font_scale ?: 'md') === $k)><span class="block text-center text-sm font-semibold py-2 rounded-lg text-neutral-500 peer-checked:bg-white peer-checked:text-neutral-900 peer-checked:shadow-sm transition">{{ $l }}</span></label>
                @endforeach
            </div>
        </div>
        <x-button icon="fa-palette">Lưu giao diện</x-button>
    </form>

    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-3">
        <p class="text-sm font-bold text-neutral-900">Dữ liệu của bạn</p>
        <p class="text-xs text-neutral-500">Tải toàn bộ dữ liệu của nhóm trẻ (hồ sơ bé, điểm danh, nhật ký, kho, học phí, chi phí) về máy bất cứ lúc nào.</p>
        <a href="{{ route('settings.data-export') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl py-3 font-semibold text-base bg-neutral-100 hover:bg-neutral-200 text-neutral-800 transition"><i class="fa-solid fa-download"></i>Tải dữ liệu nhóm trẻ</a>
    </div>

    <div class="grid grid-cols-2 gap-2">
        <a href="{{ route('centers.index') }}" class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-white border border-neutral-200 text-neutral-700 text-sm font-semibold hover:bg-neutral-50"><i class="fa-solid fa-house-chimney"></i>Các cơ sở của tôi</a>
        <a href="{{ route('profile.edit') }}" class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-white border border-neutral-200 text-neutral-700 text-sm font-semibold hover:bg-neutral-50"><i class="fa-solid fa-user"></i>Tài khoản</a>
    </div>
</div>
@endsection
