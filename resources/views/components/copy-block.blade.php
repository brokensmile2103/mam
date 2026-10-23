@props(['text', 'title' => null, 'alert' => false])
{{-- Khối văn bản + nút "Sao chép" (dán Zalo/Messenger). --}}
<div x-data="{ copied: false }" {{ $attributes->merge(['class' => 'bg-white rounded-2xl border shadow-sm p-4 '.($alert ? 'border-red-200' : 'border-neutral-200')]) }}>
    <div class="flex items-center justify-between gap-2 mb-2">
        <div class="min-w-0 text-sm font-bold text-neutral-900">{{ $title ?? '' }}</div>
        <button type="button" @click="copyText($refs.t.innerText).then(() => { copied = true; setTimeout(() => copied = false, 1500) }).catch(() => alert('Không sao chép được — hãy bôi đen đoạn văn bản rồi sao chép thủ công.'))"
                class="shrink-0 text-xs px-3 py-1.5 rounded-lg font-semibold transition" :class="copied ? 'bg-emerald-600 text-white' : 'bg-[var(--accent-light)] text-[var(--accent-text)]'">
            <i class="fa-solid" :class="copied ? 'fa-check' : 'fa-copy'"></i> <span x-text="copied ? 'Đã chép' : 'Sao chép'">Sao chép</span>
        </button>
    </div>
    <pre x-ref="t" class="whitespace-pre-wrap font-sans text-sm text-neutral-700 leading-relaxed">{{ $text }}</pre>
</div>
