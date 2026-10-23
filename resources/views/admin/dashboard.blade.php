@extends('layouts.admin')
@section('title', 'Danh sách nhóm trẻ · Super Admin')
@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <h1 class="text-xl font-bold text-neutral-900">Toàn bộ nhóm trẻ trên nền tảng</h1>
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" id="admin-search" value="{{ request('q') }}" placeholder="Tìm theo tên nhóm trẻ, SĐT chủ nhóm..." autocomplete="off"
                   class="rounded-xl border border-neutral-300 bg-white text-neutral-900 placeholder-neutral-400 py-2 px-4 text-sm w-full sm:w-72 focus:border-neutral-500 focus:outline-none">
        </form>
    </div>

    <div class="grid grid-cols-3 gap-2 sm:gap-3 mb-6">
        <div class="bg-white rounded-2xl p-3 sm:p-4 border border-neutral-200">
            <p class="text-xs text-neutral-500 font-medium mb-1">Tổng số nhóm trẻ</p>
            <p class="text-xl sm:text-2xl font-bold text-neutral-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3 sm:p-4 border border-neutral-200">
            <p class="text-xs text-neutral-500 font-medium mb-1">Đang hoạt động</p>
            <p class="text-xl sm:text-2xl font-bold text-emerald-600">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3 sm:p-4 border border-neutral-200">
            <p class="text-xs text-neutral-500 font-medium mb-1">Đang tạm khoá</p>
            <p class="text-xl sm:text-2xl font-bold text-red-500">{{ $stats['inactive'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden">
        @forelse($centers as $f)
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-5 py-4 border-b border-neutral-100 last:border-0">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-neutral-900">{{ $f->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $f->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">
                            {{ $f->is_active ? 'Hoạt động' : 'Đã khoá' }}
                        </span>
                        @php
                            $daysSince = $f->last_event_at ? \Illuminate\Support\Carbon::parse($f->last_event_at)->diffInDays(now()) : null;
                        @endphp
                        @if(is_null($daysSince))
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-neutral-100 text-neutral-500">Chưa ghi nhật ký</span>
                        @elseif($daysSince > 14)
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-amber-50 text-amber-700">{{ (int) $daysSince }} ngày không hoạt động</span>
                        @endif
                    </div>
                    <p class="text-sm text-neutral-500 mt-0.5">
                        Chủ nhóm: {{ $f->owner?->name ?? '(đã xoá)' }} · {{ $f->owner?->phone }}
                        · {{ $f->children_count }} bé · {{ $f->staff_count }} nhân sự · Đăng ký {{ $f->created_at->format('d/m/Y') }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.centers.show', $f->id) }}"
                       class="text-xs px-3 py-1.5 rounded-lg font-medium bg-neutral-100 text-neutral-700 hover:bg-neutral-200 transition">
                        Xem chi tiết
                    </a>
                    <form method="POST" action="{{ route('admin.centers.toggle', $f->id) }}" class="shrink-0"
                          onsubmit="return confirm({{ \Illuminate\Support\Js::from(($f->is_active ? 'Tạm khoá' : 'Mở lại').' nhóm trẻ "'.$f->name.'"?') }})">
                        @csrf @method('PUT')
                        <button class="text-xs px-3 py-1.5 rounded-lg font-medium transition {{ $f->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                            {{ $f->is_active ? 'Tạm khoá' : 'Mở lại' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-center py-10 text-neutral-400 text-sm">Chưa có nhóm trẻ nào đăng ký.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $centers->links() }}</div>
@endsection
