@props(['count' => 0, 'color' => 'accent', 'inverted' => false])
{{--
    Badge số nhỏ gắn lên icon/label của menu — TỰ ẨN khi count = 0 (không hiện "0" để tránh cảnh báo giả).
    color: 'accent' (theo dõi), 'danger' (cần xử lý, VD: kho sắp hết), 'info'.
    inverted = true khi hàng menu đang active (nền đã là màu accent) → badge đổi sang nền trắng.
--}}
@php
    $solid = ['accent' => 'bg-[var(--accent)] text-white', 'danger' => 'bg-red-500 text-white', 'info' => 'bg-blue-600 text-white'];
    $inv = ['accent' => 'bg-white text-[var(--accent-text)]', 'danger' => 'bg-white text-red-600', 'info' => 'bg-white text-blue-600'];
    $classes = ($inverted ? $inv : $solid)[$color] ?? $solid['accent'];
@endphp
@if($count > 0)
    <span {{ $attributes->merge(['class' => "inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[0.65rem] font-bold leading-none $classes"]) }}>
        {{ $count > 99 ? '99+' : $count }}
    </span>
@endif
