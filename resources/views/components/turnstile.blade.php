{{--
    Cloudflare Turnstile for one form.

    Renders nothing at all when no keys are configured, so local work and any
    environment without credentials keeps working forms.

    `data-action` is sent to Cloudflare and echoed back by siteverify, which is
    what stops a token minted on the reseller form being replayed against
    registration. `data-response-field-name` renames the hidden input the
    widget injects from `cf-turnstile-response` to something our validator can
    name without hyphens.
--}}
@props([
    'action',
    'theme' => 'auto',
])

@if (\App\Services\Turnstile::enabled())
    <div>
        <div class="cf-turnstile"
             data-sitekey="{{ \App\Services\Turnstile::siteKey() }}"
             data-action="{{ $action }}"
             data-response-field-name="{{ \App\Services\Turnstile::FIELD }}"
             data-theme="{{ $theme }}"
             data-size="flexible"></div>

        @error(\App\Services\Turnstile::FIELD)
            <p class="mt-1.5 text-meta text-danger">{{ $message }}</p>
        @enderror
    </div>

    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
