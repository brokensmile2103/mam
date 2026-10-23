@extends('layouts.app')
@php
    $types = config('mam.entry_types');
    $noteRequired = config('mam.note_required_types');
    $typeData = collect($types)->map(fn ($m, $k) => [
        'label' => $m['label'], 'icon' => $m['icon'], 'fields' => $m['fields'], 'item' => $m['item'], 'cats' => $m['item_categories'],
        'note' => in_array($k, $noteRequired, true),
    ])->all();
    $itemData = $items->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit, 'cat' => $i->category, 'stock' => (float) $i->current_stock])->values()->all();
    $childData = $children->map(function ($c) {
        $att = $c->attendances->first();

        return [
            'id' => $c->id, 'name' => $c->displayName(), 'full' => $c->full_name, 'ini' => $c->initials(), 'cls' => $c->classroom_id,
            'present' => $att?->status === 'co_mat', 'absent' => $att && $att->status !== 'co_mat', 'allergy' => $c->hasAllergy(),
        ];
    })->values()->all();
    $classData = $classrooms->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all();
    $nowTime = now()->format('H:i');
@endphp
@section('title', 'Ghi nhật ký · Mầm')
@section('page-title', 'Ghi nhật ký')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto"
     x-data="{
        type: @js(old('type', $type)),
        types: @js($typeData),
        items: @js($itemData),
        kids: @js($childData),
        classes: @js($classData),
        menu: @js($menuToday),
        sel: @js(array_map('intval', old('child_ids', $preselect))),
        itemId: @js((string) old('item_id', '')),
        meal: @js(old('meal', now()->hour < 10 ? 'sang' : (now()->hour < 14 ? 'trua' : 'xe'))),
        level: @js(old('level', 'het')),
        toilet: @js(old('toilet', 'tieu')),
        get cur() { return this.types[this.type] },
        has(f) { return this.cur.fields.includes(f) },
        get itemList() { const c = this.cur.cats; return this.items.filter(i => !c.length || c.includes(i.cat) || i.id == this.itemId); },
        get item() { return this.items.find(i => i.id == this.itemId) },
        toggle(id) { this.sel = this.sel.includes(id) ? this.sel.filter(x => x !== id) : [...this.sel, id]; },
        pick(ids) { this.sel = [...new Set(ids)]; },
        setType(t) { this.type = t; if (!this.types[t].item) this.itemId = ''; },
     }">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ $returnTo ?: route('diary.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 class="text-lg font-bold text-neutral-900">Ghi nhật ký</h2>
    </div>

    @if($children->isEmpty())
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">
            Chưa có bé nào đang học. @if(auth()->user()->isOwner())<a href="{{ route('children.create') }}" class="font-semibold underline">Thêm hồ sơ bé</a> trước nhé.@endif
        </div>
    @else
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-4">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('diary.store') }}" class="space-y-4">
        @csrf
        @if($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
        <input type="hidden" name="type" :value="type">

        <div class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-2">Ghi gì?</p>
            <div class="grid grid-cols-5 gap-2">
                @foreach($types as $t => $m)
                    <button type="button" @click="setType('{{ $t }}')"
                        :class="type === '{{ $t }}' ? 'border-[var(--accent)] bg-[var(--accent-light)] text-[var(--accent-text)]' : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50'"
                        class="flex flex-col items-center gap-1 rounded-xl border px-1 py-2.5 transition">
                        <i class="fa-solid {{ $m['icon'] }} text-base"></i>
                        <span class="text-[0.65rem] font-semibold leading-tight text-center">{{ $m['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Bé nào? <span class="normal-case text-neutral-400" x-show="sel.length" x-text="'(' + sel.length + ' bé)'"></span></p>
                <button type="button" @click="sel = []" x-show="sel.length" class="text-xs font-semibold text-neutral-500">Bỏ chọn</button>
            </div>
            <div class="flex flex-wrap gap-1.5 mb-3">
                <button type="button" @click="pick(kids.filter(k => k.present).map(k => k.id))" x-show="kids.some(k => k.present)" class="text-xs px-2.5 py-1.5 rounded-full border border-emerald-300 text-emerald-700 bg-emerald-50 font-semibold">Các bé có mặt</button>
                <template x-for="c in classes" :key="c.id">
                    <button type="button" @click="pick(kids.filter(k => k.cls === c.id && !k.absent).map(k => k.id))" class="text-xs px-2.5 py-1.5 rounded-full border border-neutral-300 text-neutral-600 font-semibold" x-text="c.name"></button>
                </template>
                <button type="button" @click="pick(kids.filter(k => !k.absent).map(k => k.id))" class="text-xs px-2.5 py-1.5 rounded-full border border-neutral-300 text-neutral-600 font-semibold">Tất cả</button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <template x-for="k in kids" :key="k.id">
                    <button type="button" @click="toggle(k.id)"
                        :class="sel.includes(k.id) ? 'border-[var(--accent)] bg-[var(--accent-light)]' : (k.absent ? 'border-neutral-200 opacity-50' : 'border-neutral-200 hover:bg-neutral-50')"
                        class="flex items-center gap-2 rounded-xl border px-2.5 py-2 text-left transition">
                        <span class="w-8 h-8 rounded-full text-xs font-bold flex items-center justify-center shrink-0" :class="sel.includes(k.id) ? 'bg-[var(--accent)] text-white' : 'bg-neutral-100 text-neutral-600'" x-text="k.ini"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-neutral-900 truncate" x-text="k.name"></span>
                            <span class="block text-[0.65rem] truncate" :class="k.absent ? 'text-red-500' : (k.present ? 'text-emerald-600' : 'text-neutral-400')" x-text="k.absent ? 'nghỉ hôm nay' : (k.present ? 'có mặt' : 'chưa điểm danh')"></span>
                        </span>
                        <i class="fa-solid fa-hand text-amber-500 text-xs" x-show="k.allergy" title="Có dị ứng"></i>
                    </button>
                </template>
            </div>
            <template x-for="id in sel" :key="id"><input type="hidden" name="child_ids[]" :value="id"></template>
            @error('child_ids')<p class="mt-2 text-xs text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
        </div>

        <div class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm space-y-4">
            {{-- Ăn --}}
            <div x-show="has('meal')" x-cloak class="space-y-3">
                <div>
                    <p class="text-sm font-medium text-neutral-700 mb-2">Bữa</p>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach(config('mam.meals') as $k => $label)
                            <label class="cursor-pointer"><input type="radio" name="meal" value="{{ $k }}" x-model="meal" class="peer sr-only" :disabled="!has('meal')">
                                <span class="block text-center text-xs font-semibold py-2.5 rounded-lg border border-neutral-200 text-neutral-600 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)] transition">{{ $label }}</span></label>
                        @endforeach
                    </div>
                    <p class="text-xs text-neutral-500 mt-1.5" x-show="menu[meal]" x-text="'Thực đơn: ' + (menu[meal] || '')"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-neutral-700 mb-2">Bé ăn thế nào?</p>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach(config('mam.eat_levels') as $k => $lv)
                            <label class="cursor-pointer"><input type="radio" name="level" value="{{ $k }}" x-model="level" class="peer sr-only" :disabled="!has('level')">
                                <span class="block text-center text-xs font-semibold py-2.5 rounded-lg border border-neutral-200 text-neutral-600 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)] transition">{{ $lv['label'] }}</span></label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Sữa --}}
            <div x-show="has('amount_ml')" x-cloak>
                <label for="amount_ml" class="block text-sm font-medium text-neutral-700 mb-2">Lượng sữa mỗi bé (ml)</label>
                <input type="number" min="0" max="3000" step="10" name="amount_ml" id="amount_ml" value="{{ old('amount_ml') }}" :disabled="!has('amount_ml')" placeholder="VD: 180"
                       class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
            </div>

            {{-- Ngủ --}}
            <div x-show="has('sleep')" x-cloak class="grid grid-cols-2 gap-3">
                <div>
                    <label for="sleep_from" class="block text-sm font-medium text-neutral-700 mb-2">Bắt đầu ngủ</label>
                    <input type="time" name="sleep_from" id="sleep_from" value="{{ old('sleep_from', '12:00') }}" :disabled="!has('sleep')" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:outline-none">
                </div>
                <div>
                    <label for="sleep_to" class="block text-sm font-medium text-neutral-700 mb-2">Thức dậy <span class="text-neutral-400 font-normal">(nếu đã dậy)</span></label>
                    <input type="time" name="sleep_to" id="sleep_to" value="{{ old('sleep_to') }}" :disabled="!has('sleep')" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:outline-none">
                </div>
            </div>

            {{-- Vệ sinh --}}
            <div x-show="has('toilet')" x-cloak>
                <p class="text-sm font-medium text-neutral-700 mb-2">Loại</p>
                <div class="grid grid-cols-4 gap-1.5">
                    @foreach(config('mam.toilet_kinds') as $k => $label)
                        <label class="cursor-pointer"><input type="radio" name="toilet" value="{{ $k }}" x-model="toilet" class="peer sr-only" :disabled="!has('toilet')">
                            <span class="block text-center text-xs font-semibold py-2.5 rounded-lg border border-neutral-200 text-neutral-600 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)] transition">{{ $label }}</span></label>
                    @endforeach
                </div>
            </div>

            {{-- Nhiệt độ --}}
            <div x-show="has('temperature')" x-cloak>
                <label for="temperature" class="block text-sm font-medium text-neutral-700 mb-2">Nhiệt độ (°C)</label>
                <input type="number" step="0.1" min="34" max="43" name="temperature" id="temperature" value="{{ old('temperature') }}" :disabled="!has('temperature')" placeholder="VD: 36.8"
                       class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
                <p class="text-xs text-neutral-400 mt-1">Từ {{ quantity(config('mam.fever_threshold'), 1) }}°C trở lên sẽ được đánh dấu sốt và nhắc báo phụ huynh.</p>
            </div>

            {{-- Cân đo --}}
            <div x-show="has('growth')" x-cloak class="grid grid-cols-2 gap-3">
                <div>
                    <label for="weight_kg" class="block text-sm font-medium text-neutral-700 mb-2">Cân nặng (kg)</label>
                    <input type="number" step="0.01" min="1" max="60" name="weight_kg" id="weight_kg" value="{{ old('weight_kg') }}" :disabled="!has('growth')" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:outline-none">
                </div>
                <div>
                    <label for="height_cm" class="block text-sm font-medium text-neutral-700 mb-2">Chiều cao (cm)</label>
                    <input type="number" step="0.1" min="30" max="150" name="height_cm" id="height_cm" value="{{ old('height_cm') }}" :disabled="!has('growth')" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:outline-none">
                </div>
            </div>

            {{-- Vật tư (sữa, tã, thuốc) --}}
            <div x-show="cur.item && itemList.length" x-cloak class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label for="item_id" class="block text-sm font-medium text-neutral-700 mb-2">Dùng đồ trong kho <span class="text-neutral-400 font-normal">(tự trừ kho)</span></label>
                    <select name="item_id" id="item_id" x-model="itemId" :disabled="!cur.item" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-3 text-base focus:outline-none">
                        <option value="">— Không —</option>
                        <template x-for="i in itemList" :key="i.id"><option :value="i.id" x-text="i.name + ' (còn ' + i.stock.toLocaleString('vi-VN') + ' ' + i.unit + ')'" :selected="i.id == itemId"></option></template>
                    </select>
                </div>
                <div x-show="itemId">
                    <label for="item_quantity" class="block text-sm font-medium text-neutral-700 mb-2">Mỗi bé <span class="text-neutral-400 font-normal" x-text="item ? '(' + item.unit + ')' : ''"></span></label>
                    <input type="number" step="any" min="0" name="item_quantity" id="item_quantity" value="{{ old('item_quantity', 1) }}" :disabled="!itemId" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-3 text-base focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-input type="date" name="occurred_on" label="Ngày" value="{{ old('occurred_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required />
                <x-input type="time" name="occurred_time" label="Giờ" value="{{ old('occurred_time', $nowTime) }}" />
            </div>

            <div>
                <label for="note" class="block text-sm font-medium text-neutral-700 mb-2">Ghi chú <span class="font-normal" :class="cur.note ? 'text-red-500' : 'text-neutral-400'" x-text="cur.note ? '(bắt buộc)' : '(không bắt buộc)'"></span></label>
                <textarea name="note" id="note" rows="2" maxlength="1000" :required="cur.note"
                          :placeholder="type === 'thuoc' ? 'VD: Siro ho 5ml theo đơn mẹ gửi' : (type === 'su_co' ? 'VD: Bé vấp ngã ở sân, trầy đầu gối, đã sát trùng' : (type === 'hoat_dong' ? 'VD: Học bài hát Cả nhà thương nhau, chơi xếp hình' : 'VD: Bé hơi quấy, ăn chậm…'))"
                          class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">{{ old('note') }}</textarea>
            </div>
        </div>

        <x-button icon="fa-floppy-disk" x-bind:disabled="sel.length === 0"><span x-text="sel.length > 1 ? 'Lưu cho ' + sel.length + ' bé' : 'Lưu ghi nhận'">Lưu ghi nhận</span></x-button>
    </form>
    @endif
</div>
@endsection
