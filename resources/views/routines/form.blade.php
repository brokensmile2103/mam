@extends('layouts.app')
@php
    $editing = $template->exists;
    $title = $editing ? 'Sửa lịch định kỳ' : 'Tạo lịch định kỳ';
    $oldTimes = old('times', $template->timesList());
    $oldDays = collect(old('weekdays', $template->weekdaysList()))->map(fn ($d) => (int) $d)->all();
    $itemData = $items->map(fn ($i) => ['id' => $i->id, 'unit' => $i->unit])->values()->all();
@endphp
@section('title', $title.' · Mầm')
@section('page-title', $title)
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto"
     x-data="{
        target: @js(old('target', $template->target ?: 'all')),
        frequency: @js(old('frequency', $template->frequency ?: 'daily')),
        times: @js(array_values($oldTimes)),
        itemId: @js((string) old('item_id', $template->item_id)),
        items: @js($itemData),
        get unit() { const i = this.items.find(x => x.id == this.itemId); return i ? i.unit : ''; },
     }">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('routines.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 class="text-lg font-bold text-neutral-900">{{ $title }}</h2>
    </div>

    <form method="POST" action="{{ $editing ? route('routines.update', $template->id) : route('routines.store') }}" class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
        @csrf
        @if($editing) @method('PUT') @endif

        <x-input name="title" label="Tên công việc" icon="fa-tag" placeholder="VD: Cho bé Na uống thuốc, Khử khuẩn đồ chơi" value="{{ old('title', $template->title) }}" required maxlength="120" />

        <x-select name="type" label="Loại công việc">
            @foreach(config('mam.task_types') as $k => $label)<option value="{{ $k }}" @selected(old('type', $template->type) === $k)>{{ $label }}</option>@endforeach
        </x-select>

        <div class="space-y-3">
            <x-select name="target" label="Áp dụng cho" x-model="target">
                <option value="all">Tất cả các bé đang học</option>
                <option value="child">Một bé cụ thể</option>
                <option value="classroom">Một lớp</option>
                <option value="none">Việc chung (dọn dẹp, đi chợ…)</option>
            </x-select>
            <div x-show="target === 'child'" x-cloak>
                <x-select name="child_id" label="Chọn bé" x-bind:disabled="target !== 'child'">
                    <option value="">— Chọn —</option>
                    @foreach($children as $c)<option value="{{ $c->id }}" @selected((int) old('child_id', $template->child_id) === $c->id)>{{ $c->displayName() }} — {{ $c->full_name }}</option>@endforeach
                </x-select>
            </div>
            <div x-show="target === 'classroom'" x-cloak>
                <x-select name="classroom_id" label="Chọn lớp" x-bind:disabled="target !== 'classroom'">
                    <option value="">— Chọn —</option>
                    @foreach($classrooms as $cl)<option value="{{ $cl->id }}" @selected((int) old('classroom_id', $template->classroom_id) === $cl->id)>{{ $cl->name }}</option>@endforeach
                </x-select>
            </div>
        </div>

        <div class="pt-3 border-t border-neutral-100 space-y-3">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Lặp lại</p>
            <x-select name="frequency" label="Tần suất" x-model="frequency">
                @foreach(config('mam.frequencies') as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </x-select>

            <div x-show="frequency === 'weekly'" x-cloak>
                <p class="text-sm font-medium text-neutral-700 mb-2">Các ngày trong tuần</p>
                <div class="grid grid-cols-7 gap-1.5">
                    @foreach(config('mam.weekday_labels') as $n => $lbl)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="weekdays[]" value="{{ $n }}" class="peer sr-only" @checked(in_array($n, $oldDays, true)) x-bind:disabled="frequency !== 'weekly'">
                            <span class="block text-center text-xs font-semibold py-2.5 rounded-lg border border-neutral-200 text-neutral-500 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)] transition">{{ $lbl }}</span>
                        </label>
                    @endforeach
                </div>
                @error('weekdays')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div x-show="frequency === 'monthly'" x-cloak>
                <x-input type="number" min="1" max="31" name="month_day" label="Vào ngày (trong tháng)" value="{{ old('month_day', $template->month_day ?: 1) }}" x-bind:disabled="frequency !== 'monthly'" />
            </div>
            <div x-show="frequency === 'interval'" x-cloak>
                <x-input type="number" min="1" max="365" name="interval_days" label="Cách mấy ngày một lần" value="{{ old('interval_days', $template->interval_days ?: 2) }}" x-bind:disabled="frequency !== 'interval'" />
            </div>

            <div>
                <p class="text-sm font-medium text-neutral-700 mb-2">Giờ thực hiện</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(t, i) in times" :key="i">
                        <div class="flex items-center gap-1 rounded-xl border border-neutral-300 pl-3 pr-1.5 py-1">
                            <input type="time" name="times[]" x-model="times[i]" required class="border-0 p-0 text-base focus:ring-0 bg-transparent">
                            <button type="button" x-show="times.length > 1" @click="times.splice(i, 1)" class="w-7 h-7 text-neutral-400 hover:text-red-500"><i class="fa-solid fa-xmark text-xs"></i></button>
                        </div>
                    </template>
                    <button type="button" x-show="times.length < 8" @click="times.push('12:00')" class="rounded-xl border border-dashed border-neutral-300 px-3 py-2 text-sm text-neutral-500 hover:bg-neutral-50"><i class="fa-solid fa-plus mr-1"></i>Thêm giờ</button>
                </div>
                @error('times')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                @error('times.*')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-input type="date" name="starts_on" label="Bắt đầu từ" value="{{ old('starts_on', optional($template->starts_on)->toDateString() ?? now()->toDateString()) }}" required />
                <x-input type="date" name="ends_on" label="Kết thúc (nếu có)" value="{{ old('ends_on', optional($template->ends_on)->toDateString()) }}" />
            </div>
        </div>

        <div class="pt-3 border-t border-neutral-100 space-y-3">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Đồ dùng trong kho (không bắt buộc)</p>
            <x-select name="item_id" label="Dùng mỗi lần" x-model="itemId">
                <option value="">— Không —</option>
                @foreach($items as $i)<option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit }})</option>@endforeach
            </x-select>
            <div x-show="itemId" x-cloak>
                <label for="item_quantity" class="block text-sm font-medium text-neutral-700 mb-2">Số lượng mỗi lần (mỗi bé) <span class="text-neutral-400 font-normal" x-text="unit ? '(' + unit + ')' : ''"></span></label>
                <input type="number" step="any" min="0" name="item_quantity" id="item_quantity" value="{{ old('item_quantity', $template->item_quantity > 0 ? input_number($template->item_quantity) : '') }}" x-bind:disabled="!itemId" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
                <p class="text-xs text-neutral-400 mt-1">Khi bấm "Xong" ở việc này, kho tự trừ theo số bé có mặt.</p>
            </div>
        </div>

        <x-input name="note" label="Ghi chú" value="{{ old('note', $template->note) }}" maxlength="255" />

        <x-button icon="fa-floppy-disk">{{ $editing ? 'Lưu thay đổi' : 'Tạo lịch' }}</x-button>
    </form>
</div>
@endsection
