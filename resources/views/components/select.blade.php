@props(['label' => null, 'name', 'icon' => null])
<div>
    @if($label)
        <label for="{{ $attributes->get('id', $name) }}" class="block text-sm font-medium text-neutral-700 mb-2">{{ $label }}</label>
    @endif
    <select name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
        {{ $attributes->except('id')->merge(['class' => 'w-full rounded-xl border border-neutral-300 bg-white text-neutral-900 py-3 px-3 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition']) }}>
        {{ $slot }}
    </select>
    @error($name)
        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
            <i class="fa-solid fa-circle-exclamation"></i>{{ $message }}
        </p>
    @enderror
</div>
