@extends('layouts.app')
@section('title', 'Công việc · Mầm')
@section('page-title', 'Công việc')
@section('content')
@php $isOwner = auth()->user()->isOwner(); @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-5 md:mb-6">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Công việc</h2>
        @if($isOwner)
            <a href="{{ route('routines.index') }}" class="text-xs px-3 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-600 hover:bg-neutral-50 font-medium transition shrink-0">
                <i class="fa-solid fa-repeat mr-1"></i>Lịch định kỳ
            </a>
        @endif
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-5">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <details class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm mb-5" @if($errors->any()) open @endif>
        <summary class="text-[var(--accent-text)] font-semibold cursor-pointer flex items-center gap-2">
            <i class="fa-solid fa-plus"></i>Thêm việc làm 1 lần
        </summary>
        <form method="POST" action="{{ route('tasks.store') }}" class="mt-4 space-y-3" x-data="{ target: '{{ old('target', 'none') }}', type: '{{ old('type', 'thuoc') }}' }">
            @csrf
            <x-input name="title" label="Việc cần làm" icon="fa-pen" placeholder="VD: Cho bé Na uống siro ho 9h" value="{{ old('title') }}" required />
            <div class="grid grid-cols-2 gap-3">
                <x-select name="type" label="Loại" x-model="type">
                    @foreach(config('mam.task_types') as $t => $label)
                        <option value="{{ $t }}" @selected(old('type') === $t)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <div x-show="type !== 'don_dep'">
                    <x-select name="target" label="Cho ai" x-model="target">
                        <option value="none">Việc chung</option>
                        <option value="child">1 bé</option>
                        <option value="classroom">Cả lớp</option>
                        <option value="all">Tất cả các bé</option>
                    </x-select>
                </div>
            </div>
            <div x-show="target === 'child' && type !== 'don_dep'" x-cloak>
                <x-select name="child_id" label="Chọn bé">
                    <option value="">— Chọn —</option>
                    @foreach($children as $c)
                        <option value="{{ $c->id }}" @selected((int) old('child_id') === $c->id)>{{ $c->displayName() }} — {{ $c->full_name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div x-show="target === 'classroom' && type !== 'don_dep'" x-cloak>
                <x-select name="classroom_id" label="Chọn lớp">
                    <option value="">— Chọn —</option>
                    @foreach($classrooms as $cl)
                        <option value="{{ $cl->id }}" @selected((int) old('classroom_id') === $cl->id)>{{ $cl->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-input type="date" name="due_date" label="Ngày" value="{{ old('due_date', now()->toDateString()) }}" required />
                <x-input type="time" name="due_time" label="Giờ" value="{{ old('due_time', now()->addHour()->format('H:00')) }}" required />
            </div>
            @if($items->isNotEmpty())
                <div class="grid grid-cols-2 gap-3">
                    <x-select name="item_id" label="Đồ dùng trong kho (nếu có)">
                        <option value="">— Không —</option>
                        @foreach($items as $it)
                            <option value="{{ $it->id }}" @selected((int) old('item_id') === $it->id)>{{ $it->name }} ({{ $it->unit }})</option>
                        @endforeach
                    </x-select>
                    <x-input type="number" step="any" min="0" name="item_quantity" label="Số lượng" value="{{ old('item_quantity') }}" />
                </div>
            @endif
            <x-button icon="fa-plus">Thêm công việc</x-button>
        </form>
    </details>

    <div class="grid grid-cols-3 gap-1 bg-neutral-100 rounded-xl p-1 mb-4">
        @foreach(['today' => 'Hôm nay', 'upcoming' => 'Sắp tới', 'done' => 'Đã làm'] as $key => $label)
            <a href="{{ route('tasks.index', ['tab' => $key]) }}"
               class="text-center text-sm font-semibold py-2 rounded-lg transition {{ $tab === $key ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-2">
        @forelse($tasks as $task)
            @if($tab === 'upcoming' && ($loop->first || ! $task->due_at->isSameDay($tasks[$loop->index - 1]->due_at)))
                <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider pt-2">{{ day_label($task->due_at) }}</p>
            @endif
            @include('tasks._row', ['task' => $task])
        @empty
            <div class="text-center py-12 text-neutral-400">
                <i class="fa-solid fa-clipboard-check text-3xl mb-2 block"></i>
                <p class="text-sm">{{ $tab === 'done' ? 'Chưa có việc nào được làm.' : 'Không có công việc nào.' }}</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
