@props([
    'brand',
    'width' => 'w-40 sm:w-48',
])

{{-- A brand tile in a homepage or section rail: artwork, name beneath. --}}
<a href="{{ route('brand', $brand->slug) }}"
   {{ $attributes->class([
       'group block overflow-hidden rounded-card border border-surface-3 bg-surface-1 shadow-card',
       'transition-colors duration-150 hover:border-accent/50',
       $width,
   ]) }}>
    <x-catalog.artwork :image="$brand->image" :name="$brand->name" ratio="aspect-[4/3]" :width="176" :height="132" />
    <div class="px-3 py-2.5">
        <p class="text-caption font-semibold text-ink-hi truncate group-hover:text-accent-hover transition-colors">{{ $brand->name }}</p>
    </div>
</a>
