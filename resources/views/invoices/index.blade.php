@extends('layouts.app')
@section('title', 'Học phí · Mầm')
@section('page-title', 'Học phí')
@section('content')
@php $mk = $month->format('Y-m'); $due = $totals['total'] - $totals['paid']; @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto space-y-4">
    <x-errors />
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Học phí</h2>
        <a href="{{ route('settings.index') }}" class="text-xs px-3 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-600 hover:bg-neutral-50 font-medium"><i class="fa-solid fa-gear mr-1"></i>Mức học phí</a>
    </div>

    <div class="flex items-center justify-between bg-white rounded-xl border border-neutral-200 px-2 py-2">
        <a href="{{ route('invoices.index', ['thang' => $month->copy()->subMonthNoOverflow()->format('Y-m')]) }}" class="w-9 h-9 rounded-lg hover:bg-neutral-100 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-left"></i></a>
        <div class="text-sm font-bold text-neutral-900">Tháng {{ $month->format('m/Y') }}</div>
        <a href="{{ route('invoices.index', ['thang' => $month->copy()->addMonthNoOverflow()->format('Y-m')]) }}" class="w-9 h-9 rounded-lg hover:bg-neutral-100 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-right"></i></a>
    </div>

    <div class="grid grid-cols-3 gap-2 text-center">
        <div class="bg-white rounded-xl border border-neutral-200 py-2.5"><p class="text-[0.65rem] text-neutral-500 font-medium">Phải thu</p><p class="text-base font-extrabold text-neutral-900">{{ money_compact($totals['total']) }}</p></div>
        <div class="bg-white rounded-xl border border-neutral-200 py-2.5"><p class="text-[0.65rem] text-neutral-500 font-medium">Đã thu</p><p class="text-base font-extrabold text-emerald-600">{{ money_compact($totals['paid']) }}</p></div>
        <div class="bg-white rounded-xl border border-neutral-200 py-2.5"><p class="text-[0.65rem] text-neutral-500 font-medium">Còn lại</p><p class="text-base font-extrabold {{ $due > 0 ? 'text-red-600' : 'text-neutral-300' }}">{{ money_compact($due) }}</p></div>
    </div>

    @if($olderDebt > 0)
        <a href="{{ route('reports.debts') }}" class="block text-xs text-red-700 bg-red-50 border border-red-200 rounded-xl px-3 py-2"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Còn nợ các tháng trước: <strong>{{ money($olderDebt) }}đ</strong> — tải danh sách công nợ (Excel/CSV).</a>
    @endif

    <form method="POST" action="{{ route('invoices.generate') }}" class="bg-white rounded-2xl border-2 border-dashed border-[var(--accent-ring)] p-4"
          onsubmit="return confirm('Tạo phiếu cho bé chưa có phiếu và tính lại theo điểm danh các phiếu CHƯA THU, CHƯA SỬA TAY tháng {{ $month->format('m/Y') }}?')">
        @csrf
        <input type="hidden" name="month" value="{{ $mk }}">
        <p class="text-sm font-bold text-neutral-900">{{ $totals['count'] ? 'Cập nhật phiếu tháng '.$month->format('m/Y') : 'Tạo phiếu học phí tháng '.$month->format('m/Y') }}</p>
        <p class="text-xs text-neutral-500 mt-1 mb-3">Học phí theo mức của từng bé (gửi tháng: mức cố định · gửi ngày: số ngày có mặt × học phí/ngày) + tiền ăn = số ngày có mặt × tiền ăn/ngày. Phiếu đã thu tiền hoặc đã sửa tay được giữ nguyên.
            @if($missing > 0)<br><strong class="text-[var(--accent-text)]">{{ $missing }} bé chưa có phiếu tháng này.</strong>@endif</p>
        <button class="w-full rounded-xl py-3 font-semibold text-sm bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i>{{ $totals['count'] ? 'Cập nhật / bổ sung phiếu' : 'Tạo phiếu cho tất cả các bé' }}</button>
    </form>

    <div class="grid grid-cols-3 gap-1 bg-neutral-100 rounded-xl p-1">
        @foreach(['all' => 'Tất cả', 'unpaid' => 'Chưa thu đủ', 'paid' => 'Đã thu đủ'] as $k => $l)
            <a href="{{ route('invoices.index', ['thang' => $mk, 'loc' => $k]) }}" class="text-center text-sm font-semibold py-2 rounded-lg transition {{ $filter === $k ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500' }}">{{ $l }}</a>
        @endforeach
    </div>

    <div class="space-y-2">
        @forelse($invoices as $inv)
            @php $st = $inv->status(); @endphp
            <div class="bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3" x-data="{ pay: false }">
                <div class="flex items-center gap-3">
                    <a href="{{ route('invoices.show', $inv->id) }}" class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-neutral-900 truncate">{{ $inv->child?->full_name }}</span>
                        <span class="block text-xs text-neutral-500 truncate">{{ $inv->child?->classroom?->name ?? 'Chưa xếp lớp' }} · {{ $inv->daysSummary() }}@if($inv->edited_at) · <i class="fa-solid fa-pen text-[0.6rem]" title="Đã sửa tay"></i>@endif</span>
                    </a>
                    <div class="text-right">
                        <p class="text-sm font-bold text-neutral-900">{{ money($inv->total_amount) }}đ</p>
                        <p class="text-[0.7rem] font-semibold {{ $st === 'paid' ? 'text-emerald-600' : ($st === 'partial' ? 'text-amber-600' : 'text-red-600') }}">{{ $st === 'paid' ? 'Đã thu đủ' : 'Còn '.money($inv->balance()).'đ' }}</p>
                    </div>
                    @if($st !== 'paid')
                        <button type="button" @click="pay = !pay" class="shrink-0 text-xs px-3 py-2 rounded-lg bg-[var(--accent)] text-white font-semibold">Thu</button>
                    @endif
                </div>
                @if($st !== 'paid')
                    <form x-show="pay" x-cloak method="POST" action="{{ route('invoices.pay', $inv->id) }}" class="mt-3 pt-3 border-t border-neutral-100 grid grid-cols-2 gap-2">
                        @csrf
                        <input type="text" inputmode="numeric" name="amount" value="{{ (int) round($inv->balance()) }}" class="money-input rounded-lg border border-neutral-300 py-2 px-3 text-sm" required>
                        <select name="method" class="rounded-lg border border-neutral-300 py-2 px-3 text-sm">@foreach(config('mam.payment_methods') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                        <input type="date" name="paid_on" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="rounded-lg border border-neutral-300 py-2 px-3 text-sm" required>
                        <button class="rounded-lg bg-emerald-600 text-white text-sm font-semibold"><i class="fa-solid fa-check mr-1"></i>Xác nhận đã thu</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-center py-10 text-neutral-400"><i class="fa-solid fa-file-invoice-dollar text-3xl mb-2 block"></i><p class="text-sm">{{ $totals['count'] ? 'Không có phiếu nào khớp bộ lọc.' : 'Chưa có phiếu học phí tháng này.' }}</p></div>
        @endforelse
    </div>
</div>
@endsection
