@props([
    'image'  => null,
    'name'   => '',
    'ratio'  => 'aspect-[4/3]',
    'width'  => 320,
    'height' => 240,
    'eager'  => false,
    'alt'    => '',
])

{{-- Every catalog image goes through here so explicit dimensions, lazy
     loading and the no-artwork fallback are decided once.

     `alt` is empty by default because every call site paints the name as
     visible text beside the thumbnail: repeating it in the alt makes a screen
     reader announce the same brand twice per card. A caller whose image is not
     accompanied by its own name passes `:alt` explicitly. `name` still feeds
     the initials shown when there is no artwork at all. --}}
<div {{ $attributes->class(['relative overflow-hidden bg-surface-2', $ratio]) }}>
    @if($image)
        <img src="{{ Storage::disk('public')->url($image) }}"
             alt="{{ $alt }}"
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
