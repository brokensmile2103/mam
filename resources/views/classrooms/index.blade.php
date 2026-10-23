@extends('layouts.app')
@section('title', 'Lớp / nhóm tuổi · Mầm')
@section('page-title', 'Lớp / nhóm tuổi')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto space-y-4" x-data="{ adding: {{ $errors->any() ? 'true' : 'false' }}, editing: null }">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-chalkboard-user"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Lớp / nhóm tuổi</h2>
        <button type="button" @click="adding = !adding" class="px-3.5 py-2 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Thêm lớp</button>
    </div>
    <p class="text-xs text-neutral-500">Chia bé theo độ tuổi để điểm danh, ghi nhật ký cả lớp nhanh hơn. Theo Thông tư 49/2021, nhóm trẻ nhận trẻ từ 3–36 tháng; số trẻ tối đa mỗi nhóm: 3–12 tháng 12 trẻ, 13–24 tháng 20 trẻ, 25–36 tháng 25 trẻ. Nhóm nhỏ thì để 1 lớp hoặc không cần tạo lớp.</p>

    @if($presets->isNotEmpty())
        <form method="POST" action="{{ route('classrooms.quick-add') }}" class="bg-white rounded-2xl border-2 border-dashed border-[var(--accent-ring)] p-4">
            @csrf
            <p class="text-sm font-bold text-neutral-900 mb-2">Thêm nhanh theo độ tuổi</p>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach(config('mam.classroom_presets') as $i => $p)
                    @if($presets->contains('name', $p['name']))
                        <label class="cursor-pointer"><input type="checkbox" name="presets[]" value="{{ $i }}" class="peer sr-only">
                            <span class="inline-block px-3 py-2 rounded-full border border-neutral-300 text-sm font-medium text-neutral-700 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)] transition select-none">{{ $p['name'] }}</span></label>
                    @endif
                @endforeach
            </div>
            <button class="text-sm px-4 py-2 rounded-xl bg-[var(--accent)] text-white font-semibold">Thêm các nhóm đã chọn</button>
        </form>
    @endif

    <form method="POST" action="{{ route('classrooms.store') }}" x-show="adding" x-cloak class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm space-y-3">
        @csrf
        <x-input name="name" label="Tên lớp" placeholder="VD: Lớp Mầm, Nhóm Sóc Nâu" value="{{ old('name') }}" required maxlength="80" />
        <div class="grid grid-cols-2 gap-3">
            <x-input name="age_label" label="Độ tuổi" placeholder="VD: 18–24 tháng" value="{{ old('age_label') }}" maxlength="40" />
            <x-input type="number" min="0" name="capacity" label="Sĩ số tối đa" value="{{ old('capacity') }}" />
        </div>
        <x-input name="note" label="Ghi chú" placeholder="VD: Cô Lan phụ trách" value="{{ old('note') }}" maxlength="255" />
        <x-button icon="fa-floppy-disk">Lưu lớp</x-button>
    </form>

    @if($unassigned > 0)
        <a href="{{ route('children.index') }}" class="block text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2"><i class="fa-solid fa-circle-info mr-1"></i>{{ $unassigned }} bé chưa được xếp lớp — mở hồ sơ bé → "Chuyển lớp".</a>
    @endif

    <div class="space-y-2">
        @forelse($classrooms as $cl)
            @php $full = $cl->capacity && $cl->active_children_count >= $cl->capacity; @endphp
            <div class="bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('children.index', ['lop' => $cl->id]) }}" class="text-sm font-bold text-neutral-900 hover:underline">{{ $cl->name }}</a>
                        <p class="text-xs text-neutral-500">{{ $cl->age_label ?: 'Chưa ghi độ tuổi' }}@if($cl->note) · {{ $cl->note }}@endif</p>
                    </div>
                    <span class="text-sm font-bold {{ $full ? 'text-red-600' : 'text-neutral-800' }}">{{ $cl->active_children_count }}{{ $cl->capacity ? '/'.$cl->capacity : '' }} <span class="text-xs font-medium text-neutral-400">bé</span></span>
                    <button type="button" @click="editing = editing === {{ $cl->id }} ? null : {{ $cl->id }}" class="text-neutral-400 hover:text-neutral-700 p-1"><i class="fa-solid fa-pen text-xs"></i></button>
                </div>
                <form x-show="editing === {{ $cl->id }}" x-cloak method="POST" action="{{ route('classrooms.update', $cl->id) }}" class="mt-3 pt-3 border-t border-neutral-100 space-y-2">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="name" value="{{ $cl->name }}" required maxlength="80" class="rounded-lg border border-neutral-300 py-2 px-3 text-sm">
                        <input type="text" name="age_label" value="{{ $cl->age_label }}" placeholder="Độ tuổi" maxlength="40" class="rounded-lg border border-neutral-300 py-2 px-3 text-sm">
                        <input type="number" min="0" name="capacity" value="{{ $cl->capacity }}" placeholder="Sĩ số tối đa" class="rounded-lg border border-neutral-300 py-2 px-3 text-sm">
                        <input type="text" name="note" value="{{ $cl->note }}" placeholder="Ghi chú" maxlength="255" class="rounded-lg border border-neutral-300 py-2 px-3 text-sm">
                    </div>
                    <div class="flex items-center justify-between">
                        <button class="text-xs px-3 py-1.5 rounded-lg bg-[var(--accent)] text-white font-semibold">Lưu</button>
                        <button type="submit" form="del-cl-{{ $cl->id }}" class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-600 font-medium"><i class="fa-solid fa-trash-can mr-1"></i>Xoá lớp</button>
                    </div>
                </form>
                <form id="del-cl-{{ $cl->id }}" method="POST" action="{{ route('classrooms.destroy', $cl->id) }}" onsubmit="return confirm('Xoá lớp này? Các bé trong lớp chuyển sang “chưa xếp lớp”.')">@csrf @method('DELETE')</form>
            </div>
        @empty
            <div class="text-center py-10 text-neutral-400"><i class="fa-solid fa-chalkboard-user text-3xl mb-2 block"></i><p class="text-sm">Chưa có lớp nào.</p></div>
        @endforelse
    </div>
</div>
@endsection
