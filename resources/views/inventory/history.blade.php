@extends('layouts.app')
@section('title', 'Lịch sử kho · Mầm')
@section('page-title', 'Lịch sử kho')
@section('content')
@php $labels = ['in' => ['Nhập kho', 'fa-arrow-down', 'bg-emerald-50 text-emerald-600'], 'out' => ['Xuất dùng', 'fa-arrow-up', 'bg-amber-50 text-amber-600'], 'adjust' => ['Kiểm kê', 'fa-scale-balanced', 'bg-neutral-100 text-neutral-600']]; @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('inventory.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-neutral-900 truncate">{{ $item->name }}</h2>
            <p class="text-xs text-neutral-500">Tồn hiện tại: <strong>{{ quantity($item->current_stock, 2) }} {{ $item->unit }}</strong>@if($item->trashed()) · đã xoá @endif</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm px-4">
        @forelse($movements as $m)
            @php $l = $labels[$m->type] ?? $labels['adjust']; @endphp
            <div class="flex items-start gap-3 py-3 border-b border-neutral-100 last:border-0">
                <div class="w-9 h-9 rounded-xl {{ $l[2] }} flex items-center justify-center shrink-0 text-sm"><i class="fa-solid {{ $l[1] }}"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-neutral-900">{{ $l[0] }} <span class="{{ (float) $m->quantity >= 0 ? 'text-emerald-600' : 'text-amber-600' }}">{{ (float) $m->quantity >= 0 ? '+' : '' }}{{ quantity($m->quantity, 3) }} {{ $item->unit }}</span></div>
                    <div class="text-xs text-neutral-500">
                        @if($isOwner && (float) $m->total_cost > 0){{ money($m->total_cost) }}đ · @endif
                        @if($m->supplier){{ $m->supplier }} · @endif
                        @if($m->note){{ $m->note }} · @endif
                    </div>
                    <div class="text-xs text-neutral-400">{{ $m->occurred_at->format('H:i d/m/Y') }}@if($m->creator) · {{ $m->creator->name }}@endif</div>
                </div>
            </div>
        @empty
            <p class="text-center py-10 text-sm text-neutral-400">Chưa có biến động nào.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
</div>
@endsection
