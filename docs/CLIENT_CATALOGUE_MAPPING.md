# Client master sheet → website: complete mapping

Every line of the client's handwritten sheet checked against the live site.
Status as of the variant work in commit `c5dfb05`.

Legend: **DONE** live and priced · **BLOCKED** needs client confirmation ·
**MISSING** product does not exist on the site.

---

## ⚠️ Correction to the external review

The review claimed *Divyashree* should be ₹5,099 / ₹8,099 / ₹11,999 at
23×16 / 29×20 / 35×24, "with/without frame".

**Those are Diksha Vaibhav's prices (sheet item #19), not Divyashree's.**
Divyashree is item #17 and lists ₹15,399 / ₹11,299 / ₹7,199 / ₹4,199. The site
shows "from ₹4,199", the lowest of those four — **already correct**. Applying
the review's figures would put one product's prices on another.

---

## 1. Wall Art — 8 on site, 22 in the sheet

| # | Client product | On site | Status |
|---|---|---|---|
| 01 | Indicate Swirl (Set) | — | **MISSING** — prices clear (70×24 ₹25,500/₹23,500; 48×16 ₹11,700/₹10,600) |
| 02 | Lord Ganesha | — | **MISSING** — 30×24 ₹11,500; 35×28 ₹15,000 |
| 03 | Lord Buddha | — | **MISSING** — 24×20 ₹7,500; 34×40 ₹20,799 `[VERIFY]` |
| 04 | Geometric Aura | — | **MISSING** — standard ladder |
| 05 | Solar Eclipse (Set) | — | **MISSING** — 48×24 ₹17,599; 40×20 ₹12,199; 36×18 ₹9,899 |
| 06 | Wheel of Creation | — | **MISSING** — standard ladder |
| 07 | Wheel of Essence | — | **MISSING** — standard ladder |
| 08 | Aura of Colours | — | **MISSING** — standard ladder |
| 09 | Padmavali | Padmavalli Lotus Mandala | **DONE** ⚠️ ladder assumed, see note |
| 10 | Vistara | Vistara Mandala | **DONE** ⚠️ ladder assumed |
| 11 | Jyotisya | Jyotisya Mandala | **DONE** ⚠️ ladder assumed |
| 12 | Mandala | — | **MISSING** |
| 13 | Omkara | — | **MISSING** |
| 14 | Subrachakra | — | **MISSING** |
| 15 | Emberhalo | — | **MISSING** |
| 16 | Kazyujin | — | **MISSING** |
| 17 | Divyashree | Divyashree Sacred Mandala | **DONE** — size↔price mapping unconfirmed |
| 18 | Loka Lattice | — | **MISSING** — 20×31 price unclear |
| 19 | Diksha Vaibhav | — | **MISSING** — ⚠️ sheet prices are inverted |
| 20 | *(name unclear)* | — | **BLOCKED** — need the name |
| 21 | Ganesha / "Dana" | — | **BLOCKED** — need the name |
| 22 | Salmira | — | **MISSING** — 35×25 without `[VERIFY]` |

**Standard ladder** = 35×35 ₹18,799 · 30×30 ₹13,799 · 24×24 ₹8,799 · 18×18 ₹4,999

**⚠️ Note on 09/10/11:** the typed sheet says "sizes/prices not clearly
visible". Those three were priced from the ditto marks (`"`) in the original
photograph, which repeat Aura of Colours' ladder. Defensible but **unconfirmed**.

**On site but not in the client's list:** Lunara Celestial, Verdelune Botanical,
Geometric Metal Leaf, Tree of Life Acrylic. Keep, rename, or remove — client's call.

---

## 2. Nameplates — acrylic

**Round, 10mm** — 13×13 ₹3,699/₹2,199 · 16×16 ₹4,699/₹3,199 · 21×21 ₹7,199/₹5,699
(With LED / Without LED) — **DONE** on 6 products: Virat, Radhe Krishna,
Shivalay, Dr. Jaiprakash Neema, Ishawasyam, Skywings.
Sheet also lists *Dwarkadhish* and *Vipul Sojitra* — **MISSING** as products.

**Rectangle** — 18×7 ₹3,899/₹2,399 · 24×9 ₹5,599/₹3,999 — **DONE** on 4:
Aashiyana, Patel Villa, Vraj Nivas, Mahavir Thakur (Black Floral Rectangle).
Sheet also lists *Hardik Pandya* — **MISSING**.

## 3. Nameplates — stainless steel

| Finish | Ladder | Status |
|---|---|---|
| SS-304 brushed **silver** | 18×7 ₹2,399 · 20×8 ₹3,199 · 24×10 ₹4,599 | **DONE** — Modern Stainless Steel Nameplate |
| SS-304 brushed **gold** | 7×18 ₹3,599 · 8×20 ₹4,499 · 10×24 ₹6,799 | **DONE** — Nidhivan Golden Nameplate |
| SS-304 brushed **rosegold** | 18×7 ₹3,599 · 20×8 ₹4,499 · 24×10 ₹6,799 | **MISSING** — no product on site |

The silver and gold lists each name ~10 designs that do not exist as separate
products. The sheet says these are example order texts, so they are probably
one customisable product per finish rather than ten products.

**BLOCKED — two products contradict themselves:**
- *Ganesh LED Backlit Nameplate (Gold)* — named LED, but the closest sheet
  entry is SS brushed gold, which has no LED option.
- *Krishna LED Acrylic Nameplate* — named acrylic, but its plate
  ("Bhargav Mehta") appears in the **SS brushed gold** list.

The ladders differ by roughly ₹1,200 a size, so these are not safe to guess.

---

## 4. Wall Clocks — DONE (base prices)

12×12 ₹1,800 · 16×16 ₹3,500 · 24×24 ₹8,000 — applied to all 8 clocks.

**NOT implemented:** the launch discounts (10% / 15% / 15% for 3 months).
No discount engine exists, `product_variants` has no sale price, and the launch
date is unknown. The homepage's "30% OFF ALL MDF WALL ART" is marketing copy
only — no discount is actually applied to any price anywhere on the site.

---

## 5. Wall Frames

| Product | Status |
|---|---|
| Canvas photo frames — 4 sizes × With/Without Frame, ₹800–₹7,200 | **DONE** — Abstract Sunset Canvas Set |
| Acrylic 10-point frame, 5mm, with studs — 12×12 ₹1,200 · 12×18 ₹1,800 · 18×24 ₹3,600 · 24×36 ₹7,200 | **MISSING** — product does not exist |

The acrylic frame's prices are unambiguous; it only needs a name, description
and photography to be added.

---

## 6. Tabletop

| Item | Status |
|---|---|
| Shri Yantra — 12×12 ₹1,999 · 8×8 ₹1,499 | **DONE** |
| "Rest of the tabletop → ₹2,000" | **BLOCKED** |

Five tabletop products currently sit at ₹4,799–₹5,999 (Buddha Mandala, Dragon
Fire, Mandala Yantra Blue Gold, Mandala Yantra Multicolor, Zen Lord Buddha).
Dropping them all to ₹2,000 would more than halve them, and the sheet may mean
only a specific group. **Confirm before changing.**

---

## 7. Feature gap: custom name text

Sections 2 and 3 state the listed names are example orders and that the product
"should allow the customer to enter/select the required name text".

There is no text input on nameplate products — a customer cannot say what name
they want. This is a build item, not a data one.

---

## What needs the client, in priority order

1. Do the ~14 missing Wall Art products get built? (needs names, sizes, images)
2. Padmavalli / Vistara / Jyotisya — confirm the assumed price ladder.
3. Divyashree — which size maps to each of the four prices?
4. Diksha Vaibhav — without-frame costs *more* than with. Typo?
5. Tabletop — does "rest → ₹2,000" cover all five products?
6. Ganesh LED Gold and Krishna LED Acrylic — acrylic or stainless steel?
7. Launch discounts — start date, and should they be applied automatically?
8. Nameplates — add a custom-name field?
9. Lunara, Verdelune, Geometric Metal Leaf, Tree of Life — keep, rename, or drop?
10. Wall Art Mirrors — not in the sheet at all.

---

## Verified working

Cart → checkout → placed order was tested with all three option types at once
(Framing, Lighting, size-only). Every line kept its own variant price, the
order total matched, and the variant label was recorded on each order line.
All 27 combinations across the newly-varianted products were added to the cart
individually and each charged correctly.
