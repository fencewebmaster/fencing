# Flat Top cart test, 7 Oct 2026

**Result: 22 of 22 scenarios pass.** Every product the Flat Top planner draws reaches the cart, in the right quantity, for
Black, White and Monument on both suppliers (JG and GO). Every one of those cart lines is then accepted by the real
WooCommerce cart.

| Check | Result |
|---|---|
| Planner: drawing vs cart (22 scenarios) | 22 / 22 exact |
| Server: cart lines mapped to a SKU (124 colour x supplier cases) | 124 / 124, nothing dropped |
| Store: lines accepted by the WooCommerce cart (124 carts) | 780 / 780 |
| Database writes during the test | 0 (no inserts, updates or deletes) |

Nothing in the app, the product data or the store was changed. `writable/products.csv` is the same file before and after
(md5 `da7a6201…`).

## How it was tested

1. **Planner.** The real `/planner` page ran in a headless browser. For each scenario it picked Flat Top, entered the
   length, clicked through the real drawers (posts, panels, ends, step-ups, gate) and picked a colour. Then it called the
   planner's own submit, the same one Step 4 → Create Plan uses. The submit was captured and blocked, so no quote was
   saved and no webhook or Zapier call went out.
2. **Drawing vs cart.** The cart was checked against what the planner actually drew, item by item:
   - posts by type (Base Plated or Cement In)
   - step-up (raked) posts and panels
   - standard panels, plus 1 donor panel for each custom gate
   - one bracket pack per panel, step-ups included
   - one pack of M10 dynabolts and one post cover per Base Plated post
   - 2 flex brackets per swivel end
   - one gate (or gate converter) and one hinge & latch kit per gate
3. **Server.** Each captured submit went through FC's own cart code (`syncCartFromSession`) in a copy of `writable/`.
   It ran once for each colour (Black, White, Monument) and each supplier (JG, GO). That confirms every line gets a SKU
   and nothing is dropped.
4. **Store.** Each server cart went through the store plugin's own add-to-cart loop against the local WooCommerce. All
   database writes, emails and outgoing HTTP were blocked, and the MySQL write counters were checked before and after.
5. **Reference.** The old eForm Flat Top planner (*FTPF - V4 JG – Raked*) sells this product list:
   - panels and posts
   - step-up posts and raked panels
   - one bracket pack per panel
   - 2 angle brackets per custom-angle join
   - gates, with one hinge & latch kit per gate
   - one base-plate cover and one M10 bolt pack per base-plated post

   FC's cart covers all of these. It also sells a gate converter for custom-width gates, which the eForm planner never
   offered. Neither planner adds concrete or post caps. The posts already include a top cap ("Includes Top Post Cap").

## Scenarios

Lengths are in mm. "Cart" is the planner's cart as submitted. B = Black, W = White, M = Monument.

| # | Scenario | Cart | Colours | Result |
|---|---|---|---|---|
| F1 | Default: 6000, Even 2400W panels, Base Plated | 4 posts, 3 panels, 3 bracket packs, 4 dynabolts, 4 covers | B W M | Pass |
| F2 | Cement In posts | 4 Cement In posts (1800L), 3 panels, 3 brackets; no dynabolts or covers | B W M | Pass |
| F3 | Full Size 2400W panels | 4 posts, 3 full panels, 3 brackets, 4 dynabolts, 4 covers | B W M | Pass |
| F4 | Full Size 3000W panels | 3 posts, 2 × 3000W panels, 2 brackets, 3 dynabolts, 3 covers. White and Monument correctly greyed out | B | Pass |
| F5 | Left No Post, right No Post + Swivel | 2 posts, 3 panels, 2 flex brackets, 2 dynabolts, 2 covers | B W M | Pass |
| F6 | Step-ups 1300H left + 1800H right, Base Plated | 3 posts + 2 raked posts (1900L), 2 panels + 2 raked panels, 4 brackets, 5 dynabolts, 5 covers | B W M | Pass |
| F7 | Step-ups 1500H + 1600H, Cement In | 3 posts + 2 raked posts (2400L), 2 + 2 panels, 4 brackets | B W M | Pass |
| F8 | Step-ups 1400H + 1700H, Base Plated | as F6 | B W M | Pass |
| F9 | Standard gate | 4 posts, gate, hinge & latch kit, 2 panels, 2 brackets, 4 dynabolts, 4 covers | B W M | Pass |
| F10 | Custom gate 1000W | gate converter + 1 donor panel (3 panels), kit, 2 brackets | B W M | Pass |
| F11 | Two sections: 6000 Base Plated + 3000 Cement In | 4 Base Plated + 3 Cement In posts, 5 panels, 5 brackets, 4 dynabolts, 4 covers | B W M | Pass |
| F12 | Left end post set to Cement In in the end drawer | 3 Base Plated + 1 Cement In post; 3 dynabolts and 3 covers (Base Plated only) | B W M | Pass |
| F13 | Both ends No Post | 2 posts, 3 panels, 3 brackets | B W M | Pass |
| F14 | Both ends No Post + Swivel | 2 posts, 4 flex brackets | B W M | Pass |
| F15 | Step-up 1300H left with that end post Cement In | 1 Cement In raked post (2400L) + 3 Base Plated posts | B W M | Pass |
| F16 | Full 3000W panels + gate, 7000 | 4 posts, 2 × 3000W panels, gate, kit | B | Pass |
| F17 | Gate moved to Last + step-up 1500H left | 4 posts + 1 raked post, gate, kit, 3 brackets, 5 dynabolts | B W M | Pass |
| F18 | Short run 1200 | 2 posts, 1 panel, 1 bracket pack | B W M | Pass |
| F19 | Long run 15000 | 8 posts, 7 panels, 7 brackets, 8 dynabolts, 8 covers | B W M | Pass |
| F20 | Custom gate 800W, Cement In, 5000 | 4 Cement In posts, converter + donor (3 panels), kit | B W M | Pass |
| F21 | Two sections, a gate in each | 8 posts, 2 gates, 2 kits, 4 panels | B W M | Pass |
| F22 | Three sections: plain / step-up 1700H / swivel + gate | 9 posts + 1 raked post, 6 + 1 panels, 7 brackets, 10 dynabolts, 2 flex brackets, gate, kit | B W M | Pass |

## SKUs the cart uses

| Product | JG | GO |
|---|---|---|
| Panel (even or full 2400) | FTP-P2400-{B,W,M}-JG | FTP-P2450-{B,W,M}-GO |
| Panel full 3000 (Black only) | FTP-P3000-B-JG | FTP-P3000-B-GO |
| Post, Base Plated | PO-1300-{B,W,M}-JG | PO-1300-{B,W,M}-GO |
| Post, Cement In | PO-1800-{B,W,M}-JG | PO-1800-{B,W,M}-GO |
| Step-up post, Base Plated | PO-1900-{B,W,M}-JG | PO-1900-{B,W,M}-**JG** |
| Step-up post, Cement In | PO-2400-{B,W,M}-JG | PO-2400-{B,W,M}-GO |
| Raked panel 1300–1800H | FTP-RP{h}-B-JG; White and Monument = FTP-RP{h}-CPC-JG | same **JG** SKUs |
| Bracket pack x4 | FTP-BRx4-{B,W,M}-JG | FTP-BRx4-{B,W,M}-GO |
| Flex (swivel) bracket | FTP-BRFx1-B-JG (Black for every colour) | FTP-BRFx1-B-GO (Black for every colour) |
| Post cover | PO-COV-2P-{B,W,M}-JG | PO-COV-2P-{B,W,M}-GO |
| Dynabolts M10 x 120 x4 | FFX-DB-M10-120-4PK-SS-JG | FFX-DB-M10-120-4PK-SS-**JG** |
| Gate | FTP-G970-{B,W,M}-JG | FTP-G975-{B,W,M}-GO |
| Gate converter | FTP-GC1200-{B,W,M}-JG | FTP-GC1200-{B,W,M}-GO |
| Hinge & latch kit | KIT-HL-MTP-HD-B-JG (SafeTech, Black for every colour) | KIT-HL-MTP-HD-B-GO; White = KIT-HL-MTP-HD-W-GO |

## Findings to review

None of these makes the test fail. Each is a decision for the owner or something to check on the live sites.

1. **A gate can be placed against a "No Post" end.** The planner lets the gate sit directly next to a No Post or
   No Post + Swivel end:
   - F23: `no post | GATE | post | panel …`
   - F24: `… post | GATE | swivel`
   - F22: the gate defaults to First, next to the swivel end.

   The cart follows the drawing, so no post is bought for that side of the gate. With a swivel end, 2 flex brackets are
   sold for the gate edge, but gates hang on hinges and a latch, not brackets. The gate drawer itself says "Hinges MUST
   be screwed into a post". This is fine if the customer is hinging off an existing wall. Otherwise the planner should
   block it, or force a post there.
2. **GO sites sell some JG SKUs.** Every GO step-up post (Base Plated), every GO raked panel and GO's dynabolts point at
   `-JG` SKUs. These rows were copied from JG on 5 Oct to stop the lines vanishing. The local store holds both suppliers'
   SKUs, so here they add fine. On a live GO store the SKU must exist, or those lines are skipped silently at checkout.
   Check the live GO catalogue, or give these rows GO SKUs. Note that `wc-products-GO.csv` and `wc-products-JG.csv` are
   byte-identical locally, so the local export can't tell the two stores apart.
3. **Corner posts in multi-section jobs.** Each section buys a post at both ends unless the customer picks No Post at
   the joining end:
   - F11 bought 4 + 3 = 7 posts.
   - The eForm planner shared corner posts between sections, which would have been 6.

   Two sections that meet at a corner therefore get one extra post, plus its dynabolts and cover, unless the customer
   changes the end.
4. **Colour substitutions (expected, listed for confirmation):**
   - White and Monument step-up panels are "Raked Panel Custom" (CPC, custom powder coat), the same as the eForm planner.
   - Flex brackets are Black for every colour.
   - JG's hinge & latch kit is Black for every colour, as in the eForm planner.
   - GO's kit is White for White and Black for Monument.
5. **No concrete for Cement In posts.** Slat and Perforated suggest KwikSet for Cement In posts. Flat Top (like the
   eForm planner) adds nothing.
6. **Planner markup quirk (no customer impact).** A No Post end keeps a hidden post marker in the drawing, and a swivel
   end has two. The cart rules handle both correctly. This only matters to anyone counting the drawing directly.

## Changes since the 5 Oct test

- 11 new scenarios: per-end post options, both-end No Post and swivel, mixed step-up post type, 3000W + gate, gate moved
  Last, short and long runs, 800W custom gate, a gate in each of two sections, and a three-section job.
- The 15 GO rows added locally on 5 Oct still work: GO now passes every scenario.
- The live GO sites still need those rows (finding 2).

Test scripts and raw results are in the session scratchpad (`flat-drive.mjs`, `flat-replay.php`, `wc-spigot-replay.php`,
`flat-report2.mjs`, `report-m.txt`).
