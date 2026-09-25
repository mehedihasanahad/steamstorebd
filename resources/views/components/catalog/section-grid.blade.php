@props([
    'title',
    'subtitle' => null,
    'viewAll'  => null,
    'id'       => null,
])

{{-- A homepage section whose cards wrap onto as many rows as they need, for a
     section holding too few brands to fill a rail or too many to scroll
     through. The column counts match the rail's card widths, so switching a
     section between the two shapes does not resize its cards.

     No Alpine, no scroll container: nothing here to drag, autoplay or nudge. --}}
<section @if($id) id="{{ $id }}" @endif aria-labelledby="{{ Str::slug($title) }}-heading" {{ $attributes }}>
    <x-catalog.section-heading :title="$title" :subtitle="$subtitle" :view-all="$viewAll" />

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">{{ $slot }}</div>
</section>
