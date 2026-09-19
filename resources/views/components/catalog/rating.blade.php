@props([
    'rating' => null,
    'count'  => 0,
])

{{--
    Hidden entirely on a product nobody has rated. Showing "0/5" advertises
    absence; showing nothing simply says the question has not come up yet.
--}}
@if($rating !== null && $count > 0)
    <span {{ $attributes->class('inline-flex items-center gap-1.5 text-caption text-ink-mid') }}>
        <x-ui.stars :rating="$rating" />
        <span class="font-semibold text-ink-hi tabular-nums">{{ number_format((float) $rating, 1) }}</span>
        <span class="text-ink-low">({{ $count }})</span>
    </span>
@endif
