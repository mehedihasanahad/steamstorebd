@props([
    'title',
    'viewAll' => null,
    'id'      => null,
])

<section @if($id) id="{{ $id }}" @endif aria-labelledby="{{ Str::slug($title) }}-heading" {{ $attributes }}>
    <div class="mb-3 flex items-end justify-between gap-4">
        <h2 id="{{ Str::slug($title) }}-heading" class="text-lede md:text-title font-bold uppercase tracking-wide text-ink-hi">{{ $title }}</h2>
        @if($viewAll)
            <a href="{{ $viewAll }}" class="flex-shrink-0 rounded-control border border-surface-3 px-3 py-1.5 text-caption font-medium text-ink-mid transition-colors hover:border-accent/50 hover:text-ink-hi">
                View all
            </a>
        @endif
    </div>

    <x-ui.rail>{{ $slot }}</x-ui.rail>
</section>
