# Product Page WhatsApp & Messenger Buttons

**Date:** 2026-09-05
**Status:** Approved
**Branch:** `feat/product-chat-buttons`

## Goal

Add WhatsApp and Messenger contact buttons directly beneath the "Add to Cart" / "Buy Now"
row on the product details page, so a customer with a question can reach support without
leaving the page. Both buttons are configured from the admin panel, independently of the
existing floating chat widget. The admin settings page is reorganised into tabs at the
same time.

## Context

- Settings live in a key/value `site_settings` table behind `App\Models\SiteSetting`
  (600-second per-key cache) and the `site_setting()` helper in `app/Helpers/helpers.php`.
- The admin page is `App\Filament\Pages\SiteSettings` (Filament v3.3.50), a custom
  `Page implements HasForms` with `statePath('data')` and a hand-rolled `save()` that
  maps each key to a group.
- The product page is `resources/views/storefront/product.blade.php`, served by
  `StorefrontController::product()` with `$category` and `$denominations`.
- Denomination selection is Alpine state: `Alpine.store('product')` holds
  `denominations[]`, `selected`, `qty`, and a `current` getter. Each denomination entry is
  `{ id, denom, bdt, price, stock, slug }` where `denom` is the pre-formatted string
  (e.g. `$10`) and `price` is the raw BDT number.
- An existing floating chat widget in `resources/views/layouts/storefront.blade.php`
  (lines 387-427) uses the separate `chat` settings group. **It is not modified.**

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Settings source | Fully independent from the floating widget | Lets product enquiries route to a different number than the floating button |
| Message content | Live product + denomination, built by Alpine | Support sees exactly what the customer was looking at |
| Disabled state | Always clickable | A customer unsure which denomination to buy is exactly who needs chat |
| Layout | Side-by-side pair under a captioned divider | Reads as a secondary action, does not compete with Buy Now |
| Architecture | Service class + Blade component | Sanitisation logic is small but error-prone; unit tests pin it down |
| Messenger behaviour | `m.me/<page>?ref=<token>` | Messenger cannot pre-fill visible text (see Constraint below) |
| Message template | One WhatsApp-only field | Avoids a Messenger field whose effect is invisible |
| Admin layout | 5 balanced tabs | The page already has 6 sections and is gaining a 7th |

## Constraint: Messenger cannot pre-fill a message

`wa.me/<number>?text=<urlencoded>` opens WhatsApp with the text ready to send.
`m.me/<page>` has **no** user-visible equivalent — Facebook removed prefilled text.
The only attachable value is `m.me/<page>?ref=<token>`, delivered to the page's webhook
and invisible to the customer unless a Messenger bot is wired up.

Consequences, all deliberate:

- Only WhatsApp gets a message template.
- The Messenger button still carries a `ref` token encoding product context, so the data
  is there if a bot is added later. It is harmless when no bot exists.
- The admin panel says this plainly in helper text, so nobody configures a Messenger
  message that silently does nothing.

## Settings

New keys, all in group `product_chat`:

| Key | Type | Default |
|---|---|---|
| `product_chat_whatsapp_enabled` | bool | `false` |
| `product_chat_whatsapp_number` | string | `''` |
| `product_chat_whatsapp_template` | text | see below |
| `product_chat_messenger_enabled` | bool | `false` |
| `product_chat_messenger_username` | string | `''` |

Default template (two lines):

```
Hi! I'm interested in {product} {denomination} {price}.
{url}
```

Supported placeholders — no others are recognised:

| Token | Renders as | When nothing is selected |
|---|---|---|
| `{product}` | Category name, e.g. `Steam Wallet` | unchanged |
| `{denomination}` | `current.denom`, e.g. `$10` | empty string |
| `{price}` | `৳1,250` — symbol and thousands separator included | empty string |
| `{url}` | Absolute product URL | unchanged |

`{price}` is built from the raw `current.price` number on both sides and must format
identically: PHP `'৳' . number_format($price, 0)`, JS
`'৳' + Number(price).toLocaleString('en-US', { maximumFractionDigits: 0 })`. The explicit
`en-US` locale matters — the default locale would render Bengali-Indic digits or a
different grouping on some devices, so the same product would produce two different
messages depending on the customer's phone.

`{price}` deliberately includes its own currency symbol so a template never has to wrap a
possibly-empty token in punctuation.

`{qty}` is **not** supported. Quantity is a checkout concern, not a pre-purchase question.

## Component 1: `app/Services/ChatLinkBuilder.php`

**Config is injected, not read from the container.** The constructor takes the five
values directly; a `fromSettings()` named constructor is the only place that touches
`site_setting()`. This keeps the class pure so its tests need no database, no cache and
no application boot — see Testing below for why that matters here.

```php
public function __construct(
    bool $whatsappEnabled,
    string $whatsappNumber,
    string $whatsappTemplate,
    bool $messengerEnabled,
    string $messengerUsername,
) {}

public static function fromSettings(): self   // the only site_setting() caller

whatsappEnabled(): bool                   // toggle on AND number survives sanitisation
messengerEnabled(): bool                  // toggle on AND username survives normalisation
enabled(): bool                           // either channel usable
messageTemplate(): string                 // raw template, handed to Alpine
renderMessage(array $replacements): string
whatsappUrl(string $message): string      // https://wa.me/<digits>?text=<rawurlencoded>
messengerUrl(string $ref = ''): string    // https://m.me/<user>[?ref=<token>]
```

The Blade component calls `ChatLinkBuilder::fromSettings()`.

### Normalisation rules

**Phone number** — `preg_replace('/\D/', '', $raw)`. `+880 17-11 22 33 44` becomes
`8801711223344`. An empty result disables the channel regardless of the toggle.

**Messenger username** — trim, then strip a leading `@`, a leading
`https://m.me/`, `https://www.facebook.com/`, `https://facebook.com/`, `m.me/` or
`facebook.com/`, and any trailing `/`. Admins paste URLs; this absorbs that.
An empty result disables the channel regardless of the toggle.

**Enabled guard** — a channel is only enabled when its toggle is on *and* its contact
detail is non-empty after normalisation. This is what prevents a dead `wa.me/` link.

### Message rendering

`renderMessage()` substitutes the four tokens, then normalises in this exact order.
Newlines are preserved throughout — only horizontal whitespace is collapsed:

1. Substitute tokens (unknown tokens are left untouched).
2. Collapse runs of spaces/tabs: `/[ \t]+/` to a single space.
3. Pull punctuation back onto the preceding word: `/ +([.,!?;:])/` to `$1`.
4. Trim each line; drop lines that are now empty.
5. Trim the whole string.

This is what turns the default template, with nothing selected, into
`Hi! I'm interested in Steam Wallet.` followed by the URL — rather than
`Hi! I'm interested in Steam Wallet  .` with a stray gap and floating period.

### Ref token

`product_<slug>` plus `_<denomination>` when one is selected. Every character outside
`[A-Za-z0-9_]` folds to `_`, repeated underscores collapse, leading/trailing underscores
are trimmed, and the result is capped at 255 characters — Facebook rejects arbitrary
ref strings.

## Component 2: `resources/views/components/product-chat-buttons.blade.php`

Anonymous Blade component. Props: `:name`, `:url`, `:slug`.
Renders **nothing at all** when `ChatLinkBuilder::enabled()` is false.

### Progressive enhancement

Each button is a real `<a href="...">` server-rendered with the no-denomination fallback
message. Alpine's `:href` then overrides it live as the customer picks a denomination.
The link therefore works before Alpine boots and if JS fails. Store access is defensive
(`$store.product?.current`) so the component does not break on a page that has not
registered the `product` store.

The JS mirrors steps 2-4 of the rendering rules above in roughly four regexes.

### Markup

- A hairline divider with a centred caption: **"Need help? Order via chat"**.
- Two `flex-1` buttons in one `flex gap-3` row, matching the page's existing
  `rounded-2xl` / `border-2` vocabulary, at `py-3.5` versus Buy Now's `py-4` so they
  read as secondary.
- WhatsApp `#25D366`, Messenger `#0084FF`. SVG paths are reused from the floating
  buttons in the storefront layout.
- If only one channel is enabled it takes the full width — `flex-1` handles this.
- `target="_blank"`, `rel="noopener noreferrer"`, an `aria-label` per link, and a
  visible focus ring.

## Placement

A single `<x-product-chat-buttons>` line at `product.blade.php:314`, directly below the
Add to Cart / Buy Now row — after the `@endif` on line 313 and before the
"How to Redeem" block that currently starts on line 315.

It therefore sits **outside** the `@if($denominations->isNotEmpty())` guard (lines
271-313) that wraps those buttons. When a product is out of stock the purchase buttons
disappear, and that is exactly when a customer wants to ask when it will be back.

## Admin panel restructure

`SiteSettings::form()` wraps its existing sections in one `Forms\Components\Tabs` with
`persistTabInQueryString()`:

| Tab | Contains |
|---|---|
| General | site name, contact email, contact WhatsApp |
| Homepage | *Hero Section* + *Announcement Bar* |
| Chat & Buttons | *Floating Chat Buttons* + *Product Page Chat Buttons* |
| Referral | existing 7 fields |
| Payments | existing 4 fields |

The existing "Chat & Messaging" section is retitled **"Floating Chat Buttons"** — a label
change only, no keys touched — because that name stops being meaningful once two chat
features sit side by side.

This is a pure schema-nesting change. Field names, `statePath('data')`, `mount()` and
`save()`'s `$groups` map are untouched, so persistence behaviour does not change.

`mount()`, `save()` and the seeder gain the five new keys.

### Required-field validation

The number and username fields are required when their toggle is on
(`->required(fn (Get $get) => $get('..._enabled'))`), because a toggle switched on with an
empty field is the one failure mode that silently produces a dead link.

With tabs, such a field can be on a tab the admin is not looking at. Implementation must
**verify** whether Filament 3.3 auto-opens the tab containing an invalid field. If it
does, nothing further is needed. If it does not, `save()` catches the validation
exception and the notification names the offending tab.

## Testing

`tests/Unit/ChatLinkBuilderTest.php` — constructs `ChatLinkBuilder` directly with inline
config, so it touches no database. This is required, not stylistic: `tests/Pest.php`
applies `RefreshDatabase` only to `Feature`, so a `Unit` test that read `site_setting()`
would query a table that has not been migrated.

- number sanitisation strips `+`, spaces and dashes
- empty number disables WhatsApp even with the toggle on
- toggle off disables the channel even with a valid number
- username normalisation for `@Page`, `m.me/Page`, `facebook.com/Page`, `Page/`
- template rendering with all four tokens populated
- empty denomination and price collapse cleanly, with newlines preserved
- ref token sanitisation and 255-character cap
- the message is `rawurlencode`d into the `wa.me` URL

`tests/Feature/ProductChatButtonsTest.php`:

- both settings off: the product page contains neither `wa.me` nor `m.me`
- WhatsApp only: a correct `wa.me` href is present, no `m.me`
- Messenger only: a correct `m.me` href is present, no `wa.me`
- both on: both hrefs present

The project has only `UserFactory`, so feature tests seed models directly rather than
introducing a factory suite. Required columns:

- `gift_card_categories`: `name`, `slug` (unique); `is_active` defaults true
- `gift_cards`: `category_id`, `name`, `slug` (unique), `denomination`,
  `denomination_currency`, `denomination_bdt`, `price_bdt`; `stock_count` defaults 0

Note `denomination_usd` was renamed to `denomination` with a separate
`denomination_currency` column by the 2026-04-25 migration.

## Files

**New**
- `app/Services/ChatLinkBuilder.php`
- `resources/views/components/product-chat-buttons.blade.php`
- `tests/Unit/ChatLinkBuilderTest.php`
- `tests/Feature/ProductChatButtonsTest.php`

**Modified**
- `app/Filament/Pages/SiteSettings.php` (tabs restructure + new section)
- `resources/views/storefront/product.blade.php` (one component line)
- `database/seeders/SiteSettingsSeeder.php` (five new defaults)

**Untouched**
- The floating chat widget and its `chat`-group settings

## Out of scope

- The "Need help? Order via chat" caption is hard-coded, not an admin field.
- No `{qty}` token.
- Not added to cart or checkout. The component is reusable if that is wanted later.
- `messenger_use_plugin` is written by `save()` but has no form field. This is a
  pre-existing inconsistency, noted but not fixed here.

## Risk

The tab restructure touches sections unrelated to chat buttons, so the blast radius is
wider than "add two buttons". It is low-risk because no field names, state paths or
persistence logic change — but it is a whole-page visual change the team will notice.
