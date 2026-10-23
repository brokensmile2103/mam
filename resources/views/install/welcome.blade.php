@extends('layouts.guest')
@section('title', 'Cài đặt nền tảng · Mầm')
@section('robots', 'noindex, nofollow')
@section('subtitle', 'Cài đặt lần đầu')
@section('content')
    <p class="text-sm text-neutral-600 mb-5 leading-relaxed">
        Chào mừng đến với <strong>Mầm</strong> — bước này khởi tạo nền tảng và tài khoản Super Admin, chỉ chạy <strong>một lần duy nhất</strong>. Các cơ sở sẽ tự đăng ký riêng sau khi hoàn tất.
    </p>
    <a href="{{ route('install.requirements') }}">
        <x-button icon="fa-arrow-right">Bắt đầu cài đặt</x-button>
    </a>
@endsection
