@props([
    'region'   => null,
    'showName' => true,
])

@php
    $flag = \App\Support\Region::flag($region);
    $name = \App\Support\Region::name($region);
@endphp

@if($flag)
    <span {{ $attributes->class('inline-flex items-center gap-1.5 text-caption text-ink-mid whitespace-nowrap') }}>
        <span aria-hidden="true">{{ $flag }}</span>
        @if($showName)<span>{{ $name }}</span>@else<span class="sr-only">{{ $name }}</span>@endif
    </span>
@endif
