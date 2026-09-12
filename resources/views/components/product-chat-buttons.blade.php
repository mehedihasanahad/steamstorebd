@props(['name', 'url', 'slug'])

@php
    $chat = \App\Services\ChatLinkBuilder::fromSettings();
@endphp

@if($chat->enabled())
    @php
        // Server-rendered fallback: correct before Alpine boots and if JS never runs.
        $fallbackMessage = $chat->renderMessage([
            '{product}'      => $name,
            '{denomination}' => '',
            '{price}'        => '',
            '{url}'          => $url,
        ]);
        $fallbackRef = \App\Services\ChatLinkBuilder::refToken($slug);
    @endphp

    <div class="mb-4"
         x-data="productChatButtons({
             template: {{ Js::from($chat->messageTemplate()) }},
             product:  {{ Js::from($name) }},
             url:      {{ Js::from($url) }},
             slug:     {{ Js::from($slug) }},
             waNumber: {{ Js::from($chat->whatsappNumber()) }},
             msUser:   {{ Js::from($chat->messengerUsername()) }},
         })">

        <div class="flex flex-col sm:flex-row gap-3">
            @if($chat->whatsappEnabled())
            <a href="{{ $chat->whatsappUrl($fallbackMessage) }}"
               x-bind:href="whatsappHref()"
               target="_blank" rel="noopener noreferrer"
               aria-label="Order {{ $name }} on WhatsApp"
               class="sm:flex-1 flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-base border-2 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
               style="border-color:#25D366; color:#128C7E; --tw-ring-color:#25D366;"
               onmouseover="this.style.backgroundColor='#F0FFF4';"
               onmouseout="this.style.backgroundColor='transparent';">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#25D366" aria-hidden="true" class="flex-shrink-0">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Order on WhatsApp
            </a>
            @endif

            @if($chat->messengerEnabled())
            <a href="{{ $chat->messengerUrl($fallbackRef) }}"
               x-bind:href="messengerHref()"
               target="_blank" rel="noopener noreferrer"
               aria-label="Chat about {{ $name }} on Messenger"
               class="sm:flex-1 flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-base border-2 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
               style="border-color:#0084FF; color:#0064C8; --tw-ring-color:#0084FF;"
               onmouseover="this.style.backgroundColor='#F0F7FF';"
               onmouseout="this.style.backgroundColor='transparent';">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#0084FF" aria-hidden="true" class="flex-shrink-0">
                    <path d="M12 0C5.373 0 0 4.975 0 11.111c0 3.497 1.745 6.616 4.472 8.652V24l4.086-2.242c1.09.301 2.246.465 3.442.465 6.627 0 12-4.975 12-11.112S18.627 0 12 0zm1.194 14.963l-3.055-3.26-5.963 3.26L10.426 8.4l3.129 3.26 5.889-3.26-6.25 6.563z"/>
                </svg>
                Order on Messenger
            </a>
            @endif
        </div>
    </div>

    @once
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productChatButtons', (config) => ({
                config,

                // Mirrors ChatLinkBuilder::normalise() in PHP. Keep both in sync.
                normalise(message) {
                    return message
                        .replace(/[ \t]+/g, ' ')
                        .replace(/ +([.,!?;:])/g, '$1')
                        .split(/\r\n|\r|\n/)
                        .map((line) => line.trim())
                        .filter((line) => line !== '')
                        .join('\n')
                        .trim();
                },

                get selected() {
                    return this.$store.product?.current ?? null;
                },

                get message() {
                    const picked = this.selected;
                    const tokens = {
                        '{product}': this.config.product,
                        '{denomination}': picked ? picked.denom : '',
                        '{price}': picked
                            ? '৳' + Number(picked.price).toLocaleString('en-US', { maximumFractionDigits: 0 })
                            : '',
                        '{url}': this.config.url,
                    };

                    let out = this.config.template;
                    for (const [token, value] of Object.entries(tokens)) {
                        out = out.split(token).join(value);
                    }

                    return this.normalise(out);
                },

                // Mirrors ChatLinkBuilder::refToken() in PHP.
                get refToken() {
                    const picked = this.selected;
                    const raw = 'product_' + this.config.slug + (picked ? '_' + picked.denom : '');

                    return raw
                        .replace(/[^A-Za-z0-9_]+/g, '_')
                        .replace(/_+/g, '_')
                        .replace(/^_|_$/g, '')
                        .slice(0, 255);
                },

                whatsappHref() {
                    return 'https://wa.me/' + this.config.waNumber + '?text=' + encodeURIComponent(this.message);
                },

                messengerHref() {
                    return 'https://m.me/' + this.config.msUser + '?ref=' + this.refToken;
                },
            }));
        });
    </script>
    @endpush
    @endonce
@endif
