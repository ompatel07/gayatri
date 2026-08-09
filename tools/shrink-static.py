"""
Shrink the fallback originals inside the snapshot only. The site renders from
the WebP srcset; these files are just the <img src> fallback, so a 1200px cap
costs nothing visually. Source images in the repo are left untouched.
"""
import os
from PIL import Image

OUT = r"e:\ANTIGRAVITY\client2\gayatri\netlify-static\assets\images"
CAP = 1200
before = after = 0
touched = 0

for sub in ("products", "categories", ""):
    d = os.path.join(OUT, sub) if sub else OUT
    if not os.path.isdir(d):
        continue
    for fn in os.listdir(d):
        p = os.path.join(d, fn)
        if not os.path.isfile(p) or not fn.lower().endswith((".jpg", ".jpeg", ".png")):
            continue
        b = os.path.getsize(p)
        before += b
        try:
            with Image.open(p) as im:
                fmt = im.format          # real format, which is not always the extension
                w, h = im.size
                if w <= CAP and b < 250_000:
                    after += b
                    continue
                im = im.convert("RGB") if fmt == "JPEG" else im
                if w > CAP:
                    im = im.resize((CAP, round(h * CAP / w)), Image.LANCZOS)
                if fmt == "JPEG":
                    im.save(p, "JPEG", quality=84, optimize=True, progressive=True)
                else:
                    im.save(p, fmt, optimize=True)
            touched += 1
        except Exception as e:
            print(f"  !! {fn}: {e}")
        after += os.path.getsize(p)

total = sum(os.path.getsize(os.path.join(r, f))
            for r, _d, fs in os.walk(os.path.dirname(OUT)) for f in fs)
print(f"recompressed {touched} fallback images: "
      f"{before/1024/1024:.1f} MB -> {after/1024/1024:.1f} MB")

root = r"e:\ANTIGRAVITY\client2\gayatri\netlify-static"
size = sum(os.path.getsize(os.path.join(r, f))
           for r, _d, fs in os.walk(root) for f in fs)
print(f"snapshot now: {size/1024/1024:.1f} MB")
