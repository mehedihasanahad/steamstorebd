# Storefront Redesign — Design Spec

**Date:** 2026-09-19
**Status:** Proposed
**Companion spec:** `docs/superpowers/specs/2026-09-19-multi-vertical-catalog-design.md`
**Plan:** `docs/superpowers/plans/2026-09-19-multi-vertical-catalog.md`

A full visual and structural rebuild of the storefront: lightweight, premium, ashy
neutral surfaces in the G2A / SEAGM idiom, replacing the current navy-and-glow
theme. This spec covers the redesign; the companion spec covers the catalog
hierarchy and fulfilment engine the redesign renders.

---

## 1. The Constraint That Governs Everything

My earlier spec said the palette was fixed and would not change. That is now
reversed, and the cost of reversing it has to be stated plainly before anything
else, because it changes the shape of the whole project.

**The current theme is not configurable. It is hardcoded into the markup.**

| Measure | Count |
|---|---|
| Inline `style="..."` attributes across storefront views | **459** |
| Hardcoded navy/brand hex literals in Blade | **293** |
| `#071428` (navy surface) alone | 131 |
| `#2563EB` / `#1D4ED8` (brand blue) | 103 |
| Views with 40+ inline styles each | 5 (`home`, `checkout`, `referral-dashboard`, `reseller`, `product`) |

`resources/css/storefront.css` declares four CSS variables at `:root`
(`--brand-navy`, `--brand-blue`, …) but almost nothing uses them — the views
write the hex values directly. `tailwind.storefront.config.js` also overrides
Tailwind's `gray` ramp with navy-tinted values, so even `text-gray-400` in this
codebase renders as desaturated blue, not grey.

**Consequence:** the new ashy theme cannot be delivered by editing a config file
or swapping a palette. Every storefront view has to be rebuilt. There is no
cheap version of this request.

**Consequence for sequencing:** since the views are being rebuilt anyway, the
redesign must be **merged into** the catalog work, not sequenced after it.
Building the new section pages, mega-menu and product pages in the old navy theme
and then re-theming them later means building the same screens twice. The revised
plan therefore front-loads a design-system phase and rebuilds each page once, in
the new theme, with the new catalog structure already in it.

---

## 2. Design Direction

**Reference:** G2A, SEAGM, Gift Cards Zone BD (supplied screenshots).

The brief is "ashy background, simplicity, eye-cool, lightweight, premium". In
practice that means four things, and the first is the one the current site
violates most:

1. **Neutral, not tinted.** Surfaces are true greys with no hue. The current navy
   (#071428) reads as a colour; ash reads as a surface. Colour is then reserved
   for meaning — price, stock, discount, the primary action — and nothing else.
2. **Fewer effects.** The current theme leans on glows (`shadow-brand-glow`),
   gradients (`card-gradient`, `hero-gradient`), blurred orbs and a grid overlay.
   The reference sites use flat surfaces with 1px borders and a single soft
   shadow on hover. Every decorative gradient and orb is removed.
3. **Density with air.** Reference product grids are tight and information-dense —
   6 cards per row on desktop — but each card is calm: image, name, price, one
   badge. No card carries more than one accent colour.
4. **Typography does the hierarchy.** Weight and size separate levels, not boxes
   and borders.

### 2.1 Palette

Defined once as CSS custom properties, consumed through Tailwind tokens. **No
view may write a hex literal.** This is the rule that prevents the current
situation from recurring.

```
/* Surfaces — neutral ash, no hue */
--surface-0:  #0F0F11   /* page background        */
--surface-1:  #17171A   /* cards, header          */
--surface-2:  #1F1F23   /* raised: inputs, hovers */
--surface-3:  #2A2A30   /* borders, dividers      */

/* Text */
--text-hi:    #F4F4F5   /* headings, prices       */
--text-mid:   #A1A1AA   /* body                   */
--text-low:   #71717A   /* meta, captions         */

/* Accent — one only */
--accent:     #2563EB   /* retained from current brand */
--accent-hover:#3B82F6
--accent-soft: rgba(37,99,235,0.12)

/* Semantic */
--success:    #22C55E   /* in stock, discount      */
--warning:    #F59E0B   /* low stock               */
--danger:     #EF4444   /* out of stock, errors    */
```

The accent stays `#2563EB`. The brand is recognised by it, it already appears in
the logo and the favicon, and against neutral ash it reads cleaner than it ever
did against navy. Changing hue as well as surface would be a rebrand, which was
not asked for.

**Light surfaces are dropped.** The current site alternates dark hero sections
with white/`#F8FAFF` content bands. The reference sites are dark throughout.
One continuous ash ground is both simpler and cheaper to build.

### 2.2 Type, spacing, radius

- Inter stays (already loaded, already configured).
- Scale: 30 / 22 / 17 / 15 / 13 / 11 px. Headings 700–800, body 400–500,
  prices 700.
- 4px spacing grid. Section rhythm 56px desktop / 36px mobile — down from the
  current 80px, which is what makes the present homepage feel long.
- Radius: 10px cards, 8px controls, 6px chips. Down from the current 20px, which
  reads consumer-playful rather than premium.
- Shadows: exactly two — `--shadow-card` (resting) and `--shadow-hover`. Both
  neutral black at low alpha. **No coloured glows.**

---

## 3. Page Specifications

### 3.1 Header (all pages)

```
+---------------------------------------------------------------------------+
| [logo]   [ Search games, gift cards, software...    ]   CATALOG v         |
|                                                     Cart(2)  Sign in [Up] |
+---------------------------------------------------------------------------+
```

- **Logo** - left, compact, links home.
- **Global search** - centre, the widest element in the bar. Always visible on
  desktop; collapses to an icon that expands to a full-width overlay on mobile.
  It is the primary navigation device once the catalog spans four verticals.
- **Catalog** - a single trigger, not one per section. Hover (desktop) or tap
  (mobile) opens the mega-panel.
- **Cart** - icon plus a count badge (the existing `$cartCount` logic is reused
  verbatim).
- **Sign in / Sign up** - text link plus filled button when guest; the existing
  avatar dropdown when authenticated.

Height 60px, `--surface-1`, 1px bottom border in `--surface-3`, sticky. The
current backdrop-blur is kept; the glow shadow is dropped.

**Mega-panel** - three columns, modelled on the supplied Gift Cards Zone BD
screenshot:

```
+---------------------------------------------------------------------------+
| SECTIONS      |  BRANDS in section              |  REGIONS                |
| Gift Cards  > |  [icon] Free Fire    1 region   |  [BD] Bangladesh   12   |
| Games Top Up  |  [icon] PUBG         1 region   |  [US] United States 8   |
| Games Key     |  [icon] Roblox       1 region   |  [GL] Global        5   |
| Subscriptions |  [icon] Genshin      1 region   |                         |
| Software      |  [icon] Valorant     2 regions  |                         |
|               |  ... View all 14 ->             |                         |
+---------------------------------------------------------------------------+
```

Column 1 selects the section; column 2 lists that section's brands with a region
count; column 3 filters by region. Hovering column 1 swaps columns 2 and 3 with
no network request - the whole tree is delivered once from the cached
`StorefrontCatalog` (companion spec risk R5).

Accessibility is non-negotiable: `aria-expanded` on the trigger, `Esc` closes,
arrow keys traverse, focus is trapped while open, and every entry is a real
`<a href>` so the panel works without JavaScript.

### 3.2 Homepage

Order, per the brief:

```
1  HERO SLIDER          full-bleed carousel, neighbours peeking at the edges
2  BEST DEALS           horizontal slider, discount badges, struck prices
3  FEATURED ITEMS       horizontal slider, admin-curated
4  CATALOG SECTIONS     one vertical block per section, each its own slider
5  CUSTOMER REVIEWS     existing reviews, restyled
6  REFERRAL PROGRAM     only when site_setting('referral_enabled')
7  RESELLER PROGRAM     only when ResellerProgram::fromSettings()->enabled()
```

**1 - Hero slider.** Admin-managed slides (image, mobile image, link, sort,
active, optional start/end window). 16:5 desktop, 4:3 mobile. Autoplay 6s, pause
on hover and on focus, dot indicators, swipe on touch. Adjacent slides peek at
~8% either side, as in the SEAGM reference, which signals the carousel without
arrows. Respects `prefers-reduced-motion` by disabling autoplay.

**2 - Best deals.** Any active SKU with `compare_at_price_bdt > price_bdt`. Card
shows the discount badge (`21tk` / `-12%`), struck original, live price.

**3 - Featured items.** Products flagged `is_featured`, admin-ordered.

**4 - Catalog sections.** One block per active section, in `sort_order`:

```
GIFT CARDS                                                    View all ->
[ card ][ card ][ card ][ card ][ card ][ card ]   < >
```

Each block is its own slider, built from the same cached tree the mega-menu uses,
so the entire homepage catalog costs one cache read. Sections with nothing
visible are omitted, never rendered empty.

**5-7** keep their existing logic and gating exactly; only their styling changes.

**Critical constraint:** the existing `$fallbackCategories` path
(`app/Http/Controllers/StorefrontController.php:26-40`) must survive. It renders
the homepage when no sections are configured, and on a production database it is
the safety net if the backfill is ever incomplete.

### 3.3 Category / product-list page

Per the supplied reference - a two-column layout:

```
+-----------------+  +----------------------------------------------------+
| CATEGORIES      |  | Apple                      [ All v ]  [ Sort by v ] |
|  [icon] Free F 1|  |                                                     |
|  [icon] Garena 1|  |  +--------+  +--------+  +--------+                 |
|  [icon] Google 2|  |  | image  |  | image  |  | image  |   <- flag badge |
|  [icon] Hulu   1|  |  |   [IN] |  |   [UK] |  |   [US] |      top-right  |
|  [icon] Netflix2|  |  +--------+  +--------+  +--------+                 |
|  ... scrollable |  |  ITUNES (INDIA) ITUNES (UK)  ITUNES (USA)           |
+-----------------+  +----------------------------------------------------+
| FEATURED        |
|  [icon] iTunes  |
|  [icon] PS USA  |
+-----------------+
```

- Left rail: scrollable category list with per-category counts, collapsible
  groups, current item highlighted. Collapses above the grid into a horizontal
  chip row on mobile.
- Region filter (`All` + flag) and `Sort by` top-right.
- Grid cards: artwork, region flag badge top-right, name beneath. 3 columns
  desktop, 2 tablet, 2 mobile.
- Featured products panel beneath the rail.
- Breadcrumbs above the heading.

This layout serves both `/category/{section}` and `/brand/{brand}`; they differ
only in what the rail is scoped to and what the heading says.

### 3.4 Product detail page

Per the supplied reference, improved in three places:

```
+---------------------------------------------------------------------------+
| [icon]  Steam Wallet Code HKD                     [ Add to favourite ]    |
|         *****  4.8 (126)   Instant Delivery   [HK] Hong Kong  v           |
|         (i) Important note: ...                                           |
+---------------------------------------------------------------------------+
+-------------------------------------------+  +--------------------------+
| [ 40 HKD   697 TK ] [ 50 HKD   871 TK ]   |  | Quantity    [- 1 +]      |
| [ 80 HKD  1394 TK ] [100 HKD  1739 TK ]   |  | Purchase limit (1-2)     |
| [120 HKD  2176 TK ] [160 HKD  2894 TK ]   |  |--------------------------|
|                                           |  | Total           TK 697   |
| [ Description | Instructions | FAQ ]      |  |--------------------------|
|                                           |  | [cart]  [    BUY    ]    |
| ...long description...                    |  +--------------------------+
+-------------------------------------------+  | RELATED PRODUCTS         |
                                               | [icon] Steam USA         |
                                               | [icon] Steam Turkey      |
                                               +--------------------------+
```

- **Region selector** in the hero switches between *sibling products* - Steam
  Wallet HKD / USA / Turkey are separate `GiftCardCategory` rows linked by a
  shared `region_group`. This is the one structural addition the reference
  demands (see section 4.4).
- **Denomination tiles** in a 2-column grid; out-of-stock tiles are dimmed and
  badged `STOCK OUT` but remain visible, which is what the reference does and
  what avoids a product page that looks empty.
- **Sticky purchase panel** on desktop; on mobile it becomes a fixed bottom bar
  showing total and Buy - the single biggest conversion improvement available on
  a mobile-dominant store.
- **Tabs** - Description / Instructions / FAQ.
- **Three improvements on the reference:** a real rating summary rather than
  `0/5` on an unrated product (hidden entirely when there are no reviews); a
  stock-aware delivery chip (`Instant` vs the SKU's `delivery_eta_label`); and
  the existing WhatsApp/Messenger chat buttons retained below the purchase panel,
  where they already convert.
- The existing buyer-input block (companion spec section 4.4) renders above the
  purchase panel when the product declares one.

### 3.5 Cart

Per the supplied reference:

```
Shopping Cart                                        3 item(s) selected
+-------------------------------------------+  +--------------------------+
| TNG Reload Pin (MY)                       |  | Total        US$ 51.88   |
|  [x] [img] TNG MYR 150  38.57  [- 1 +]  x |  | Discount     - US$ 0.46  |
|  [x] [img] TNG MYR 10    2.57  [- 1 +]  x |  |                          |
+-------------------------------------------+  | [     CHECKOUT      ]    |
| Steam Wallet Code (USD)                   |  +--------------------------+
|  [x] [img] Steam 5 USD   5.37  [- 2 +]  x |
|      Discount 4.0%       5.60 struck      |
+-------------------------------------------+
```

- Lines **grouped by product**, with the product name as the group header.
- **Per-line checkboxes** with an "N item(s) selected" count; only selected lines
  are totalled and checked out.
- Quantity steppers reusing the existing `cart.update-quantity` endpoint.
- Per-line discount shown against a struck original.
- Sticky totals panel; on mobile it becomes a fixed bottom bar.

Selection state lives in the session cart line as a `selected` boolean,
defaulting to `true` so an existing cart behaves exactly as it does today
(companion spec R3 - carts survive deployment).

### 3.6 Footer

Content and links unchanged. Re-coloured to the ash palette, decorative gradients
removed, payment-method logos kept.

### 3.7 Everything else

Checkout, order confirmation, order detail, order lookup, referral dashboard,
reseller, FAQ, how-to-redeem, contact, policy pages, auth pages and both e-mail
template families are re-themed to the new tokens. **Layout and logic are not
redesigned** - these screens work; only their surfaces, type and spacing change.

E-mails are the exception to "dark throughout": they stay light-background, since
dark-background HTML e-mail renders unpredictably across clients. They adopt the
new accent, type scale and radius only.

---

## 4. Data the Redesign Requires

The reference UI asks for things the current schema cannot express. Each addition
below follows the companion spec's AD-8 rule: **expand-only, nullable or
defaulted, so every existing row keeps working untouched.**

### 4.1 Hero slider - new `banners` table

`id`, `title`, `image`, `mobile_image`, `link_url` (nullable), `sort_order`,
`is_active`, `starts_at` / `ends_at` (both nullable), timestamps.

A nullable date window lets a campaign be scheduled and expire on its own rather
than needing someone to remember to switch it off.

### 4.2 Deals and featured - new columns

- `gift_cards.compare_at_price_bdt` (decimal, nullable) - the struck price.
  Purely presentational. **It must not touch `buy_price_bdt`**, which drives the
  existing margin reporting in `GiftCardResource`.
- `gift_card_categories.is_featured` (bool, default false)
- `gift_card_categories.featured_sort` (int, default 0)

A deal is derived, not flagged: any active SKU where
`compare_at_price_bdt > price_bdt`. One source of truth, no flag to forget.

### 4.3 Purchase limits - new columns

`gift_cards.min_quantity` (default 1) and `max_quantity` (default 10).

The reference shows `Purchase Limit (1 - 2)`. Today the cap is the hardcoded
`'max:10'` in `CheckoutController::addToCart()` (line 65) and
`updateQuantity()` (line 95). Defaulting `max_quantity` to 10 reproduces today's
behaviour exactly on every existing row.

### 4.4 Regions - the one structural addition

- `gift_card_categories.region` (nullable, ISO-3166 alpha-2, or `GL` for global)
- `gift_card_categories.region_group` (nullable string)

`region_group` is what makes the hero region selector work: Steam Wallet HKD,
USA and Turkey are three separate products sharing `region_group = 'steam-wallet'`,
so each can list the others. Products with a null `region_group` show no selector
and behave exactly as they do today.

This supersedes the `gift_cards.region` column proposed in the companion spec
Task 2.3. Region is a property of the product, not of the denomination - a
$10 and a $50 Steam HKD card are both Hong Kong. Task 2.3 is amended accordingly.

### 4.5 Product content tabs - new columns

`gift_card_categories.instructions` (text, nullable) and `faq` (json, nullable).

`long_description` already exists and becomes the Description tab. Brand-level
`how_to_redeem` on `MainCategory` stays as the fallback for Instructions, so
products that do not set their own inherit what is already written.

### 4.6 Per-product ratings - new column on `reviews`

`reviews.gift_card_category_id` (nullable FK).

`reviews` today links to `user` and `order` only, so a rating cannot be attributed
to a product. Nullable means every existing review stays valid and keeps
appearing in the homepage testimonial block exactly as now; only new reviews
carry a product. The product page hides its rating block entirely until a product
has ratings, rather than showing the reference's `0/5`, which advertises absence.

### 4.7 Cart line selection

No migration. A `selected` boolean on the session cart line, defaulting to `true`
when absent - which is what every pre-deploy cart will be.

### 4.8 Deliberately deferred: favourites

"Add to favourite" needs a `favourites` table, authenticated-only behaviour, and
a place in the account area. It is a retention feature with no revenue path, and
it is the easiest thing in this spec to add later. **Recommendation: render the
control only when logged in, or omit it from v1.** Flagged for a decision.

---

## 5. Build Strategy

### 5.1 Token-first, then components, then pages

1. **Tokens** - palette, type scale, spacing, radius, shadow as CSS custom
   properties in `storefront.css`, mirrored into `tailwind.storefront.config.js`.
2. **Primitives** - button, input, card, badge, chip, tabs, slider, modal,
   skeleton. Blade components, each themed only through tokens.
3. **Catalog components** - product card, brand card, denomination tile,
   price block, region chip, rating stars, section rail.
4. **Pages** - assembled from the above. A page that needs a new colour is a
   signal the token set is wrong, not a licence to write a hex literal.

### 5.2 The rule that prevents regression

**No hex literal and no `style="..."` in any rebuilt view.** This is enforced,
not merely intended: a Pest architecture test greps the rebuilt views for
`style="` and `#[0-9a-fA-F]{6}` and fails the build. Without that test the 459
inline styles will simply grow back.

Views are exempt until rebuilt, so the test starts with an allow-list of
not-yet-migrated files that shrinks to empty as the redesign lands. The build
stays green throughout and the remaining work is always visible.

### 5.3 Old and new theme coexist during the rebuild

`storefront.css` carries both token sets during the transition. A rebuilt page
uses new tokens; an untouched page keeps the old ones. Nothing forces a big-bang
switch, and each page ships when it is ready.

The one thing that cannot be half-done is the **header and footer**, since they
appear on every page. They ship first, and they are built so the old navy pages
still look deliberate beneath a new ash header - which is why the accent colour
is retained rather than changed.

### 5.4 Performance

The current homepage carries 90 inline styles and several blurred orbs and
gradient overlays. Blur filters are among the most expensive things a mobile GPU
can be asked to do, and this store is mobile-dominant.

Targets for the rebuild:
- Lighthouse mobile performance **at or above** the Phase 0 baseline on every
  rebuilt page. Not "no worse" - the removal of the orbs and gradients should
  make it better.
- Sliders are CSS scroll-snap with a small Alpine controller. No carousel
  library, no jQuery.
- Every catalog image gets explicit `width`/`height` (CLS), `loading="lazy"`
  below the fold, and the hero's first slide preloaded.
- The full catalog tree for the header and homepage is one cached read.

---

## 6. Scope Reality

This is not an extension of the catalog plan; it is a second project of
comparable size running through the same files.

| | Catalog work (companion spec) | Redesign (this spec) |
|---|---|---|
| Nature | Schema, admin, fulfilment engine | Every storefront view rebuilt |
| Files | ~20 PHP, 6 migrations | ~35 Blade, CSS, Tailwind config |
| Risk | Order path, production backfill | Visual regression, mobile layout |
| Can ship partially | Yes, by phase | Yes, by page |

**The two must be merged, not sequenced.** Building the new section pages,
mega-menu and product page in the navy theme and re-theming them afterwards means
building the same five screens twice. The revised plan therefore inserts a design
system phase before any storefront page is built, and each page is then built once.

**Revised estimate: 26-34 days**, against 12-16 for the catalog work alone.

The release checkpoint moves. It is no longer "after Phase 3" but **after the
redesigned homepage, category and product pages ship with the section layer** -
at which point the store looks new, browses by vertical, and sells gift cards and
software. Subscriptions and top-ups follow with the fulfilment engine.
