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
php -S 127.0.0.1:8000 -t .
```

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

## Configuration

Copy `.env.example` to `.env`. **`.env` is gitignored — never commit real keys.**

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
