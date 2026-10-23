@extends('layouts.guest')
@section('title', 'Đăng ký miễn phí · Mầm')
@section('meta_description', 'Tạo nhóm trẻ trên Mầm miễn phí — điểm danh, nhật ký bé, thực đơn, học phí cho nhóm trẻ gia đình ngay trên điện thoại.')
@section('subtitle', 'Tạo nhóm trẻ của bạn — miễn phí, dùng được ngay')
@section('content')
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <x-input name="center_name" label="Tên nhóm trẻ" icon="fa-house-chimney" placeholder="VD: Nhóm trẻ Hai Lúa" value="{{ old('center_name') }}" required autofocus autocomplete="off" />
        <x-input name="name" label="Tên của bạn" icon="fa-user" value="{{ old('name') }}" required autocomplete="name" />
        <x-input name="phone" label="Số điện thoại (dùng để đăng nhập)" icon="fa-phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" />
        <x-input name="password" type="password" label="Mật khẩu (tối thiểu 8 ký tự)" icon="fa-lock" required autocomplete="new-password" />
        <x-input name="password_confirmation" type="password" label="Nhập lại mật khẩu" icon="fa-lock" required autocomplete="new-password" />

        <label class="flex items-start gap-2.5 cursor-pointer">
            <input type="checkbox" name="terms" value="1" required
                   class="w-4 h-4 mt-0.5 rounded border-neutral-300 text-pink-600 focus:ring-pink-500 shrink-0">
            <span class="text-sm text-neutral-600 leading-relaxed">
                Tôi đã đọc và đồng ý với
                <a href="{{ route('pages.terms') }}" target="_blank" class="text-pink-600 font-medium hover:underline">Điều khoản sử dụng</a>
                và
                <a href="{{ route('pages.privacy') }}" target="_blank" class="text-pink-600 font-medium hover:underline">Chính sách bảo mật</a>
                của Mầm.
            </span>
        </label>

        <x-form-guard name="register" />

        <x-button icon="fa-rocket" class="mt-2">Tạo nhóm trẻ ngay</x-button>
    </form>

    @if(config('services.google.client_id'))
        <div class="flex items-center gap-4 my-5">
            <div class="flex-1 h-px bg-neutral-200"></div>
            <span class="text-xs text-neutral-400 font-medium px-2">HOẶC</span>
            <div class="flex-1 h-px bg-neutral-200"></div>
        </div>

        <a href="{{ route('auth.google') }}"
           class="flex items-center justify-center gap-2.5 w-full rounded-xl border border-neutral-300 bg-white hover:bg-neutral-50 text-neutral-700 py-3 px-4 text-sm font-semibold transition">
            <i class="fa-brands fa-google text-[#EA4335]"></i>Tiếp tục với Google
        </a>
    @endif

    <p class="text-center text-sm text-neutral-500 mt-5">
        Đã có tài khoản? <a href="{{ route('login') }}" class="text-pink-600 font-semibold hover:underline">Đăng nhập</a>
    </p>
    <p class="text-center text-xs text-neutral-400 mt-2">
        <a href="{{ route('pages.about') }}" class="hover:underline">Giới thiệu</a> ·
        <a href="{{ route('pages.terms') }}" class="hover:underline">Điều khoản</a> ·
        <a href="{{ route('pages.privacy') }}" class="hover:underline">Chính sách bảo mật</a>
    </p>
@endsection
