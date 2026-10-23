@extends('layouts.app')
@section('title', 'Điểm danh · Mầm')
@section('page-title', 'Điểm danh')
@section('content')
@php
    $statuses = config('mam.attendance_statuses');
    $isToday = $date->isToday();
    $btn = [
        'co_mat' => 'bg-emerald-600 border-emerald-600 text-white',
        'vang_phep' => 'bg-amber-500 border-amber-500 text-white',
        'vang' => 'bg-red-500 border-red-500 text-white',
    ];
@endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <x-errors class="mb-4" />
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-clipboard-user"></i></div>
        <div class="flex-1 min-w-0">
            <h2 class="text-lg font-bold text-neutral-900">Điểm danh</h2>
            <p class="text-xs text-neutral-500">{{ day_label($date) }}{{ $isToday ? ' · '.$date->format('d/m/Y') : '' }}</p>
        </div>
    </div>

    <form method="GET" class="grid grid-cols-2 gap-2 mb-4">
        <input type="date" name="ngay" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
        <select name="lop" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
            <option value="">Tất cả các lớp</option>
            @foreach($classrooms as $cl)<option value="{{ $cl->id }}" @selected($classroomId === $cl->id)>{{ $cl->name }}</option>@endforeach
        </select>
    </form>

    <div class="grid grid-cols-4 gap-2 mb-4 text-center">
        <div class="bg-white rounded-xl border border-neutral-200 py-2"><p class="text-lg font-extrabold text-emerald-600">{{ $stats['present'] }}</p><p class="text-[0.65rem] text-neutral-500">Có mặt</p></div>
        <div class="bg-white rounded-xl border border-neutral-200 py-2"><p class="text-lg font-extrabold text-amber-600">{{ $stats['absent'] }}</p><p class="text-[0.65rem] text-neutral-500">Nghỉ</p></div>
        <div class="bg-white rounded-xl border border-neutral-200 py-2"><p class="text-lg font-extrabold {{ $stats['unmarked'] ? 'text-blue-600' : 'text-neutral-300' }}">{{ $stats['unmarked'] }}</p><p class="text-[0.65rem] text-neutral-500">Chưa điểm danh</p></div>
        <div class="bg-white rounded-xl border border-neutral-200 py-2"><p class="text-lg font-extrabold text-neutral-700">{{ $stats['out'] }}</p><p class="text-[0.65rem] text-neutral-500">Đã về</p></div>
    </div>

    @if($stats['unmarked'] > 0)
        <form method="POST" action="{{ route('attendance.mark-all') }}" class="mb-4" onsubmit="return confirm('Đánh dấu CÓ MẶT cho {{ $stats['unmarked'] }} bé chưa điểm danh?')">
            @csrf
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            @if($classroomId)<input type="hidden" name="classroom_id" value="{{ $classroomId }}">@endif
            <button class="w-full rounded-xl border-2 border-dashed border-emerald-300 bg-emerald-50 text-emerald-700 font-semibold text-sm py-3 hover:bg-emerald-100 transition">
                <i class="fa-solid fa-check-double mr-1"></i>Các bé còn lại đều có mặt ({{ $stats['unmarked'] }})
            </button>
        </form>
    @endif

    <div class="space-y-2">
        @forelse($children as $child)
            @php $row = $rows->get($child->id); @endphp
            <div id="be-{{ $child->id }}" class="scroll-mt-24 bg-white rounded-2xl border {{ $row ? 'border-neutral-200' : 'border-blue-200' }} shadow-sm p-3" x-data="{ edit: false, out: false }">
                <div class="flex items-center gap-3">
                    <a href="{{ route('children.show', $child->id) }}" class="w-10 h-10 rounded-full bg-[var(--accent-light)] text-[var(--accent-text)] text-sm font-bold flex items-center justify-center shrink-0">{{ $child->initials() }}</a>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-neutral-900 truncate">{{ $child->displayName() }} @if($child->hasAllergy())<i class="fa-solid fa-hand text-amber-500 text-xs" title="Dị ứng: {{ $child->allergies }}"></i>@endif</p>
                        <p class="text-xs text-neutral-500 truncate">
                            {{ $child->ageLabel() }}@if($child->classroom) · {{ $child->classroom->name }}@endif
                            @unless($child->isActive()) · <span class="text-neutral-400">nghỉ từ {{ $child->left_on?->format('d/m') }}</span>@endunless
                            @if($row?->isPresent())
                                · <span class="text-emerald-700 font-medium">đến {{ $row->checkInLabel() ?? '—' }}</span>
                                @if($row->check_out) · <span class="text-neutral-700 font-medium">về {{ $row->checkOutLabel() }}{{ $row->picked_up_by ? ' ('.$row->picked_up_by.')' : '' }}</span>@endif
                            @endif
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-1.5 mt-2.5">
                    @foreach($statuses as $key => $s)
                        <form method="POST" action="{{ route('attendance.mark') }}">
                            @csrf
                            <input type="hidden" name="child_id" value="{{ $child->id }}">
                            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                            <input type="hidden" name="status" value="{{ $key }}">
                            <button class="w-full text-xs font-semibold py-2.5 rounded-lg border transition {{ $row?->status === $key ? $btn[$key] : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">
                                <i class="fa-solid {{ $s['icon'] }} mr-0.5"></i>{{ $s['short'] }}
                            </button>
                        </form>
                    @endforeach
                </div>

                @if($row?->isPresent())
                    <div class="flex items-center gap-2 mt-2">
                        @if(! $row->check_out)
                            <button type="button" @click="out = !out" class="whitespace-nowrap text-xs px-3 py-1.5 rounded-lg bg-neutral-900 text-white font-semibold"><i class="fa-solid fa-person-walking-arrow-right mr-1"></i>Bé về</button>
                        @endif
                        <button type="button" @click="edit = !edit" class="whitespace-nowrap text-xs px-3 py-1.5 rounded-lg bg-neutral-100 text-neutral-600 font-medium"><i class="fa-solid fa-clock mr-1"></i>Sửa giờ</button>
                        @include('attendance._undo', ['row' => $row])
                    </div>

                    @if(! $row->check_out)
                        <form x-show="out" x-cloak method="POST" action="{{ route('attendance.check-out', $row->id) }}" class="mt-2 grid grid-cols-3 gap-2">
                            @csrf
                            <input type="time" name="check_out" value="{{ now()->format('H:i') }}" class="rounded-lg border border-neutral-300 py-2 px-2 text-sm">
                            <input type="text" name="picked_up_by" list="pickup-{{ $child->id }}" placeholder="Ai đón?" maxlength="100" class="rounded-lg border border-neutral-300 py-2 px-2 text-sm">
                            <datalist id="pickup-{{ $child->id }}">
                                @foreach(array_filter([$child->parent_relation ?: null, $child->parent_name, $child->parent2_name, 'Bố', 'Mẹ', 'Ông', 'Bà']) as $p)<option value="{{ $p }}">@endforeach
                            </datalist>
                            <button class="rounded-lg bg-[var(--accent)] text-white text-sm font-semibold">Lưu</button>
                            @if($child->pickup_note)<p class="col-span-3 text-xs text-amber-700"><i class="fa-solid fa-circle-info mr-1"></i>Người được đón: {{ $child->pickup_note }}</p>@endif
                        </form>
                    @endif

                    <form x-show="edit" x-cloak method="POST" action="{{ route('attendance.update', $row->id) }}" class="mt-2 grid grid-cols-2 gap-2">
                        @csrf @method('PUT')
                        <label class="text-xs text-neutral-500">Giờ đến<input type="time" name="check_in" value="{{ $row->checkInLabel() }}" class="mt-1 w-full rounded-lg border border-neutral-300 py-2 px-2 text-sm"></label>
                        <label class="text-xs text-neutral-500">Giờ về<input type="time" name="check_out" value="{{ $row->checkOutLabel() }}" class="mt-1 w-full rounded-lg border border-neutral-300 py-2 px-2 text-sm"></label>
                        <input type="text" name="picked_up_by" value="{{ $row->picked_up_by }}" placeholder="Người đón" maxlength="100" class="rounded-lg border border-neutral-300 py-2 px-2 text-sm">
                        <input type="text" name="note" value="{{ $row->note }}" placeholder="Ghi chú" maxlength="255" class="rounded-lg border border-neutral-300 py-2 px-2 text-sm">
                        <button class="col-span-2 rounded-lg bg-[var(--accent)] text-white text-sm font-semibold py-2">Lưu giờ</button>
                    </form>
                @elseif($row)
                    <div class="flex items-center justify-between gap-2 mt-2">
                        <p class="text-xs text-neutral-500 italic truncate">@if($row->note)“{{ $row->note }}”@endif</p>
                        @include('attendance._undo', ['row' => $row])
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 text-neutral-400">
                <i class="fa-solid fa-children text-3xl mb-2 block"></i>
                <p class="text-sm">Chưa có bé nào đang học{{ $classroomId ? ' trong lớp này' : '' }}.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
