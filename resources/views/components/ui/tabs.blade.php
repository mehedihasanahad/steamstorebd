@props([
    'tabs'    => [],   // ['key' => 'Label', ...]
    'default' => null,
])

@php
    $keys    = array_keys($tabs);
    $initial = $default ?? ($keys[0] ?? null);
    $uid     = 'tabs-' . substr(md5(implode('|', $keys) . $initial), 0, 6);
@endphp

<div x-data="{
        tab: @js($initial),
        keys: @js($keys),
        move(step) {
            const at = this.keys.indexOf(this.tab);
            const next = (at + step + this.keys.length) % this.keys.length;
            this.tab = this.keys[next];
            this.$refs[this.keys[next]]?.focus();
        },
     }"
     {{ $attributes }}>

    <div role="tablist" class="flex gap-1 overflow-x-auto border-b border-surface-3 scrollbar-hide"
         @keydown.right.prevent="move(1)" @keydown.left.prevent="move(-1)">
        @foreach($tabs as $key => $label)
            <button type="button"
                    role="tab"
                    id="{{ $uid }}-tab-{{ $key }}"
                    x-ref="{{ $key }}"
                    aria-controls="{{ $uid }}-panel-{{ $key }}"
                    :aria-selected="tab === @js($key) ? 'true' : 'false'"
                    :tabindex="tab === @js($key) ? 0 : -1"
                    @click="tab = @js($key)"
                    class="relative px-4 min-h-[44px] text-body font-semibold whitespace-nowrap transition-colors duration-150"
                    :class="tab === @js($key) ? 'text-ink-hi' : 'text-ink-low hover:text-ink-mid'">
                {{ $label }}
                <span class="absolute inset-x-0 -bottom-px h-0.5 rounded-full transition-opacity duration-150"
                      :class="tab === @js($key) ? 'bg-accent opacity-100' : 'opacity-0'"></span>
            </button>
        @endforeach
    </div>

    <div class="pt-5">{{ $slot }}</div>
</div>
