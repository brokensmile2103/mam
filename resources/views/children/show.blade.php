@extends('layouts.app')
@section('title', $child->displayName().' · Mầm')
@section('page-title', $child->full_name)
@section('content')
@php
    $here = route('children.show', $child->id, false);
    $active = $child->isActive();
    $types = config('mam.entry_types');
    $parentUrl = $child->parent_token ? route('parent.show', $child->parent_token) : null;
    $initialTab = request()->has('page') ? 'log' : (request()->has('tab') ? request('tab') : 'day');
    $weights = $growth->whereNotNull('weight_kg')->values();
@endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto space-y-4" x-data="{ tab: @js($initialTab), panel: null }">

    <div class="flex items-center gap-3">
        <a href="{{ route('children.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <span class="w-12 h-12 rounded-full {{ $child->gender === 'nu' ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700' }} text-base font-bold flex items-center justify-center shrink-0">{{ $child->initials() }}</span>
        <div class="min-w-0 flex-1">
            <h2 class="text-lg font-bold text-neutral-900 leading-tight">{{ $child->full_name }}@if($child->nickname) <span class="font-medium text-neutral-500">({{ $child->nickname }})</span>@endif</h2>
            <p class="text-xs text-neutral-500">{{ $child->code }} · {{ $child->ageLabel() }} · sinh {{ $child->birth_date->format('d/m/Y') }}@if($child->classroom) · {{ $child->classroom->name }}@endif</p>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full font-semibold shrink-0 {{ $active ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">{{ $active ? 'Đang học' : 'Đã nghỉ' }}</span>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($child->hasAllergy())
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5 text-sm text-amber-900"><i class="fa-solid fa-hand mr-1.5 text-amber-500"></i><strong>Dị ứng:</strong> {{ $child->allergies }}</div>
    @endif

    {{-- Liên hệ nhanh --}}
    <div class="grid grid-cols-2 gap-2">
        @if($child->parent_phone)
            <a href="{{ $child->telLink() }}" class="flex items-center gap-2.5 bg-white rounded-xl border border-neutral-200 shadow-sm px-3 py-2.5 hover:border-[var(--accent-ring)]">
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center"><i class="fa-solid fa-phone"></i></span>
                <span class="min-w-0"><span class="block text-xs text-neutral-500">{{ $child->parent_relation ?: 'Phụ huynh' }} {{ $child->parent_name }}</span><span class="block text-sm font-semibold text-neutral-900">{{ $child->parent_phone }}</span></span>
            </a>
        @endif
        @if($child->parent2_phone)
            <a href="{{ $child->telLink($child->parent2_phone) }}" class="flex items-center gap-2.5 bg-white rounded-xl border border-neutral-200 shadow-sm px-3 py-2.5 hover:border-[var(--accent-ring)]">
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center"><i class="fa-solid fa-phone"></i></span>
                <span class="min-w-0"><span class="block text-xs text-neutral-500 truncate">{{ $child->parent2_name ?: 'Người thứ 2' }}</span><span class="block text-sm font-semibold text-neutral-900">{{ $child->parent2_phone }}</span></span>
            </a>
        @endif
    </div>

    {{-- Ghi nhanh cho bé này --}}
    @if($active)
        <div class="grid grid-cols-5 gap-2">
            @foreach(['an', 'ngu', 've_sinh', 'suc_khoe', 'su_co'] as $t)
                <a href="{{ route('diary.create', ['loai' => $t, 'be' => $child->id, 'return_to' => $here]) }}" class="flex flex-col items-center gap-1 bg-white rounded-xl border border-neutral-200 shadow-sm py-2.5 hover:border-[var(--accent-ring)] active:scale-95 transition">
                    <span class="w-8 h-8 rounded-lg {{ color_badge($types[$t]['color']) }} flex items-center justify-center text-sm"><i class="fa-solid {{ $types[$t]['icon'] }}"></i></span>
                    <span class="text-[0.65rem] font-semibold text-neutral-700 text-center leading-tight">{{ $types[$t]['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 bg-neutral-100 rounded-xl p-1 overflow-x-auto scrollbar-hide">
        @foreach(array_filter(['day' => 'Trong ngày', 'health' => 'Sức khoẻ', 'log' => 'Nhật ký', 'info' => 'Hồ sơ', 'fee' => $isOwner ? 'Học phí' : null]) as $k => $label)
            <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500'" class="flex-1 whitespace-nowrap text-sm font-semibold py-2 px-3 rounded-lg transition">{{ $label }}</button>
        @endforeach
    </div>

    {{-- TRONG NGÀY --}}
    <div x-show="tab === 'day'" class="space-y-3">
        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="day">
            <a href="{{ route('children.show', ['child' => $child->id, 'ngay' => $day->copy()->subDay()->toDateString()]) }}" class="w-9 h-9 rounded-lg bg-white border border-neutral-200 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-left text-xs"></i></a>
            <input type="date" name="ngay" value="{{ $day->toDateString() }}" max="{{ now()->toDateString() }}" class="flex-1 rounded-lg border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
            @unless($day->isToday())
                <a href="{{ route('children.show', ['child' => $child->id, 'ngay' => $day->copy()->addDay()->toDateString()]) }}" class="w-9 h-9 rounded-lg bg-white border border-neutral-200 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-right text-xs"></i></a>
            @endunless
        </form>

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-neutral-500">Điểm danh {{ mb_strtolower(day_label($day)) }}</p>
                @if($dayAttendance)
                    <p class="text-sm font-bold {{ $dayAttendance->isPresent() ? 'text-emerald-700' : 'text-amber-700' }}">{{ $dayAttendance->meta()['label'] }}
                        @if($dayAttendance->isPresent())<span class="font-medium text-neutral-600"> · đến {{ $dayAttendance->checkInLabel() ?? '—' }}{{ $dayAttendance->check_out ? ' · về '.$dayAttendance->checkOutLabel() : '' }}{{ $dayAttendance->picked_up_by ? ' ('.$dayAttendance->picked_up_by.' đón)' : '' }}</span>@endif
                    </p>
                @else
                    <p class="text-sm font-semibold text-neutral-400">Chưa điểm danh</p>
                @endif
            </div>
            <a href="{{ route('attendance.index', ['ngay' => $day->toDateString()]) }}#be-{{ $child->id }}" class="text-xs font-semibold text-[var(--accent-text)] hover:underline">Điểm danh →</a>
        </div>

        <x-copy-block :text="$dailyReport" title="Báo cáo gửi phụ huynh" />

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm px-4">
            @forelse($dayEntries as $entry)
                @include('diary._row', ['entry' => $entry, 'showChild' => false, 'sameDay' => true])
            @empty
                <p class="text-center text-sm text-neutral-400 py-6">Chưa có ghi nhận nào trong ngày.</p>
            @endforelse
        </div>
    </div>

    {{-- SỨC KHOẺ --}}
    <div x-show="tab === 'health'" x-cloak class="space-y-3">
        @if($child->health_note)
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 text-sm"><p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-1">Lưu ý sức khoẻ</p><p class="whitespace-pre-line text-neutral-800">{{ $child->health_note }}</p></div>
        @endif

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-bold text-neutral-900"><i class="fa-solid fa-ruler-vertical text-[var(--accent)] mr-1.5"></i>Cân đo</p>
                @if($active)<a href="{{ route('diary.create', ['loai' => 'can_do', 'be' => $child->id, 'return_to' => $here.'?tab=health']) }}" class="text-xs font-semibold text-[var(--accent-text)] hover:underline">+ Cân đo</a>@endif
            </div>
            @if($weights->count() >= 2)
                @php
                    $minW = (float) $weights->min('weight_kg'); $maxW = (float) $weights->max('weight_kg'); $span = max(0.5, $maxW - $minW);
                    $n = $weights->count();
                    $pts = $weights->values()->map(fn ($w, $i) => [round(8 + $i * (284 / max(1, $n - 1)), 1), round(82 - ((float) $w->weight_kg - $minW) / $span * 70, 1)]);
                @endphp
                <svg viewBox="0 0 300 92" class="w-full h-24 mb-2" role="img" aria-label="Biểu đồ cân nặng">
                    <polyline fill="none" stroke="var(--accent)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" points="{{ $pts->map(fn ($p) => $p[0].','.$p[1])->implode(' ') }}" />
                    @foreach($pts as $i => $p)<circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="3.5" fill="white" stroke="var(--accent)" stroke-width="2" />@endforeach
                    <text x="8" y="90" font-size="9" fill="#a3a3a3">{{ quantity($minW, 2) }} kg</text>
                    <text x="292" y="10" font-size="9" fill="#a3a3a3" text-anchor="end">{{ quantity($maxW, 2) }} kg</text>
                </svg>
            @endif
            @if($growth->isEmpty())
                <p class="text-sm text-neutral-400 text-center py-3">Chưa có lần cân đo nào.</p>
            @else
                <table class="w-full text-sm">
                    <thead><tr class="text-xs text-neutral-400 text-left"><th class="font-medium py-1">Ngày</th><th class="font-medium py-1">Tuổi</th><th class="font-medium py-1 text-right">Cân nặng</th><th class="font-medium py-1 text-right">Chiều cao</th></tr></thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($growth->sortByDesc('occurred_at') as $g)
                            <tr><td class="py-1.5">{{ $g->occurred_at->format('d/m/Y') }}</td><td class="py-1.5 text-neutral-500">{{ $child->ageLabel($g->occurred_at) }}</td><td class="py-1.5 text-right font-semibold">{{ $g->weight_kg !== null ? quantity($g->weight_kg, 2).' kg' : '—' }}</td><td class="py-1.5 text-right font-semibold">{{ $g->height_cm !== null ? quantity($g->height_cm, 1).' cm' : '—' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <p class="text-sm font-bold text-neutral-900 mb-2"><i class="fa-solid fa-calendar-check text-[var(--accent)] mr-1.5"></i>Chuyên cần tháng {{ now()->format('m/Y') }}</p>
            <div class="grid grid-cols-3 gap-2 text-center">
                @foreach(config('mam.attendance_statuses') as $k => $s)
                    <div class="rounded-xl {{ color_badge($s['color']) }} py-2"><p class="text-lg font-extrabold">{{ (int) ($monthAttendance[$k] ?? 0) }}</p><p class="text-[0.65rem] font-semibold">{{ $s['label'] }}</p></div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- NHẬT KÝ --}}
    <div x-show="tab === 'log'" x-cloak>
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm px-4">
            @forelse($entries as $entry)
                @include('diary._row', ['entry' => $entry, 'showChild' => false])
            @empty
                <p class="text-center text-sm text-neutral-400 py-8">Chưa có ghi nhận nào.</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $entries->appends(['tab' => 'log'])->links() }}</div>
    </div>

    {{-- HỒ SƠ --}}
    <div x-show="tab === 'info'" x-cloak class="space-y-3">
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 text-sm space-y-1.5">
            @if($child->genderLabel())<p><span class="text-neutral-400">Giới tính:</span> {{ $child->genderLabel() }}</p>@endif
            <p><span class="text-neutral-400">Nhập học:</span> {{ $child->enrolled_on->format('d/m/Y') }}@if($child->left_on) · <span class="text-neutral-400">Nghỉ:</span> {{ $child->left_on->format('d/m/Y') }}@endif</p>
            @if($child->pickup_note)<p><span class="text-neutral-400">Người được đón:</span> {{ $child->pickup_note }}</p>@endif
            @if($child->address)<p><span class="text-neutral-400">Địa chỉ:</span> {{ $child->address }}</p>@endif
            @if($child->note)<p class="whitespace-pre-line"><span class="text-neutral-400">Ghi chú:</span> {{ $child->note }}</p>@endif
        </div>

        @if($isOwner)
            {{-- Link phụ huynh --}}
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4" x-data="{ copied: false }">
                <p class="text-sm font-bold text-neutral-900 mb-1"><i class="fa-solid fa-link text-[var(--accent)] mr-1.5"></i>Link cho phụ huynh</p>
                <p class="text-xs text-neutral-500 mb-3">Phụ huynh mở link (không cần đăng nhập) để xem nhật ký 7 ngày, cân đo, thực đơn tuần và học phí của bé. Chỉ gửi cho đúng gia đình bé.</p>
                @if($parentUrl)
                    <div class="flex items-center gap-2 mb-2">
                        <input type="text" readonly value="{{ $parentUrl }}" class="flex-1 min-w-0 rounded-lg border border-neutral-300 bg-neutral-50 py-2 px-3 text-xs text-neutral-600" onclick="this.select()">
                        <button type="button" @click="copyText(@js($parentUrl)).then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="text-xs px-3 py-2 rounded-lg font-semibold" :class="copied ? 'bg-emerald-600 text-white' : 'bg-[var(--accent-light)] text-[var(--accent-text)]'"><span x-text="copied ? 'Đã chép' : 'Sao chép'">Sao chép</span></button>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ $parentUrl }}" target="_blank" class="text-xs px-3 py-1.5 rounded-lg bg-neutral-100 text-neutral-700 font-medium">Xem thử</a>
                        <form method="POST" action="{{ route('children.share', $child->id) }}" onsubmit="return confirm('Tạo link mới? Link cũ sẽ không mở được nữa.')">@csrf<button class="text-xs px-3 py-1.5 rounded-lg bg-neutral-100 text-neutral-700 font-medium">Đổi link</button></form>
                        <form method="POST" action="{{ route('children.unshare', $child->id) }}" onsubmit="return confirm('Tắt link phụ huynh?')">@csrf @method('DELETE')<button class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-600 font-medium">Tắt link</button></form>
                    </div>
                @elseif($active)
                    <form method="POST" action="{{ route('children.share', $child->id) }}">@csrf<button class="text-sm px-4 py-2 rounded-xl bg-[var(--accent)] text-white font-semibold"><i class="fa-solid fa-link mr-1"></i>Tạo link phụ huynh</button></form>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('children.edit', $child->id) }}" class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold"><i class="fa-solid fa-pen mr-1"></i>Sửa hồ sơ</a>
                @if($active)
                    <button type="button" @click="panel = panel === 'move' ? null : 'move'" class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold"><i class="fa-solid fa-right-left mr-1"></i>Chuyển lớp</button>
                    <form method="POST" action="{{ route('children.leave', $child->id) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('Chuyển '.$child->displayName().' sang danh sách nghỉ học? Hồ sơ, nhật ký và học phí vẫn được giữ lại.') }})">
                        @csrf<button class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold"><i class="fa-solid fa-door-open mr-1"></i>Nghỉ học</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('children.rejoin', $child->id) }}">@csrf<button class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold"><i class="fa-solid fa-rotate-left mr-1"></i>Đi học lại</button></form>
                @endif
                <form method="POST" action="{{ route('children.destroy', $child->id) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('XOÁ VĨNH VIỄN hồ sơ '.$child->full_name.' cùng toàn bộ điểm danh, nhật ký? Không thể hoàn tác. (Nếu bé chỉ nghỉ học, hãy dùng nút "Nghỉ học".)') }})">
                    @csrf @method('DELETE')<button class="text-xs px-3.5 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-semibold"><i class="fa-solid fa-trash-can mr-1"></i>Xoá</button>
                </form>
            </div>

            <form x-show="panel === 'move'" x-cloak method="POST" action="{{ route('children.move', $child->id) }}" class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 space-y-3">
                @csrf
                <x-select name="classroom_id" label="Chuyển sang lớp">
                    <option value="">— Chưa xếp lớp —</option>
                    @foreach($classrooms as $cl)<option value="{{ $cl->id }}" @selected($child->classroom_id === $cl->id)>{{ $cl->name }}</option>@endforeach
                </x-select>
                <x-button icon="fa-check">Lưu</x-button>
            </form>
        @endif
    </div>

    {{-- HỌC PHÍ (chủ nhóm) --}}
    @if($isOwner)
        <div x-show="tab === 'fee'" x-cloak class="space-y-2">
            <p class="text-xs text-neutral-500">Mức áp dụng:
                @if($child->isDailyTuition())
                    <strong>gửi ngày</strong> — học phí <strong>{{ money($child->effectiveDailyFee()) }}đ</strong>/ngày có mặt
                @else
                    học phí <strong>{{ money($child->effectiveTuition()) }}đ</strong>/tháng
                @endif
                · tiền ăn <strong>{{ money($child->effectiveMealRate()) }}đ</strong>/ngày{{ $child->hasCustomFee() ? '' : ' (theo mức chung)' }}.</p>
            @forelse($invoices as $inv)
                <a href="{{ route('invoices.show', $inv->id) }}" class="flex items-center justify-between bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3 hover:border-[var(--accent-ring)]">
                    <span><span class="block text-sm font-semibold text-neutral-900">{{ $inv->monthLabel() }}</span><span class="block text-xs text-neutral-500">{{ $inv->daysSummary() }} · {{ $inv->statusLabel() }}</span></span>
                    <span class="text-right"><span class="block text-sm font-bold text-neutral-900">{{ money($inv->total_amount) }}đ</span>@if($inv->balance() > 0)<span class="block text-xs font-semibold text-red-600">còn {{ money($inv->balance()) }}đ</span>@endif</span>
                </a>
            @empty
                <p class="text-center text-sm text-neutral-400 py-6">Chưa có phiếu học phí nào. Tạo phiếu ở mục <a href="{{ route('invoices.index') }}" class="underline">Học phí</a>.</p>
            @endforelse
        </div>
    @endif
</div>
@endsection
