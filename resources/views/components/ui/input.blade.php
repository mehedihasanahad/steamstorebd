@props([
    'label'  => null,
    'name'   => null,
    'type'   => 'text',
    'hint'   => null,
    'error'  => null,
])

@php $id = $attributes->get('id') ?? ($name ? 'f-' . $name : null); @endphp

<div class="w-full">
    @if($label)
        <label @if($id) for="{{ $id }}" @endif class="block text-caption font-medium text-ink-mid mb-1.5">
            {{ $label }}
            @if($attributes->get('required'))<span class="text-danger">*</span>@endif
        </label>
    @endif

    <input type="{{ $type }}"
           @if($name) name="{{ $name }}" @endif
           @if($id) id="{{ $id }}" @endif
           @if($error) aria-invalid="true" @endif
           {{ $attributes->except(['id'])->class([
               'w-full rounded-control border bg-surface-2 px-3 min-h-[44px] text-body text-ink-hi',
               'placeholder:text-ink-low transition-colors duration-150',
               'focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30',
               'disabled:opacity-50',
               $error ? 'border-danger' : 'border-surface-3',
           ]) }}>

    @if($error)
        <p class="mt-1.5 text-meta text-danger">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-1.5 text-meta text-ink-low">{{ $hint }}</p>
    @endif
</div>
