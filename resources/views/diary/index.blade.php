@extends('layouts.app')
@section('title', 'Nhật ký · Mầm')
@section('page-title', 'Sổ nhật ký')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <x-errors class="mb-4" />
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-book-open"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Sổ nhật ký</h2>
        <a href="{{ route('diary.report') }}" class="text-xs px-3 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-600 hover:bg-neutral-50 font-medium transition shrink-0"><i class="fa-solid fa-paper-plane mr-1"></i>Báo cáo ngày</a>
        <a href="{{ route('diary.create') }}" class="text-xs px-3 py-2 rounded-lg bg-[var(--accent)] text-white font-semibold hover:bg-[var(--accent-hover)] transition shrink-0"><i class="fa-solid fa-plus mr-1"></i>Ghi</a>
    </div>

    <form method="GET" class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
        <select name="be" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
            <option value="">Tất cả các bé</option>
            @foreach($children as $c)<option value="{{ $c->id }}" @selected((int) request('be') === $c->id)>{{ $c->displayName() }}{{ $c->isActive() ? '' : ' (đã nghỉ)' }}</option>@endforeach
        </select>
        <select name="lop" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
            <option value="">Mọi lớp</option>
            @foreach($classrooms as $cl)<option value="{{ $cl->id }}" @selected((int) request('lop') === $cl->id)>{{ $cl->name }}</option>@endforeach
        </select>
        <select name="loai" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
            <option value="">Mọi loại</option>
            @foreach(config('mam.entry_types') as $k => $m)<option value="{{ $k }}" @selected(request('loai') === $k)>{{ $m['label'] }}</option>@endforeach
        </select>
        <input type="date" name="ngay" value="{{ $date?->toDateString() }}" max="{{ now()->toDateString() }}" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
    </form>

    @php $lastDay = null; @endphp
    @forelse($entries as $entry)
        @php $d = $entry->occurred_at->toDateString(); @endphp
        @if($d !== $lastDay)
            @if($lastDay !== null)</div>@endif
            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mt-4 mb-2">{{ day_label($entry->occurred_at) }}</p>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm px-4">
            @php $lastDay = $d; @endphp
        @endif
        @include('diary._row', ['entry' => $entry, 'showChild' => true, 'sameDay' => true])
        @if($loop->last)</div>@endif
    @empty
        <div class="text-center py-12 text-neutral-400">
            <i class="fa-solid fa-book-open text-3xl mb-2 block"></i>
            <p class="text-sm">Chưa có ghi nhận nào. Bấm <strong>Ghi</strong> để ghi bữa ăn, giấc ngủ, vệ sinh… cho các bé.</p>
        </div>
    @endforelse

    <div class="mt-4">{{ $entries->links() }}</div>
</div>
@endsection
