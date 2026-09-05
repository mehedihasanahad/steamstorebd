# Product Page Chat Buttons Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add admin-configurable WhatsApp and Messenger buttons beneath the Add to Cart / Buy Now row on the product details page, and reorganise the admin settings page into five tabs.

**Architecture:** A pure `ChatLinkBuilder` service owns all phone/username sanitisation, message-template rendering and URL construction, with config injected through its constructor so it needs no database. An anonymous Blade component renders two links whose `href` is server-rendered as a working fallback and then overridden live by Alpine as the customer picks a denomination. The Filament settings page wraps its existing sections in a `Tabs` component and gains one new section.

**Tech Stack:** Laravel 11, Filament v3.3.50, Livewire 3, Alpine.js, Tailwind CSS, Pest 3.

**Spec:** `docs/superpowers/specs/2026-09-05-product-chat-buttons-design.md`

## Global Constraints

- Settings are read through the `site_setting()` helper (`app/Helpers/helpers.php`), which wraps `SiteSetting::get()` and caches each key for 600 seconds.
- In tests, always write settings with `SiteSetting::set()`, never `SiteSetting::create()`. Only `set()` calls `Cache::forget()`, so `create()` leaves a stale cached value and the test reads the wrong setting.
- All five new setting keys use the group `product_chat`.
- Only these placeholders are recognised: `{product}`, `{denomination}`, `{price}`, `{url}`. There is no `{qty}`.
- `{price}` must format identically on both sides: PHP `'৳' . number_format($price, 0)`, JS `'৳' + Number(price).toLocaleString('en-US', { maximumFractionDigits: 0 })`. The `en-US` locale is explicit and must not be omitted — the browser default renders Bengali-Indic digits on some devices.
- Message normalisation preserves newlines. Only horizontal whitespace (`[ \t]`) is collapsed.
- A channel is enabled only when its toggle is on **and** its contact detail is non-empty after sanitisation.
- The existing floating chat widget (`resources/views/layouts/storefront.blade.php` lines 387-427) and its `chat`-group settings must not be modified.
- Existing setting keys, `statePath('data')`, and the `$groups` map in `SiteSettings::save()` must keep working unchanged.
- Filament tests need panel context. Every Filament test file uses this `beforeEach`:
  ```php
  beforeEach(function () {
      $this->actingAs(User::factory()->create(['is_admin' => true]));
      Filament::setCurrentPanel(Filament::getPanel('admin'));
  });
  ```
  `User::canAccessPanel()` returns `$this->is_admin`, so the `is_admin` override is required.

## File Structure

| File | Responsibility |
|---|---|
| `app/Services/ChatLinkBuilder.php` (new) | Pure logic: sanitise number/username, render message template, build `wa.me` / `m.me` URLs, build ref tokens. No database access except in `fromSettings()`. |
| `resources/views/components/product-chat-buttons.blade.php` (new) | Presentation only. Renders nothing when no channel is enabled. Owns the Alpine data component. |
| `app/Filament/Pages/SiteSettings.php` (modify) | Admin form. Gains a `Tabs` wrapper and one new section; `mount()` and `save()` gain five keys. |
| `resources/views/storefront/product.blade.php` (modify) | One component invocation at line 314. |
| `database/seeders/SiteSettingsSeeder.php` (modify) | Five new default rows. |
| `tests/Unit/ChatLinkBuilderTest.php` (new) | Sanitisation and rendering rules. No database. |
| `tests/Feature/SiteSettingsPageTest.php` (new) | Settings page renders and persists. |
| `tests/Feature/ProductChatButtonsTest.php` (new) | Buttons appear on the storefront only when configured. |

## Task Order

Task 1 has no dependencies. Task 2 is independent of Task 1. Task 3 depends on Task 2. Task 4 depends on Tasks 1 and 3.

---

### Task 1: ChatLinkBuilder service

**Files:**
- Create: `app/Services/ChatLinkBuilder.php`
- Test: `tests/Unit/ChatLinkBuilderTest.php`

**Interfaces:**
- Consumes: nothing. This task is self-contained.
- Produces, relied on by Tasks 3 and 4:
  - `ChatLinkBuilder::DEFAULT_TEMPLATE` — `string` constant
  - `new ChatLinkBuilder(bool $whatsappOn, string $whatsappNumberRaw, string $whatsappTemplateRaw, bool $messengerOn, string $messengerUsernameRaw)`
  - `ChatLinkBuilder::fromSettings(): self`
  - `ChatLinkBuilder::refToken(string $slug, string $denomination = ''): string` — static
  - `ChatLinkBuilder::formatPrice(float|int|string|null $price): string` — static
  - `ChatLinkBuilder::normalise(string $message): string` — static
  - `->whatsappEnabled(): bool`, `->messengerEnabled(): bool`, `->enabled(): bool`
  - `->whatsappNumber(): string`, `->messengerUsername(): string`, `->messageTemplate(): string`
  - `->renderMessage(array $replacements): string`
  - `->whatsappUrl(string $message): string`, `->messengerUrl(string $ref = ''): string`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/ChatLinkBuilderTest.php`. This file lives in `tests/Unit`, which does **not** get `RefreshDatabase`, so it must never call `site_setting()` or touch a model — construct the builder directly.

```php
<?php

use App\Services\ChatLinkBuilder;

function builder(array $overrides = []): ChatLinkBuilder
{
    return new ChatLinkBuilder(
        $overrides['whatsappOn'] ?? true,
        $overrides['whatsappNumber'] ?? '+880 17-11 22 33 44',
        $overrides['whatsappTemplate'] ?? ChatLinkBuilder::DEFAULT_TEMPLATE,
        $overrides['messengerOn'] ?? true,
        $overrides['messengerUsername'] ?? 'SteamStoreBD',
    );
}

it('strips every non-digit from the phone number', function () {
    expect(builder()->whatsappNumber())->toBe('8801711223344');
});

it('disables whatsapp when the number is empty even if the toggle is on', function () {
    expect(builder(['whatsappNumber' => '   '])->whatsappEnabled())->toBeFalse();
});

it('disables whatsapp when the toggle is off even with a valid number', function () {
    expect(builder(['whatsappOn' => false])->whatsappEnabled())->toBeFalse();
});

it('normalises pasted messenger usernames', function (string $input) {
    expect(builder(['messengerUsername' => $input])->messengerUsername())->toBe('SteamStoreBD');
})->with([
    'SteamStoreBD',
    '@SteamStoreBD',
    'm.me/SteamStoreBD',
    'https://m.me/SteamStoreBD',
    'https://www.facebook.com/SteamStoreBD',
    'facebook.com/SteamStoreBD/',
]);

it('disables messenger when the username is empty even if the toggle is on', function () {
    expect(builder(['messengerUsername' => '@'])->messengerEnabled())->toBeFalse();
});

it('reports enabled when either channel is usable', function () {
    expect(builder(['whatsappOn' => false])->enabled())->toBeTrue()
        ->and(builder(['whatsappOn' => false, 'messengerOn' => false])->enabled())->toBeFalse();
});

it('renders every token and preserves the newline', function () {
    $message = builder()->renderMessage([
        '{product}'      => 'Steam Wallet',
        '{denomination}' => '$10',
        '{price}'        => ChatLinkBuilder::formatPrice(1250),
        '{url}'          => 'https://steamstorebd.com/product/steam-wallet',
    ]);

    expect($message)->toBe(
        "Hi! I'm interested in Steam Wallet \$10 ৳1,250.\nhttps://steamstorebd.com/product/steam-wallet"
    );
});

it('collapses cleanly when no denomination is selected', function () {
    $message = builder()->renderMessage([
        '{product}'      => 'Steam Wallet',
        '{denomination}' => '',
        '{price}'        => '',
        '{url}'          => 'https://steamstorebd.com/product/steam-wallet',
    ]);

    expect($message)->toBe(
        "Hi! I'm interested in Steam Wallet.\nhttps://steamstorebd.com/product/steam-wallet"
    );
});

it('drops a line that becomes empty after substitution', function () {
    $message = builder(['whatsappTemplate' => "Hello.\n{url}"])->renderMessage(['{url}' => '']);

    expect($message)->toBe('Hello.');
});

it('falls back to the default template when the admin left it blank', function () {
    expect(builder(['whatsappTemplate' => '   '])->messageTemplate())
        ->toBe(ChatLinkBuilder::DEFAULT_TEMPLATE);
});

it('formats the price with an explicit locale and currency symbol', function () {
    expect(ChatLinkBuilder::formatPrice(1250))->toBe('৳1,250')
        ->and(ChatLinkBuilder::formatPrice('1250.00'))->toBe('৳1,250')
        ->and(ChatLinkBuilder::formatPrice(null))->toBe('')
        ->and(ChatLinkBuilder::formatPrice(''))->toBe('');
});

it('builds a facebook-safe ref token', function () {
    expect(ChatLinkBuilder::refToken('steam-wallet', '$10'))->toBe('product_steam_wallet_10')
        ->and(ChatLinkBuilder::refToken('steam-wallet'))->toBe('product_steam_wallet');
});

it('caps the ref token at 255 characters', function () {
    expect(strlen(ChatLinkBuilder::refToken(str_repeat('a', 400))))->toBe(255);
});

it('raw url encodes the message into the whatsapp url', function () {
    $url = builder()->whatsappUrl("Hi there.\nSecond line");

    expect($url)->toBe('https://wa.me/8801711223344?text=Hi%20there.%0ASecond%20line');
});

it('omits the text parameter when the message is empty', function () {
    expect(builder()->whatsappUrl(''))->toBe('https://wa.me/8801711223344');
});

it('builds messenger urls with and without a ref', function () {
    expect(builder()->messengerUrl('product_steam_wallet'))
        ->toBe('https://m.me/SteamStoreBD?ref=product_steam_wallet')
        ->and(builder()->messengerUrl())->toBe('https://m.me/SteamStoreBD');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Unit/ChatLinkBuilderTest.php`
Expected: FAIL with `Class "App\Services\ChatLinkBuilder" not found`.

- [ ] **Step 3: Write the implementation**

Create `app/Services/ChatLinkBuilder.php`:

```php
<?php

namespace App\Services;

class ChatLinkBuilder
{
    public const DEFAULT_TEMPLATE = "Hi! I'm interested in {product} {denomination} {price}.\n{url}";

    public function __construct(
        protected bool $whatsappOn = false,
        protected string $whatsappNumberRaw = '',
        protected string $whatsappTemplateRaw = '',
        protected bool $messengerOn = false,
        protected string $messengerUsernameRaw = '',
    ) {
    }

    /**
     * The only place in this class that touches application settings.
     * Everything else is pure so it can be unit tested without a database.
     */
    public static function fromSettings(): self
    {
        return new self(
            (bool) site_setting('product_chat_whatsapp_enabled', false),
            (string) site_setting('product_chat_whatsapp_number', ''),
            (string) site_setting('product_chat_whatsapp_template', self::DEFAULT_TEMPLATE),
            (bool) site_setting('product_chat_messenger_enabled', false),
            (string) site_setting('product_chat_messenger_username', ''),
        );
    }

    public function whatsappNumber(): string
    {
        return preg_replace('/\D/', '', $this->whatsappNumberRaw) ?? '';
    }

    public function messengerUsername(): string
    {
        $username = trim($this->messengerUsernameRaw);
        $username = preg_replace('#^https?://#i', '', $username);
        $username = preg_replace('#^(www\.)?(m\.me|messenger\.com|facebook\.com)/#i', '', $username);
        $username = ltrim($username, '@');

        return trim($username, '/');
    }

    public function whatsappEnabled(): bool
    {
        return $this->whatsappOn && $this->whatsappNumber() !== '';
    }

    public function messengerEnabled(): bool
    {
        return $this->messengerOn && $this->messengerUsername() !== '';
    }

    public function enabled(): bool
    {
        return $this->whatsappEnabled() || $this->messengerEnabled();
    }

    public function messageTemplate(): string
    {
        $template = trim($this->whatsappTemplateRaw);

        return $template !== '' ? $template : self::DEFAULT_TEMPLATE;
    }

    public function renderMessage(array $replacements): string
    {
        return self::normalise(strtr($this->messageTemplate(), $replacements));
    }

    /**
     * Collapses horizontal whitespace left behind by empty tokens without
     * destroying the newlines the template author wrote on purpose.
     */
    public static function normalise(string $message): string
    {
        $message = preg_replace('/[ \t]+/', ' ', $message);
        $message = preg_replace('/ +([.,!?;:])/', '$1', $message);

        $lines = array_filter(
            array_map('trim', preg_split('/\R/', $message)),
            static fn (string $line): bool => $line !== '',
        );

        return trim(implode("\n", $lines));
    }

    public static function formatPrice(float|int|string|null $price): string
    {
        if ($price === null || $price === '') {
            return '';
        }

        return '৳' . number_format((float) $price, 0);
    }

    /**
     * Facebook rejects arbitrary ref strings, so fold everything outside
     * [A-Za-z0-9_] and cap the result.
     */
    public static function refToken(string $slug, string $denomination = ''): string
    {
        $raw = 'product_' . $slug . ($denomination !== '' ? '_' . $denomination : '');
        $token = preg_replace('/[^A-Za-z0-9_]+/', '_', $raw);
        $token = preg_replace('/_+/', '_', $token);

        return substr(trim($token, '_'), 0, 255);
    }

    public function whatsappUrl(string $message): string
    {
        $url = 'https://wa.me/' . $this->whatsappNumber();

        return $message !== '' ? $url . '?text=' . rawurlencode($message) : $url;
    }

    public function messengerUrl(string $ref = ''): string
    {
        $url = 'https://m.me/' . $this->messengerUsername();

        return $ref !== '' ? $url . '?ref=' . $ref : $url;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test tests/Unit/ChatLinkBuilderTest.php`
Expected: PASS, 16 tests (the username test is a dataset of 6).

- [ ] **Step 5: Commit**

```bash
git add app/Services/ChatLinkBuilder.php tests/Unit/ChatLinkBuilderTest.php
git commit -m "feat: add ChatLinkBuilder for product chat links

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: Restructure the settings page into tabs

**Files:**
- Modify: `app/Filament/Pages/SiteSettings.php:70-190` (the `form()` method body)
- Test: `tests/Feature/SiteSettingsPageTest.php`

**Interfaces:**
- Consumes: nothing from Task 1.
- Produces: five `Tabs\Tab` labels that Task 3 adds a section to — `General`, `Homepage`, `Chat & Buttons`, `Referral`, `Payments`.

This task changes **nesting only**. No field name, no default, no group mapping, and no line of `mount()` or `save()` changes.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SiteSettingsPageTest.php`:

```php
<?php

use App\Filament\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('renders all five settings tabs', function () {
    Livewire::test(SiteSettings::class)
        ->assertOk()
        ->assertSee('General')
        ->assertSee('Homepage')
        ->assertSee('Chat &amp; Buttons', escape: false)
        ->assertSee('Referral')
        ->assertSee('Payments');
});

it('still persists an existing setting after the restructure', function () {
    SiteSetting::set('site_name', 'Old Name', 'general');

    Livewire::test(SiteSettings::class)
        ->fillForm(['site_name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(site_setting('site_name'))->toBe('New Name');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/SiteSettingsPageTest.php`
Expected: FAIL on the first test — `Chat &amp; Buttons` is not present, because the section is still called "Chat & Messaging" and there are no tabs.

- [ ] **Step 3: Add the `Get` import**

In `app/Filament/Pages/SiteSettings.php`, below the existing `use Filament\Forms\Form;` line, add:

```php
use Filament\Forms\Get;
```

`Forms\Components\Tabs` needs no new import — the file already has `use Filament\Forms;`.

- [ ] **Step 4: Wrap the existing sections in tabs**

Replace the entire body of `form()` with the following. Every `Section` below is the existing one moved verbatim — only the "Chat & Messaging" title changes, to "Floating Chat Buttons".

```php
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')->label('Site Name'),
                                Forms\Components\TextInput::make('contact_email')->label('Contact Email')->email(),
                                Forms\Components\TextInput::make('contact_whatsapp')->label('WhatsApp Number'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Homepage')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Forms\Components\Section::make('Hero Section')->schema([
                                    Forms\Components\TextInput::make('hero_title')->label('Hero Title'),
                                    Forms\Components\TextInput::make('hero_subtitle')->label('Hero Subtitle'),
                                ])->columns(2),

                                Forms\Components\Section::make('Announcement Bar')->schema([
                                    Forms\Components\Textarea::make('announcement_bar_text')->label('Announcement Text')->rows(2),
                                    Forms\Components\Toggle::make('announcement_bar_active')->label('Active'),
                                ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Chat & Buttons')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('Floating Chat Buttons')
                                    ->description('Floating chat buttons shown to visitors on every page.')
                                    ->schema([
                                        Forms\Components\Toggle::make('whatsapp_chat_enabled')
                                            ->label('Enable WhatsApp Chat Button')
                                            ->helperText('Shows a floating WhatsApp button on every page.'),
                                        Forms\Components\TextInput::make('whatsapp_chat_number')
                                            ->label('WhatsApp Number')
                                            ->placeholder('8801XXXXXXXXX')
                                            ->helperText('International format without + or spaces (e.g. 8801711223344).'),
                                        Forms\Components\TextInput::make('whatsapp_chat_message')
                                            ->label('Default WhatsApp Message')
                                            ->placeholder('Hello! I want to buy a Steam gift card.')
                                            ->helperText('Pre-filled message when user opens the WhatsApp link.'),

                                        Forms\Components\Toggle::make('messenger_chat_enabled')
                                            ->label('Enable Messenger Chat Button'),
                                        Forms\Components\TextInput::make('messenger_page_username')
                                            ->label('Facebook Page Username')
                                            ->placeholder('YourPageName')
                                            ->helperText('Used for the m.me/YourPageName link button.'),
                                        Forms\Components\TextInput::make('messenger_page_id')
                                            ->label('Facebook Page ID')
                                            ->placeholder('123456789012345')
                                            ->helperText('Optional — for reference only.'),
                                    ])->columns(1),
                            ]),

                        Forms\Components\Tabs\Tab::make('Referral')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                Forms\Components\Section::make('Referral Program')
                                    ->description('Configure the referral system. Referral codes are auto-generated for every user on registration.')
                                    ->schema([
                                        Forms\Components\Toggle::make('referral_enabled')
                                            ->label('Enable Referral Program')
                                            ->helperText('When enabled, users get a unique referral code they can share. Off = no discounts applied and code input hidden at checkout.'),
                                        Forms\Components\Select::make('referral_discount_type')
                                            ->label('Discount Type for Buyer')
                                            ->options(['flat' => 'Flat Amount (৳)', 'percentage' => 'Percentage (%)'])
                                            ->helperText('How the discount is calculated for the person who uses the referral code.'),
                                        Forms\Components\TextInput::make('referral_discount_value')
                                            ->label('Discount Value')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('৳ amount (if flat) or % number (if percentage). E.g. 50 = ৳50 off or 5 = 5% off.'),
                                        Forms\Components\TextInput::make('referral_max_discount_cap')
                                            ->label('Max Discount Cap (৳)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('For percentage type only — maximum BDT discount allowed. Set 0 for no cap.'),
                                        Forms\Components\TextInput::make('referral_min_order_amount')
                                            ->label('Minimum Order Amount (৳)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('Referral code only applies if the cart total is at or above this amount. Set 0 for no minimum.'),
                                        Forms\Components\TextInput::make('referral_owner_reward_amount')
                                            ->label('Referrer Wallet Reward (৳)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('Flat BDT amount credited to the referral code owner\'s wallet after each successful referred order.'),
                                        Forms\Components\TextInput::make('referral_min_withdrawal_amount')
                                            ->label('Minimum Withdrawal Amount (৳)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->helperText('Minimum BDT a user must have to place a withdrawal request. Default: 50.'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Payments')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Section::make('Payment Methods')
                                    ->description('Enable or disable payment options shown at checkout. Credentials are configured in .env.')
                                    ->schema([
                                        Forms\Components\Toggle::make('payment_bkash_online_enabled')
                                            ->label('bKash Tokenized Checkout (Online)')
                                            ->helperText('Redirects customer to bKash payment gateway. Requires BKASH_APP_KEY etc. in .env.'),
                                        Forms\Components\Toggle::make('payment_bkash_send_money_enabled')
                                            ->label('bKash Send Money')
                                            ->helperText('Customer sends money manually, then submits TRX ID. Requires BKASH_SEND_MONEY_NUMBER in .env.'),
                                        Forms\Components\Toggle::make('payment_nagad_send_money_enabled')
                                            ->label('Nagad Send Money')
                                            ->helperText('Customer sends money via Nagad manually, then submits TRX ID. Requires NAGAD_SEND_MONEY_NUMBER in .env.'),
                                        Forms\Components\Toggle::make('payment_rocket_send_money_enabled')
                                            ->label('Rocket Send Money')
                                            ->helperText('Customer sends money via Rocket (Dutch-Bangla) manually, then submits TRX ID. Requires ROCKET_SEND_MONEY_NUMBER in .env.'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test tests/Feature/SiteSettingsPageTest.php`
Expected: PASS, 2 tests.

If `Livewire::test()` throws a panel-related error, the `Filament::setCurrentPanel()` line in `beforeEach` is missing or the user was created without `is_admin => true`.

- [ ] **Step 6: Verify visually**

Run: `php artisan serve`, open `http://localhost:8000/admin/site-settings`, confirm five tabs appear and clicking one appends `?tab=...` to the URL. Change a value on the Referral tab, save, reload, and confirm it persisted.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Pages/SiteSettings.php tests/Feature/SiteSettingsPageTest.php
git commit -m "refactor: group site settings into five tabs

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: Add the Product Page Chat Buttons settings

**Files:**
- Modify: `app/Filament/Pages/SiteSettings.php` — `mount()` `$keys` and `$defaults`, the `Chat & Buttons` tab, and `save()` `$groups`
- Modify: `database/seeders/SiteSettingsSeeder.php`
- Test: `tests/Feature/SiteSettingsPageTest.php` (append)

**Interfaces:**
- Consumes from Task 1: `ChatLinkBuilder::DEFAULT_TEMPLATE`.
- Consumes from Task 2: the `Chat & Buttons` tab, which gains a second section.
- Produces, relied on by Task 4: the five setting keys `product_chat_whatsapp_enabled`, `product_chat_whatsapp_number`, `product_chat_whatsapp_template`, `product_chat_messenger_enabled`, `product_chat_messenger_username`, all in group `product_chat`.

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/SiteSettingsPageTest.php`:

```php
it('persists the product chat settings under the product_chat group', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'product_chat_whatsapp_enabled'   => true,
            'product_chat_whatsapp_number'    => '+880 1711-223344',
            'product_chat_whatsapp_template'  => 'Hi about {product}.',
            'product_chat_messenger_enabled'  => true,
            'product_chat_messenger_username' => 'SteamStoreBD',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(site_setting('product_chat_whatsapp_number'))->toBe('+880 1711-223344')
        ->and(site_setting('product_chat_messenger_username'))->toBe('SteamStoreBD')
        ->and(SiteSetting::where('key', 'product_chat_whatsapp_number')->value('group'))->toBe('product_chat');
});

it('refuses to save an enabled whatsapp button with no number', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'product_chat_whatsapp_enabled' => true,
            'product_chat_whatsapp_number'  => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['product_chat_whatsapp_number' => 'required']);
});

it('refuses to save an enabled messenger button with no username', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'product_chat_messenger_enabled'  => true,
            'product_chat_messenger_username' => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['product_chat_messenger_username' => 'required']);
});

it('allows saving when both product chat toggles are off', function () {
    Livewire::test(SiteSettings::class)
        ->fillForm([
            'product_chat_whatsapp_enabled'  => false,
            'product_chat_whatsapp_number'   => '',
            'product_chat_messenger_enabled' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/SiteSettingsPageTest.php`
Expected: FAIL — the new fields do not exist on the form, so `fillForm()` errors or the assertions find no values.

- [ ] **Step 3: Add the import and the five keys to `mount()`**

Add to the imports in `app/Filament/Pages/SiteSettings.php`:

```php
use App\Services\ChatLinkBuilder;
```

In `mount()`, append these five entries to the end of the `$keys` array:

```php
            'product_chat_whatsapp_enabled',
            'product_chat_whatsapp_number',
            'product_chat_whatsapp_template',
            'product_chat_messenger_enabled',
            'product_chat_messenger_username',
```

And append these to the `$defaults` array:

```php
            'product_chat_whatsapp_enabled'   => false,
            'product_chat_whatsapp_template'  => ChatLinkBuilder::DEFAULT_TEMPLATE,
            'product_chat_messenger_enabled'  => false,
```

Do not add defaults for the number or username — an empty string is correct for both, and `mount()` already falls back to `''`.

- [ ] **Step 4: Add the new section to the Chat & Buttons tab**

In `form()`, inside the `Chat & Buttons` tab's `->schema([...])`, add this section directly after the existing `Floating Chat Buttons` section:

```php
                                Forms\Components\Section::make('Product Page Chat Buttons')
                                    ->description('Buttons shown under "Add to Cart" and "Buy Now" on the product details page. Configured separately from the floating buttons above, so product enquiries can go to a different number.')
                                    ->schema([
                                        Forms\Components\Toggle::make('product_chat_whatsapp_enabled')
                                            ->label('Enable WhatsApp Button')
                                            ->live(),
                                        Forms\Components\TextInput::make('product_chat_whatsapp_number')
                                            ->label('WhatsApp Number')
                                            ->placeholder('8801XXXXXXXXX')
                                            ->helperText('International format. Spaces, dashes and + are stripped automatically.')
                                            ->required(fn (Get $get): bool => (bool) $get('product_chat_whatsapp_enabled')),
                                        Forms\Components\Textarea::make('product_chat_whatsapp_template')
                                            ->label('WhatsApp Message Template')
                                            ->rows(3)
                                            ->helperText('Placeholders: {product} {denomination} {price} {url}. Empty placeholders are removed cleanly, so the message still reads correctly before the customer picks an amount.'),

                                        Forms\Components\Toggle::make('product_chat_messenger_enabled')
                                            ->label('Enable Messenger Button')
                                            ->live(),
                                        Forms\Components\TextInput::make('product_chat_messenger_username')
                                            ->label('Facebook Page Username')
                                            ->placeholder('YourPageName')
                                            ->helperText('Messenger cannot pre-fill a message — Facebook removed that. The button opens a chat with product details attached as an invisible ref tag, which only a Messenger bot can read. There is no message template for Messenger because it would do nothing.')
                                            ->required(fn (Get $get): bool => (bool) $get('product_chat_messenger_enabled')),
                                    ])->columns(1),
```

The `->live()` on each toggle is required — without it the `required()` closure does not re-evaluate when the toggle is flipped.

- [ ] **Step 5: Add the five keys to `save()`**

Append to the `$groups` array in `save()`:

```php
            'product_chat_whatsapp_enabled'   => 'product_chat',
            'product_chat_whatsapp_number'    => 'product_chat',
            'product_chat_whatsapp_template'  => 'product_chat',
            'product_chat_messenger_enabled'  => 'product_chat',
            'product_chat_messenger_username' => 'product_chat',
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test tests/Feature/SiteSettingsPageTest.php`
Expected: PASS, 6 tests.

- [ ] **Step 7: Verify which tab a validation error opens**

This is the spec's explicit verification step. Run `php artisan serve`, open `/admin/site-settings`, switch to the **General** tab, then enable the WhatsApp product button on the **Chat & Buttons** tab, clear its number, switch back to **General**, and press Save.

- If Filament switches to the Chat & Buttons tab and highlights the field, no further work is needed. Tick this step and move on.
- If it shows only a generic error notification while staying on General, wrap the body of `save()` so the admin is told where to look:

```php
    public function save(): void
    {
        try {
            $data = $this->form->getState();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Notification::make()
                ->title('Could not save')
                ->body('Check the Chat & Buttons tab — an enabled button is missing its number or page username.')
                ->danger()
                ->send();

            throw $e;
        }

        // ... existing $groups array and foreach loop unchanged ...
    }
```

- [ ] **Step 8: Add the seeder defaults**

In `database/seeders/SiteSettingsSeeder.php`, add `use App\Services\ChatLinkBuilder;` to the imports and append to the `$settings` array:

```php
            ['key' => 'product_chat_whatsapp_enabled', 'value' => '0', 'group' => 'product_chat'],
            ['key' => 'product_chat_whatsapp_number', 'value' => '', 'group' => 'product_chat'],
            ['key' => 'product_chat_whatsapp_template', 'value' => ChatLinkBuilder::DEFAULT_TEMPLATE, 'group' => 'product_chat'],
            ['key' => 'product_chat_messenger_enabled', 'value' => '0', 'group' => 'product_chat'],
            ['key' => 'product_chat_messenger_username', 'value' => '', 'group' => 'product_chat'],
```

The seeder uses `firstOrCreate`, so re-running it will not overwrite values an admin has already set. Both toggles seed to off, so nothing appears on the storefront until it is deliberately configured.

- [ ] **Step 9: Run the seeder and the full suite**

Run: `php artisan db:seed --class=SiteSettingsSeeder && php artisan test`
Expected: seeder succeeds, whole suite passes.

- [ ] **Step 10: Commit**

```bash
git add app/Filament/Pages/SiteSettings.php database/seeders/SiteSettingsSeeder.php tests/Feature/SiteSettingsPageTest.php
git commit -m "feat: add product page chat button settings

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: Blade component and product page placement

**Files:**
- Create: `resources/views/components/product-chat-buttons.blade.php`
- Modify: `resources/views/storefront/product.blade.php:314`
- Test: `tests/Feature/ProductChatButtonsTest.php`

**Interfaces:**
- Consumes from Task 1: `ChatLinkBuilder::fromSettings()`, `->enabled()`, `->whatsappEnabled()`, `->messengerEnabled()`, `->whatsappNumber()`, `->messengerUsername()`, `->messageTemplate()`, `->renderMessage()`, `->whatsappUrl()`, `->messengerUrl()`, `ChatLinkBuilder::refToken()`, `ChatLinkBuilder::formatPrice()`.
- Consumes from Task 3: the five `product_chat_*` setting keys.
- Consumes from the existing page: `Alpine.store('product')`, whose `current` getter returns `{ id, denom, bdt, price, stock, slug }` or `null`.
- Produces: nothing further depends on this task.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ProductChatButtonsTest.php`:

```php
<?php

use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use App\Models\SiteSetting;

beforeEach(function () {
    $this->category = GiftCardCategory::create([
        'name' => 'Steam Wallet',
        'slug' => 'steam-wallet',
    ]);

    GiftCard::create([
        'category_id'           => $this->category->id,
        'name'                  => 'Steam Wallet $10',
        'slug'                  => 'steam-wallet-10',
        'denomination'          => 10,
        'denomination_currency' => 'USD',
        'denomination_bdt'      => 1200,
        'price_bdt'             => 1250,
        'stock_count'           => 5,
    ]);
});

function enableWhatsapp(): void
{
    SiteSetting::set('product_chat_whatsapp_enabled', true, 'product_chat');
    SiteSetting::set('product_chat_whatsapp_number', '+880 1711-223344', 'product_chat');
}

function enableMessenger(): void
{
    SiteSetting::set('product_chat_messenger_enabled', true, 'product_chat');
    SiteSetting::set('product_chat_messenger_username', 'SteamStoreBD', 'product_chat');
}

it('shows no chat buttons when both channels are off', function () {
    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertDontSee('wa.me')
        ->assertDontSee('m.me')
        ->assertDontSee('Need help? Order via chat');
});

it('shows only the whatsapp button when only whatsapp is configured', function () {
    enableWhatsapp();

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee('https://wa.me/8801711223344', escape: false)
        ->assertSee('Need help? Order via chat')
        ->assertDontSee('m.me');
});

it('shows only the messenger button when only messenger is configured', function () {
    enableMessenger();

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee('https://m.me/SteamStoreBD', escape: false)
        ->assertDontSee('wa.me');
});

it('shows both buttons when both are configured', function () {
    enableWhatsapp();
    enableMessenger();

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee('https://wa.me/8801711223344', escape: false)
        ->assertSee('https://m.me/SteamStoreBD', escape: false);
});

it('hides the button when the toggle is on but the number is blank', function () {
    SiteSetting::set('product_chat_whatsapp_enabled', true, 'product_chat');
    SiteSetting::set('product_chat_whatsapp_number', '', 'product_chat');

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertDontSee('wa.me');
});

it('server renders a working fallback message with no denomination selected', function () {
    enableWhatsapp();

    $expected = rawurlencode("Hi! I'm interested in Steam Wallet.\n" . url('/product/steam-wallet'));

    $this->get('/product/steam-wallet')
        ->assertOk()
        ->assertSee($expected, escape: false);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test tests/Feature/ProductChatButtonsTest.php`
Expected: the first test passes (nothing is rendered yet), the rest FAIL because no `wa.me` or `m.me` link exists.

- [ ] **Step 3: Create the Blade component**

Create `resources/views/components/product-chat-buttons.blade.php`:

```blade
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

    <div class="mt-4 pt-4" style="border-top:1px solid #EEF2FF;"
         x-data="productChatButtons({
             template: {{ Js::from($chat->messageTemplate()) }},
             product:  {{ Js::from($name) }},
             url:      {{ Js::from($url) }},
             slug:     {{ Js::from($slug) }},
             waNumber: {{ Js::from($chat->whatsappNumber()) }},
             msUser:   {{ Js::from($chat->messengerUsername()) }},
         })">

        <p class="text-center text-xs font-semibold mb-3" style="color:#94A3B8;">Need help? Order via chat</p>

        <div class="flex gap-3">
            @if($chat->whatsappEnabled())
            <a href="{{ $chat->whatsappUrl($fallbackMessage) }}"
               x-bind:href="whatsappHref()"
               target="_blank" rel="noopener noreferrer"
               aria-label="Order {{ $name }} on WhatsApp"
               class="flex-1 flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-sm border-2 transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
               style="border-color:#25D366; color:#128C7E; --tw-ring-color:#25D366;"
               onmouseover="this.style.backgroundColor='#F0FFF4';"
               onmouseout="this.style.backgroundColor='transparent';">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#25D366" aria-hidden="true" class="flex-shrink-0">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                WhatsApp
            </a>
            @endif

            @if($chat->messengerEnabled())
            <a href="{{ $chat->messengerUrl($fallbackRef) }}"
               x-bind:href="messengerHref()"
               target="_blank" rel="noopener noreferrer"
               aria-label="Chat about {{ $name }} on Messenger"
               class="flex-1 flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-sm border-2 transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
               style="border-color:#0084FF; color:#0064C8; --tw-ring-color:#0084FF;"
               onmouseover="this.style.backgroundColor='#F0F7FF';"
               onmouseout="this.style.backgroundColor='transparent';">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#0084FF" aria-hidden="true" class="flex-shrink-0">
                    <path d="M12 0C5.373 0 0 4.975 0 11.111c0 3.497 1.745 6.616 4.472 8.652V24l4.086-2.242c1.09.301 2.246.465 3.442.465 6.627 0 12-4.975 12-11.112S18.627 0 12 0zm1.194 14.963l-3.055-3.26-5.963 3.26L10.426 8.4l3.129 3.26 5.889-3.26-6.25 6.563z"/>
                </svg>
                Messenger
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
```

Two notes for the reviewer, both deliberate:

- `encodeURIComponent` and PHP's `rawurlencode` differ on `!*'()`. Both produce URLs WhatsApp accepts, so this is not a bug. Do not "fix" it by hand-rolling an encoder.
- The `@push('scripts')` requires the host page to have a `@stack('scripts')`. The storefront layout does, at line 387. Reusing this component on a page without that stack would silently lose the Alpine registration and the links would fall back to the server-rendered href — degraded, not broken.

- [ ] **Step 4: Place the component on the product page**

In `resources/views/storefront/product.blade.php`, insert a blank line and the component after the `@endif` on line 313 and before the `@if($category->mainCategory && ...)` that starts on line 315:

```blade
                <x-product-chat-buttons
                    :name="$category->name"
                    :url="route('product', $category->slug)"
                    :slug="$category->slug" />
```

It must sit **outside** the `@if($denominations->isNotEmpty())` block that ends on line 313, so the buttons still show when a product is out of stock.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test tests/Feature/ProductChatButtonsTest.php`
Expected: PASS, 6 tests.

- [ ] **Step 6: Verify the live link in a browser**

Run `php artisan serve`, enable both buttons at `/admin/site-settings` with a real number and page name, then open `/product/steam-wallet`.

- Before selecting an amount, hover the WhatsApp button — the status bar URL must contain the product name but no denomination.
- Select a denomination — the URL must update to include the amount and the `৳` price with a comma separator.
- Click through and confirm WhatsApp opens with the message pre-filled.
- Confirm the Messenger button opens a chat. It will **not** show a pre-filled message; that is expected and documented.

- [ ] **Step 7: Run the full suite**

Run: `php artisan test`
Expected: all tests pass.

- [ ] **Step 8: Commit**

```bash
git add resources/views/components/product-chat-buttons.blade.php resources/views/storefront/product.blade.php tests/Feature/ProductChatButtonsTest.php
git commit -m "feat: add whatsapp and messenger buttons to product page

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Self-Review

**Spec coverage.** Every spec section maps to a task: settings keys and defaults → Task 3; `ChatLinkBuilder` surface, normalisation, ref token, price formatting → Task 1; Blade component, progressive enhancement, markup → Task 4; placement at line 314 outside the stock guard → Task 4 Step 4; tab restructure and the "Floating Chat Buttons" retitle → Task 2; required-field validation and the tab-switching verification → Task 3 Steps 4 and 7; seeder → Task 3 Step 8; both test files → Tasks 1, 3, 4. No gaps.

**Placeholder scan.** No TBD or TODO. Task 3 Step 7 is a verification with both outcomes written out and the fallback code supplied, not an open question.

**Type consistency.** The constructor parameter names (`$whatsappOn`, `$whatsappNumberRaw`, `$whatsappTemplateRaw`, `$messengerOn`, `$messengerUsernameRaw`) are distinct from the method names (`whatsappEnabled()`, `whatsappNumber()`, `messageTemplate()`, `messengerEnabled()`, `messengerUsername()`), so no property/method collision. Every method the component calls in Task 4 is defined in Task 1's Interfaces block. The Alpine config keys (`template`, `product`, `url`, `slug`, `waNumber`, `msUser`) are identical between the `x-data` attribute and the `Alpine.data` body. The store shape used in JS (`picked.denom`, `picked.price`) matches the entries built at `product.blade.php:125-132`.
