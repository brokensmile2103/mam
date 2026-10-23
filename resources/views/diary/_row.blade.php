{{-- 1 dòng nhật ký. Biến: $entry, $showChild (bool), $sameDay (bool) --}}
@php
    $meta = $entry->meta();
    $user = auth()->user();
    $deletable = $user && ($user->isOwner() || ($entry->created_by === $user->id && $entry->created_at->gt(now()->subHours(12))));
    $alert = $entry->isAlert();
@endphp
<div class="flex items-start gap-3 py-3 border-b border-neutral-100 last:border-0">
    <div class="w-9 h-9 rounded-xl {{ $alert ? 'bg-red-100 text-red-700' : color_badge($meta['color']) }} flex items-center justify-center shrink-0 text-sm">
        <i class="fa-solid {{ $meta['icon'] }}"></i>
    </div>
    <div class="min-w-0 flex-1">
        <div class="text-sm font-semibold text-neutral-900">
            {{ $meta['label'] }}
            @if(($showChild ?? true) && $entry->child)
                <a href="{{ route('children.show', $entry->child_id) }}" class="font-medium text-[var(--accent-text)] hover:underline">· {{ $entry->child->displayName() }}</a>
            @endif
        </div>
        @if($entry->summary())
            <div class="text-xs {{ $alert ? 'text-red-600 font-semibold' : 'text-neutral-600' }} mt-0.5">{{ $entry->summary() }}</div>
        @endif
        @if($entry->note)
            <div class="text-xs text-neutral-500 mt-0.5 italic whitespace-pre-line">“{{ $entry->note }}”</div>
        @endif
        <div class="text-xs text-neutral-400 mt-0.5">
            {{ $entry->occurred_at->format('H:i') }}@if(! ($sameDay ?? false)) · {{ $entry->occurred_at->format('d/m/Y') }}@endif
            @if($entry->creator) · {{ $entry->creator->name }}@endif
            @if($user?->isOwner() && (float) $entry->cost > 0) · <span class="text-neutral-500">{{ money($entry->cost) }}đ vật tư</span>@endif
        </div>
    </div>
    @if($deletable)
        <form method="POST" action="{{ route('diary.destroy', $entry->id) }}" onsubmit="return confirm('Xoá ghi nhận này?{{ $entry->item_id ? ' Vật tư đã dùng sẽ được hoàn lại kho.' : '' }}')">
            @csrf @method('DELETE')
            <button class="text-neutral-300 hover:text-red-500 transition p-1" title="Xoá"><i class="fa-solid fa-trash-can text-xs"></i></button>
        </form>
    @endif
</div>
