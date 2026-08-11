# The Gayatri Decors

PHP + Bootstrap 5 storefront for premium home décor — 3D MDF wall clocks, metal
wall art, LED nameplates, tabletop frames and decorative mirrors.

## Repo layout

| Path | What it is |
|---|---|
| `/` (php files, `includes/`, `admin/`, `customer/`) | The real application. PHP 8 + MySQL *or* the bundled SQLite file. |
| `netlify-static/` | Pre-rendered **visual snapshot** for static hosts. No backend. |
| `tools/` | Scripts that regenerate the snapshot. |

## Running it locally

```bash
php -S 127.0.0.1:8000 -t . router.php
```

**Always pass `router.php`.** PHP's built-in server ignores `.htaccess`, so
without it the server will serve `/.env`, `/database.sqlite` and `/config/*.php`
as plain text — which leaks the Razorpay secret to anyone who asks, especially
if you tunnel the site to the public. `router.php` reproduces the `.htaccess`
deny rules for the dev server.

`config/db.php` tries MySQL (`gayatri_db`) first, then falls back to the bundled
`database.sqlite`. For a fresh MySQL setup, import `database.sql`.

Requires the `pdo_sqlite` (or `pdo_mysql`) and `mbstring` extensions.

Demo accounts:

- `admin@tgd.com` / `admin123` → admin console
- `customer@tgd.com` / `customer123` → customer area

> The admin password is intentionally **not** printed on `login.php`, because
> that page is publicly reachable when the site is shared for review.

## Product variants (size / LED)

Products on the client's price list have rows in `product_variants`:

| column | meaning |
|---|---|
| `size_label` | e.g. `13x13`, `24x24` (inches) |
| `has_led` | 0/1 — also the radio value the product page submits |
| `option_group` | `Lighting`, or **NULL** when the product varies by size only |
| `option_label` | `With LED` / `Without LED`, NULL for size-only |
| `price`, `stock` | per variant |

A product with **no** rows here sells at its single `products.price`.

Three shapes exist today:

- **size + lighting** — acrylic nameplates (round and rectangle)
- **size only** — MDF wall clocks, Sri Yantra tabletop (`option_group` NULL)
- **size, lighting fixed on** — mandala wall art, where the price list gives
  only "with" rows

The cart is keyed on **product + variant**, so one product can sit in the cart
at several size/LED combinations. Order lines record `variant_id` and a
human-readable `variant_label`, and stock is drawn from the variant with a
`WHERE stock >= ?` guard so concurrent orders cannot oversell.

**There is no admin UI for variants yet** — they are DB-only. That is the most
obvious next piece of work.

## Images

`assets/images/products/responsive/` holds WebP variants at 400/800/1200/1600
plus each source's native width. `responsive_img()` in `includes/functions.php`
emits the `srcset`/`sizes`; the `SIZES_*` constants there mirror the Bootstrap
grid slot in each view, so a phone pulls ~27KB per card instead of 500–900KB.

Regenerate variants after adding photos with the same Pillow-based approach used
in `tools/` (see git history for `build_images.py`).

## Regenerating the static snapshot

Netlify and Cloudflare Pages/Workers cannot run PHP. `netlify-static/` is a
crawl of the running app — product browsing, images and the size/LED selector
work; cart, login and checkout show a "visual demo" notice.

```bash
php -S 127.0.0.1:8000 -t .     # in one terminal
python tools/build-static.py   # crawl -> netlify-static/
python tools/shrink-static.py  # recompress fallback images (~51MB -> ~27MB)
```

`build-static.py` wipes its output directory and re-emits `_headers`,
`_redirects` and `404.html`, so do not hand-edit files in there.

### Sharing a live, working demo

A static snapshot cannot demo checkout. For that, run the app locally and expose
it with a free Cloudflare quick tunnel:

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

That serves the real PHP app on a public `*.trycloudflare.com` URL. The URL
changes on every restart and dies when the machine sleeps.

## Payments (Razorpay Standard Checkout)

No Composer in this project, so there is no `razorpay/razorpay` SDK — the REST
API is called directly with cURL, and signatures are verified with
`hash_hmac`, which is exactly what the SDK does internally.

| File | Role |
|---|---|
| `config/env.php` | reads `.env`, exposes `razorpay_key_id()` / `razorpay_key_secret()` / `razorpay_api()` |
| `api/create-order.php` | `POST` → creates a Razorpay order for the current cart |
| `api/verify-payment.php` | `POST` → verifies the signature, then writes the order |
| `checkout.php` | renders the Razorpay button and drives the modal |

Flow: checkout posts the shipping block to `create-order.php`, which computes
the amount **from the cart on the server** and returns an `order_id`. The
Razorpay modal opens, and on success the three returned fields are posted to
`verify-payment.php`.

Verification is deliberately strict — it refuses unless **all** hold:

1. the signature `HMAC-SHA256(order_id|payment_id, KEY_SECRET)` matches;
2. the `order_id` is the one this session started (blocks replaying a valid
   signature from a cheaper order);
3. Razorpay itself confirms the payment exists, is captured/authorized,
   belongs to that order, and is for the exact expected amount.

If Razorpay is unreachable at step 3 the order is written as **pending**, not
paid — an unconfirmed payment is never assumed good. If the payment succeeded
but the order write failed, the payment ID is written to the error log with a
`PAID BUT ORDER FAILED` marker and shown to the customer.

COD and Razorpay both go through `place_cart_order()` so the two paths cannot
drift apart. `place-order.php` refuses any method other than COD, so the
gateway cannot be bypassed by posting a different `payment_method`.

Test cards: <https://razorpay.com/docs/payments/payments/test-card-details/>
(e.g. `4111 1111 1111 1111`, any future expiry, any CVV).

Orders carry `payment_id` and `payment_order_id` for reconciliation; both are
NULL for COD.

## Configuration

Copy `.env.example` to `.env`. **`.env` is gitignored — never commit real keys.**

```
RAZORPAY_KEY_ID=rzp_test_xxxxxxxxxxxx
RAZORPAY_KEY_SECRET=xxxxxxxxxxxxxxxx
```

`RAZORPAY_KEY_SECRET` is server-side only and never reaches the browser — only
`key_id` is sent to the client. Real environment variables take precedence over
the file, so a host that sets them properly needs no `.env` on disk. Online
payment hides itself automatically when either key is missing.

`.htaccess` denies `.env`, `*.sqlite` and `*.sql` over HTTP. **If you deploy on
nginx you must replicate those rules** — they are Apache-only.

---

## Open items

Things deliberately left undone, with the reason:

1. **Four SS nameplates have no variants** — Modern Stainless Steel, Nidhivan
   Golden, Ganesh LED Backlit (Gold), Krishna LED Acrylic. The price list has
   three different stainless ladders (Silver ₹2,399/3,199/4,599 vs Gold and
   Rosegold ₹3,599/4,499/6,799) and nothing identifies which finish each
   product is. Two are also named "LED" while the SS tables have no LED column.
   **Needs the client to confirm the finish before pricing them.**

2. **Four products share one placeholder image** (gold metal leaves):
   Modern SS Nameplate, Geometric Metal Leaf Wall Art, Tree of Life Acrylic
   Wall Panel, Abstract Sunset Canvas Set.

3. **Ten products have no photos yet** — 5 nameplates and 5 tabletop frames.
   Their current images are low-res stitched collages (~320px). Waiting on
   source material from the client.

4. **Not on the price list:** Lunara, Verdelune, Sunburst Mirror, Round Wooden
   Nameplate with Planter, Geometric Metal Leaf.

5. **Unconfirmed matches:** Tree of Life ← "Vriksha Vaibhav" (*vriksha* = tree),
   Abstract Sunset Canvas ← "Canvas Photo frames". Note the canvas table's
   with/without means **frame**, not LED.

6. **Client's list has an error** — "Vriksha Vaibhav" prices are inverted:
   without-LED costs more than with-LED, unlike every other product.

7. **Base prices not applied** — tabletop flat ₹2,000, canvas frames, and the
   launch discounts (30% MDF wall art, 10–15% clocks/frames for 3 months) are
   in the client's list but were explicitly out of scope.

### Known issues worth fixing

- No CSRF tokens on any POST (cart, checkout, register, reviews, admin CRUD).
- Open redirect: `login.php?redirect=` goes straight into `header("Location:")`.
- `database.sqlite` is web-servable — block it at the web server in production.
- `?v=<?= time() ?>` on CSS/JS busts cache on every request, defeating the
  1-year `Expires` headers in `.htaccess`.
- `sanitize()` runs on **input** in register/place-order, storing HTML entities
  in the DB, then again on output — `O'Brien` renders as `O&#039;Brien`.
- No pagination on `shop.php`.
