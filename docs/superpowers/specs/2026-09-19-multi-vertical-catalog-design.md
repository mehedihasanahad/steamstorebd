# Multi-Vertical Catalog — Design Spec

**Date:** 2026-09-19
**Status:** Proposed
**Plan:** `docs/superpowers/plans/2026-09-19-multi-vertical-catalog.md`

Turning Steam Store BD from a gift-card-only shop into a multi-vertical digital
goods store (gift cards, software, subscriptions, game top-ups) without changing
a single existing URL, model name, or payment path.

---

## 1. Requirements Analysis

### 1.1 What was asked

> Currently my website only sells gift cards. Now I want to sell gaming software,
> subscriptions etc. So I will add a top layer category. The current cards will be
> under a "Gift Cards" category. Categories already exist, but a parent category
> will be added; the current ones shift under the Gift Cards parent.

Constraints given: don't break existing logic, don't break coding principles, the
UI must be cool and user friendly.

### 1.2 What the code actually is today

The catalog is **already three levels deep**, not two:

| Level | Model | Table | Example | Public URL |
|---|---|---|---|---|
| 1 | `MainCategory` | `main_categories` | Steam | `/brand/steam` |
| 2 | `GiftCardCategory` | `gift_card_categories` | Steam Wallet | `/product/steam-wallet` |
| 3 | `GiftCard` | `gift_cards` | Steam Wallet $10 | (redirects to level 2) |
| — | `GiftCardCode` | `gift_card_codes` | the actual code | inventory only |

`MainCategory` is labelled "Brands / Main Categories" in Filament and its own
model code names the variable `$brand` (`app/Models/MainCategory.php:39`). So the
"top layer category" being asked for is a **fourth** level above brands, not a
second level.

The request therefore resolves to:

```
Gift Cards (new)   →  Steam (exists)  →  Steam Wallet (exists)  →  $10 (exists)
Software (new)     →  Microsoft       →  Windows 11 Pro         →  1 PC Retail
Subscriptions(new) →  Netflix         →  Netflix Premium        →  1 Month
Game Top-Up (new)  →  PUBG Mobile     →  PUBG UC                →  660 UC
```

### 1.3 The requirement that was not asked for but blocks launch

**Every order today requires pre-stocked codes.** `OrderService::createOrder()`
(`app/Services/OrderService.php:64-82`) reserves `GiftCardCode` rows and throws
`RuntimeException` when there aren't enough. `CheckoutController::resolveCartItems()`
refuses any line whose `stock_count` is short, and `GiftCard::getStockCountAttribute()`
derives that number by counting available codes.

That is correct for gift cards and for software keys. It is wrong for the other
two verticals:

| Vertical | Delivered as | Fits today's engine? |
|---|---|---|
| Gift cards | one code per unit from a pool | Yes |
| Software / game keys | one licence key per unit from a pool | Yes — same mechanism |
| Subscriptions | account credentials, or activation on the buyer's own account | **No** |
| Game top-up | credited to a Player ID the buyer supplies | **No** |

Selling top-ups or subscriptions on the current engine means either faking code
rows or bypassing `OrderService`. Both break invariants the codebase relies on.
So the fulfilment engine has to grow a second mode, and the storefront has to be
able to collect buyer input (Player ID, Zone ID, account email) before checkout.

This is in scope. It is the difference between "the categories exist" and "you can
actually sell the thing".

### 1.4 Secondary gaps found

- **No catalog navigation exists.** The header
  (`resources/views/layouts/storefront.blade.php:137-140`) is Home / FAQ / Contact.
  Brands are reachable only from the homepage grid and the footer. Survivable for
  one vertical, unusable for four.
- **No search.** No route, no controller, no UI. With four verticals the catalog
  stops being browsable by scrolling.
- **The cart is keyed by gift card ID.** `$cart[$giftCard->id] = [...]`
  (`app/Http/Controllers/CheckoutController.php:77`). Two top-ups of the same SKU
  for two different Player IDs would silently overwrite each other.
- **Delivery copy is hardcoded.** `resources/views/emails/order-codes.blade.php:57`
  prints the literal label "Steam Wallet Code" for every item of every order.
- **Homepage assumes one vertical.** Hero copy, the `#brands` section and the
  `ItemList` JSON-LD all flatten to a single brand grid.

---

## 2. Market Analysis

Six Bangladeshi competitors and three international marketplaces were reviewed.

### 2.1 Local direct competitors

| Site | Top-level sections | Hierarchy | URL shape |
|---|---|---|---|
| [Gift Cards Zone BD](https://giftcardszonebd.com/) | Gift Cards, Games Top Up, Games Key, Subscriptions, Utility | section → brand → denomination | `/card`, `/topup`, `/subscriptions` |
| [Siwcart](https://siwcart.com/) | Game, Direct Top-Up, Card, In-App Top-Up | section → product | `/cat/{slug}`, `/buy/{slug}` |
| [Premium Bangladesh](https://premiumbangladesh.com/) | Education Tools, Game Top Up, Gift Card, Licence Key, Music, Streaming, Subscriptions, VPN | 8 flat sections | `/{product}/` |
| [MOXBD](https://moxbd.com/) | Games, Subscriptions, Gift Cards, Software | section → product | — |

The consensus shape is unambiguous: **4–6 top-level sections split by product
type, then brand, then variant.** Gift Cards Zone BD is the closest match to the
target and its hierarchy maps 1:1 onto what already exists here.

Details worth copying:

- Gift Cards Zone BD's homepage runs one horizontal rail **per section**
  (Games Top Up → Gift Cards → Games Key → Subscriptions) beneath four large
  section tiles. Browsing by vertical is the primary axis.
- Its category pages carry **filter + sort** controls and pagination — necessary
  once a section holds 30+ brands.
- Product cards carry a **region flag** (BD / US / Global). Region is a real
  purchasing decision for gift cards and a frequent support ticket.
- Its cards show a **discount badge** with a struck-through original price.

### 2.2 International marketplaces

Eneba, G2A and Kinguin all run the same four verticals — keys, gift cards,
subscriptions, top-ups — under a persistent mega-menu, and all three lead product
cards with price, platform/region, and delivery speed.

### 2.3 SEO architecture consensus

Three to five hierarchy levels is the accepted range, with **three levels below
the root** the common optimum (home → L1 → L2 → L3 → product). Hierarchy-based
breadcrumbs backed by `BreadcrumbList` schema are standard, and URLs should mirror
the breadcrumb hierarchy conceptually without necessarily nesting the slugs.
Baymard's finding that 36% of e-commerce sites drop the full category path on
mobile is a trap worth avoiding here, since this store is mobile-dominant.

The existing `/brand/{slug}` and `/product/{slug}` flat slugs already satisfy this
— they are conceptually hierarchical without being physically nested, which is why
adding a level above them costs nothing in URL churn.

### 2.4 What this store already does better than the local field

Worth protecting, not rebuilding: real `Product` / `Offer` / `BreadcrumbList`
JSON-LD, `SlugRedirect` 301 handling on slug change, a sitemap that refuses to
advertise hidden products, per-product WhatsApp/Messenger deep links, a referral +
wallet system, and admin-side order editing with code revocation. None of the
local competitors surfaced showed comparable SEO or post-sale tooling.

---

## 3. Architecture Decisions

### AD-1 — New `catalog_sections` table, not a self-referencing parent

**Decision:** Add a dedicated `catalog_sections` table and a nullable
`main_categories.catalog_section_id` FK.

**Rejected alternative:** `main_categories.parent_id` self-reference.

**Why:** A section and a brand are different things with different fields. A brand
owns `how_to_redeem` and a portrait cover image; a section owns a tagline, an
accent colour and a nav icon. Merging them forces every query to filter on
`whereNull('parent_id')`, makes the Filament resource ambiguous ("is this row a
section or a brand?"), and turns `StorefrontCatalog::brands()` into an adjacency
walk. A separate table keeps `MainCategory` meaning exactly what it means today,
which is the cheapest way to honour "don't break existing logic".

**Cost:** One more table and one more Filament resource. Accepted.

### AD-2 — Additive URLs only; no existing URL changes

**Decision:** `/brand/{slug}` and `/product/{slug}` keep their exact shapes. The
only new public catalog route is `/category/{sectionSlug}`.

**Why:** SEO work landed on this repo in September 2026 and those pages carry the
rankings. Re-nesting them to `/gift-cards/steam/steam-wallet` would 301 every
ranked URL for zero user benefit. `/category/` was chosen over `/shop/` because
`/shop` and `/shop/{any}` are already bound to the legacy redirect handler
(`routes/web.php:67-68`), and over a bare `/{section}` because that would collide
with `/faq`, `/about`, `/terms` and every future static page.

### AD-3 — Do not rename `GiftCard*` to `Product*` in this phase

**Decision:** Tables, models, columns, relations and the `gift_card_id` foreign
keys keep their names. Renaming is confined to **user-facing labels only**.

**Why:** A true rename touches 18 models, 10 Filament resources, every Blade view,
the session cart shape, 20 test files, and both e-mail templates — a very large
diff whose entire payoff is internal readability. It is the single highest-risk,
lowest-value change available here and it directly contradicts "don't break any
existing logic". The naming discomfort is documented instead and left as an
optional later phase, behind a green test suite.

**Mitigation:** Add `GiftCard::FULFILMENT_*` constants and a `deliveryLabel()`
accessor so new code reads in domain terms even while the table is still called
`gift_cards`.

### AD-4 — Fulfilment mode lives on the SKU; buyer-input schema lives on the product

**Decision:**
- `gift_cards.fulfilment_type` — `code_pool` (default) | `manual` | `credentials`
- `gift_cards.delivery_eta_label` — free text, e.g. "Instant" / "5–30 minutes"
- `gift_card_categories.buyer_input_fields` — JSON schema of fields to collect

**Why:** `OrderService` branches on the SKU, because the SKU is what an `OrderItem`
points at — so fulfilment mode has to be readable from the SKU without a join. The
buyer-input **form** is rendered once on the product page and is identical across
that product's variants ("PUBG UC" asks for a Player ID whether you buy 60 UC or
8100 UC), so it belongs on the product.

**Compatibility:** `fulfilment_type` defaults to `code_pool` and
`buyer_input_fields` defaults to `null`, so every existing row keeps today's
behaviour byte-for-byte.

### AD-5 — A dedicated `manual_stock` column, **not** a reuse of `stock_count`

**Decision:** Add `gift_cards.manual_stock` (integer, default 0).
`GiftCard::getStockCountAttribute()` returns `manual_stock` when
`fulfilment_type !== 'code_pool'`, and keeps its current code-counting behaviour
otherwise. `gift_cards.stock_count` is not touched by any new code.

**Rejected alternative:** reusing the existing `stock_count` column. It is
superficially elegant — the column exists, and `OrderService` already
decrements and increments it — but it is unsafe against **six months of
production data**, for three compounding reasons:

1. `stock_count` is a *shadowed* column. `getStockCountAttribute()` overrides it
   on read for every gift card, so its stored value has never had to be correct.
   `OrderEditService::syncStockCounts()` (`app/Services/OrderEditService.php:409-419`)
   exists precisely because it drifts, and it only resyncs the cards a given
   order edit happened to touch.
2. That means today's production table almost certainly holds stale values —
   possibly 0, possibly far above real stock — on rows nobody has edited recently.
3. Flipping an existing gift card to `manual` would therefore promote a stale,
   never-validated number to authoritative stock in one click. The failure mode is
   silent overselling of a product that cannot be auto-delivered, which is the
   worst combination available.

A new column starts empty on every existing row and has no legacy meaning, so
there is no value to be wrong. The cost is one integer column.

**Consequence to handle:** `GiftCard::scopeInStock()` and `StockAlertWidget`
(`app/Filament/Widgets/StockAlertWidget.php:18,24`) both query the raw
`stock_count` column and bypass the accessor. Once manual SKUs exist, those two
queries silently exclude them. Both must become fulfilment-aware — see Task 2.3.

**Concurrency:** for manual SKUs the decrement happens inside the **reservation**
transaction with `lockForUpdate()` on the `gift_cards` row, not at completion,
otherwise concurrent checkouts oversell. Code-pool SKUs are already protected by
the row lock on `gift_card_codes`.

### AD-6 — Cart lines keep integer keys unless buyer input is present

**Decision:** The session cart key stays the bare gift card ID when a line has no
buyer inputs. Lines with buyer inputs use `"{id}:{8-char hash of inputs}"`.
`resolveCartItems()` reads `$item['gift_card_id']` from the payload instead of
from the array key.

**Why:** It keeps every currently-open cart session valid, keeps the overwhelmingly
common path unchanged, and lets two Player IDs for the same SKU coexist as separate
lines. The payload already carries `gift_card_id`
(`app/Http/Controllers/CheckoutController.php:78`), so this is a one-line read
change, not a data migration.

### AD-7 — Reuse the dormant `processing` order status; encrypt the delivered payload

**Decision:** Use the **existing** `processing` order status for "paid, awaiting
manual fulfilment". Add no new status. To `order_items` add `fulfilment_status`,
`buyer_inputs` (JSON) and `delivered_payload` (text, `encrypted` cast).

**Why:** A paid top-up order is neither `paid`-and-done nor `pending_review` (which
means "we haven't confirmed your money yet"). It needs its own queue. But that
status already exists and is currently dead code — `processing` is in the enum
from the original migration (`2026_04_23_104644_01_create_orders_table.php:20`),
is already labelled in Filament (`app/Filament/Resources/OrderResource.php:178`),
and is already classified by `OrderEditService` as both editable
(`EDITABLE_STATUSES`) and code-delivering (`DELIVERED_STATUSES`). No code path
ever sets it.

Those two existing classifications are exactly right for a partially-fulfilled
order: its code-pool items really have reached the customer, and an admin really
should still be able to edit its lines. Reusing it means **no `orders` migration
at all** — and the `status` enum is MySQL-only DDL that has already had to be
rebuilt twice in this repo's history
(`2026_04_26_000001`, `2026_06_06_102023`). Avoiding a third rebuild is worth
more than a marginally more descriptive status name.

Account credentials are materially more sensitive than a spent gift card code and
must not sit in plaintext; Laravel's `encrypted` cast is the zero-ceremony way to
do that.

### AD-8 — Production-safe migration: expand only, never contract

**Context:** This site has been live for roughly six months. There is real order
history, real `gift_card_codes` inventory, real customer accounts with wallet
balances, and live sessions. Every decision below follows from that and from the
deployment topology the repo actually declares:

| Setting | Value | Consequence for this change |
|---|---|---|
| `SESSION_DRIVER` | `database` | Carts live in the `sessions` table and **survive deployment**. A cart added before the deploy is resolved by code after it. |
| `QUEUE_CONNECTION` | `database` | Jobs queued by old code are executed by whatever worker picks them up. |
| `CACHE_STORE` | `database` | `home_main_categories` (300 s) and `site_setting()` (600 s) **survive deployment** and will serve pre-deploy data after it. |
| `supervisor.conf` | `queue:work --max-time=3600` | Workers hold old code in memory for **up to an hour** unless `queue:restart` is issued. |
| CI / deploy script | none in repo | Deployment is manual, so the runbook has to be written down rather than encoded. |

**Decision — every migration in this change is expand-only.** Add columns and
tables; never drop, rename, re-type or narrow an existing one. No migration in
this plan rewrites a column another running process might be reading.

This is what makes old and new code safe to run concurrently during a deploy, and
it is why AD-3 (no renaming) and AD-2 (no URL changes) matter operationally as
well as strategically: a rename is a contract, and a contract cannot be deployed
without downtime.

**Corollaries:**

- **`migrate:fresh` is forbidden outside local and CI.** It drops every table.
  Verification steps in the plan use `migrate` against a restored production
  snapshot, never `migrate:fresh`.
- **Backfills are idempotent and query-builder-based.** They must survive being
  re-run, and must not depend on the current shape of an Eloquent model that a
  later commit may change.
- **`down()` never destroys admin-entered data.** Rolling back Task 1.2 drops the
  FK column but leaves the seeded "Gift Cards" section row in place.
- **Cache and queue are flushed as part of deployment**, not left to expire.
- **The rollback plan for every phase is `git revert` + `cache:clear`**, not
  `migrate:rollback`. Because the schema is expand-only, the previous release runs
  correctly against the new schema — the added columns are simply ignored. That
  makes reverting the *code* a complete and safe rollback, which is a much faster
  and less frightening operation at 2am than reversing a migration.

**Deployment order that follows from this:** migrate first, deploy code second,
flush cache and restart workers third. The new columns exist and are unused for
the few seconds before the new code lands, which is harmless; the reverse order
would have new code querying columns that do not yet exist.

---

## 4. UI / UX Design

Design language is fixed by `tailwind.storefront.config.js`: `brand.*` blue
(#2563EB), the dark navy `gray.*` ramp (#071428 / #0E1F35), `shadow-brand-glow`,
and the `float` / `pulse-slow` animations. Everything below extends that system;
no new palette is introduced.

### 4.1 Header — section mega-menu

Replaces the three-link nav. Desktop: each section is a trigger; hovering opens a
full-width panel listing that section's brands in a 4-column grid with icons, plus
a right-hand promo tile. Mobile: an accordion inside the existing `mobileOpen`
drawer, so the current Alpine state machine is reused rather than replaced.

```
+----------------------------------------------------------------------+
| [logo] Gift Cards v  Software v  Subscriptions v  Top-Up v  Q  Ahad v |
+----------------------------------------------------------------------+
        +======================================================+
        |  Steam        Google Play     App Store              |
        |  PlayStation  Xbox            Razer Gold             |
        |  ---------------------------------------------------- |
        |  View all 14 Gift Card brands ->    [ promo tile ]   |
        +======================================================+
```

Rules: keyboard accessible (`Esc` closes, arrow keys move, focus trap while open),
`aria-expanded` on triggers, sections read from the cached `StorefrontCatalog` so
the menu costs no extra queries, and the whole menu degrades to plain links with
JS off.

### 4.2 Search

New `/search?q=` route. A single input in the header expands into an overlay with
live results grouped by section as the user types (Alpine + `fetch`, 250 ms
debounce, minimum 2 characters). Results show icon, name, section badge and
"from ৳X". Empty state offers the four section tiles.

v1 is `LIKE` across `main_categories.name`, `gift_card_categories.name` and
`gift_cards.name` with indexes. Scout/FULLTEXT is a later upgrade and is not needed
below a few thousand rows.

### 4.3 Section landing page — `/category/{slug}`

```
Home > Gift Cards
+--------------------------------------------------------+
|  GIFT CARDS                                            |
|  Steam, Google Play, App Store and 11 more brands      |
|  - Instant delivery - 100% genuine - bKash & Nagad     |
+--------------------------------------------------------+
[ All ] [ Gaming ] [ Streaming ] [ Shopping ]   Sort: Popular v
+--------+ +--------+ +--------+ +--------+
| cover  | | cover  | | cover  | | cover  |
| Steam  | | Google | | Apple  | | Xbox   |
| from Tk| | from Tk| | from Tk| | from Tk|
| BD  ⚡ | | GLB ⚡ | | US  ⚡ | | GLB ⚡ |
+--------+ +--------+ +--------+ +--------+
```

Reuses the brand-card markup already in `resources/views/storefront/home.blade.php:275-299`
(portrait 1057×1488 covers, hover lift, glow). Adds: sort control, region chip,
delivery-speed chip, and `from ৳X` computed with `withMin()` — the same technique
`StorefrontController::product()` already uses for related categories
(`app/Http/Controllers/StorefrontController.php:104-112`), so no N+1 is introduced.

### 4.4 Product page — buyer input block

Rendered only when `buyer_input_fields` is non-empty; the page is otherwise
untouched, so gift cards look exactly as they do today.

```
+- Enter your account details ------------------------+
|  Player ID *        [ 5123456789          ]         |
|  Zone ID *          [ 1234                ]         |
|  (i) Find your Player ID in-game > Profile, top-left|
+-----------------------------------------------------+
   Delivery: 5-30 minutes - manual top-up
```

Validation is Alpine-side for instant feedback and re-validated server-side against
the stored schema in `AddToCartRequest` — the client rules are a convenience, never
the authority.

### 4.5 Homepage

Section tiles move above the fold; the brand grid becomes one rail per section.

```
HERO (existing, copy widened beyond gift cards)
+----------+----------+----------+----------+
| Gift     | Software | Subscri- | Game     |   <- new section tiles
| Cards    |          | ptions   | Top-Up   |
+----------+----------+----------+----------+
GIFT CARDS                            View all >
[ brand ][ brand ][ brand ][ brand ][ brand ] >    <- horizontal rail
SOFTWARE                              View all >
[ brand ][ brand ][ brand ][ brand ][ brand ] >
... existing How-it-works / Reviews / Payment / Referral sections unchanged
```

### 4.6 Breadcrumbs

Four levels everywhere, visible on mobile (no truncation to a single "Back"), with
`BreadcrumbList` schema extended from the existing builder at
`resources/views/storefront/product.blade.php:46-55`.

`Home > Gift Cards > Steam > Steam Wallet`

### 4.7 Account: My Digital Items

The current order detail page renders `orderItemCodes` only
(`resources/views/storefront/order-detail.blade.php:44-49`). It gains a second
branch for credential payloads (masked behind a "Reveal" button, copy-to-clipboard
per field) and a status strip for items still awaiting fulfilment, with the ETA
label and a support deep link.

---

## 5. Non-Goals

Explicitly out of scope:

- Renaming `GiftCard*` to `Product*` (see AD-3).
- Any change to bKash, send-money, referral, wallet, withdrawal or reseller logic.
- Recurring / auto-renewing subscription billing. Subscriptions are sold as
  fixed-duration one-off purchases, exactly as the local market sells them.
- Automated top-up APIs. The schema supports plugging one in per SKU later; v1
  fulfilment is manual through the admin queue.
- Encrypting `gift_card_codes.code` at rest. Worth doing, unrelated to this work,
  and a separate migration with its own backfill risk.
- Multi-currency display. Everything stays BDT.

---

## 6. Risk Register

| # | Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|---|
| R1 | Fulfilment branch regresses gift card checkout | Medium | Critical | Phase 0 characterisation tests written *before* touching `OrderService`; the `code_pool` path stays literally the same code, moved into a private method |
| R2 | Manual-stock oversell under concurrency | Medium | High | `lockForUpdate()` on the `gift_cards` row, decrement inside the reservation transaction, restore on fail/cancel/refund |
| R3 | Cart key change drops live carts | **High** | Medium | `SESSION_DRIVER=database`, so pre-deploy carts persist through the deploy and **will** hit new code. Integer keys preserved for input-less lines; `resolveCartItems()` reads the payload, not the key; a malformed line is dropped individually rather than voiding the whole cart |
| R4 | Section pages cannibalise brand-page rankings | Medium | Medium | Distinct titles/descriptions per section, canonical stays self-referencing, sections link down to brands and brands link up, no duplicated brand copy on section pages |
| R5 | Mega-menu adds queries to every page | Medium | Medium | Menu reads the existing `home_main_categories` cache; extend that key rather than adding a second cache |
| R6 | Credentials leak via logs or plaintext storage | Low | Critical | `encrypted` cast on `delivered_payload`, never logged, never in an e-mail subject, masked in Filament table columns |
| R7 | Scope sprawl across four verticals at once | High | Medium | Ship Gift Cards + Software first (both `code_pool`, no engine change needed), then Subscriptions + Top-Up once Phase 4 lands |
| **R8** | **Stale `stock_count` promoted to live truth** | **High if reused** | **Critical** | Dedicated `manual_stock` column (AD-5). No existing row carries a legacy value |
| **R9** | **Backfill corrupts or partially applies to live `main_categories`** | Low | Critical | Rehearsed against a restored production snapshot (Task 0.3); idempotent `firstOrCreate` + `WHERE catalog_section_id IS NULL`; verified row counts before/after; DB backup taken immediately prior |
| **R10** | **Queue workers run old code for up to 1 hour post-deploy** | **High** | Medium | `supervisor.conf` sets `--max-time=3600`. `php artisan queue:restart` is a mandatory deploy step; jobs stay backward-compatible within a release |
| **R11** | **Stale catalog served post-deploy** | **High** | Low | `CACHE_STORE=database`, so `home_main_categories` and `site_setting()` survive the deploy. `cache:clear` is a mandatory deploy step |
| **R12** | **In-flight orders straddle the Phase 4 deploy** | Medium | High | Orders in `pending` / `payment_initiated` hold reserved codes. Phase 4 changes only the *reservation* path; completion of an already-reserved order is untouched. Verified explicitly in Task 4.3 Step 5 |
| **R13** | **`APP_KEY` rotation renders `delivered_payload` unrecoverable** | Low | High | Encrypted-at-rest data is unrecoverable without the key. Document the dependency, confirm `APP_KEY` is in the backup set, and never rotate it without a re-encryption migration |
| **R14** | **Manual SKUs invisible to stock queries** | Medium | Medium | `scopeInStock()` and `StockAlertWidget` query the raw `stock_count` column and bypass the accessor; both are made fulfilment-aware in Task 2.3 |

---

## 7. Success Criteria

1. Every existing URL returns the same content it does today; zero 301s added to
   `/brand/*` or `/product/*`.
2. `php artisan test` green at every phase boundary.
3. An admin can create a section, attach brands, and publish a software product
   with no developer involvement.
4. A buyer can complete a top-up purchase supplying a Player ID, and the item
   appears in the admin fulfilment queue with that ID attached.
5. The header mega-menu and search add **zero** additional queries to a cached page
   load.
6. Lighthouse mobile performance on `/`, `/category/*` and `/product/*` does not
   drop relative to the pre-change baseline captured in Phase 0.
7. **No production data is lost or altered other than by the one intended
   backfill.** `main_categories` row count is identical before and after; order,
   code, wallet and user tables are untouched by every migration in this plan.
8. **A cart created before a deploy still checks out after it**, verified on
   staging with `SESSION_DRIVER=database`.
9. **Every phase is rehearsed against a restored production snapshot before it
   ships**, and the `git revert` rollback path is proven at least once.
