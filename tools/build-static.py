"""
Crawl the running PHP site and emit a static snapshot Netlify can publish.

Netlify serves no PHP, so every ".php?query=" URL becomes a clean directory:
    /index.php                     -> /index.html
    /shop.php                      -> /shop/index.html
    /shop.php?category=wall-clock  -> /shop/category/wall-clock/index.html
    /product.php?slug=ember-dark   -> /product/ember-dark/index.html
Only images the database actually references are copied; the 43 orphaned
files stay out of the repo.
"""
import os, re, shutil, sqlite3, urllib.request, urllib.parse

SRC   = r"e:\ANTIGRAVITY\client2\gayatri"
OUT   = os.path.join(SRC, "netlify-static")
BASE  = "http://localhost:8000"
LOCAL = BASE + "/"

db = sqlite3.connect(os.path.join(SRC, "database.sqlite"))
db.row_factory = sqlite3.Row
slugs = [r["slug"] for r in db.execute("SELECT slug FROM products ORDER BY id")]
cats  = [r["slug"] for r in db.execute("SELECT slug FROM categories ORDER BY id")]

# source url -> output path (directory-style for clean URLs)
pages = {"/index.php": "index.html",
         "/shop.php": "shop/index.html",
         "/cart.php": "cart/index.html",
         "/login.php": "login/index.html",
         "/register.php": "register/index.html"}
for s in slugs:
    pages[f"/product.php?slug={s}"] = f"product/{s}/index.html"
for c in cats:
    pages[f"/shop.php?category={c}"] = f"shop/category/{c}/index.html"

# ---------------------------------------------------------------- link rewrite
def static_path(php_url):
    """Map an absolute site path (with query) to its static clean URL."""
    u = urllib.parse.urlparse(php_url)
    q = urllib.parse.parse_qs(u.query)
    p = u.path
    if p in ("", "/", "/index.php"):
        return "/"
    if p == "/product.php" and "slug" in q:
        return f"/product/{q['slug'][0]}/"
    if p == "/shop.php":
        if "category" in q:
            return f"/shop/category/{q['category'][0]}/"
        return "/shop/"
    if p == "/cart.php":
        return "/cart/"
    if p == "/login.php":
        return "/login/"
    if p == "/register.php":
        return "/register/"
    if p.startswith("/assets/"):
        return p
    # customer/, admin/, logout, checkout, place-order: no backend on Netlify
    if p.startswith(("/customer/", "/admin/", "/logout", "/checkout", "/place-order", "/order-success")):
        return "#demo-disabled"
    return p

DEMO_BANNER = """
<div id="demo-note" style="position:fixed;left:50%;bottom:18px;transform:translateX(-50%);
 z-index:9999;background:#26301A;color:#F7F5E8;border:1px solid #6B7A2F;border-radius:999px;
 padding:9px 18px;font:600 13px/1.3 system-ui,sans-serif;box-shadow:0 6px 24px rgba(0,0,0,.35);
 display:none;max-width:92vw;text-align:center;">
  <span id="demo-note-text"></span>
</div>
<script>
(function(){
  var note=document.getElementById('demo-note'),txt=document.getElementById('demo-note-text'),t;
  function say(m){txt.textContent=m;note.style.display='block';clearTimeout(t);
    t=setTimeout(function(){note.style.display='none';},3200);}
  // Nothing can POST on a static host - intercept and explain instead.
  document.addEventListener('submit',function(e){
    var f=e.target;
    if(f.method&&f.method.toLowerCase()==='get'&&f.action.indexOf('/shop')>-1)return;
    e.preventDefault();
    say('Visual demo - ordering is disabled on this preview.');
  },true);
  document.addEventListener('click',function(e){
    var a=e.target.closest('a');
    if(a&&a.getAttribute('href')==='#demo-disabled'){e.preventDefault();
      say('Accounts and checkout are disabled on this preview.');}
  },true);
})();
</script>
"""

def rewrite(html):
    # absolute localhost links -> site-root relative
    html = html.replace(LOCAL, "/").replace(BASE, "")
    # then map every remaining .php reference to its static path
    def sub(m):
        quote, url = m.group(1), m.group(2)
        if url.startswith(("http", "//", "mailto:", "tel:", "#", "data:")):
            return m.group(0)
        if ".php" not in url:
            return m.group(0)
        if not url.startswith("/"):
            url = "/" + url.lstrip("./")
        return f'{quote}{static_path(url)}{quote}'
    html = re.sub(r'(["\'])([^"\']*?\.php[^"\']*)\1', sub, html)
    # cache-buster querystrings on assets break static file lookup
    html = re.sub(r'(/assets/[^"\']+?)\?v=\d+', r'\1', html)
    return html.replace("</body>", DEMO_BANNER + "</body>")

# ---------------------------------------------------------------- build
if os.path.isdir(OUT):
    shutil.rmtree(OUT)
os.makedirs(OUT)

written = 0
for url, dest in pages.items():
    try:
        html = urllib.request.urlopen(BASE + url, timeout=60).read().decode("utf-8", "replace")
    except Exception as e:
        print(f"  !! {url}: {e}")
        continue
    full = os.path.join(OUT, dest.replace("/", os.sep))
    os.makedirs(os.path.dirname(full), exist_ok=True)
    with open(full, "w", encoding="utf-8") as f:
        f.write(rewrite(html))
    written += 1
print(f"pages written: {written}")

# ---------------------------------------------------------------- assets
referenced = set()
for r in db.execute("SELECT image_url, hover_image_url FROM products"):
    for v in (r["image_url"], r["hover_image_url"]):
        if v:
            referenced.add(os.path.basename(v))
for r in db.execute("SELECT image_url FROM categories"):
    if r["image_url"]:
        referenced.add(os.path.basename(r["image_url"]))

copied = skipped = 0
for root, _dirs, files in os.walk(os.path.join(SRC, "assets")):
    for fn in files:
        src = os.path.join(root, fn)
        rel = os.path.relpath(src, SRC)
        parts = rel.replace("\\", "/").split("/")
        # drop orphaned product photos, keep every responsive variant + logos
        if parts[:3] == ["assets", "images", "products"] and len(parts) == 4:
            if fn not in referenced:
                skipped += 1
                continue
        dst = os.path.join(OUT, rel)
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        shutil.copy2(src, dst)
        copied += 1

size = sum(os.path.getsize(os.path.join(r, f))
           for r, _d, fs in os.walk(OUT) for f in fs)
print(f"assets copied: {copied}  (orphans skipped: {skipped})")
print(f"snapshot size: {size/1024/1024:.1f} MB")
