@extends('layouts.app')
@section('title', 'Báo cáo thu/chi · Mầm')
@section('page-title', 'Báo cáo thu/chi')
@section('content')
@php
    $max = max(1, $trend->max(fn ($t) => max($t['in'], $t['out'])));
    $maxCost = max(1, collect($r['costs'])->max('amount'));
@endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto space-y-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-chart-line"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Báo cáo thu/chi</h2>
        <a href="{{ route('reports.debts') }}" class="text-xs font-semibold text-[var(--accent-text)] hover:underline"><i class="fa-solid fa-file-csv mr-1"></i>Công nợ (CSV)</a>
    </div>

    <div class="flex items-center justify-between bg-white rounded-xl border border-neutral-200 px-2 py-2">
        <a href="{{ route('reports.index', ['thang' => $month->copy()->subMonthNoOverflow()->format('Y-m')]) }}" class="w-9 h-9 rounded-lg hover:bg-neutral-100 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-left"></i></a>
        <div class="text-sm font-bold text-neutral-900">Tháng {{ $month->format('m/Y') }}</div>
        <a href="{{ route('reports.index', ['thang' => $month->copy()->addMonthNoOverflow()->format('Y-m')]) }}" class="w-9 h-9 rounded-lg hover:bg-neutral-100 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-right"></i></a>
    </div>

    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
        <div class="grid grid-cols-3 gap-2 text-center">
            <div><p class="text-[0.7rem] text-neutral-500 uppercase font-semibold">Đã thu</p><p class="text-lg font-extrabold text-emerald-600">{{ money_compact($r['collected']) }}</p></div>
            <div><p class="text-[0.7rem] text-neutral-500 uppercase font-semibold">Đã chi</p><p class="text-lg font-extrabold text-neutral-900">{{ money_compact($r['cost_total']) }}</p></div>
            <div><p class="text-[0.7rem] text-neutral-500 uppercase font-semibold">{{ $r['profit'] >= 0 ? 'Lãi' : 'Lỗ' }}</p><p class="text-lg font-extrabold {{ $r['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ signed_money($r['profit']) }}</p></div>
        </div>
        @if($r['by_method'])
            <p class="text-xs text-neutral-500 text-center mt-2">@foreach($r['by_method'] as $m => $v){{ config('mam.payment_methods.'.$m, $m) }}: {{ money($v) }}đ@if(! $loop->last) · @endif @endforeach</p>
        @endif

        <div class="mt-4 pt-4 border-t border-neutral-100 space-y-2">
            @foreach($r['costs'] as $c)
                <div>
                    <div class="flex justify-between text-sm"><span class="text-neutral-600">{{ $c['label'] }}</span><span class="font-semibold text-neutral-900">{{ money($c['amount']) }}đ</span></div>
                    <div class="h-1.5 rounded-full bg-neutral-100 mt-1"><div class="h-1.5 rounded-full bg-[var(--accent)]" style="width: {{ $c['amount'] > 0 ? max(2, round($c['amount'] / $maxCost * 100)) : 0 }}%"></div></div>
                </div>
            @endforeach
        </div>
        <p class="text-[0.7rem] text-neutral-400 mt-3 leading-relaxed">Tính theo tiền thực thu (ngày thu học phí) và thực chi (khoản chi đã ghi + tiền nhập kho) trong tháng.</p>
    </div>

    <div class="grid grid-cols-2 gap-2">
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <p class="text-xs text-neutral-500 font-medium">Học phí tháng {{ $month->format('m') }}</p>
            <p class="text-lg font-extrabold text-neutral-900">{{ money_compact($r['invoice_paid']) }}<span class="text-sm text-neutral-400">/{{ money_compact($r['invoice_total']) }}</span></p>
            <p class="text-[0.7rem] text-neutral-400">{{ $r['invoice_count'] }} phiếu · đã thu / phải thu</p>
        </div>
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <p class="text-xs text-neutral-500 font-medium">Công nợ học phí (mọi tháng)</p>
            <p class="text-lg font-extrabold {{ $r['outstanding'] > 0 ? 'text-red-600' : 'text-neutral-300' }}">{{ money($r['outstanding']) }}đ</p>
        </div>
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <p class="text-xs text-neutral-500 font-medium">Chuyên cần</p>
            <p class="text-lg font-extrabold text-neutral-900">{{ $r['attendance_rate'] !== null ? number_format($r['attendance_rate'], 0).'%' : '—' }}</p>
            <p class="text-[0.7rem] text-neutral-400">{{ $r['present_count'] }} lượt có mặt</p>
        </div>
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <p class="text-xs text-neutral-500 font-medium">Sĩ số</p>
            <p class="text-lg font-extrabold text-neutral-900">{{ $r['active_children'] }} bé</p>
            <p class="text-[0.7rem] text-neutral-400">+{{ $r['enrolled'] }} nhập học · −{{ $r['left'] }} nghỉ trong tháng</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
        <p class="text-sm font-bold text-neutral-900 mb-3">6 tháng gần nhất</p>
        <div class="flex items-end gap-2 h-36">
            @foreach($trend as $t)
                <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
                    <div class="w-full flex items-end gap-0.5 flex-1">
                        <div class="flex-1 rounded-t bg-emerald-500" style="height: {{ round($t['in'] / $max * 100) }}%" title="Thu {{ money($t['in']) }}đ"></div>
                        <div class="flex-1 rounded-t bg-neutral-300" style="height: {{ round($t['out'] / $max * 100) }}%" title="Chi {{ money($t['out']) }}đ"></div>
                    </div>
                    <span class="text-[0.6rem] text-neutral-500">{{ substr($t['label'], 0, 2) }}</span>
                </div>
            @endforeach
        </div>
        <p class="text-[0.7rem] text-neutral-500 mt-2"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500 align-middle"></span> Thu &nbsp; <span class="inline-block w-2.5 h-2.5 rounded-sm bg-neutral-300 align-middle"></span> Chi</p>
    </div>
</div>
@endsection
