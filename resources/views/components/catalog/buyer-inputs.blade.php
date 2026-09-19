@props(['schema' => []])

{{--
    What this product must ask before it can be fulfilled — a Player ID, a
    Zone ID, an account e-mail. The rules echoed into the markup are a
    convenience for the shopper; AddToCartRequest re-validates every field
    against the stored schema, and that is the authority.
--}}
@if(! empty($schema))
<fieldset class="rounded-card border border-surface-3 bg-surface-1 p-4">
    <legend class="px-1 text-caption font-semibold text-ink-hi">Enter your account details</legend>

    <div class="mt-2 space-y-3">
        @foreach($schema as $field)
            @php
                $key = $field['key'];
                $id  = 'buyer-' . $key;
            @endphp
            <div>
                <label for="{{ $id }}" class="mb-1.5 block text-caption font-medium text-ink-mid">
                    {{ $field['label'] }}
                    @if($field['required'] ?? true)<span class="text-danger">*</span>@endif
                </label>

                <input id="{{ $id }}"
                       name="buyer_inputs[{{ $key }}]"
                       type="{{ ($field['type'] ?? 'text') === 'email' ? 'email' : (($field['type'] ?? 'text') === 'number' ? 'text' : 'text') }}"
                       @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif
                       value="{{ old('buyer_inputs.' . $key) }}"
                       placeholder="{{ $field['placeholder'] ?? '' }}"
                       @if($field['required'] ?? true) required @endif
                       class="w-full rounded-control border border-surface-3 bg-surface-2 px-3 min-h-[44px] text-body text-ink-hi
                              placeholder:text-ink-low transition-colors
                              focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">

                @if(! empty($field['help']))
                    <p class="mt-1.5 text-meta text-ink-low">{{ $field['help'] }}</p>
                @endif
                @error('buyer_inputs.' . $key)
                    <p class="mt-1.5 text-meta text-danger">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
</fieldset>
@endif
