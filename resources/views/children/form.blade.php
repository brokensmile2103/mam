@extends('layouts.app')
@php
    $editing = $child->exists;
    $title = $editing ? 'Sửa hồ sơ '.$child->displayName() : 'Thêm hồ sơ bé';
    $input = 'w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition';
@endphp
@section('title', $title.' · Mầm')
@section('page-title', $title)
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ $editing ? route('children.show', $child->id) : route('children.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 class="text-lg font-bold text-neutral-900">{{ $title }}</h2>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-4">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('children.update', $child->id) : route('children.store') }}" class="space-y-4">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Thông tin bé</p>
            <x-input name="full_name" label="Họ và tên" icon="fa-child" placeholder="VD: Nguyễn Ngọc An Nhiên" value="{{ old('full_name', $child->full_name) }}" required maxlength="100" />
            <div class="grid grid-cols-2 gap-3">
                <x-input name="nickname" label="Tên ở nhà" placeholder="VD: Na, Bin" value="{{ old('nickname', $child->nickname) }}" maxlength="40" />
                <x-select name="gender" label="Giới tính">
                    <option value="">— Chọn —</option>
                    <option value="nam" @selected(old('gender', $child->gender) === 'nam')>Bé trai</option>
                    <option value="nu" @selected(old('gender', $child->gender) === 'nu')>Bé gái</option>
                </x-select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-input type="date" name="birth_date" label="Ngày sinh" value="{{ old('birth_date', optional($child->birth_date)->toDateString()) }}" max="{{ now()->toDateString() }}" required />
                <x-input type="date" name="enrolled_on" label="Ngày nhập học" value="{{ old('enrolled_on', optional($child->enrolled_on)->toDateString() ?? now()->toDateString()) }}" required />
            </div>
            <x-select name="classroom_id" label="Lớp / nhóm">
                <option value="">— Chưa xếp lớp —</option>
                @foreach($classrooms as $cl)
                    <option value="{{ $cl->id }}" @selected((int) old('classroom_id', $child->classroom_id) === $cl->id)>{{ $cl->name }}{{ $cl->age_label ? ' ('.$cl->age_label.')' : '' }}</option>
                @endforeach
            </x-select>
            @if($classrooms->isEmpty())<p class="text-xs text-neutral-400 -mt-2">Chưa có lớp nào — <a href="{{ route('classrooms.index') }}" class="underline">tạo lớp</a> nếu nhóm chia theo độ tuổi.</p>@endif
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Phụ huynh</p>
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-1">
                    <x-input name="parent_relation" label="Quan hệ" placeholder="Mẹ" value="{{ old('parent_relation', $child->parent_relation) }}" maxlength="20" list="relations" />
                    <datalist id="relations"><option value="Mẹ"><option value="Bố"><option value="Ông"><option value="Bà"><option value="Người giám hộ"></datalist>
                </div>
                <div class="col-span-2"><x-input name="parent_name" label="Họ tên" value="{{ old('parent_name', $child->parent_name) }}" maxlength="100" /></div>
            </div>
            <x-input type="tel" name="parent_phone" label="Số điện thoại" icon="fa-phone" value="{{ old('parent_phone', $child->parent_phone) }}" maxlength="20" />
            <div class="grid grid-cols-2 gap-3">
                <x-input name="parent2_name" label="Người thứ 2 (nếu có)" placeholder="VD: Bố — Anh Tuấn" value="{{ old('parent2_name', $child->parent2_name) }}" maxlength="100" />
                <x-input type="tel" name="parent2_phone" label="SĐT người thứ 2" value="{{ old('parent2_phone', $child->parent2_phone) }}" maxlength="20" />
            </div>
            <x-input name="pickup_note" label="Người được phép đón bé" icon="fa-person-walking-arrow-right" placeholder="VD: Bố, mẹ, bà ngoại. KHÔNG giao cho người lạ." value="{{ old('pickup_note', $child->pickup_note) }}" maxlength="255" />
            <x-input name="address" label="Địa chỉ" icon="fa-location-dot" value="{{ old('address', $child->address) }}" maxlength="255" />
        </div>

        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Sức khoẻ</p>
            <div>
                <label for="allergies" class="block text-sm font-medium text-neutral-700 mb-2">Dị ứng <span class="text-neutral-400 font-normal">(thức ăn, thuốc…)</span></label>
                <input type="text" name="allergies" id="allergies" value="{{ old('allergies', $child->allergies) }}" maxlength="500" placeholder="VD: Dị ứng tôm, sữa bò" class="{{ $input }}">
                <p class="text-xs text-neutral-400 mt-1">Hiện cảnh báo cho cô giáo ở mục Hôm nay và khi ghi nhật ký.</p>
            </div>
            <div>
                <label for="health_note" class="block text-sm font-medium text-neutral-700 mb-2">Lưu ý sức khoẻ khác</label>
                <textarea name="health_note" id="health_note" rows="2" maxlength="2000" placeholder="VD: Hay bị viêm họng, dễ sốt khi trời lạnh" class="{{ $input }}">{{ old('health_note', $child->health_note) }}</textarea>
            </div>
        </div>

        @php
            $modes = config('mam.tuition_modes');
            $centerMode = $center->tuitionMode();
            $childMode = old('tuition_mode', $child->tuition_mode ?? '');
        @endphp
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4"
             x-data="{ mode: @js($childMode), centerMode: @js($centerMode) }">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Học phí riêng <span class="normal-case font-normal text-neutral-400">(để trống = theo mức chung)</span></p>
            <x-select name="tuition_mode" label="Cách tính học phí" x-model="mode">
                <option value="">Theo nhóm — {{ mb_strtolower($modes[$centerMode]['label']) }}</option>
                @foreach($modes as $k => $m)
                    <option value="{{ $k }}" @selected($childMode === $k)>{{ $m['short'] }} — {{ mb_strtolower($m['hint']) }}</option>
                @endforeach
            </x-select>
            <div class="grid grid-cols-2 gap-3">
                <div x-show="(mode || centerMode) === 'monthly'" x-cloak>
                    <x-input type="text" inputmode="numeric" class="money-input" name="tuition_fee" label="Học phí / tháng (đ)" placeholder="{{ money($center->tuition_default) }}" value="{{ old('tuition_fee', $child->tuition_fee !== null ? (int) $child->tuition_fee : '') }}" />
                </div>
                <div x-show="(mode || centerMode) === 'daily'" x-cloak>
                    <x-input type="text" inputmode="numeric" class="money-input" name="daily_fee" label="Học phí / ngày (đ)" placeholder="{{ money($center->daily_fee_default) }}" value="{{ old('daily_fee', $child->daily_fee !== null ? (int) $child->daily_fee : '') }}" />
                </div>
                <x-input type="text" inputmode="numeric" class="money-input" name="meal_rate" label="Tiền ăn / ngày (đ)" placeholder="{{ money($center->meal_rate_default) }}" value="{{ old('meal_rate', $child->meal_rate !== null ? (int) $child->meal_rate : '') }}" />
            </div>
            <div>
                <label for="note" class="block text-sm font-medium text-neutral-700 mb-2">Ghi chú nội bộ</label>
                <textarea name="note" id="note" rows="2" maxlength="2000" class="{{ $input }}">{{ old('note', $child->note) }}</textarea>
            </div>
        </div>

        <x-button icon="fa-floppy-disk">{{ $editing ? 'Lưu thay đổi' : 'Thêm hồ sơ' }}</x-button>
    </form>
</div>
@endsection
