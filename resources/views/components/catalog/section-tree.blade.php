@props([
    'sections',
    'section' => null,
    'brand'   => null,
])

{{--
    The catalog tree: every section, and the brands inside it.

    Rendered twice on the listing page — once in the desktop sidebar, once
    inside the mobile dropdown — so it lives here rather than being written
    out twice and drifting.
--}}
@foreach($sections as $railSection)
    <div x-data="{ expanded: {{ ($section?->id === $railSection->id || $brand?->catalog_section_id === $railSection->id) ? 'true' : 'false' }} }" class="mb-1">
        <div class="flex items-center gap-1">
            <a href="{{ route('category', $railSection->slug) }}"
               class="flex-1 rounded-control px-2 py-2 text-caption font-bold uppercase tracking-wide transition-colors
                      {{ $section?->id === $railSection->id ? 'text-accent-hover' : 'text-ink-hi hover:bg-surface-2' }}">
                {{ $railSection->name }}
            </a>
            <button @click="expanded = !expanded" :aria-expanded="expanded ? 'true' : 'false'"
                    aria-label="Toggle {{ $railSection->name }} brands"
                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-control text-ink-low transition-colors hover:bg-surface-2">
                <svg class="h-3.5 w-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>

        <ul x-show="expanded" x-cloak class="mt-0.5 space-y-0.5">
            @foreach($railSection->mainCategories as $railBrand)
                @php $count = $railBrand->giftCardCategories->count(); @endphp
                <li>
                    <a href="{{ route('brand', $railBrand->slug) }}"
                       @if($brand?->id === $railBrand->id) aria-current="page" @endif
                       class="flex items-center gap-2.5 rounded-control px-2 py-1.5 transition-colors
                              {{ $brand?->id === $railBrand->id ? 'bg-accent/15' : 'hover:bg-surface-2' }}">
                        <x-catalog.artwork :image="$railBrand->image" :name="$railBrand->name" ratio="aspect-square"
                                           class="w-7 flex-shrink-0 rounded-chip" :width="28" :height="28" />
                        <span class="min-w-0 flex-1 truncate text-caption {{ $brand?->id === $railBrand->id ? 'font-semibold text-accent-hover' : 'text-ink-mid' }}">{{ $railBrand->name }}</span>
                        <span class="flex-shrink-0 rounded-chip bg-surface-2 px-1.5 py-0.5 text-meta font-semibold text-ink-low">{{ $count }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endforeach
