@props([
    'title',
    'viewAll' => null,
    'id'      => null,
])

{{-- A homepage section whose cards sit in one row, scrolled sideways.
     x-catalog.section-grid is the same section wrapped onto several rows;
     a catalog section picks between them with its display_mode. --}}
<section @if($id) id="{{ $id }}" @endif aria-labelledby="{{ Str::slug($title) }}-heading" {{ $attributes }}>
    <x-catalog.section-heading :title="$title" :view-all="$viewAll" />

    <x-ui.rail>{{ $slot }}</x-ui.rail>
</section>
