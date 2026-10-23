@extends('layouts.app')
@section('title', 'Phiếu học phí · Mầm')
@section('page-title', 'Phiếu học phí')
@section('content')
@php $child = $invoice->child; $st = $invoice->status(); @endphp
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto space-y-4" x-data="{ edit: {{ $errors->hasAny(['tuition_mode', 'tuition_amount', 'tuition_days', 'tuition_rate', 'meal_days', 'meal_rate', 'other_amount', 'discount_amount', 'total']) ? 'true' : 'false' }}, mode: @js(old('tuition_mode', $invoice->tuition_mode === 'daily' ? 'daily' : 'monthly')) }">
    <div class="flex items-center gap-3">
        <a href="{{ route('invoices.index', ['thang' => $invoice->month]) }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="min-w-0 flex-1">
            <h2 class="text-lg font-bold text-neutral-900 truncate">{{ $child?->full_name }}</h2>
            <p class="text-xs text-neutral-500">{{ $invoice->monthLabel() }}@if($child?->classroom) · {{ $child->classroom->name }}@endif</p>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $st === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($st === 'partial' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-600') }}">{{ $invoice->statusLabel() }}</span>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 text-sm space-y-1.5">
        <div class="flex justify-between gap-3"><span class="text-neutral-500">{{ $invoice->tuitionLabel() }}</span><span class="shrink-0">{{ money($invoice->tuition_amount) }}đ</span></div>
        @if((float) $invoice->meal_amount > 0 || ! $invoice->isDaily())
            <div class="flex justify-between gap-3"><span class="text-neutral-500">Tiền ăn ({{ $invoice->meal_days }} ngày × {{ money($invoice->meal_rate) }}đ)</span><span class="shrink-0">{{ money($invoice->meal_amount) }}đ</span></div>
        @endif
        @if((float) $invoice->other_amount > 0)<div class="flex justify-between"><span class="text-neutral-500">{{ $invoice->other_label ?: 'Khoản khác' }}</span><span>{{ money($invoice->other_amount) }}đ</span></div>@endif
        @if((float) $invoice->discount_amount > 0)<div class="flex justify-between"><span class="text-neutral-500">Giảm trừ</span><span class="text-emerald-700">−{{ money($invoice->discount_amount) }}đ</span></div>@endif
        <div class="flex justify-between pt-1.5 border-t border-neutral-100 font-bold"><span>Tổng cộng</span><span>{{ money($invoice->total_amount) }}đ</span></div>
        <div class="flex justify-between"><span class="text-neutral-500">Đã thu</span><span class="text-emerald-700 font-semibold">{{ money($invoice->paid_amount) }}đ</span></div>
        <div class="flex justify-between font-bold {{ $invoice->balance() > 0 ? 'text-red-600' : 'text-neutral-400' }}"><span>Còn lại</span><span>{{ money($invoice->balance()) }}đ</span></div>
        @if($invoice->note)<p class="text-xs text-neutral-500 pt-1 italic">{{ $invoice->note }}</p>@endif
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-1">
            <button type="button" @click="edit = !edit" class="text-xs font-semibold text-[var(--accent-text)] hover:underline"><i class="fa-solid fa-pen mr-1"></i>Sửa phiếu (số ngày, phụ thu, giảm trừ)</button>
            <form method="POST" action="{{ route('invoices.recalculate', $invoice->id) }}" onsubmit="return confirm('Tính lại phiếu theo điểm danh và mức học phí hiện tại của bé? Số ngày, đơn giá đã sửa tay sẽ được thay (giữ khoản khác, giảm trừ, ghi chú).')">
                @csrf<button class="text-xs font-semibold text-neutral-500 hover:underline"><i class="fa-solid fa-rotate mr-1"></i>Tính lại theo điểm danh</button>
            </form>
        </div>
        @if($invoice->edited_at)
            <p class="text-[0.7rem] text-neutral-400"><i class="fa-solid fa-circle-info mr-1"></i>Phiếu đã sửa tay lúc {{ $invoice->edited_at->format('H:i d/m/Y') }} — nút "Cập nhật phiếu" hàng loạt sẽ giữ nguyên phiếu này.</p>
        @endif
    </div>

    <form x-show="edit" x-cloak method="POST" action="{{ route('invoices.update', $invoice->id) }}" class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 space-y-3">
        @csrf @method('PUT')
        <div class="grid grid-cols-2 gap-1 bg-neutral-100 rounded-xl p-1">
            @foreach(config('mam.tuition_modes') as $k => $m)
                <label class="relative cursor-pointer"><input type="radio" name="tuition_mode" value="{{ $k }}" x-model="mode" class="peer sr-only"><span class="block text-center text-sm font-semibold py-2 rounded-lg text-neutral-500 peer-checked:bg-white peer-checked:text-neutral-900 peer-checked:shadow-sm transition">{{ $m['short'] }}</span></label>
            @endforeach
        </div>
        <div x-show="mode === 'monthly'" x-cloak>
            <x-input type="text" inputmode="numeric" class="money-input" name="tuition_amount" label="Học phí tháng (đ)" value="{{ old('tuition_amount', (int) $invoice->tuition_amount) }}" x-bind:disabled="mode !== 'monthly'" />
        </div>
        <div x-show="mode === 'daily'" x-cloak class="grid grid-cols-2 gap-3">
            <x-input type="number" min="0" max="31" name="tuition_days" label="Số ngày học" value="{{ old('tuition_days', $invoice->isDaily() ? $invoice->tuition_days : $invoice->meal_days) }}" x-bind:disabled="mode !== 'daily'" />
            <x-input type="text" inputmode="numeric" class="money-input" name="tuition_rate" label="Học phí / ngày (đ)" value="{{ old('tuition_rate', (int) ($invoice->isDaily() ? $invoice->tuition_rate : $invoice->child?->effectiveDailyFee())) }}" x-bind:disabled="mode !== 'daily'" />
        </div>
        <div class="grid grid-cols-2 gap-3">
            <x-input type="number" min="0" max="31" name="meal_days" label="Số ngày ăn" value="{{ old('meal_days', $invoice->meal_days) }}" required />
            <x-input type="text" inputmode="numeric" class="money-input" name="meal_rate" label="Tiền ăn / ngày (đ)" value="{{ old('meal_rate', (int) $invoice->meal_rate) }}" required />
        </div>
        <div class="grid grid-cols-2 gap-3">
            <x-input name="other_label" label="Khoản khác" placeholder="VD: Đồng phục, học liệu" value="{{ old('other_label', $invoice->other_label) }}" maxlength="120" />
            <x-input type="text" inputmode="numeric" class="money-input" name="other_amount" label="Số tiền (đ)" value="{{ old('other_amount', (int) $invoice->other_amount ?: '') }}" />
        </div>
        <x-input type="text" inputmode="numeric" class="money-input" name="discount_amount" label="Giảm trừ (đ) — VD: hoàn tiền ăn tháng trước, giảm anh chị em" value="{{ old('discount_amount', (int) $invoice->discount_amount ?: '') }}" />
        <x-input name="note" label="Ghi chú trên phiếu" value="{{ old('note', $invoice->note) }}" maxlength="500" />
        <x-button icon="fa-floppy-disk">Lưu phiếu</x-button>
    </form>

    @if($invoice->balance() > 0)
        <form method="POST" action="{{ route('invoices.pay', $invoice->id) }}" class="bg-white rounded-2xl border border-[var(--accent-ring)] shadow-sm p-4 space-y-3">
            @csrf
            <p class="text-sm font-bold text-neutral-900"><i class="fa-solid fa-hand-holding-dollar text-[var(--accent)] mr-1.5"></i>Ghi nhận thu tiền</p>
            <div class="grid grid-cols-2 gap-3">
                <x-input type="text" inputmode="numeric" class="money-input" name="amount" label="Số tiền (đ)" value="{{ old('amount', (int) round($invoice->balance())) }}" required />
                <x-select name="method" label="Hình thức">@foreach(config('mam.payment_methods') as $k => $l)<option value="{{ $k }}" @selected(old('method') === $k)>{{ $l }}</option>@endforeach</x-select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-input type="date" name="paid_on" label="Ngày thu" value="{{ old('paid_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required />
                <x-input name="note" label="Ghi chú" value="" maxlength="255" />
            </div>
            <x-button icon="fa-check" variant="success">Xác nhận đã thu</x-button>
        </form>
    @endif

    @if($invoice->payments->isNotEmpty())
        <div>
            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Các lần thu</p>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm divide-y divide-neutral-100">
                @foreach($invoice->payments->sortByDesc('paid_on') as $p)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-neutral-900">{{ money($p->amount) }}đ · {{ $p->methodLabel() }}</p>
                            <p class="text-xs text-neutral-500">{{ $p->paid_on->format('d/m/Y') }}@if($p->creator) · {{ $p->creator->name }}@endif @if($p->note) · {{ $p->note }}@endif</p>
                        </div>
                        <form method="POST" action="{{ route('payments.destroy', $p->id) }}" onsubmit="return confirm('Xoá lần thu {{ money($p->amount) }}đ này?')">@csrf @method('DELETE')<button class="text-neutral-300 hover:text-red-500 p-1"><i class="fa-solid fa-trash-can text-xs"></i></button></form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <x-copy-block :text="$text" title="Phiếu báo gửi phụ huynh (Zalo)" />

    @if($qr && $invoice->balance() > 0)
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 text-center">
            <p class="text-sm font-bold text-neutral-900 mb-2">Mã QR chuyển khoản</p>
            <img src="{{ $qr }}" alt="Mã VietQR chuyển khoản học phí" class="mx-auto w-56 h-auto rounded-lg" loading="lazy">
            <p class="text-xs text-neutral-500 mt-2">Phụ huynh quét bằng app ngân hàng — số tiền {{ money($invoice->balance()) }}đ{{ $invoice->transferMemo() !== '' ? ' và nội dung "'.$invoice->transferMemo().'"' : '' }} được điền sẵn.</p>
        </div>
    @elseif(! $center->hasVietQr())
        <p class="text-xs text-neutral-400 text-center">Nhập mã ngân hàng + số tài khoản ở <a href="{{ route('settings.index') }}" class="underline">Cài đặt</a> để phiếu có mã QR chuyển khoản.</p>
    @endif

    @if($invoice->payments->isEmpty())
        <form method="POST" action="{{ route('invoices.destroy', $invoice->id) }}" onsubmit="return confirm('Xoá phiếu học phí này?')" class="text-center">
            @csrf @method('DELETE')
            <button class="text-xs text-red-500 hover:underline"><i class="fa-solid fa-trash-can mr-1"></i>Xoá phiếu</button>
        </form>
    @endif
</div>
@endsection
