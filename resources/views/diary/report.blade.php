@extends('layouts.app')
@section('title', 'Báo cáo ngày · Mầm')
@section('page-title', 'Báo cáo ngày cho phụ huynh')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-4">
        <a href="{{ route('diary.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Báo cáo ngày</h2>
    </div>
    <p class="text-sm text-neutral-500 mb-4">Mỗi bé 1 đoạn tóm tắt ăn, ngủ, vệ sinh, sức khoẻ trong ngày. Bấm <strong>Sao chép</strong> rồi dán vào Zalo/Messenger gửi phụ huynh.</p>

    <form method="GET" class="grid grid-cols-2 gap-2 mb-4">
        <input type="date" name="ngay" value="{{ $day->toDateString() }}" max="{{ now()->toDateString() }}" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
        <select name="lop" class="rounded-xl border border-neutral-300 bg-white py-2 px-3 text-sm" onchange="this.form.submit()">
            <option value="">Mọi lớp</option>
            @foreach($classrooms as $cl)<option value="{{ $cl->id }}" @selected($classroomId === $cl->id)>{{ $cl->name }}</option>@endforeach
        </select>
    </form>

    <div class="space-y-3">
        @forelse($reports as $r)
            <x-copy-block :text="$r['text']" :alert="$r['alert']">
                <x-slot:title><a href="{{ route('children.show', ['child' => $r['child']->id, 'ngay' => $day->toDateString()]) }}" class="hover:underline">{{ $r['child']->displayName() }} <span class="font-normal text-neutral-400">· {{ $r['child']->full_name }}</span></a></x-slot:title>
            </x-copy-block>
        @empty
            <div class="text-center py-12 text-neutral-400">
                <i class="fa-solid fa-paper-plane text-3xl mb-2 block"></i>
                <p class="text-sm">Ngày này chưa có điểm danh hay ghi nhận nào.</p>
            </div>
        @endforelse
    </div>

    @if($missing->isNotEmpty() && $reports->isNotEmpty())
        <p class="text-xs text-neutral-400 mt-4">Chưa có ghi nhận: {{ $missing->map(fn ($c) => $c->displayName())->implode(', ') }}.</p>
    @endif
</div>
@endsection
