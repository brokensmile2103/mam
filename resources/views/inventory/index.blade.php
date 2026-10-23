@extends('layouts.app')
@section('title', 'Kho · Mầm')
@section('page-title', 'Kho')
@section('content')
@php $cats = config('mam.item_categories'); @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto" x-data="{ adding: {{ $errors->any() && old('_form') === 'new' ? 'true' : 'false' }}, open: null }">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-warehouse"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Kho sữa · tã · đồ dùng</h2>
        @if($isOwner)<button type="button" @click="adding = !adding" class="px-3.5 py-2 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Thêm</button>@endif
    </div>

    @if($isOwner)
    <form method="POST" action="{{ route('inventory.store') }}" x-show="adding" x-cloak class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm space-y-3 mb-4">
        @csrf
        <input type="hidden" name="_form" value="new">
        <p class="text-sm font-bold text-neutral-900">Thêm vật tư mới</p>
        <x-input name="name" label="Tên" placeholder="VD: Sữa Meiji 0-1, Tã quần size M, Khăn ướt" value="{{ old('name') }}" required maxlength="100" />
        <div class="grid grid-cols-2 gap-3">
            <x-select name="category" label="Nhóm">
                @foreach($cats as $k => $c)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $c['label'] }}</option>@endforeach
            </x-select>
            <x-input name="unit" label="Đơn vị" placeholder="hộp, ml, cái, gói, kg…" value="{{ old('unit', 'cái') }}" required maxlength="20" />
        </div>
        <div class="grid grid-cols-2 gap-3">
            <x-input type="number" step="any" min="0" name="initial_quantity" label="Tồn ban đầu" value="{{ old('initial_quantity') }}" />
            <x-input type="text" inputmode="numeric" class="money-input" name="initial_cost" label="Tổng tiền (đ)" value="{{ old('initial_cost') }}" />
        </div>
        <x-input type="number" step="any" min="0" name="low_stock_threshold" label="Cảnh báo khi tồn ≤ (không bắt buộc)" value="{{ old('low_stock_threshold') }}" />
        <x-button icon="fa-floppy-disk">Lưu vật tư</x-button>
    </form>
    @endif

    <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-2 mb-3 -mx-1 px-1">
        <a href="{{ route('inventory.index') }}" class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ ! $category ? 'bg-[var(--accent)] text-white border-[var(--accent)]' : 'bg-white text-neutral-600 border-neutral-300' }}">Tất cả</a>
        @foreach($cats as $k => $c)
            <a href="{{ route('inventory.index', ['category' => $k]) }}" class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ $category === $k ? 'bg-[var(--accent)] text-white border-[var(--accent)]' : 'bg-white text-neutral-600 border-neutral-300' }}">{{ $c['label'] }}</a>
        @endforeach
    </div>

    <div class="space-y-2">
        @forelse($items as $item)
            @php
                $perDay = (float) ($usage[$item->id] ?? 0);
                $daysLeft = $perDay > 0 ? (float) $item->current_stock / $perDay : null;
                $neg = (float) $item->current_stock < 0;
            @endphp
            <div class="bg-white rounded-xl border {{ $item->isLowStock() || $neg ? 'border-amber-300' : 'border-neutral-200' }} shadow-sm">
                <div class="flex items-center gap-3 px-4 py-3.5">
                    <div class="w-10 h-10 rounded-xl bg-neutral-100 text-neutral-500 flex items-center justify-center shrink-0"><i class="fa-solid {{ $cats[$item->category]['icon'] ?? 'fa-cube' }}"></i></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-neutral-900 truncate">{{ $item->name }}</div>
                        <div class="text-xs text-neutral-500">
                            {{ $item->categoryLabel() }}
                            @if($isOwner && (float) $item->avg_cost > 0) · giá vốn {{ money($item->avg_cost) }}đ/{{ $item->unit }}@endif
                        </div>
                        @if($daysLeft !== null && ! $neg)
                            <div class="text-xs mt-0.5 {{ $daysLeft < 5 ? 'text-amber-600 font-semibold' : 'text-neutral-400' }}">Dùng ~{{ quantity($perDay, 2) }} {{ $item->unit }}/ngày · đủ khoảng {{ (int) floor($daysLeft) }} ngày</div>
                        @endif
                        @if($neg)<div class="text-xs mt-0.5 text-amber-600 font-semibold">Tồn âm — hãy nhập kho hoặc kiểm kê lại</div>@endif
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-lg font-bold leading-tight {{ $item->isLowStock() || $neg ? 'text-amber-600' : 'text-neutral-900' }}">{{ quantity($item->current_stock, 2) }}</div>
                        <div class="text-[0.65rem] text-neutral-400">{{ $item->unit }}@if($item->isLowStock() && ! $neg) · sắp hết @endif</div>
                    </div>
                </div>
                @if($isOwner)
                <div class="border-t border-neutral-100 px-3 py-2 flex items-center gap-1 text-xs font-semibold text-neutral-500 overflow-x-auto scrollbar-hide">
                    <button type="button" @click="open = open === 'in{{ $item->id }}' ? null : 'in{{ $item->id }}'" class="px-2.5 py-1.5 rounded-lg hover:bg-neutral-100 text-[var(--accent-text)] shrink-0"><i class="fa-solid fa-arrow-down mr-1"></i>Nhập kho</button>
                    <button type="button" @click="open = open === 'ad{{ $item->id }}' ? null : 'ad{{ $item->id }}'" class="px-2.5 py-1.5 rounded-lg hover:bg-neutral-100 shrink-0"><i class="fa-solid fa-scale-balanced mr-1"></i>Kiểm kê</button>
                    <button type="button" @click="open = open === 'ed{{ $item->id }}' ? null : 'ed{{ $item->id }}'" class="px-2.5 py-1.5 rounded-lg hover:bg-neutral-100 shrink-0"><i class="fa-solid fa-pen mr-1"></i>Sửa</button>
                    <a href="{{ route('inventory.history', $item->id) }}" class="px-2.5 py-1.5 rounded-lg hover:bg-neutral-100 shrink-0"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Lịch sử</a>
                </div>
                <div x-show="open === 'in{{ $item->id }}'" x-cloak class="border-t border-neutral-100 p-4">
                    <form method="POST" action="{{ route('inventory.stock-in', $item->id) }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <x-input type="number" step="any" min="0" name="quantity" label="Số lượng ({{ $item->unit }})" required />
                            <x-input type="text" inputmode="numeric" class="money-input" name="total_cost" label="Tổng tiền (đ)" required />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <x-input name="supplier" label="Nhà cung cấp" maxlength="120" />
                            <x-input type="date" name="occurred_on" label="Ngày nhập" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" />
                        </div>
                        <x-button icon="fa-arrow-down">Nhập kho</x-button>
                    </form>
                </div>
                <div x-show="open === 'ad{{ $item->id }}'" x-cloak class="border-t border-neutral-100 p-4">
                    <form method="POST" action="{{ route('inventory.adjust', $item->id) }}" class="space-y-3">
                        @csrf
                        <p class="text-xs text-neutral-500">Nhập số tồn thực tế đếm được, hệ thống tự ghi chênh lệch.</p>
                        <x-input type="number" step="any" min="0" name="actual" label="Tồn thực tế ({{ $item->unit }})" value="{{ input_number($item->current_stock) }}" required />
                        <x-input name="note" label="Ghi chú" maxlength="255" />
                        <x-button icon="fa-check" variant="ghost">Cập nhật tồn kho</x-button>
                    </form>
                </div>
                <div x-show="open === 'ed{{ $item->id }}'" x-cloak class="border-t border-neutral-100 p-4">
                    <form method="POST" action="{{ route('inventory.update', $item->id) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <x-input name="name" label="Tên" value="{{ $item->name }}" required maxlength="100" />
                        <div class="grid grid-cols-2 gap-3">
                            <x-select name="category" label="Nhóm" id="ed_c{{ $item->id }}">
                                @foreach($cats as $k => $c)<option value="{{ $k }}" @selected($item->category === $k)>{{ $c['label'] }}</option>@endforeach
                            </x-select>
                            <x-input name="unit" label="Đơn vị" value="{{ $item->unit }}" required maxlength="20" />
                        </div>
                        <x-input type="number" step="any" min="0" name="low_stock_threshold" label="Cảnh báo khi tồn ≤" value="{{ input_number($item->low_stock_threshold) }}" />
                        <x-input name="note" label="Ghi chú" value="{{ $item->note }}" maxlength="255" />
                        <x-button icon="fa-floppy-disk">Lưu</x-button>
                    </form>
                    <form method="POST" action="{{ route('inventory.destroy', $item->id) }}" onsubmit="return confirm('Xoá vật tư này khỏi kho? Lịch sử vẫn được giữ và có thể khôi phục.')" class="mt-2">
                        @csrf @method('DELETE')
                        <button class="w-full text-center text-sm text-red-600 hover:text-red-700 font-semibold py-2"><i class="fa-solid fa-trash-can mr-1"></i>Xoá khỏi kho</button>
                    </form>
                </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 text-neutral-400">
                <i class="fa-solid fa-warehouse text-3xl mb-2 block"></i>
                <p class="text-sm">Kho đang trống.@if($isOwner) Bấm "Thêm" để tạo sữa, tã bỉm, thực phẩm, thuốc…@endif</p>
            </div>
        @endforelse
    </div>

    @if($isOwner && $trashed->isNotEmpty())
        <details class="mt-6">
            <summary class="text-sm font-semibold text-neutral-500 cursor-pointer">Đã xoá ({{ $trashed->count() }})</summary>
            <div class="mt-2 space-y-2">
                @foreach($trashed as $t)
                    <form method="POST" action="{{ route('inventory.restore', $t->id) }}" class="flex items-center justify-between bg-white rounded-xl border border-neutral-200 px-4 py-2.5">
                        @csrf
                        <span class="text-sm text-neutral-600">{{ $t->name }}</span>
                        <button class="text-xs font-semibold text-[var(--accent-text)]">Khôi phục</button>
                    </form>
                @endforeach
            </div>
        </details>
    @endif
</div>
@endsection
