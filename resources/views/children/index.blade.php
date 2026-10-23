@extends('layouts.app')
@section('title', 'Các bé · Mầm')
@section('page-title', 'Các bé')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-children"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Các bé</h2>
        @if($isOwner)
            <a href="{{ route('children.create', array_filter(['lop' => $classroomId])) }}" class="text-xs px-3 py-2 rounded-lg bg-[var(--accent)] text-white font-semibold hover:bg-[var(--accent-hover)] transition shrink-0"><i class="fa-solid fa-plus mr-1"></i>Thêm bé</a>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-1 bg-neutral-100 rounded-xl p-1 mb-3">
        <a href="{{ route('children.index', array_filter(['lop' => $classroomId])) }}" class="text-center text-sm font-semibold py-2 rounded-lg transition {{ $status === 'active' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500' }}">Đang học ({{ $counts['active'] }})</a>
        <a href="{{ route('children.index', ['status' => 'left']) }}" class="text-center text-sm font-semibold py-2 rounded-lg transition {{ $status === 'left' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500' }}">Đã nghỉ ({{ $counts['left'] }})</a>
    </div>

    <form method="GET" class="flex gap-2 mb-4">
        @if($status === 'left')<input type="hidden" name="status" value="left">@endif
        <input type="search" name="q" value="{{ $q }}" placeholder="Tìm tên bé, phụ huynh, SĐT…" class="flex-1 min-w-0 rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm">
        @if($classrooms->isNotEmpty())
            <select name="lop" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm max-w-[45%]" onchange="this.form.submit()">
                <option value="">Mọi lớp</option>
                @foreach($classrooms as $cl)<option value="{{ $cl->id }}" @selected($classroomId === $cl->id)>{{ $cl->name }}</option>@endforeach
            </select>
        @endif
    </form>

    <div class="space-y-2">
        @forelse($children as $child)
            @php $att = $child->attendances->first(); @endphp
            <a href="{{ route('children.show', $child->id) }}" class="flex items-center gap-3 bg-white rounded-2xl border border-neutral-200 shadow-sm px-3 py-3 hover:border-[var(--accent-ring)] transition">
                <span class="w-11 h-11 rounded-full {{ $child->gender === 'nu' ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700' }} text-sm font-bold flex items-center justify-center shrink-0">{{ $child->initials() }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-neutral-900 truncate">{{ $child->full_name }}@if($child->nickname) <span class="font-normal text-neutral-500">({{ $child->nickname }})</span>@endif</span>
                    <span class="block text-xs text-neutral-500 truncate">
                        {{ $child->ageLabel() }}@if($child->classroom) · {{ $child->classroom->name }}@endif
                        @if($child->parent_name) · {{ $child->parent_relation ?: 'PH' }} {{ $child->parent_name }}@endif
                    </span>
                </span>
                @if($child->hasAllergy())<i class="fa-solid fa-hand text-amber-500 text-sm" title="Dị ứng"></i>@endif
                @if($status === 'active')
                    @if($att)
                        <span class="text-[0.65rem] px-2 py-1 rounded-full font-semibold {{ color_badge($att->meta()['color']) }}">{{ $att->meta()['short'] }}</span>
                    @else
                        <span class="text-[0.65rem] px-2 py-1 rounded-full font-semibold bg-neutral-100 text-neutral-400">—</span>
                    @endif
                @else
                    <span class="text-[0.65rem] text-neutral-400">nghỉ {{ $child->left_on?->format('d/m/Y') }}</span>
                @endif
            </a>
        @empty
            <div class="text-center py-12 text-neutral-400">
                <i class="fa-solid fa-children text-3xl mb-2 block"></i>
                <p class="text-sm">{{ $q !== '' ? 'Không tìm thấy bé nào.' : ($status === 'left' ? 'Chưa có bé nào nghỉ học.' : 'Chưa có hồ sơ bé nào.') }}</p>
                @if($isOwner && $status === 'active' && $q === '')
                    <a href="{{ route('children.create') }}" class="inline-block mt-3 text-sm font-semibold text-[var(--accent-text)] hover:underline">+ Thêm bé đầu tiên</a>
                @endif
            </div>
        @endforelse
    </div>
</div>
@endsection
