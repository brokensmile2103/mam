@extends('layouts.app')
@section('title', 'Thực đơn · Mầm')
@section('page-title', 'Thực đơn tuần')
@section('content')
@php $meals = config('mam.meals'); @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <x-errors class="mb-4" />
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-utensils"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Thực đơn tuần</h2>
    </div>

    <div class="flex items-center justify-between bg-white rounded-xl border border-neutral-200 px-2 py-2 mb-3">
        <a href="{{ route('menus.index', ['tuan' => $monday->copy()->subWeek()->toDateString()]) }}" class="w-9 h-9 rounded-lg hover:bg-neutral-100 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-left"></i></a>
        <div class="text-center">
            <div class="text-sm font-bold text-neutral-900">{{ $monday->format('d/m') }} – {{ $monday->copy()->addDays(5)->format('d/m/Y') }}</div>
            @if($monday->isSameDay(now()->startOfWeek()))<div class="text-xs text-[var(--accent-text)] font-semibold">Tuần này</div>@endif
        </div>
        <a href="{{ route('menus.index', ['tuan' => $monday->copy()->addWeek()->toDateString()]) }}" class="w-9 h-9 rounded-lg hover:bg-neutral-100 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-right"></i></a>
    </div>

    @if($hasPrevious)
        <form method="POST" action="{{ route('menus.copy') }}" class="mb-3">
            @csrf <input type="hidden" name="week" value="{{ $monday->toDateString() }}">
            <button class="text-xs font-semibold text-[var(--accent-text)] hover:underline"><i class="fa-solid fa-copy mr-1"></i>Chép thực đơn tuần trước vào các ô còn trống</button>
        </form>
    @endif

    <form method="POST" action="{{ route('menus.update') }}" class="space-y-3">
        @csrf @method('PUT')
        <input type="hidden" name="week" value="{{ $monday->toDateString() }}">
        @foreach($days as $d)
            @php $dm = $menus->get($d->toDateString(), collect()); @endphp
            <div class="bg-white rounded-2xl border {{ $d->isToday() ? 'border-[var(--accent-ring)]' : 'border-neutral-200' }} shadow-sm p-4">
                <p class="text-sm font-bold text-neutral-900 mb-2">{{ day_label($d) }}{{ $d->isToday() || $d->isYesterday() ? ' · '.$d->format('d/m') : '' }}</p>
                <div class="space-y-2">
                    @foreach($meals as $k => $label)
                        <label class="flex items-center gap-2">
                            <span class="w-20 shrink-0 text-xs font-semibold text-neutral-500">{{ $label }}</span>
                            <input type="text" name="dishes[{{ $d->toDateString() }}][{{ $k }}]" value="{{ $dm[$k] ?? '' }}" maxlength="500"
                                   placeholder="{{ $k === 'trua' ? 'VD: Cháo thịt bằm bí đỏ, chuối' : '' }}"
                                   class="flex-1 min-w-0 rounded-lg border border-neutral-300 py-2 px-3 text-sm focus:border-[var(--accent-ring)] focus:outline-none">
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
        <x-button icon="fa-floppy-disk">Lưu thực đơn tuần</x-button>
    </form>
</div>
@endsection
