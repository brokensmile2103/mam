@extends('layouts.app')
@section('title', 'Hôm nay · Mầm')
@section('page-title', 'Hôm nay')
@section('content')
@php
    $totalToday = $todayTasks->count() + $doneToday;
    $quick = ['an', 'ngu', 've_sinh', 'sua', 'suc_khoe', 'su_co'];
    $types = config('mam.entry_types');
@endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto space-y-5">
    <x-errors />

    <div>
        <h2 class="text-xl font-bold text-neutral-900">Xin chào, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->last() }} 👋</h2>
        <p class="text-sm text-neutral-500">{{ day_label(now()) }} · {{ now()->format('d/m/Y') }} · {{ $center->name }}</p>
    </div>

    @if($needsSetup)
        <div class="bg-white rounded-2xl border-2 border-dashed border-[var(--accent-ring)] p-5">
            <h3 class="font-bold text-neutral-900 mb-1"><i class="fa-solid fa-seedling text-[var(--accent)] mr-1.5"></i>Bắt đầu nào!</h3>
            @if($isOwner)
                <ol class="text-sm text-neutral-600 space-y-2 mt-3">
                    <li class="flex gap-2"><span class="w-6 h-6 rounded-full bg-[var(--accent-light)] text-[var(--accent-text)] text-xs font-bold flex items-center justify-center shrink-0">1</span>
                        <span>@if($needsClassrooms)<a href="{{ route('classrooms.index') }}" class="font-semibold text-[var(--accent-text)] hover:underline">Tạo lớp / nhóm tuổi</a> (VD: Nhóm 13–24 tháng) — có thể bỏ qua nếu chỉ có 1 nhóm.@else <span class="line-through text-neutral-400">Tạo lớp / nhóm tuổi</span> ✓ @endif</span></li>
                    <li class="flex gap-2"><span class="w-6 h-6 rounded-full bg-[var(--accent-light)] text-[var(--accent-text)] text-xs font-bold flex items-center justify-center shrink-0">2</span>
                        <span><a href="{{ route('children.create') }}" class="font-semibold text-[var(--accent-text)] hover:underline">Thêm hồ sơ các bé</a> — ngày sinh, phụ huynh, dị ứng.</span></li>
                    <li class="flex gap-2"><span class="w-6 h-6 rounded-full bg-[var(--accent-light)] text-[var(--accent-text)] text-xs font-bold flex items-center justify-center shrink-0">3</span>
                        <span><a href="{{ route('settings.index') }}" class="font-semibold text-[var(--accent-text)] hover:underline">Đặt học phí & tiền ăn</a>, rồi <a href="{{ route('staff.index') }}" class="font-semibold text-[var(--accent-text)] hover:underline">thêm tài khoản cô giáo</a>.</span></li>
                </ol>
            @else
                <p class="text-sm text-neutral-500">Chủ nhóm chưa thêm hồ sơ bé nào. Hãy nhờ chủ nhóm thiết lập trước nhé.</p>
            @endif
        </div>
    @endif

    {{-- Sĩ số hôm nay --}}
    <a href="{{ route('attendance.index') }}" class="block bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm hover:border-[var(--accent-ring)] transition">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-bold text-neutral-900"><i class="fa-solid fa-clipboard-user text-[var(--accent)] mr-1.5"></i>Sĩ số hôm nay</p>
            <span class="text-xs font-semibold text-[var(--accent-text)]">{{ $stats['unmarked'] > 0 ? 'Điểm danh ngay →' : 'Xem điểm danh →' }}</span>
        </div>
        <div class="grid grid-cols-4 gap-2 text-center">
            <div><p class="text-2xl font-extrabold text-emerald-600">{{ $stats['present'] }}</p><p class="text-[0.7rem] text-neutral-500 font-medium">Có mặt</p></div>
            <div><p class="text-2xl font-extrabold text-amber-600">{{ $stats['absent'] }}</p><p class="text-[0.7rem] text-neutral-500 font-medium">Nghỉ</p></div>
            <div><p class="text-2xl font-extrabold {{ $stats['unmarked'] ? 'text-blue-600' : 'text-neutral-300' }}">{{ $stats['unmarked'] }}</p><p class="text-[0.7rem] text-neutral-500 font-medium">Chưa điểm danh</p></div>
            <div><p class="text-2xl font-extrabold text-neutral-900">{{ $stats['total'] }}</p><p class="text-[0.7rem] text-neutral-500 font-medium">Tổng số bé</p></div>
        </div>
        @if($stats['present'] > 0 && now()->hour >= 15)
            <p class="text-xs text-neutral-500 mt-3 text-center">{{ $stats['not_out'] }} bé chưa được đón về.</p>
        @endif
    </a>

    {{-- Cảnh báo sức khoẻ / sự cố hôm nay --}}
    @if($alerts->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <p class="text-sm font-bold text-red-700 mb-2"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Cần báo phụ huynh</p>
            <div class="space-y-1.5">
                @foreach($alerts as $e)
                    <a href="{{ route('children.show', $e->child_id) }}" class="flex items-start gap-2 text-sm hover:underline">
                        <i class="fa-solid {{ $e->meta()['icon'] }} text-red-500 mt-0.5 w-4 text-center"></i>
                        <span class="text-neutral-800"><strong>{{ $e->child?->displayName() }}</strong> · {{ $e->meta()['label'] }}@if($e->summary(false)) — {{ $e->summary(false) }}@endif @if($e->note)<span class="text-neutral-500">“{{ \Illuminate\Support\Str::limit($e->note, 80) }}”</span>@endif <span class="text-xs text-neutral-400">{{ $e->occurred_at->format('H:i') }}</span></span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Dị ứng của các bé có mặt --}}
    @if($allergies->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <p class="text-sm font-bold text-amber-800 mb-2"><i class="fa-solid fa-hand mr-1.5"></i>Lưu ý dị ứng (bé có mặt hôm nay)</p>
            <div class="space-y-1">
                @foreach($allergies as $c)
                    <p class="text-sm text-neutral-800"><strong>{{ $c->displayName() }}</strong>@if($c->classroom) <span class="text-neutral-400 text-xs">({{ $c->classroom->name }})</span>@endif: {{ $c->allergies }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Ghi nhanh</p>
        <div class="grid grid-cols-3 gap-2">
            @foreach($quick as $type)
                @php $m = $types[$type]; @endphp
                <a href="{{ route('diary.create', ['loai' => $type]) }}" class="flex flex-col items-center gap-1.5 bg-white rounded-xl border border-neutral-200 shadow-sm py-3 px-1 hover:border-[var(--accent-ring)] active:scale-95 transition">
                    <span class="w-9 h-9 rounded-xl {{ color_badge($m['color']) }} flex items-center justify-center"><i class="fa-solid {{ $m['icon'] }}"></i></span>
                    <span class="text-xs font-semibold text-neutral-700 text-center leading-tight">{{ $m['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    @if($overdue->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-bold text-red-500 uppercase tracking-wider"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Quá hạn ({{ $overdue->count() }})</p>
                <form method="POST" action="{{ route('tasks.skip-overdue') }}" onsubmit="return confirm('Bỏ qua tất cả công việc quá hạn?')">
                    @csrf
                    <button class="text-xs text-neutral-500 hover:text-neutral-700 underline">Bỏ qua tất cả</button>
                </form>
            </div>
            <div class="space-y-2">
                @foreach($overdue as $task) @include('tasks._row', ['task' => $task]) @endforeach
            </div>
        </div>
    @endif

    <div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Việc hôm nay</p>
            <a href="{{ route('tasks.index') }}" class="text-xs font-semibold text-[var(--accent-text)] hover:underline">Xem tất cả</a>
        </div>
        @if($todayTasks->isEmpty())
            <div class="bg-white rounded-xl border border-neutral-200 px-4 py-6 text-center text-sm text-neutral-400">
                <i class="fa-solid fa-circle-check text-2xl text-emerald-400 mb-2 block"></i>
                @if($totalToday > 0) Đã xong hết việc hôm nay, giỏi quá! @else Hôm nay chưa có việc nào.
                    @if($isOwner) <a href="{{ route('routines.create') }}" class="text-[var(--accent-text)] font-semibold hover:underline">Tạo lịch định kỳ</a> (VD: cho bé uống thuốc 9h, khử khuẩn đồ chơi T6). @endif
                @endif
            </div>
        @else
            <div class="space-y-2">
                @foreach($todayTasks as $task) @include('tasks._row', ['task' => $task]) @endforeach
            </div>
        @endif
    </div>

    @if($menu->isNotEmpty())
        <a href="{{ route('menus.index') }}" class="block bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 hover:border-[var(--accent-ring)] transition">
            <p class="text-sm font-bold text-neutral-900 mb-2"><i class="fa-solid fa-utensils text-[var(--accent)] mr-1.5"></i>Thực đơn hôm nay</p>
            <div class="space-y-1 text-sm">
                @foreach(config('mam.meals') as $key => $label)
                    @if($menu->has($key))
                        <p><span class="text-neutral-400">{{ $label }}:</span> <span class="text-neutral-800">{{ $menu[$key]->dishes }}</span></p>
                    @endif
                @endforeach
            </div>
        </a>
    @endif

    @if($birthdays->isNotEmpty())
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <p class="text-sm font-bold text-neutral-900 mb-2"><i class="fa-solid fa-cake-candles text-rose-500 mr-1.5"></i>Sinh nhật sắp tới</p>
            <div class="space-y-1">
                @foreach($birthdays as $c)
                    @php $next = $c->nextBirthday(); @endphp
                    <a href="{{ route('children.show', $c->id) }}" class="flex items-center justify-between text-sm hover:underline">
                        <span class="text-neutral-800 font-medium">{{ $c->displayName() }}</span>
                        <span class="{{ $next->isToday() ? 'text-rose-600 font-bold' : 'text-neutral-500' }}">{{ $next->isToday() ? 'Hôm nay 🎉' : $next->format('d/m') }} · tròn {{ $next->year - $c->birth_date->year }} tuổi</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($isOwner && $unpaid && (int) $unpaid->n > 0)
        <a href="{{ route('invoices.index', ['loc' => 'unpaid']) }}" class="flex items-center justify-between bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 hover:border-[var(--accent-ring)] transition">
            <span class="text-sm font-bold text-neutral-900"><i class="fa-solid fa-file-invoice-dollar text-[var(--accent)] mr-1.5"></i>Học phí chưa thu ({{ $unpaid->n }} phiếu)</span>
            <span class="text-sm font-extrabold text-red-600">{{ money($unpaid->due) }}đ</span>
        </a>
    @endif

    @if($lowStock->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <p class="text-sm font-bold text-red-700 mb-2"><i class="fa-solid fa-box-open mr-1.5"></i>Kho sắp hết</p>
            <div class="space-y-1">
                @foreach($lowStock as $it)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-neutral-800">{{ $it->name }}</span>
                        <span class="text-red-600 font-semibold">còn {{ quantity($it->current_stock) }} {{ $it->unit }}</span>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('inventory.index') }}" class="inline-block mt-2 text-xs font-semibold text-red-700 hover:underline">Mở kho →</a>
        </div>
    @endif

    @if($recentEntries->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Vừa ghi nhận</p>
                <a href="{{ route('diary.report') }}" class="text-xs font-semibold text-[var(--accent-text)] hover:underline">Báo cáo ngày gửi phụ huynh</a>
            </div>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm px-4">
                @foreach($recentEntries as $entry) @include('diary._row', ['entry' => $entry, 'showChild' => true, 'sameDay' => true]) @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
