@props([
    'rating' => 0,
    'size'   => 'w-3.5 h-3.5',
])

@php $filled = (int) round((float) $rating); @endphp

<span class="inline-flex items-center gap-0.5" role="img" aria-label="{{ number_format((float) $rating, 1) }} out of 5">
    @for($i = 1; $i <= 5; $i++)
        <svg class="{{ $size }} flex-shrink-0 {{ $i <= $filled ? 'text-warning' : 'text-surface-3' }}"
             fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.29 3.97a1 1 0 00.95.69h4.17c.97 0 1.37 1.24.59 1.81l-3.38 2.45a1 1 0 00-.36 1.12l1.29 3.97c.3.92-.76 1.69-1.54 1.12l-3.37-2.45a1 1 0 00-1.18 0l-3.37 2.45c-.79.57-1.84-.2-1.54-1.12l1.29-3.97a1 1 0 00-.37-1.12L1.05 9.4c-.78-.57-.38-1.81.59-1.81h4.17a1 1 0 00.95-.69l1.29-3.97z"/>
        </svg>
    @endfor
</span>
