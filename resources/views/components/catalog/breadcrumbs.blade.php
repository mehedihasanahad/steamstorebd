@props(['items' => []])

{{-- Full path on every breakpoint. Mobile truncation to a single "Back" is
     the most common mobile catalog mistake and this store is mobile-dominant. --}}
<nav aria-label="Breadcrumb" {{ $attributes->class('min-w-0') }}>
    <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-caption text-ink-low">
        @foreach($items as $item)
            <li class="flex items-center gap-1.5 min-w-0">
                @if(! $loop->first)
                    <svg class="w-3 h-3 flex-shrink-0 text-surface-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif

                @if($loop->last || empty($item['url']))
                    <span class="font-medium text-ink-hi truncate" @if($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="hover:text-accent-hover transition-colors truncate">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
