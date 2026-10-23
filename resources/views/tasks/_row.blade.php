{{-- 1 công việc. Biến: $task --}}
@php
    $meta = $task->meta();
    $usesItem = $task->item_id && $task->item;
    $overdue = $task->isOverdue() && $task->due_at->lt(\Illuminate\Support\Carbon::today());
    $single = $task->target === 'child';
    // Loại cần số liệu riêng từng bé: 1 bé → nhập ngay tại đây; nhiều bé → mở màn ghi nhật ký cho cả nhóm.
    $needsValues = in_array($task->type, ['suc_khoe', 'can_do', 'an'], true) && $task->target !== 'none';
    $input = 'rounded-xl border border-neutral-300 py-2 px-3 text-sm focus:border-[var(--accent-ring)] focus:outline-none';
@endphp
<form method="POST" action="{{ route('tasks.complete', $task->id) }}" x-data="{ open: false }"
      class="bg-white rounded-xl border {{ $overdue ? 'border-red-200' : 'border-neutral-200' }} shadow-sm px-3 py-3">
    @csrf
    <div class="flex items-center gap-3">
        <button type="button" @click="open = !open" class="flex items-center gap-3 min-w-0 flex-1 text-left">
            <div class="w-10 h-10 rounded-xl {{ color_badge($meta['color']) }} flex items-center justify-center shrink-0">
                <i class="fa-solid {{ $meta['icon'] }}"></i>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-neutral-900 truncate">{{ $task->title }}</div>
                <div class="text-xs text-neutral-500 truncate">
                    <span class="{{ $overdue ? 'text-red-600 font-semibold' : '' }}">
                        {{ $task->due_at->isToday() ? $task->due_at->format('H:i') : $task->due_at->format('d/m H:i') }}
                    </span>
                    @if($task->targetLabel()) · {{ $task->targetLabel() }} @endif
                    @if($usesItem && $task->item_quantity !== null) · {{ quantity($task->item_quantity) }} {{ $task->item->unit }} {{ $task->item->name }}{{ $single ? '' : '/bé' }} @endif
                </div>
            </div>
        </button>
        @if($task->isPending())
            <button @if($needsValues && $single) x-on:click="if (! open) { open = true; $event.preventDefault(); }" @endif
                    class="shrink-0 px-3.5 py-2 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold transition active:scale-95">
                <i class="fa-solid fa-check mr-1"></i>Xong
            </button>
        @else
            <span class="shrink-0 text-xs px-2.5 py-1 rounded-full font-medium {{ $task->status === 'done' ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">
                {{ $task->status === 'done' ? 'Đã xong' : 'Đã bỏ qua' }}
            </span>
        @endif
    </div>

    @if($task->isPending())
        <div x-show="open" x-cloak class="mt-3 pt-3 border-t border-neutral-100 space-y-2.5">
            @if($needsValues && $single)
                @if($task->type === 'suc_khoe')
                    <input type="number" step="0.1" min="34" max="43" name="temperature" placeholder="Nhiệt độ (°C)" x-bind:required="open" class="w-full {{ $input }}">
                @elseif($task->type === 'can_do')
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="0.01" min="1" max="60" name="weight_kg" placeholder="Cân nặng (kg)" class="{{ $input }}">
                        <input type="number" step="0.1" min="30" max="150" name="height_cm" placeholder="Chiều cao (cm)" class="{{ $input }}">
                    </div>
                @elseif($task->type === 'an')
                    <div class="grid grid-cols-2 gap-2">
                        <select name="meal" class="{{ $input }}">@foreach(config('mam.meals') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                        <select name="level" class="{{ $input }}">@foreach(config('mam.eat_levels') as $k => $l)<option value="{{ $k }}">{{ $l['label'] }}</option>@endforeach</select>
                    </div>
                @endif
            @elseif($needsValues)
                <a href="{{ route('diary.create', ['loai' => $task->type, 'return_to' => route('tasks.index', [], false)]) }}" class="block text-xs font-semibold text-[var(--accent-text)] hover:underline">
                    <i class="fa-solid fa-pen-to-square mr-1"></i>Ghi {{ mb_strtolower($meta['label']) }} cho từng bé →
                </a>
                <p class="text-[0.7rem] text-neutral-400 -mt-1">Bấm "Xong" chỉ đánh dấu đã làm (không ghi số liệu vào sổ của bé).</p>
            @endif
            @if($usesItem)
                <div class="flex items-center gap-2">
                    <input type="number" step="any" min="0" name="item_quantity" value="{{ input_number($task->item_quantity) }}" placeholder="Số lượng {{ $single ? '' : 'mỗi bé' }}" class="flex-1 {{ $input }}">
                    <span class="text-sm text-neutral-500">{{ $task->item->unit }} {{ $task->item->name }}{{ $single ? '' : ' / bé' }}</span>
                </div>
            @endif
            <input type="text" name="note" maxlength="200" placeholder="Ghi chú (không bắt buộc)" class="w-full {{ $input }}">
            <div class="flex items-center justify-between">
                <button formaction="{{ route('tasks.skip', $task->id) }}" formnovalidate class="text-xs px-3 py-1.5 rounded-lg bg-neutral-100 text-neutral-600 hover:bg-neutral-200 font-medium transition">
                    <i class="fa-solid fa-forward mr-1"></i>Bỏ qua việc này
                </button>
                @unless($task->template_id)
                    <button type="submit" formaction="{{ route('tasks.destroy', $task->id) }}" name="_method" value="DELETE" formnovalidate
                            onclick="return confirm('Xoá công việc này?')"
                            class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-medium transition">
                        <i class="fa-solid fa-trash-can mr-1"></i>Xoá
                    </button>
                @endunless
            </div>
        </div>
    @elseif($task->status === 'skipped')
        <div class="mt-2 text-right">
            <button formaction="{{ route('tasks.reopen', $task->id) }}" formnovalidate class="text-xs text-neutral-500 hover:text-neutral-700 underline">Mở lại</button>
        </div>
    @endif
</form>
