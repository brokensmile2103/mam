<!DOCTYPE html>
<html lang="vi" data-accent="{{ $center->accent_color ?: 'pink' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $child->displayName() }} · {{ $center->name }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 antialiased">
@php $meals = config('mam.meals'); @endphp
<div class="max-w-xl mx-auto px-4 py-6 space-y-4">
    <header class="text-center">
        <p class="text-xs font-semibold text-[var(--accent-text)] uppercase tracking-wider"><i class="fa-solid fa-seedling mr-1"></i>{{ $center->name }}</p>
        @if($center->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $center->phone) }}" class="text-xs text-neutral-500 hover:underline"><i class="fa-solid fa-phone mr-1"></i>{{ $center->phone }}</a>@endif
    </header>

    <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm p-5 flex items-center gap-4">
        <span class="w-16 h-16 rounded-full {{ $child->gender === 'nu' ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700' }} text-xl font-bold flex items-center justify-center shrink-0">{{ $child->initials() }}</span>
        <div class="min-w-0">
            <h1 class="text-xl font-extrabold text-neutral-900">{{ $child->displayName() }}</h1>
            <p class="text-sm text-neutral-500">{{ $child->full_name }}</p>
            <p class="text-xs text-neutral-400 mt-0.5">{{ $child->ageLabel() }}@if($child->classroom) · {{ $child->classroom->name }}@endif</p>
        </div>
    </div>

    @foreach($days as $i => $d)
        @php $att = $d['attendance']; $entries = $d['entries']; @endphp
        <details class="bg-white rounded-2xl border border-neutral-200 shadow-sm group" @if($i === 0) open @endif>
            <summary class="list-none cursor-pointer px-4 py-3 flex items-center justify-between">
                <span>
                    <span class="text-sm font-bold text-neutral-900">{{ day_label($d['date']) }}</span>
                    <span class="text-xs text-neutral-400 ml-1">{{ $d['date']->format('d/m') }}</span>
                </span>
                <span class="flex items-center gap-2">
                    @if($att)
                        <span class="text-[0.7rem] px-2 py-0.5 rounded-full font-semibold {{ color_badge($att->meta()['color']) }}">{{ $att->meta()['label'] }}</span>
                    @endif
                    <i class="fa-solid fa-chevron-down text-xs text-neutral-400 group-open:rotate-180 transition"></i>
                </span>
            </summary>
            <div class="px-4 pb-4">
                @if($att?->isPresent())
                    <p class="text-xs text-neutral-500 mb-2"><i class="fa-solid fa-door-open mr-1"></i>Đến lớp {{ $att->checkInLabel() ?? '—' }}@if($att->check_out) · Về {{ $att->checkOutLabel() }}{{ $att->picked_up_by ? ' ('.$att->picked_up_by.' đón)' : '' }}@endif</p>
                @endif
                @forelse($entries as $e)
                    @php $m = $e->meta(); @endphp
                    <div class="flex items-start gap-3 py-2 border-t border-neutral-100 first:border-0">
                        <span class="w-8 h-8 rounded-lg {{ $e->isAlert() ? 'bg-red-100 text-red-700' : color_badge($m['color']) }} flex items-center justify-center shrink-0 text-sm"><i class="fa-solid {{ $m['icon'] }}"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-neutral-900">{{ $m['label'] }} <span class="text-xs font-normal text-neutral-400">{{ $e->occurred_at->format('H:i') }}</span></p>
                            @if($e->summary(false))<p class="text-xs {{ $e->isAlert() ? 'text-red-600 font-semibold' : 'text-neutral-600' }}">{{ $e->summary(false) }}</p>@endif
                            @if($e->note)<p class="text-xs text-neutral-500 italic whitespace-pre-line">{{ $e->note }}</p>@endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-400 text-center py-3">{{ $att && ! $att->isPresent() ? 'Bé nghỉ hôm nay.' : 'Cô chưa ghi nhận gì.' }}</p>
                @endforelse
            </div>
        </details>
    @endforeach

    @if($menus->isNotEmpty())
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <p class="text-sm font-bold text-neutral-900 mb-2"><i class="fa-solid fa-utensils text-[var(--accent)] mr-1.5"></i>Thực đơn tuần này</p>
            <div class="space-y-2">
                @foreach(range(0, 5) as $i)
                    @php $day = $monday->copy()->addDays($i); $dm = $menus->get($day->toDateString()); @endphp
                    @if($dm)
                        <div class="text-sm {{ $day->isToday() ? 'bg-[var(--accent-light)] -mx-2 px-2 py-1 rounded-lg' : '' }}">
                            <p class="text-xs font-bold text-neutral-500">{{ day_label($day) }}</p>
                            @foreach($meals as $k => $label)@if(isset($dm[$k]))<p><span class="text-neutral-400">{{ $label }}:</span> {{ $dm[$k] }}</p>@endif @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if($growth->isNotEmpty())
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <p class="text-sm font-bold text-neutral-900 mb-2"><i class="fa-solid fa-ruler-vertical text-[var(--accent)] mr-1.5"></i>Cân đo gần đây</p>
            <div class="divide-y divide-neutral-100 text-sm">
                @foreach($growth as $g)
                    <div class="flex justify-between py-1.5"><span class="text-neutral-500">{{ $g->occurred_at->format('d/m/Y') }}</span><span class="font-semibold">{{ $g->summary(false) }}</span></div>
                @endforeach
            </div>
        </div>
    @endif

    @foreach($invoices as $inv)
        @php $qr = vietqr_url($center->bank_code, $center->bank_account, $inv->balance(), $inv->transferMemo(), $center->bank_holder); @endphp
        <div class="bg-white rounded-2xl border border-amber-200 shadow-sm p-4">
            <p class="text-sm font-bold text-neutral-900 mb-2"><i class="fa-solid fa-file-invoice-dollar text-amber-500 mr-1.5"></i>Học phí {{ mb_strtolower($inv->monthLabel()) }}</p>
            <div class="text-sm space-y-1">
                @if((float) $inv->tuition_amount > 0)<div class="flex justify-between gap-3"><span class="text-neutral-500">{{ $inv->tuitionLabel() }}</span><span class="shrink-0">{{ money($inv->tuition_amount) }}đ</span></div>@endif
                @if((float) $inv->meal_amount > 0)<div class="flex justify-between"><span class="text-neutral-500">Tiền ăn ({{ $inv->meal_days }} ngày)</span><span>{{ money($inv->meal_amount) }}đ</span></div>@endif
                @if((float) $inv->other_amount > 0)<div class="flex justify-between"><span class="text-neutral-500">{{ $inv->other_label ?: 'Khoản khác' }}</span><span>{{ money($inv->other_amount) }}đ</span></div>@endif
                @if((float) $inv->discount_amount > 0)<div class="flex justify-between"><span class="text-neutral-500">Giảm trừ</span><span>−{{ money($inv->discount_amount) }}đ</span></div>@endif
                @if((float) $inv->paid_amount > 0)<div class="flex justify-between"><span class="text-neutral-500">Đã đóng</span><span>{{ money($inv->paid_amount) }}đ</span></div>@endif
                <div class="flex justify-between font-bold pt-1 border-t border-neutral-100"><span>Cần đóng</span><span class="text-red-600">{{ money($inv->balance()) }}đ</span></div>
            </div>
            @if($center->bank_account)
                <p class="text-xs text-neutral-500 mt-3">Chuyển khoản: <strong>{{ trim(($center->bank_code ? $center->bank_code.' · ' : '').$center->bank_account) }}</strong>@if($center->bank_holder) · {{ $center->bank_holder }}@endif@if($inv->transferMemo() !== '')<br>Nội dung: <strong>{{ $inv->transferMemo() }}</strong>@endif</p>
            @endif
            @if($qr)<img src="{{ $qr }}" alt="Mã QR chuyển khoản" class="mx-auto w-52 mt-3 rounded-lg" loading="lazy">@endif
            @if($center->invoice_note)<p class="text-xs text-neutral-400 mt-2">{{ $center->invoice_note }}</p>@endif
        </div>
    @endforeach

    <p class="text-center text-[0.7rem] text-neutral-400 pt-2">Trang dành riêng cho gia đình bé — vui lòng không chia sẻ link này.<br>Tạo bởi <a href="{{ url('/') }}" class="underline">Mầm</a> · phần mềm quản lý nhóm trẻ</p>
</div>
</body>
</html>
