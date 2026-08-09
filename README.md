# The Gayatri Decors

PHP + Bootstrap 5 storefront for premium home décor — 3D MDF wall clocks, metal
wall art, LED nameplates, tabletop frames and decorative mirrors.

## Two things live in this repo

| Path | What it is |
|---|---|
| `/` (php files, `includes/`, `admin/`, `customer/`) | The real application. Needs PHP 8 + MySQL *or* the bundled SQLite file. |
| `netlify-static/` | A pre-rendered **visual snapshot** for client previews. No backend. |

**Netlify cannot run PHP.** The Netlify site publishes `netlify-static/`, which
is a crawl of the running app. Product browsing, the size/LED selector, images
and all styling work; accounts, cart POSTs and checkout do not, and clicking
them shows a "visual demo" notice.

## Running the real app locally

```bash
php -S localhost:8000 -t .
```

`config/db.php` tries MySQL first (`gayatri_db`), then falls back to the bundled
`database.sqlite`. To start from scratch on MySQL, import `database.sql`.

Demo accounts:

- `admin@tgd.com` / `admin123`
- `customer@tgd.com` / `customer123`

## Product variants

Products on the client's price list have size + LED variants in
`product_variants` (size, `has_led`, price, stock). Products with no rows there
sell at the single `products.price`. Order lines record `variant_id` and a
human-readable `variant_label`, and stock is drawn from the variant.

## Images

Product photos are served responsively. `assets/images/products/responsive/`
holds WebP variants at 400/800/1200/1600 plus each source's native width;
`responsive_img()` in `includes/functions.php` emits the `srcset`/`sizes`.

To regenerate the static snapshot after changing the site, run the app locally
and re-run the build script in `tools/`.

## Configuration

Copy `.env.example` to `.env` and fill in. `.env` is gitignored — do not commit
real keys.
