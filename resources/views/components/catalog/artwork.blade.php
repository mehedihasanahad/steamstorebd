@props([
    'image'  => null,
    'name'   => '',
    'ratio'  => 'aspect-[4/3]',
    'width'  => 320,
    'height' => 240,
    'eager'  => false,
])

{{-- Every catalog image goes through here so explicit dimensions, lazy
     loading and the no-artwork fallback are decided once. --}}
<div {{ $attributes->class(['relative overflow-hidden bg-surface-2', $ratio]) }}>
    @if($image)
        <img src="{{ Storage::disk('public')->url($image) }}"
             alt="{{ $name }}"
             width="{{ $width }}" height="{{ $height }}"
             loading="{{ $eager ? 'eager' : 'lazy' }}"
             decoding="async"
             class="absolute inset-0 w-full h-full object-cover">
    @else
        <div class="absolute inset-0 flex items-center justify-center bg-surface-2" aria-hidden="true">
            <span class="text-title font-black text-ink-low">{{ strtoupper(mb_substr($name, 0, 2)) }}</span>
        </div>
    @endif
</div>
