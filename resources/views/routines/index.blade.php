@extends('layouts.app')
@section('title', 'Lịch định kỳ · Mầm')
@section('page-title', 'Lịch định kỳ')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-calendar-check"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Công việc định kỳ</h2>
        <a href="{{ route('routines.create') }}" class="px-3.5 py-2 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Tạo lịch</a>
    </div>
    <p class="text-xs text-neutral-500 mb-5">Tạo một lần, hệ thống tự sinh việc mỗi ngày ở mục "Hôm nay" (cho bé uống thuốc, khử khuẩn đồ chơi, cân đo hằng tháng…).</p>

    <div class="space-y-2">
        @forelse($templates as $t)
            @php $m = $t->meta(); @endphp
            <div class="bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3.5 {{ $t->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl {{ color_badge($m['color']) }} flex items-center justify-center shrink-0"><i class="fa-solid {{ $m['icon'] }}"></i></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-neutral-900">{{ $t->title }}</div>
                        <div class="text-xs text-neutral-600">{{ $t->scheduleLabel() }}</div>
                        <div class="text-xs text-neutral-500">{{ $t->targetLabel() }}@if($t->item) · {{ $t->item->name }}@if((float) $t->item_quantity > 0) {{ quantity($t->item_quantity, 2) }} {{ $t->item->unit }}@endif @endif</div>
                        @if($t->ends_on)<div class="text-xs text-neutral-400">Đến hết {{ $t->ends_on->format('d/m/Y') }}</div>@endif
                    </div>
                </div>
                <div class="flex items-center gap-1 mt-2 pt-2 border-t border-neutral-100 text-xs font-semibold text-neutral-500">
                    <a href="{{ route('routines.edit', $t->id) }}" class="px-2.5 py-1.5 rounded-lg hover:bg-neutral-100"><i class="fa-solid fa-pen mr-1"></i>Sửa</a>
                    <form method="POST" action="{{ route('routines.toggle', $t->id) }}">@csrf<button class="px-2.5 py-1.5 rounded-lg hover:bg-neutral-100"><i class="fa-solid {{ $t->is_active ? 'fa-pause' : 'fa-play' }} mr-1"></i>{{ $t->is_active ? 'Tạm dừng' : 'Bật lại' }}</button></form>
                    <form method="POST" action="{{ route('routines.destroy', $t->id) }}" onsubmit="return confirm('Xoá lịch này? Các việc chưa làm sắp tới cũng bị xoá.')" class="ml-auto">@csrf @method('DELETE')<button class="px-2.5 py-1.5 rounded-lg hover:bg-red-50 text-red-500"><i class="fa-solid fa-trash-can"></i></button></form>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-neutral-400">
                <i class="fa-solid fa-calendar-check text-3xl mb-2 block"></i>
                <p class="text-sm">Chưa có lịch nào. Bấm "Tạo lịch" để bắt đầu.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
