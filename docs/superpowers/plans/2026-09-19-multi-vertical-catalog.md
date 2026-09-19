# Multi-Vertical Catalog + Storefront Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Two merged workstreams. (1) Add a top-level `CatalogSection` layer above the existing brand → product → variant catalog, move every current brand under a seeded "Gift Cards" section, and extend the fulfilment engine so software, subscriptions and game top-ups can be sold alongside gift cards. (2) Rebuild the storefront on a new ash-neutral design system in the G2A / SEAGM idiom. Neither changes any existing public URL, model name, or payment path.

**Why merged and not sequenced:** the redesign cannot be applied by swapping a palette. The current theme is hardcoded as **459 inline `style="..."` attributes and 293 hex literals** across the storefront views, so every page has to be rebuilt regardless. Building the new section pages, mega-menu, product page and cart in the old navy theme and re-theming them afterwards would mean building the same screens twice. Each page is therefore built **once**, in the new theme, with the new catalog structure already in it.

**Architecture:** A new `catalog_sections` table sits above `main_categories` via a nullable FK, mirroring the existing `main_categories` → `gift_card_categories` shape exactly. `GiftCard` gains a `fulfilment_type` discriminator that `OrderService` branches on; the existing code-reservation logic is moved verbatim into a private method and becomes the `code_pool` branch, so the gift-card path executes the same statements it does today. Buyer-supplied fields (Player ID, account e-mail) are declared as a JSON schema on the product, collected on the product page, validated server-side, and carried on the order item. The storefront gains a section landing page, a mega-menu and search, all reading from the existing `StorefrontCatalog` cache.

**Tech Stack:** Laravel 12, PHP 8.2, Filament v3.3, Livewire 3, Alpine.js, Tailwind CSS (`tailwind.storefront.config.js`), Pest 3, MySQL.

**Specs:**
- Catalog & fulfilment: `docs/superpowers/specs/2026-09-19-multi-vertical-catalog-design.md`
- Redesign: `docs/superpowers/specs/2026-09-19-storefront-redesign-design.md`

---

## Implementation Status — 2026-09-19

Phases 0–6 are implemented on branch `v2`. Suites:

- **574 tests, 1418 assertions, green** (`php artisan test --parallel`), against
  a Phase 0 baseline of 352.
- **124 browser tests, green** (`npx playwright test`) — every public page, the
  mega-menu and search, 23 reference-layout assertions and 31 visual baselines,
  on desktop (1440×900) and a Pixel 7.

| Phase | State | Where it landed |
|---|---|---|
| 0 Characterisation | Done | `tests/Feature/CheckoutCharacterisationTest.php`, unedited through the Phase 5 refactor |
| 1 Section layer | Done | `catalog_sections`, `MainCategory.catalog_section_id`, `CatalogSectionSeeder`, `StorefrontCatalog` |
| 2 Admin & schema | Done | `CatalogSectionResource`, `BannerResource`, fulfilment/region/merchandising/content columns |
| 3 Design system | Done | `resources/css/storefront.css` tokens, `tailwind.storefront.config.js`, `components/ui/*`, `components/catalog/*`, `DesignSystemTest` |
| 4 Storefront rebuild | Done | header + mega-menu + search, homepage, `storefront/catalog.blade.php` (section **and** brand), product page, cart |
| 5 Fulfilment engine | Done | `BuyerInputSchema`, `Cart`, `AddToCartRequest`, `OrderService` branch, `FulfilmentService`, `FulfilmentResource`, order-detail + e-mail branches |
| 6 Re-theme & retire | Done | checkout, order pages, account, programme and content pages, auth, e-mails; legacy navy palette and glow shadows removed from the Tailwind config |

**Decisions taken during implementation, beyond the plan as written:**

1. **Mega-menu trigger.** The spec called for one `CATALOG` trigger; the supplied
   reference screenshots show a trigger per section. Implemented as one panel
   with the section list as column 1, opened from any section trigger — the
   reference's interaction over the spec's single data source, not two menus.
2. **Favourites shipped rather than deferred** (redesign spec §4.8, flagged for a
   decision). "Add to favourite" is in the reference product hero, and the
   feature is one additive table, one toggle route and one page. Guests see the
   control and are sent to sign in.
3. **`order_items.fulfilment_status` is nullable with no default.** Null means
   "delivered from the code pool", which is what every pre-existing row is. Any
   default would have claimed something untrue about six months of orders.
4. **`sectionsWithBrands()` now reads through `brands()`** rather than rebuilding
   the visible-brand tree, so a cold cache builds it once instead of twice.
5. **Section pages are in the sitemap**; `/search` is not — it is `noindex` and
   disallowed in robots.txt, so advertising it would contradict both.
6. **The browser suite ignores `public/hot` and disables Laravel Boost**
   (`STOREFRONT_IGNORE_VITE_HOT`, `BOOST_ENABLED=false` in
   `playwright.config.js`; the hot file is redirected in `AppServiceProvider`).
   A developer's running `npm run dev` was attaching Vite's HMR client to every
   page under test, and HMR answers a file change with a full page reload —
   which cancelled whatever navigation was in flight and read as three flaky
   navigation tests. Boost's browser-log watcher was separately posting 2s
   requests to a dev server that answers one at a time. With both out of the
   way the navigation suite went from 3.4 minutes with retries to 57 seconds
   with none, and the suite now tests the assets that actually ship.

**Still outstanding (needs a person, not code):**

- Lighthouse mobile comparison against the Phase 0 baseline (Task 0.2 never
  recorded one, so there is nothing to compare against yet).
- Rehearsal against a restored production snapshot, and the deploy runbook
  below — migrate, deploy, `cache:clear`, `queue:restart`.
- One real order end-to-end on staging through both bKash-online and send-money.
- Region flags render as emoji. Android and iOS show them; Windows desktop does
  not render regional-indicator pairs at all, so the region **name** is always
  shown beside the flag. Swap to flag images if desktop parity matters.

---

## Global Constraints

- **No existing public URL changes.** `/brand/{slug}` and `/product/{slug}` keep their exact shapes and content. Only `/category/{slug}` and `/search` are added. Any task that proposes re-nesting these URLs is out of scope — see AD-2.
- **No renaming.** `GiftCard`, `GiftCardCategory`, `MainCategory`, `gift_card_id` and every other existing identifier keep their names. See AD-3.
- **Every new column is nullable or defaulted** so that existing rows keep today's behaviour with no backfill required, except the one deliberate backfill in Task 1.2.
- `fulfilment_type` defaults to `'code_pool'`. Any code path that does not explicitly check it must behave exactly as it does today.
- Settings are read through the `site_setting()` helper (`app/Helpers/helpers.php`), which caches each key for 600 seconds. In tests always write with `SiteSetting::set()`, never `SiteSetting::create()` — only `set()` calls `Cache::forget()`.
- `StorefrontCatalog::BRANDS_CACHE_KEY` (`home_main_categories`) is cleared by `MainCategory::booted()` and `GiftCardCategory::booted()`. `CatalogSection` must clear it too, or the mega-menu will serve stale sections for 5 minutes.
- Money is always BDT and always formatted with `format_bdt()`. Never introduce a second formatter.
- Slug changes must go through `SlugRedirect::rememberSlugChange()` in a `booted()` hook, exactly as `MainCategory` and `GiftCardCategory` already do — otherwise a renamed section 404s instead of 301ing.
- `php artisan test` must be green at the end of every phase, not just at the end of the plan.

## Design Constraints

- **No hex literal and no `style="..."` in any rebuilt view.** Colour, spacing,
  radius and shadow come from tokens only. Enforced by `DesignSystemTest`
  (Task 3.5), not left to discipline — the current 459 inline styles are what
  discipline alone produced.
- **A page that needs a colour the tokens do not have is a signal the token set is
  wrong**, not a licence to write a hex value. Fix the tokens.
- **The accent stays `#2563EB`.** Surfaces change from navy to neutral ash; the
  brand colour does not. Changing both would be a rebrand, which was not asked for,
  and retaining it is what lets un-migrated pages coexist with the new header.
- **Old and new themes coexist** until Phase 6.5. Each page migrates on its own
  schedule and the site is never half-broken.
- **E-mails stay light-background.** Dark-background HTML mail renders
  unpredictably across clients; they adopt the accent, type scale and radius only.
- **Mobile first, and measured.** Every rebuilt page is checked at 375 / 768 /
  1440, must not scroll horizontally at 375px, and must meet or beat the Phase 0
  Lighthouse baseline — removing the blurred orbs and gradient overlays should
  make it faster, not merely no slower.
- **Every existing behaviour survives a re-theme.** Payment selection, referral and
  wallet discounts, send-money transaction IDs, chat buttons, JSON-LD and the
  `$fallbackCategories` homepage path are load-bearing. Restyle them; do not
  rewrite them.

## Production Constraints

**This site has been live for ~6 months. There is real order history, real code
inventory, real wallet balances, and live sessions.** These constraints are not
advisory — violating any one of them can destroy customer data. See AD-8.

- **`php artisan migrate:fresh` must never run anywhere but local and CI.** It
  drops every table. It appears in this plan exactly once, in Task 1.2 Step 4,
  explicitly scoped to local.
- **Expand-only schema.** Every migration in this plan adds columns or tables.
  Nothing is dropped, renamed, re-typed or narrowed. This is what lets old and new
  code run side by side during a deploy, and it is what makes `git revert` a
  complete rollback.
- **Rollback is `git revert` + `php artisan cache:clear`, not `migrate:rollback`.**
  Because the schema only ever grew, the previous release runs correctly against
  it — the new columns are simply ignored. Reversing migrations against live data
  is the more dangerous option and should not be the first instinct at 2am.
- **Backfills are idempotent and written with the query builder**, never with an
  Eloquent model whose `$fillable` or scopes may change in a later commit.
- **`down()` never destroys admin-entered data.** Task 1.2's `down()` drops the FK
  column and leaves the seeded section row alone.
- **Sessions are in the database** (`SESSION_DRIVER=database`), so **carts created
  before a deploy are resolved by code after it**. Any change to the cart's shape
  must tolerate a pre-existing cart. This is why AD-6 keeps integer keys.
- **Cache is in the database** (`CACHE_STORE=database`), so `home_main_categories`
  (300 s) and `site_setting()` (600 s) **survive deployment**. `cache:clear` is a
  mandatory deploy step, not an optimisation.
- **Queue workers hold old code for up to an hour.** `supervisor.conf` runs
  `queue:work --max-time=3600`. `php artisan queue:restart` is a mandatory deploy
  step. Within a single release, queued jobs must stay backward-compatible.
- **`delivered_payload` is encrypted with `APP_KEY`.** If that key is ever rotated
  without a re-encryption migration, every delivered credential becomes permanently
  unreadable. Confirm `APP_KEY` is included in the backup set before Phase 5 ships.

### Deployment runbook (every phase)

Run in this order. The order matters: migrating first means the new columns exist
before any code reads them; the reverse has new code querying columns that do not
yet exist.

```
1.  php artisan down --render=errors::503 --retry=60   # only for Phase 4; 1-3 are online-safe
2.  mysqldump the database, and verify the dump restores    # non-negotiable
3.  git pull && composer install --no-dev --optimize-autoloader
4.  php artisan migrate --force                        # never migrate:fresh
5.  npm ci && npm run build
6.  php artisan config:cache && php artisan route:cache && php artisan view:cache
7.  php artisan cache:clear                            # CACHE_STORE=database — survives deploy
8.  php artisan queue:restart                          # workers hold old code up to 1h
9.  php artisan up
10. Smoke test: /, /brand/{existing}, /product/{existing}, add to cart, /sitemap.xml
```

Phases 1–4 are online-safe (expand-only schema, additive routes) and do not need
step 1. **Phase 5 touches the order path and should be deployed behind
maintenance mode at a low-traffic hour**, after confirming no orders sit in
`pending` or `payment_initiated` (see Task 5.3 Step 5).

## Sequencing Rationale

| Phase | Ships | Visible to customers? |
|---|---|---|
| 0 | Safety net, production snapshot, visual baseline | No |
| 1 | Section layer + backfill | No |
| 2 | Admin for sections, fulfilment, banners, deals, regions | No (admin only) |
| 3 | **Design system** — tokens, primitives, catalog components | No |
| 4 | **Storefront rebuild** — header, home, category, product, cart | **Yes — the new store** |
| 5 | Fulfilment engine | Yes — top-ups & subscriptions sellable |
| 6 | Remaining pages + e-mails re-themed | Yes |
| 7 | Polish | Yes |

Phases 0–2 are invisible: schema, backfill and admin. That is deliberate — it
means content can be entered (sections, banners, regions, featured flags) while
the design system is being built, so Phase 4 has real data to render instead of
placeholders.

**Phase 3 must come before any page is built.** It is what stops the redesign
from regenerating the 459 inline styles it exists to remove.

**The release checkpoint is the end of Phase 4.** At that point the store looks
new, browses by vertical, and sells **Gift Cards and Software** — a software
licence key is delivered from a code pool exactly like a gift card code, so it
needs no engine change. Phase 5 unlocks Subscriptions and Game Top-Up.

If the work has to stop early, stopping after Phase 4 leaves a coherent product.
Stopping mid-Phase 5 does not, so Phase 5 is one unit.

**Within Phase 4, pages ship one at a time.** Old and new themes coexist (redesign
spec §5.3), so each rebuilt page can go live on its own. The exception is the
header and footer, which appear everywhere and ship first.

---

## File Structure

| File | Responsibility |
|---|---|
| `database/migrations/*_create_catalog_sections_table.php` (new) | The new top-level table. |
| `database/migrations/*_add_catalog_section_to_main_categories.php` (new) | Nullable FK + the "Gift Cards" backfill. |
| `database/migrations/*_add_fulfilment_fields_to_gift_cards.php` (new) | `fulfilment_type`, `delivery_eta_label`, `region`. |
| `database/migrations/*_add_buyer_input_fields_to_gift_card_categories.php` (new) | `buyer_input_fields` JSON. |
| `database/migrations/*_add_fulfilment_to_order_items.php` (new) | `fulfilment_status`, `buyer_inputs`, `delivered_payload`. |
| `app/Models/CatalogSection.php` (new) | Model, relations, cache + slug-redirect hooks. |
| `app/Models/MainCategory.php` (modify) | `catalogSection()` belongsTo + fillable. |
| `app/Models/GiftCard.php` (modify) | Fulfilment constants, `stock_count` accessor branch, scopes. |
| `app/Models/GiftCardCategory.php` (modify) | `buyer_input_fields` cast + `buyerInputSchema()`. |
| `app/Models/OrderItem.php` (modify) | New casts incl. `encrypted`, fulfilment helpers. |
| `app/Services/StorefrontCatalog.php` (modify) | `sections()`, `sectionsWithBrands()`, `navigation()`. |
| `app/Services/OrderService.php` (modify) | Extract `reserveCodePoolStock()`, add `reserveManualStock()`, branch. |
| `app/Services/BuyerInputSchema.php` (new) | Pure: parse schema, build validation rules, normalise submitted values. |
| `app/Services/FulfilmentService.php` (new) | Deliver a manual/credentials order item, flip statuses, dispatch mail. |
| `app/Http/Controllers/StorefrontController.php` (modify) | `section()` action; brand/product gain section context. |
| `app/Http/Controllers/SearchController.php` (new) | `/search` page + `/search/suggest` JSON. |
| `app/Http/Controllers/CheckoutController.php` (modify) | Cart line keys, buyer inputs, `resolveCartItems()` reads payload. |
| `app/Http/Requests/AddToCartRequest.php` (new) | Moves inline `validate()` out of the controller, adds dynamic buyer-input rules. |
| `app/Filament/Resources/CatalogSectionResource.php` (new) | Admin CRUD for sections. |
| `app/Filament/Resources/MainCategoryResource.php` (modify) | Section select + column + filter. |
| `app/Filament/Resources/GiftCardResource.php` (modify) | Fulfilment type, ETA, region, conditional stock field. |
| `app/Filament/Resources/GiftCardCategoryResource.php` (modify) | Buyer-input schema repeater. |
| `app/Filament/Resources/OrderResource.php` (modify) | Fulfilment queue tab + deliver action. |
| `resources/views/components/catalog/mega-menu.blade.php` (new) | Desktop + mobile section navigation. |
| `resources/views/components/catalog/breadcrumbs.blade.php` (new) | Shared 4-level trail + `BreadcrumbList` schema. |
| `resources/views/components/catalog/brand-card.blade.php` (new) | Extracted from `home.blade.php:275-299`, reused in 3 places. |
| `resources/views/components/catalog/buyer-inputs.blade.php` (new) | Renders the declared input schema. |
| `resources/views/storefront/section.blade.php` (new) | `/category/{slug}` landing page. |
| `resources/views/storefront/search.blade.php` (new) | Full search results page. |
| `resources/views/layouts/storefront.blade.php` (modify) | Mega-menu + search slot in header and mobile drawer. |
| `resources/views/storefront/home.blade.php` (modify) | Section tiles + per-section rails. |
| `resources/views/storefront/product.blade.php` (modify) | Buyer-input block, delivery chip, 4-level breadcrumb. |
| `resources/views/storefront/order-detail.blade.php` (modify) | Credential + awaiting-fulfilment branches. |
| `resources/views/emails/order-codes.blade.php` (modify) | Dynamic item label instead of hardcoded "Steam Wallet Code". |
| `app/Http/Controllers/SitemapController.php` (modify) | Emit section URLs. |
| `database/seeders/CatalogSectionSeeder.php` (new) | The four launch sections. |
| `tests/Feature/CheckoutCharacterisationTest.php` (new) | Phase 0 safety net. |
| `tests/Feature/CatalogSectionTest.php` (new) | Section model, page, sitemap, redirects. |
| `tests/Feature/FulfilmentTest.php` (new) | Manual + credentials order lifecycle. |
| `tests/Unit/BuyerInputSchemaTest.php` (new) | Pure schema/validation logic. |
| `tests/Feature/SearchTest.php` (new) | Search relevance and visibility rules. |

### Redesign files (Phases 2–6)

| File | Responsibility |
|---|---|
| `resources/css/storefront.css` (modify) | Ash token set; old `--brand-*` kept until Phase 6.5. |
| `tailwind.storefront.config.js` (modify) | Tokens as utilities; navy `gray` override removed last. |
| `resources/views/components/ui/*.blade.php` (new) | Primitives: button, input, card, badge, chip, tabs, modal, skeleton, stars, flag. |
| `resources/views/components/ui/slider.blade.php` (new) | One component, three variants (hero / rail / compact). CSS scroll-snap + Alpine, no library. |
| `resources/views/components/catalog/*.blade.php` (new) | product-card, brand-card, denomination-tile, price, region-chip, rating, section-rail, breadcrumbs, buyer-inputs, mega-menu. |
| `database/migrations/*_create_banners_table.php` (new) | Hero slider slides. |
| `database/migrations/*_add_region_to_gift_card_categories.php` (new) | `region`, `region_group`. |
| `database/migrations/*_add_merchandising_fields.php` (new) | `compare_at_price_bdt`, quantity limits, `is_featured`. |
| `database/migrations/*_add_content_to_gift_card_categories.php` (new) | `instructions`, `faq`. |
| `database/migrations/*_add_product_to_reviews.php` (new) | `gift_card_category_id` — nullable, existing reviews untouched. |
| `app/Models/Banner.php` (new) | Slides with an optional date window. |
| `app/Filament/Resources/BannerResource.php` (new) | Slider admin. |
| `resources/views/storefront/*.blade.php` (rebuild) | Every storefront view, one at a time. |
| `resources/views/emails/*.blade.php` (modify) | Re-themed; **light background retained**. |
| `tests/Feature/DesignSystemTest.php` (new) | Fails the build on `style="` or a hex literal in a migrated view. |

---

# Phase 0 — Safety Net

**Nothing in this phase changes behaviour.** Its entire purpose is to make the Phase 4 refactor provably safe. Do not skip it; `OrderService` currently has no test coverage at all, and it handles money.

### Task 0.1: Characterisation tests for the current checkout

**Files:** Create `tests/Feature/CheckoutCharacterisationTest.php`

Write tests that pin the behaviour that exists **right now**, before any change. Reuse the `seoBrand()` / `seoProduct()` / `seoCard()` helpers from `tests/Feature/SeoTest.php` — extract them into `tests/Pest.php` first so both files share them.

- [ ] **Step 1: Extract the fixture helpers**
  Move `seoBrand`, `seoProduct`, `seoCard` from `tests/Feature/SeoTest.php` into `tests/Pest.php`, unchanged. Run `php artisan test --filter=Seo` — it must stay green.

- [ ] **Step 2: Pin the checkout invariants**
  Cover, at minimum:
  - adding to cart succeeds when `stock_count >= quantity`
  - adding to cart is refused when stock is short, with the exact current message
  - `OrderService::createOrder()` reserves exactly `quantity` codes and flips them to `reserved`
  - `createOrder()` throws `RuntimeException` when codes are short, and the transaction leaves no `Order` row behind
  - `completeOrder()` flips `reserved` → `sold`, creates `OrderItemCode` rows, decrements `stock_count`, dispatches `SendOrderCodesEmail`
  - `failOrder()` and `cancelOrder()` release reserved codes back to `available` with a null `order_item_id`
  - `refundOrder()` releases sold codes and increments `stock_count`
  - referral discount and wallet debit totals land on the order exactly as they do today

- [ ] **Step 3: Verify**
  `php artisan test` green. Record the count of passing tests — this number must never go down in later phases.

### Task 0.2: Capture the performance baseline

- [ ] **Step 1:** Record a Lighthouse mobile score for `/`, one `/brand/*` and one `/product/*`, and note the query count per page (`DB::listen` in a scratch route, or Laravel Debugbar). Commit the numbers into this file as a comment block so Success Criterion 6 is measurable later.

### Task 0.3: Production snapshot and staging rehearsal

**This is the task that makes the rest of the plan safe against live data.** Every
migration in this plan must be rehearsed here before it goes near production.

- [ ] **Step 1: Verified backup**
  `mysqldump` production. Then **restore that dump into a scratch database and
  confirm the site boots against it.** An unverified backup is not a backup; the
  only way to know a dump is restorable is to restore it.
  Confirm `APP_KEY` is in the backup set alongside the dump (R13) — the database
  alone will not be enough to read encrypted columns once Phase 4 ships.

- [ ] **Step 2: Record the production shape**
  Capture counts and distributions that later assertions depend on:
  ```sql
  SELECT COUNT(*) FROM main_categories;
  SELECT COUNT(*) FROM gift_card_categories WHERE main_category_id IS NULL;
  SELECT status, COUNT(*) FROM orders GROUP BY status;
  SELECT status, COUNT(*) FROM gift_card_codes GROUP BY status;
  -- How far has the shadowed stock column drifted? This is the evidence for AD-5.
  SELECT g.id, g.name, g.stock_count,
         (SELECT COUNT(*) FROM gift_card_codes c
           WHERE c.gift_card_id = g.id AND c.status = 'available') AS real_available
  FROM gift_cards g
  HAVING g.stock_count <> real_available;
  ```
  Paste the results into this file. The last query is the one that matters: if it
  returns rows, AD-5's rejection of `stock_count` reuse is confirmed against real
  data rather than inferred.

- [ ] **Step 3: Stand up a staging environment**
  A copy of the restored snapshot with production's `SESSION_DRIVER=database`,
  `QUEUE_CONNECTION=database` and `CACHE_STORE=database`. SQLite in the test suite
  will not reproduce the MySQL enum behaviour, the session persistence, or the
  cache-survives-deploy problem — and those are three of the top production risks.

- [ ] **Step 4: Rehearse the runbook**
  Run the full deployment runbook against staging for each phase before it ships.
  Rehearse the rollback too: `git revert` to the previous release **without**
  reversing the migration, and confirm the site still works. That is the rollback
  path AD-8 commits to, so it has to be proven at least once rather than assumed.

- [ ] **Step 5: Pre-deploy cart compatibility fixture**
  On staging, add items to a cart **before** deploying, then deploy and confirm the
  cart still resolves (R3). `SESSION_DRIVER=database` means this is the real
  production behaviour, not a hypothetical.

---

# Phase 1 — The Section Layer

Ships the data model and the backfill. No UI yet.

### Task 1.1: `catalog_sections` table and model

**Files:**
- Create: `database/migrations/*_create_catalog_sections_table.php`
- Create: `app/Models/CatalogSection.php`
- Test: `tests/Feature/CatalogSectionTest.php`

**Interfaces produced (relied on by every later task):**
- `CatalogSection::CACHE_KEY`
- `->mainCategories(): HasMany` and `->activeMainCategories(): HasMany`
- `CatalogSection::scopeActive($query)`

- [ ] **Step 1: Write the failing test**
  A section can be created; `slug` is unique; `is_active` and `sort_order` cast correctly; saving clears `StorefrontCatalog::BRANDS_CACHE_KEY`; changing the slug writes a `SlugRedirect` row.

- [ ] **Step 2: Migration**
  Columns: `id`, `name`, `slug` (unique), `tagline` (nullable), `description` (text, nullable), `icon` (nullable — accepts an emoji or a heroicon name, same convention as `main_categories.icon`), `image` (nullable), `accent_color` (nullable, hex), `sort_order` (int, default 0), `is_active` (bool, default true), `seo_title`, `seo_description`, `seo_content` (nullable), timestamps.
  Index `['is_active', 'sort_order']` — every storefront read filters and orders on exactly that pair.

- [ ] **Step 3: Model**
  Mirror `app/Models/MainCategory.php` structure precisely: same `$fillable` style, same `casts()` method form, same `booted()` hooks for `Cache::forget(StorefrontCatalog::BRANDS_CACHE_KEY)` and `SlugRedirect::rememberSlugChange()`. Consistency with the existing models is the point — do not invent a different shape.

- [ ] **Step 4: Verify** — tests green.

### Task 1.2: Link brands to sections and backfill "Gift Cards"

**Files:**
- Create: `database/migrations/*_add_catalog_section_to_main_categories.php`
- Modify: `app/Models/MainCategory.php`

- [ ] **Step 1: Write the failing test**
  After migrating, every pre-existing `MainCategory` belongs to a section slugged `gift-cards`; a brand with a null section still renders on the homepage (the existing unbranded-tolerance behaviour must not regress).

- [ ] **Step 2: Migration**
  Add nullable `catalog_section_id` guarded by `Schema::hasColumn()`, FK `nullOnDelete()` — copy the defensive style of `2026_06_06_130108_add_main_category_to_gift_card_categories.php` exactly. Index the column.

  In the same migration's `up()`, after the column exists: `firstOrCreate` a `Gift Cards` section (slug `gift-cards`, `sort_order` 0) and assign it to every `main_categories` row where `catalog_section_id IS NULL`.

  **Write the backfill with the query builder, not the Eloquent model.** A model-based backfill breaks the moment someone later edits `CatalogSection`'s `$fillable` or adds a global scope — migrations must not depend on today's model shape.

  `down()` drops the FK then the column. Do not delete the seeded section in `down()`; destroying admin-entered data on a rollback is worse than leaving an orphan row.

- [ ] **Step 3: Model**
  Add `catalog_section_id` to `$fillable`, `'catalog_section_id' => 'integer'` to `casts()`, and a `catalogSection(): BelongsTo`.

- [ ] **Step 4: Verify**
  Locally: `php artisan migrate:fresh --seed` then `php artisan test` green.
  **Then against a restored production snapshot (Task 0.3): `php artisan migrate` only — never `migrate:fresh`.** Assert that `main_categories` row count is unchanged, that no row has a null `catalog_section_id`, and that running the migration a second time is a no-op.

### Task 1.3: Section seeder

**Files:** Create `database/seeders/CatalogSectionSeeder.php`; modify `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1:** Seed four sections with `firstOrCreate` on slug so re-running is safe: Gift Cards (`gift-cards`, sort 0), Software (`software`, sort 1), Subscriptions (`subscriptions`, sort 2), Game Top-Up (`game-top-up`, sort 3). Give each a tagline and accent colour drawn from the `brand.*` / accent ramp.
- [ ] **Step 2:** Register it in `DatabaseSeeder` **before** `CategorySeeder`.
- [ ] **Step 3: Verify** — seeder is idempotent across two consecutive runs.

### Task 1.4: `StorefrontCatalog` reads sections

**Files:** Modify `app/Services/StorefrontCatalog.php`

- [ ] **Step 1: Write the failing test**
  `sectionsWithBrands()` returns only active sections that have at least one active brand with at least one active product with at least one active card — the same visibility rule `brands()` already enforces one level down. Empty sections never appear. Assert the whole call runs in a bounded query count (eager-loaded, no N+1).

- [ ] **Step 2: Implement**
  Add `sections(): Collection` and `sectionsWithBrands(): Collection`, cached under the existing `BRANDS_CACHE_KEY` family. Follow the established filter-after-eager-load idiom already used in `brands()` (`app/Services/StorefrontCatalog.php:34-48`) rather than adding `whereHas` chains — it is the same problem and deserves the same solution.

  Keep `brands()` and `unbrandedCategories()` working exactly as they do. `SitemapController` and the homepage call them today.

- [ ] **Step 3: Verify** — tests green, including the existing `SeoTest` sitemap assertions.

---

# Phase 2 — Admin

Sections are useless until an admin can fill them. This phase is deliberately before any storefront work so content can be entered while the front end is being built.

### Task 2.1: `CatalogSectionResource`

**Files:** Create `app/Filament/Resources/CatalogSectionResource.php` + `Pages/`

- [ ] **Step 1:** Model it on `app/Filament/Resources/MainCategoryResource.php` — same `$navigationGroup = 'Catalog'`, same section-and-SEO form layout, same slug auto-fill from name via `live(onBlur: true)`. Set `$navigationSort = -1` so it sits above Brands, which is where it now sits in the hierarchy.
- [ ] **Step 2:** Table columns: image, name, slug, brand count (`->counts('mainCategories')`), sort order, active toggle. Reorderable by `sort_order`.
- [ ] **Step 3: Verify** — a Filament page test asserts the list and create pages render for an admin and 403 for a non-admin. `User::canAccessPanel()` returns `$this->is_admin`, so the test user needs `is_admin => true`.

### Task 2.2: Brand form gains a section

**Files:** Modify `app/Filament/Resources/MainCategoryResource.php`

- [ ] **Step 1:** Add a `Select::make('catalog_section_id')` as the **first** field, labelled "Section", `->options(CatalogSection::orderBy('sort_order')->pluck('name','id'))`, searchable, nullable with a `— Select section —` placeholder. Mirror the existing `main_category_id` select in `GiftCardCategoryResource.php:26-32` exactly — same idiom, same nullability.
- [ ] **Step 2:** Add a `TextColumn::make('catalogSection.name')` badge column and a `SelectFilter` on section to the table.
- [ ] **Step 3: Verify** — Filament tests green.

### Task 2.3: Fulfilment fields in the admin

**Files:**
- Create: `database/migrations/*_add_fulfilment_fields_to_gift_cards.php`
- Create: `database/migrations/*_add_buyer_input_fields_to_gift_card_categories.php`
- Modify: `app/Models/GiftCard.php`, `app/Models/GiftCardCategory.php`, `app/Filament/Resources/GiftCardResource.php`, `app/Filament/Resources/GiftCardCategoryResource.php`

This task adds the **columns and the admin UI only**. `OrderService` is not touched until Phase 4, so nothing behaves differently yet.

- [ ] **Step 1: Write the failing test**
  A `GiftCard` created without `fulfilment_type` is `code_pool`; `stock_count` for a `code_pool` card still counts available codes; `stock_count` for a `manual` card returns `manual_stock`. Add the case that matters against production data: a card whose stored `stock_count` column is stale (say 99) but which has zero available codes still reports 0 while it is `code_pool`, and reports `manual_stock` — not 99 — once flipped to `manual`.

- [ ] **Step 2: Migrations**
  `gift_cards`: `fulfilment_type` string(20) default `'code_pool'` indexed, `manual_stock` integer default 0, `delivery_eta_label` nullable string.
  `gift_card_categories`: `buyer_input_fields` json nullable.

  **Region is no longer on `gift_cards`.** The original draft put a `region` column on the SKU; the reference UI proves that wrong. Region is a property of the *product* — a $10 and a $50 Steam HKD card are both Hong Kong — and the product page needs to switch between regional siblings. It moves to `gift_card_categories` in Task 2.4. See redesign spec §4.4.

  Use `string` rather than a native enum — adding a value to a MySQL enum later needs a table rebuild, and `2026_06_06_102023_add_cancelled_status_to_orders_table.php` already shows that cost being paid once in this repo.

  **`manual_stock` is a new column on purpose — do not reuse `stock_count`.** See AD-5 and R8: `stock_count` is shadowed by an accessor on read, has drifted across six months of production, and `OrderEditService::syncStockCounts()` (`app/Services/OrderEditService.php:409-419`) exists specifically because of that drift. Promoting a stale value to authoritative stock would silently oversell a product that cannot be auto-delivered. Task 0.3 Step 2 measures the actual drift.

- [ ] **Step 3: `GiftCard` model**
  Add `FULFILMENT_CODE_POOL`, `FULFILMENT_MANUAL`, `FULFILMENT_CREDENTIALS` constants and a `FULFILMENT_TYPES` label map. Add the four columns to `$fillable`.

  Amend `getStockCountAttribute()` — and keep the existing code-pool branch **byte-identical**, including its comment:

  ```php
  public function getStockCountAttribute(): int
  {
      if ($this->fulfilment_type !== self::FULFILMENT_CODE_POOL) {
          return (int) ($this->attributes['manual_stock'] ?? 0);
      }

      // ... existing code_pool body, unchanged ...
  }
  ```

  Add `usesCodePool(): bool` so no caller compares the string literal.

- [ ] **Step 3b: Fix the two queries that bypass the accessor** (R14)
  `GiftCard::scopeInStock()` (`app/Models/GiftCard.php:84`) and `StockAlertWidget` (`app/Filament/Widgets/StockAlertWidget.php:18,24`) filter on the raw `stock_count` column, so manual SKUs would be invisible to both — a top-up would never appear in an in-stock listing and would never raise a low-stock alert.

  Make both fulfilment-aware, e.g.:
  ```php
  public function scopeInStock($query)
  {
      return $query->where(fn ($q) => $q
          ->where(fn ($q) => $q->where('fulfilment_type', self::FULFILMENT_CODE_POOL)
                               ->where('stock_count', '>', 0))
          ->orWhere(fn ($q) => $q->where('fulfilment_type', '!=', self::FULFILMENT_CODE_POOL)
                                 ->where('manual_stock', '>', 0)));
  }
  ```
  Add a regression test asserting a low-stock **manual** SKU appears in the alert widget — this is the kind of gap that is invisible until an admin wonders why a sold-out top-up never warned them.

- [ ] **Step 4: `GiftCardCategory` model**
  Cast `buyer_input_fields` to `array`, add to `$fillable`, and add `buyerInputSchema(): array` returning `[]` when null so callers never null-check.

- [ ] **Step 5: `GiftCardResource` form**
  A "Fulfilment" section: `Select` for type (with helper text explaining each), `TextInput` for `delivery_eta_label` (placeholder "Instant" / "5–30 minutes"). Add the `manual_stock` field `->visible(fn (Get $get) => $get('fulfilment_type') !== 'code_pool')` so a code-pool card never shows an editable stock number it does not use — that field being editable-but-ignored is exactly the kind of thing that generates a support ticket six months from now.

- [ ] **Step 6: `GiftCardCategoryResource` form**
  A collapsible "Buyer Inputs" `Repeater` on `buyer_input_fields`, each row: `key` (slug), `label`, `type` (text | number | email | select), `required` toggle, `help` text, `options` (visible only for select). Collapsed by default and clearly optional, so the gift-card editing experience is unchanged for the 99% case.

- [ ] **Step 7: Verify** — tests green; manually confirm an existing gift card still saves with no fulfilment fields touched.

---

### Task 2.4: Regions and regional sibling products

**Files:**
- Create: `database/migrations/*_add_region_to_gift_card_categories.php`
- Modify: `app/Models/GiftCardCategory.php`, `app/Filament/Resources/GiftCardCategoryResource.php`

See redesign spec §4.4. This is what makes the product page's region selector work.

- [ ] **Step 1: Write the failing test**
  A product with a null `region_group` reports no regional siblings (so it renders exactly as today); three products sharing a `region_group` each list the other two; siblings are ordered deterministically; an inactive sibling is never listed.

- [ ] **Step 2: Migration**
  `gift_card_categories`: `region` nullable string(2) (ISO-3166 alpha-2, or `GL` for global), `region_group` nullable string, indexed. Both nullable — every existing product keeps working with no region and no selector.

- [ ] **Step 3: Model**
  Add both to `$fillable`. Add `regionalSiblings()` returning active products sharing the `region_group`, excluding self, and `hasRegions(): bool`. Return an empty collection when `region_group` is null rather than making callers null-check.

- [ ] **Step 4: Admin**
  `Select` for region (country list with flags) and a `TextInput` for `region_group` with helper text: *"Products sharing this key appear in each other's region switcher. Leave blank if this product has no regional variants."*

- [ ] **Step 5: Verify** — tests green; an existing product with no region renders unchanged.

### Task 2.5: Deals, featured products and purchase limits

**Files:**
- Create: `database/migrations/*_add_merchandising_fields.php`
- Modify: `app/Models/GiftCard.php`, `app/Models/GiftCardCategory.php`, both Filament resources, `app/Http/Controllers/CheckoutController.php`

See redesign spec §4.2 and §4.3.

- [ ] **Step 1: Write the failing test**
  A SKU with `compare_at_price_bdt` above `price_bdt` is a deal and reports the right discount percentage; one at or below it is not a deal; `max_quantity` defaults to 10 so today's `'max:10'` cap is reproduced exactly; a SKU with `max_quantity = 2` rejects a quantity of 3.

- [ ] **Step 2: Migration**
  `gift_cards`: `compare_at_price_bdt` decimal(10,2) nullable, `min_quantity` int default 1, `max_quantity` int default 10.
  `gift_card_categories`: `is_featured` bool default false, `featured_sort` int default 0, indexed on `['is_featured','featured_sort']`.

  **`compare_at_price_bdt` must not touch `buy_price_bdt`**, which drives the existing margin column in `GiftCardResource`. It is presentational only.

- [ ] **Step 3: Models**
  `GiftCard`: `isDeal(): bool`, `discountPercent(): ?int`, `scopeDeals($query)`. A deal is *derived* from the two prices, never a separate flag — one source of truth.
  `GiftCardCategory`: `scopeFeatured($query)`.

- [ ] **Step 4: Replace the hardcoded quantity cap**
  `CheckoutController::addToCart()` and `updateQuantity()` both hardcode `'max:10'` (lines 65 and 95). Replace with the SKU's own `min_quantity` / `max_quantity`. Defaulting `max_quantity` to 10 means every existing row behaves identically — verify that explicitly.

- [ ] **Step 5: Admin**
  `compare_at_price_bdt` beside `price_bdt` with helper text "Shown struck through. Leave blank for no discount." Quantity limits in the same group. `is_featured` toggle + `featured_sort` on the category form, and a featured filter on its table.

- [ ] **Step 6: Verify** — tests green.

### Task 2.6: Hero banners

**Files:** Create `database/migrations/*_create_banners_table.php`, `app/Models/Banner.php`, `app/Filament/Resources/BannerResource.php`

See redesign spec §4.1.

- [ ] **Step 1: Write the failing test**
  `Banner::scopeVisible()` returns only active banners inside their date window; a banner with null `starts_at`/`ends_at` is always visible; one whose window has passed is not; ordering is by `sort_order`.

- [ ] **Step 2: Migration and model**
  `id`, `title`, `image`, `mobile_image` nullable, `link_url` nullable, `sort_order` default 0, `is_active` default true, `starts_at` / `ends_at` nullable timestamps. Cast the dates. Clear the storefront cache on save, the same way `CatalogSection` does.

- [ ] **Step 3: Admin**
  `BannerResource` under the Catalog navigation group. Image uploads to `disk('public')`, directory `images/banners`, matching the existing convention in `MainCategoryResource`. Reorderable by `sort_order`, with an image preview column.

- [ ] **Step 4: Verify** — tests green.

### Task 2.7: Product content tabs and per-product ratings

**Files:**
- Create: `database/migrations/*_add_content_to_gift_card_categories.php`, `database/migrations/*_add_product_to_reviews.php`
- Modify: `app/Models/GiftCardCategory.php`, `app/Models/Review.php`, `app/Http/Controllers/ReviewController.php`, `app/Filament/Resources/GiftCardCategoryResource.php`

See redesign spec §4.5 and §4.6.

- [ ] **Step 1: Write the failing test**
  A product with no `instructions` falls back to its brand's `how_to_redeem`; a product with neither renders no Instructions tab; an existing review with a null `gift_card_category_id` still appears in the homepage testimonial block (**this is the regression that matters** — there are six months of them); a product with no reviews reports a null rating rather than zero.

- [ ] **Step 2: Migrations**
  `gift_card_categories`: `instructions` text nullable, `faq` json nullable.
  `reviews`: `gift_card_category_id` nullable FK, `nullOnDelete()`, indexed.

  Nullable is doing real work here: every existing review row stays valid and keeps rendering exactly where it does now.

- [ ] **Step 3: Models**
  `GiftCardCategory`: `reviews()` HasMany, `averageRating(): ?float`, `reviewCount(): int`. Return `null` — not `0` — when there are no reviews, so the view can hide the block entirely instead of advertising `0/5` the way the reference does.
  `Review`: add the FK to `$fillable` and a `giftCardCategory()` relation.

- [ ] **Step 4: Attribute new reviews**
  `ReviewController::store()` currently records order-level reviews. Where an order has exactly one distinct product, attribute the review to it. Where it has several, leave the FK null rather than guessing.

- [ ] **Step 5: Admin**
  A "Product Content" section on the category form: `RichEditor` for `instructions` (helper: "Leave blank to inherit the brand's How to Redeem"), `Repeater` for `faq` (question / answer).

- [ ] **Step 6: Verify** — tests green, and confirm the homepage testimonial block renders identically to before.

---

# Phase 3 — Design System

**No page is built in this phase and no page changes.** This is the foundation
that stops the rebuild from regenerating the 459 inline styles it exists to
remove. Skipping it does not save time; it moves the cost to Phase 4 and
multiplies it.

Full detail in the redesign spec, §2 (palette, type, spacing) and §5 (strategy).

### Task 3.1: Tokens

**Files:** Modify `resources/css/storefront.css`, `tailwind.storefront.config.js`

- [ ] **Step 1: Define the token set**
  Add the ash palette, type scale, spacing, radius and the two shadows from redesign spec §2.1–2.2 as CSS custom properties on `:root`, namespaced (`--ui-surface-0`, `--ui-text-hi`, …) so they cannot collide with the four existing `--brand-*` variables.

- [ ] **Step 2: Mirror into Tailwind**
  Expose the tokens as Tailwind colours (`surface.0`, `text.hi`, `accent.DEFAULT`, …) in `tailwind.storefront.config.js`, so views use utility classes and never a raw value.

  **Do not touch the existing `gray` override yet.** It is navy-tinted and every un-migrated page depends on it. It is removed in Phase 6, once nothing references it.

- [ ] **Step 3: Both themes coexist**
  Old `--brand-*` variables and the `.btn-brand` / `.card-gradient` / `.hero-gradient` helpers stay until Phase 6. Redesign spec §5.3 — each page migrates on its own schedule and the site is never half-broken.

- [ ] **Step 4: Verify** — every existing page renders byte-identically. This phase adds tokens; it changes nothing.

### Task 3.2: Primitive components

**Files:** Create `resources/views/components/ui/*.blade.php`

- [ ] **Step 1:** Build `button`, `input`, `select`, `card`, `badge`, `chip`, `tabs`, `modal`, `skeleton`, `stars`, `flag`. Each themed **only** through tokens — no hex, no `style="..."`.
- [ ] **Step 2:** Every interactive primitive gets a visible focus ring, an accessible disabled state, and a touch target of at least 44×44px. Mobile-dominant store; this is not optional polish.
- [ ] **Step 3: Verify** — render them all on a scratch `/ui-kit` route (local only, never registered in production) and check both light and dark rendering of the e-mail-safe variants.

### Task 3.3: Slider component

**Files:** Create `resources/views/components/ui/slider.blade.php`

- [ ] **Step 1:** CSS scroll-snap plus a small Alpine controller. **No carousel library** — redesign spec §5.4.
- [ ] **Step 2:** Three variants from one component: `hero` (full-bleed, autoplay, dots, neighbours peeking ~8%), `rail` (catalog rows, arrows on desktop, free scroll on mobile), `compact` (related products).
- [ ] **Step 3:** Autoplay pauses on hover and on focus, and is disabled entirely under `prefers-reduced-motion`. Swipe works on touch. Arrow keys work when focused.
- [ ] **Step 4: Verify** — no layout shift on load (explicit dimensions), and no horizontal page scroll at 375px.

### Task 3.4: Catalog components

**Files:** Create `resources/views/components/catalog/*.blade.php`

- [ ] **Step 1:** Build `product-card`, `brand-card`, `denomination-tile`, `price` (handles struck compare-at + discount badge), `region-chip`, `rating`, `section-rail`, `breadcrumbs`, `buyer-inputs`.

  These supersede the extraction described in the original Task 3.1 of the pre-redesign draft — the old markup is not lifted, because it is built from the navy palette and inline styles. It is rebuilt on tokens instead.

- [ ] **Step 2:** `denomination-tile` must render an out-of-stock state that is dimmed and badged but still visible (redesign spec §3.4) — hiding them makes a product page look empty.
- [ ] **Step 3:** `price` is the single place that formats money. It calls `format_bdt()` and nothing else formats currency anywhere in the rebuild.
- [ ] **Step 4: Verify** — component tests for each; visual check at 375 / 768 / 1440.

### Task 3.5: The regression guard

**Files:** Create `tests/Feature/DesignSystemTest.php`

- [ ] **Step 1: Write the test that keeps this honest**
  A Pest test that greps rebuilt views for `style="` and `#[0-9a-fA-F]{6}` and fails on a hit. Redesign spec §5.2.

- [ ] **Step 2: Allow-list what is not yet migrated**
  Start with every current storefront view on the allow-list, and **remove entries as Phase 4 and 6 rebuild them**. The build stays green throughout and the remaining work is always visible in one place.

- [ ] **Step 3: Verify** — the test passes now, and fails if a hex literal is added to an already-migrated view.

---

# Phase 4 — Storefront Rebuild

Every page here is **built once, in the new theme, with the new catalog structure
already in it.** Nothing is styled in navy and re-themed later.

Pages ship individually — old and new themes coexist (redesign spec §5.3) — with
one exception: the header and footer appear everywhere, so they ship first.

As each view is rebuilt, **remove it from the allow-list in `DesignSystemTest`**
(Task 3.5). That list shrinking to empty is the definition of done for the
redesign.

Full page specifications: redesign spec §3.

### Task 4.1: Header, mega-menu and footer

**Files:** Modify `resources/views/layouts/storefront.blade.php`; create `resources/views/components/catalog/mega-menu.blade.php`

Ships first. Until this lands nothing looks new; once it lands everything does,
which is why the accent colour is retained — old navy pages still look
deliberate beneath a new ash header.

- [ ] **Step 1: Write the failing test**
  Every page renders links to all active sections; an inactive section never appears; the layout's query count on a cached page load is unchanged from the Phase 0 baseline; the header renders for guest and authenticated users alike.

- [ ] **Step 2: Header**
  Rebuild per redesign spec §3.1: logo, global search (centre, widest element), a single `CATALOG` trigger, cart with count, sign-in / sign-up. 60px, `--ui-surface-1`, 1px bottom border, sticky.

  Reuse the existing `$cartCount` calculation and the existing authenticated avatar dropdown logic verbatim — only their styling changes. Do not rewrite working auth UI.

- [ ] **Step 3: Mega-panel**
  Three columns (sections / brands / regions) per redesign spec §3.1. Read from `StorefrontCatalog::sectionsWithBrands()` — already cached, so this is free. Resolve it once via a view composer or `View::share`, never per-view.

  Hovering column 1 swaps columns 2 and 3 **client-side**; the whole tree ships with the page. No fetch on hover.

  Mobile: an accordion inside the existing `x-data="{ mobileOpen: false }"` drawer (`resources/views/layouts/storefront.blade.php:252`). Extend that Alpine component; do not add a second one.

  Accessibility is a requirement, not polish: `aria-expanded`, `Esc` closes, arrow-key traversal, focus trap while open, visible focus rings, and every entry a real `<a href>` so it works with JS off.

- [ ] **Step 4: Keep the old links**
  Home / FAQ / Contact survive, in the panel footer or an overflow. Do not remove them; they are in the footer sitemap and in user habit.

- [ ] **Step 5: Footer**
  Same content and links, re-coloured to the ash tokens, decorative gradients removed, payment logos kept (redesign spec §3.6).

- [ ] **Step 6: Verify** — tests green; check at 375 / 768 / 1440; confirm no horizontal scroll at 375px.

### Task 4.2: Global search

**Files:** Create `app/Http/Controllers/SearchController.php`, `resources/views/storefront/search.blade.php`, `tests/Feature/SearchTest.php`; modify `routes/web.php`

- [ ] **Step 1: Write the failing test**
  Searching "steam" returns the Steam brand and its products; inactive and empty records never appear; a 1-character query returns nothing rather than the whole catalog; the suggest endpoint is rate-limited.

- [ ] **Step 2: Implement**
  `GET /search?q=` (page) and `GET /search/suggest?q=` (JSON, `throttle:60,1`). Query brands, products and cards by name with `LIKE`, apply the same visibility rules as `StorefrontCatalog`, group by section, cap at 8 per group.

  Escape `%` and `_` in the user's term before building the `LIKE` pattern — otherwise a query of `%` matches the entire catalog.

- [ ] **Step 3: Overlay**
  Expands from the header input. 250 ms debounce, minimum 2 characters, results grouped by section showing icon, name, section badge and "from ৳X". Empty state offers the section tiles. Keyboard: arrows move, `Enter` opens, `Esc` closes.

- [ ] **Step 4: Verify** — tests green.

### Task 4.3: Homepage

**Files:** Modify `resources/views/storefront/home.blade.php`, `app/Http/Controllers/StorefrontController.php`

Seven blocks in the order given in redesign spec §3.2.

- [ ] **Step 1: Write the failing test**
  The page renders hero slides, deals, featured, and one rail per non-empty section; an empty section is omitted entirely; **with zero sections configured it falls back to today's flat brand grid** (the `$fallbackCategories` path at `app/Http/Controllers/StorefrontController.php:26-40` must keep working — it is the safety net if the backfill is ever incomplete on production); referral and reseller bands appear only when their settings are on.

- [ ] **Step 2: Controller**
  Add visible banners, deals (`GiftCard::deals()`), and featured products. The section tree comes from the cache the header already resolved — **the whole homepage catalog must cost one cache read**, not one query per rail.

- [ ] **Step 3: View**
  Hero slider → best deals → featured → one slider per section → reviews → referral (if enabled) → reseller (if enabled). All sliders use `<x-ui.slider>` from Task 3.3.

  Keep the gating logic for referral and reseller exactly as it is; only restyle. Update the `ItemList` JSON-LD at `home.blade.php:8-19` to list sections rather than flattening brands.

- [ ] **Step 4: Performance**
  First hero slide preloaded; everything below the fold `loading="lazy"`; explicit `width`/`height` on every image. The 90 inline styles, blurred orbs and gradient overlays are all removed — Lighthouse mobile must come out **at or above** the Phase 0 baseline, not merely level with it.

- [ ] **Step 5: Verify** — tests green; Lighthouse compared to baseline; remove `home.blade.php` from the `DesignSystemTest` allow-list.

### Task 4.4: Category and product-list pages

**Files:**
- Modify: `routes/web.php`, `app/Http/Controllers/StorefrontController.php`, `resources/views/storefront/brand.blade.php`
- Create: `resources/views/storefront/section.blade.php`

One layout serves both `/category/{section}` and `/brand/{brand}` (redesign spec §3.3); they differ only in what the rail is scoped to and what the heading says.

- [ ] **Step 1: Write the failing test**
  `/category/gift-cards` renders and lists only active brands with stock; an inactive section 404s; a renamed section 301s to its new slug via `SlugRedirect` (the same guard shape as `StorefrontController::brand()` at lines 55-66); the page emits a self-referencing canonical and a `CollectionPage` + `BreadcrumbList` block; the region filter and sort control narrow results correctly.

- [ ] **Step 2: Route**
  `Route::get('/category/{sectionSlug}', [StorefrontController::class, 'section'])->name('section');`
  With the other storefront routes, **above** the legacy `/shop/{any}` catch-all.

- [ ] **Step 3: Controller**
  `section()` follows the shape of `brand()` exactly: look up by slug + `is_active`, fall back to `SlugRedirect::findModel()`, `abort_unless($moved?->is_active, 404)`, 301 to the new slug.

  Load brands with `withMin(['giftCards as min_price_bdt' => ...], 'price_bdt')` through the product relation so each card shows "from ৳X" without an N+1 — the technique already used at `StorefrontController.php:104-112`.

  `?sort=` and `?region=` resolved through an allow-list `match`. **Never interpolate a raw query value into `orderBy` or a `where`.**

- [ ] **Step 4: View**
  Two columns per redesign spec §3.3: scrollable category rail with counts and a featured panel beneath, grid with region-flag badges, region filter and sort top-right, breadcrumbs above the heading. Rail collapses to a horizontal chip row on mobile.

  Filter links carry `rel="nofollow"` and the page canonicalises to the unfiltered URL — otherwise facet combinations bloat the index (risk R4).

  Empty state is a "coming soon" panel linking the other sections, never a blank page.

- [ ] **Step 5: Verify** — tests green; remove both views from the allow-list.

### Task 4.5: Product detail page

**Files:** Modify `resources/views/storefront/product.blade.php`, `app/Http/Controllers/StorefrontController.php`

The highest-value page on the site. Redesign spec §3.4.

- [ ] **Step 1: Write the failing test**
  Denominations render with prices; out-of-stock tiles are visible but disabled; the region switcher lists only active siblings and is absent when `region_group` is null; quantity respects `min_quantity` / `max_quantity`; the rating block is **hidden entirely** when the product has no reviews; Instructions falls back to the brand's `how_to_redeem`; the buyer-input block renders only when a schema is declared.

- [ ] **Step 2: Controller**
  Load regional siblings, rating aggregate, and the existing related-products query. Keep `withAvailableCodesCount()` on the denominations — it is what keeps stock reads off the N+1 path.

- [ ] **Step 3: View**
  Hero (icon, name, rating, delivery chip, region switcher, important note) → denomination grid → tabs (Description / Instructions / FAQ) → sticky purchase panel → related products.

  On mobile the purchase panel becomes a **fixed bottom bar** with total and Buy. This is the single biggest conversion improvement available on a mobile-dominant store.

  Keep the existing `<x-product-chat-buttons>` below the purchase panel — including its deliberate placement outside the stock guard (see the comment at `product.blade.php:314`, which exists because an out-of-stock product is exactly when a customer wants to ask).

  Keep the existing `Product` / `Offer` / `BreadcrumbList` JSON-LD, extended to four levels and with `aggregateRating` when ratings exist.

- [ ] **Step 4: Verify** — tests green; `SeoTest` green; remove from the allow-list.

### Task 4.6: Cart

**Files:** Modify `resources/views/storefront/cart.blade.php`, `app/Http/Controllers/CheckoutController.php`

Redesign spec §3.5.

- [ ] **Step 1: Write the failing test**
  Lines group by product; deselecting a line removes it from the total but leaves it in the cart; the selected count is accurate; **a cart created before this change has every line selected by default** (R3 — `SESSION_DRIVER=database`, pre-deploy carts are the normal case); checkout receives only selected lines.

- [ ] **Step 2: Selection**
  A `selected` boolean on each session cart line, **defaulting to `true` when absent**. No migration. Add a `cart.toggle-selection` endpoint alongside the existing `cart.update-quantity`.

- [ ] **Step 3: Guard the checkout path**
  `resolveCartItems()` must filter to selected lines for totalling and order creation. Deselecting everything disables the checkout button rather than submitting an empty order.

- [ ] **Step 4: View**
  Grouped lines with checkboxes, quantity steppers, per-line struck compare-at price, sticky totals panel, fixed bottom bar on mobile.

  Carry over the softened failure from Task 5.4 Step 3 if that has already shipped — one unresolvable line must not wipe the basket.

- [ ] **Step 5: Verify** — tests green; test with a cart created before the deploy on staging.

### Task 4.7: Sitemap, breadcrumbs and SEO

**Files:** Modify `app/Http/Controllers/SitemapController.php`, `resources/views/components/catalog/breadcrumbs.blade.php`

- [ ] **Step 1: Write the failing test**
  The sitemap lists section URLs; a section with no visible brands is omitted (matching the "never advertise a hidden product" rule `SeoTest` already pins); product and brand breadcrumbs carry four levels including the section.

- [ ] **Step 2: Implement**
  Extend `SitemapController::index()` with section URLs, deriving `lastmod` with the existing `latest()` helper. Each section page gets a distinct `seo_title` / `seo_description` and a self-referencing canonical (risk R4).

  Breadcrumbs render the full path on mobile — wrapping, never truncated to a single "Back" (Baymard: 36% of e-commerce sites get this wrong).

- [ ] **Step 3: Verify** — full `SeoTest` suite green.

**Release checkpoint — this is the one that matters.** The store now looks new,
browses by vertical, and sells **Gift Cards and Software**; both deliver from a
code pool and `OrderService` has not been touched. Ship, watch, then start
Phase 5.

---

# Phase 5 — The Fulfilment Engine

Unlocks Subscriptions and Game Top-Up. Treat this phase as one unit — do not release partway through.

### Task 5.1: `BuyerInputSchema` service

**Files:** Create `app/Services/BuyerInputSchema.php`, `tests/Unit/BuyerInputSchemaTest.php`

Pure logic, no database, so it goes in `tests/Unit` (which does not get `RefreshDatabase` — it must not touch a model or call `site_setting()`).

- [ ] **Step 1: Write the failing test**
  Covers: a null schema produces no rules; a required text field produces `['required','string','max:255']`; a select field produces an `in:` rule built from its declared options; submitted values are filtered to declared keys only (an attacker cannot smuggle extra keys into `buyer_inputs`); values are trimmed; the stable hash used for cart keys is identical for identical inputs and differs for different ones.

- [ ] **Step 2: Implement**
  `fromArray(?array $schema)`, `rules(): array`, `attributes(): array`, `normalise(array $input): array`, `hash(array $normalised): string`.

- [ ] **Step 3: Verify** — tests green.

### Task 5.2: Order-side schema

**Files:**
- Create: `database/migrations/*_add_fulfilment_to_order_items.php`
- Modify: `app/Models/OrderItem.php`

**No `orders` migration.** The `processing` status already exists in the enum
(`database/migrations/2026_04_23_104644_01_create_orders_table.php:20`), is already
labelled in `app/Filament/Resources/OrderResource.php:178`, and is already listed in
both `OrderEditService::EDITABLE_STATUSES` and `::DELIVERED_STATUSES` — yet no code
path ever sets it. It means exactly "paid, being worked on", which is what a
partially-fulfilled order is. Reuse it. See AD-7.

- [ ] **Step 1: Write the failing test**
  A new `OrderItem` defaults to `fulfilment_status = 'not_required'`; `delivered_payload` is unreadable in the raw database row but readable through the model (proving the `encrypted` cast is live); an order carrying a pending item reports `processing` and `OrderEditService::canEdit()` still returns true for it.

- [ ] **Step 2: Migration**
  `order_items`: `fulfilment_status` string(20) default `'not_required'` indexed, `buyer_inputs` json nullable, `delivered_payload` text nullable, `delivered_at` timestamp nullable, `delivered_by_admin_id` nullable FK to users.

- [ ] **Step 3: Model**
  Casts: `'buyer_inputs' => 'array'`, `'delivered_payload' => 'encrypted'`, `'delivered_at' => 'datetime'`. Add `needsFulfilment(): bool` and `isDelivered(): bool`.

- [ ] **Step 4: Verify** — tests green; `php artisan test` full suite green (the Phase 0 count must not drop).

### Task 5.3: Refactor `OrderService` — extract, then branch

This is the highest-risk task in the plan. Do the two steps in this order and do not merge them.

**Files:** Modify `app/Services/OrderService.php`

- [ ] **Step 1: Pure extraction, zero behaviour change**
  The identical code-reservation block appears twice, at `app/Services/OrderService.php:64-82` (`createOrder`) and `:158-172` (`createSendMoneyOrder`). Move it verbatim into `private function reserveCodePoolStock(OrderItem $orderItem, int $quantity): void` and call it from both places.

  Likewise the identical release loop in `completeOrder()` and `approveSendMoneyOrder()` becomes `private function releaseCodesToBuyer(OrderItem $orderItem): void`.

  **Run the Phase 0 characterisation tests. They must pass with no edits.** If any test needs changing, the extraction was not pure — revert and redo it. This is the whole reason Phase 0 exists.

- [ ] **Step 2: Add the branch**
  ```php
  $giftCard->usesCodePool()
      ? $this->reserveCodePoolStock($orderItem, $item['quantity'])
      : $this->reserveManualStock($orderItem, $giftCard, $item['quantity']);
  ```

  `reserveManualStock()` re-reads the `gift_cards` row with `lockForUpdate()`, throws the same `RuntimeException` message shape when `manual_stock < quantity`, decrements `manual_stock`, and sets the order item's `fulfilment_status` to `pending` (risk R2 — the decrement must happen here, at reservation, not at completion).

- [ ] **Step 3: Completion, failure and refund paths**
  `completeOrder()` and `approveSendMoneyOrder()`: release codes for code-pool items as today; for manual items leave `fulfilment_status = 'pending'` and set the **order** status to `processing` when any item still needs fulfilment, `paid` when none do — so a pure gift-card order still lands on `paid` exactly as it does now.
  `failOrder()`, `cancelOrder()`, `refundOrder()`: restore `manual_stock` alongside the existing code release. A cancelled top-up that never gives its stock back is a silent inventory leak.

- [ ] **Step 4: E-mail**
  `SendOrderCodesEmail` must not claim codes were delivered when some items are still pending. Send it only when every item is deliverable; otherwise send a "we're processing your order" variant carrying the ETA label.

- [ ] **Step 5: Verify, including in-flight orders** (R12)
  Phase 0 tests green **unchanged**, plus new `tests/Feature/FulfilmentTest.php` covering the manual lifecycle including a concurrent-reservation test that proves no oversell.

  Then prove the deploy is safe for orders that straddle it. Orders sitting in `pending` or `payment_initiated` already hold **reserved** `gift_card_codes`; a customer mid-bKash-redirect when this ships will return and complete against new code. Phase 4 changes only the *reservation* path, so completion of an already-reserved order should be untouched — but that has to be demonstrated, not assumed:

  - Reserve an order under the old code path, migrate, then complete it and assert codes are released normally.
  - On staging, confirm `SELECT status, COUNT(*) FROM orders WHERE status IN ('pending','payment_initiated')` is empty before deploying, and schedule this phase for a low-traffic hour behind `php artisan down`.
  - Confirm queued `SendOrderCodesEmail` jobs written by old code still execute correctly after the deploy, then run `php artisan queue:restart` (R10 — workers hold old code for up to an hour).

### Task 5.4: Buyer inputs through cart and checkout

**Files:** Modify `app/Http/Controllers/CheckoutController.php`; create `app/Http/Requests/AddToCartRequest.php`, `resources/views/components/catalog/buyer-inputs.blade.php`; modify `resources/views/storefront/product.blade.php`

- [ ] **Step 1: Write the failing test**
  Adding a top-up without a required Player ID fails validation; two different Player IDs for the same SKU create two cart lines; an existing integer-keyed cart session still resolves (risk R3); `buyer_inputs` lands on the `OrderItem`; a buyer cannot inject an undeclared key.

- [ ] **Step 2: `AddToCartRequest`**
  Move the inline `$request->validate()` from `CheckoutController::addToCart()` (`app/Http/Controllers/CheckoutController.php:63-66`) into a form request, then merge in the dynamic rules from `BuyerInputSchema::rules()` for the card's category. Validation belongs in a form request, not a controller body — and this rule set is now dynamic, which makes the controller the wrong home for it twice over.

- [ ] **Step 3: Cart line keys — written for carts that already exist**
  `SESSION_DRIVER=database`, so carts created before this deploy are sitting in the `sessions` table and **will** be resolved by this code (R3). Treat a pre-existing cart as the normal case, not the edge case.

  Key stays the bare integer when there are no buyer inputs; becomes `"{$id}:{$hash}"` when there are. Change `resolveCartItems()` to read `$item['gift_card_id']` instead of the array key (`app/Http/Controllers/CheckoutController.php:296`), falling back to the key when that field is absent so a cart written by any older shape still resolves.

  Change the remove route param from `{giftCardId}` to `{lineKey}` with a `[\w:.-]+` constraint, and update the cart view's remove form.

  **Soften the all-or-nothing failure.** `resolveCartItems()` currently returns `null` for the whole cart if any single line is unresolvable, and the caller then empties it (`CheckoutController.php:56-60`). With more product types and a live cart crossing a deploy, that turns one stale line into a wiped basket. Drop the offending line, keep the rest, and flash which item was removed.

- [ ] **Step 3b: Verify against a real pre-deploy cart**
  On staging (Task 0.3 Step 5): add items to a cart, deploy this phase, then confirm the cart still resolves and checks out. A unit test with a hand-built session array does not prove this — the database session driver does.

- [ ] **Step 4: Product page block**
  Render `<x-catalog.buyer-inputs>` only when the schema is non-empty, per spec §4.4, plus a delivery chip showing `delivery_eta_label`. A gift card product page must render identically to today — diff the HTML to confirm.

- [ ] **Step 5: Verify** — tests green.

### Task 5.5: Admin fulfilment queue

**Files:** Create `app/Services/FulfilmentService.php`; modify `app/Filament/Resources/OrderResource.php`

- [ ] **Step 1: Write the failing test**
  Delivering an item stores the payload, stamps `delivered_at` and the admin id, flips the item to `delivered`, and flips the order to `paid` once no item is pending; the buyer's delivery e-mail goes out exactly once.

- [ ] **Step 2: `FulfilmentService`**
  `deliver(OrderItem $item, string $payload, User $admin): void` inside a transaction. All state changes go through this service — never by editing the model from a Filament action closure, or the order-level status roll-up gets skipped.

- [ ] **Step 3: `OrderResource`**
  An "Awaiting Fulfilment" tab filtered to `status = 'processing'`, a badge count in the navigation so the queue can't be missed, buyer inputs shown read-only on the order detail, and a per-item "Deliver" action taking the payload. Mask `delivered_payload` in table columns (risk R6).

- [ ] **Step 4: Verify** — tests green.

### Task 5.6: Buyer-facing delivery

**Files:** Modify `resources/views/storefront/order-detail.blade.php`, `resources/views/emails/order-codes.blade.php`, `resources/views/emails/order-codes-plain.blade.php`

- [ ] **Step 1: Write the failing test**
  A code-pool order renders codes as it does today; a credentials order renders the payload behind a reveal; a pending item renders its ETA and no payload.

- [ ] **Step 2: Implement**
  Add the two branches to the order detail page (spec §4.7). Replace the hardcoded `"Steam Wallet Code"` label at `resources/views/emails/order-codes.blade.php:57` with the item's own product name — it is wrong for every non-Steam product sold today, not just for the new verticals.

- [ ] **Step 3: Verify** — tests green.

---

# Phase 6 — Remaining Pages and E-mails

Every screen not rebuilt in Phase 4. **Layout and logic are not redesigned** —
these pages work; only surfaces, type and spacing change (redesign spec §3.7).

This phase is mechanical and parallelisable: each page is independent, and each
one removes an entry from the `DesignSystemTest` allow-list.

### Task 6.1: Checkout and order flow

**Files:** `checkout.blade.php` (64 inline styles), `checkout-success`, `checkout-pending`, `checkout-failed`, `order-detail`, `order-lookup`, `my-orders`

- [ ] **Step 1:** Re-theme to tokens. **Do not touch the payment-method selection logic, the referral/wallet discount UI, or the send-money transaction-ID field.** They are load-bearing and tested.
- [ ] **Step 2:** `order-detail` keeps the code/credential/awaiting-fulfilment branches from Task 5.6 if that has shipped; otherwise it keeps its current single branch and gains the others later.
- [ ] **Step 3: Verify** — place a real test order end-to-end on staging through both bKash-online and send-money paths.

### Task 6.2: Account and programme pages

**Files:** `referral-dashboard.blade.php` (52 inline styles), `reseller.blade.php` (46), `profile/*`, `auth/*`

- [ ] **Step 1:** Re-theme. The referral dashboard and reseller form carry the most inline styling of any remaining page; budget accordingly.
- [ ] **Step 2:** Auth pages use the `.auth-*` helpers in `storefront.css` rather than inline styles, so they re-theme by editing those helpers — much cheaper than the others.
- [ ] **Step 3: Verify** — referral code copy, wallet balance display and withdrawal request all still work.

### Task 6.3: Content pages

**Files:** `faq`, `how-to-redeem`, `contact`, `pages/about`, `pages/terms`, `pages/privacy-policy`, `pages/refund-policy`, `errors/404`

- [ ] **Step 1:** Re-theme. These are mostly prose; a shared `pages/layout.blade.php` already exists and does most of the work.
- [ ] **Step 2:** Widen FAQ and How-to-Redeem copy beyond gift cards, now that three other verticals are sold (this is Task 7.4's content work; do it here if the copy is ready).
- [ ] **Step 3: Verify** — contact form still submits and still throttles.

### Task 6.4: E-mail templates

**Files:** `emails/*.blade.php` (both HTML and plain-text variants)

- [ ] **Step 1:** Adopt the new accent, type scale and radius. **E-mails stay light-background** — dark-background HTML mail renders unpredictably across clients (redesign spec §3.7). This is the one deliberate exception to "dark throughout".
- [ ] **Step 2:** Keep every plain-text variant in sync. They exist for deliverability and are easy to forget.
- [ ] **Step 3: Verify** — send one of each to Gmail, Outlook and a phone client. Confirm the dynamic item label from Task 5.6 replaced the hardcoded "Steam Wallet Code".

### Task 6.5: Retire the old theme

**Files:** `resources/css/storefront.css`, `tailwind.storefront.config.js`

Only when the `DesignSystemTest` allow-list is empty.

- [ ] **Step 1:** Confirm the allow-list is empty — that is the gate for this task.
- [ ] **Step 2:** Delete the `--brand-*` variables, `.card-gradient`, `.hero-gradient`, `.surface`, `.surface-2`, `.btn-steam`, `.shadow-steam-glow`, `.grid-bg`, `.orb`, and the `brand-glow` shadows in the Tailwind config.
- [ ] **Step 3: Remove the navy `gray` override** in `tailwind.storefront.config.js`. This is deliberately last: every un-migrated page depended on it, so it can only go once none remain.
- [ ] **Step 4: Verify** — full suite green, every page visually checked, CSS bundle size recorded against the Phase 0 baseline.

---

# Phase 7 — Polish

Each task here is independently shippable.

### Task 7.1: Section filters and pagination
- [ ] Brand-level tags/filters on section pages once a section exceeds ~20 brands. Filters must be crawlable-safe: `rel="nofollow"` on filter links and a canonical back to the unfiltered section, or the facet combinations will bloat the index.

### Task 7.2: FAQ and How-to-Redeem per section
- [ ] `resources/views/storefront/faq.blade.php` and `how-to-redeem.blade.php` are gift-card-specific today. Add per-section content driven by the section's `seo_content` plus a per-brand `how_to_redeem`, which `MainCategory` already has. Do this in Task 6.3 instead if the copy is ready by then.

### Task 7.3: Analytics
- [ ] GA4 events for section views, search queries (with zero-result queries logged — the cheapest catalog-gap signal available), slider interaction, and buyer-input completion.

### Task 7.4: Favourites — needs a decision first
- [ ] The reference product page shows "Add to favourite". It needs a `favourites` table, authenticated-only behaviour, and a place in the account area. It is a retention feature with no direct revenue path (redesign spec §4.8). **Decide before building:** ship it, render it only for logged-in users, or drop it from v1. My recommendation is to drop it from v1 and revisit once the four verticals have traffic.

### Task 7.5: Performance pass
- [ ] Re-measure every page against the Phase 0 baseline. Convert catalog artwork to WebP with dimensions; audit the CSS bundle after the Phase 6.5 theme retirement; confirm the slider does not cause layout shift on slow connections.

*(Region chips and discount badges are no longer polish — they moved into Tasks 2.4, 2.5 and 3.4, because the redesign renders them on day one.)*

---

## Effort Estimate

| Phase | Scope | Estimate |
|---|---|---|
| 0 | Safety net, production snapshot, staging, runbook + visual baseline | 1.5–2 days |
| 1 | Section layer + backfill | 1 day |
| 2 | Admin: sections, fulfilment, regions, deals, banners, content, ratings | 3–4 days |
| 3 | Design system: tokens, primitives, slider, catalog components, guard | 3–4 days |
| 4 | Storefront rebuild: header, search, home, category, product, cart, SEO | 8–10 days |
| **— release —** | **New store live; Gift Cards + Software sellable** | |
| 5 | Fulfilment engine | 3–4 days |
| 6 | Remaining pages + e-mails re-themed, old theme retired | 4–5 days |
| 7 | Polish | 2–3 days |
| | **Total** | **26–33 days** |

### Why this roughly doubled

The catalog work alone was 12–16 days. The redesign adds 14–17, and it is not
padding. The driver is the number in the header of this plan: **459 inline
`style="..."` attributes and 293 hex literals**. There is no configuration change
that reaches them. Every storefront view is rebuilt by hand.

Phase 2 grew from 1–1.5 days to 3–4 because the reference UI needs data the
schema cannot express today — regions and regional siblings, deals, featured
flags, purchase limits, banners, content tabs, per-product ratings. Phase 4 is
the bulk of the work and is also the most parallelisable: after Task 4.1 ships
the header, the remaining pages are largely independent.

### Where to stop if you have to

**After Phase 4.** The store looks new, browses by four verticals, and sells gift
cards and software. Phases 5–7 are each individually valuable and individually
deferrable.

The one phase that must not be skipped to save time is **Phase 3**. Building
pages without the token system does not save three days; it moves them into
Phase 4 and multiplies them, and it recreates the exact problem — hardcoded
colour in markup — that makes this redesign cost what it costs.

Phase 0 grew by a day once the live-data constraints were accounted for. That day
buys a verified restorable backup, a staging environment that actually reproduces
production's session/queue/cache drivers, and a rehearsed rollback — on a site
that has been taking real money for six months, it is the cheapest day in the plan.
