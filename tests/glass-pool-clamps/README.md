# Glass Pool panel-to-panel clamp tests

Node-only checks for the clamp logic in `public/assets/js/frontend/fences/calc/glass_pool.js`
(`GlassPoolCalc.clamp*` helpers, the solver's enforced minimum gap and its clamp-widened pass).
No dependencies, no browser, no PHP.

## Running it

From the project root:

```bash
node tests/glass-pool-clamps/run.js
```

Exit code 0 when every block passes, 1 on any failure (the first eight failing cases of each
block are printed), 2 when the solver sources could not be loaded. The full run takes about
half a minute: blocks 4-6 share one sweep of ~49,000 solver cases, run three times (baseline,
working tree non-enforced, working tree enforced).

The harness loads the solver into a `vm` context with `{ GlassPool: {}, console }` — sloppy
mode on purpose, because the solver assigns an implicit global (`numFixedElements`) — so
`calculatePanels()` runs exactly as the planner runs it, minus `FENCE` (hinge/latch gaps fall
back to 10/9 and every gate case passes a finite hinge panel width, so the `FENCE`-dependent
default lookups never run).

## What each block proves

| Block | Proves |
|---|---|
| 1 `clampForGap` table | The §1 range rule on the shipped sizes (13/19 below_min, 20..49 Small, 50..95 Large, 96+ above_max, 0/NaN invalid); `clampResult` rounds first (19.6 → 20 Small, 49.6 → 50 Large); a config-derived list (25-60 / 60-90) and a three-size unsorted list drive the classification instead of the defaults. A list with a hole (Small 20-45 / Large 50-95) classifies 45..49 as `no_size` — 45 because Small is half-open at its maximum, 50 is Large — while 19 / 96 keep `below_min` / `above_max`; `clampResult` carries `no_size` through (46.6 → 47, no slug). |
| 2 `clampSizesFromInfo` | Missing / non-object / empty `panel_clamps` → defaults; malformed rows (blank or NaN numbers, min ≥ max, blank slug, non-object) are dropped while valid ones are coerced and sorted; all-invalid → defaults. |
| 3 `clampRequest` | `selected` reads the `post_option` setting **by key** (a `[post_option, panel_option]` order still works); `enforce` needs the `panel_clamps` row (`gap_adjust` = `'1'` or `1`) or `forceEnforce: true`, and never without `selected`; undefined / empty / junk `customFence` is inert; config ranges flow through to `minGapMm` / `maxGapMm`. |
| 4 regression | Fewest panels now applies to EVERY glass solve, clamps or not (owner's call, Sept 2026), so this is no longer byte-identity with the pinned baseline. What it pins: a length the baseline could build still builds; any layout that changed uses strictly FEWER panels; and every changed layout stays inside the customer's Max Panel Spacing (still a hard cap without clamps), inside the 80 mm ceiling, and on a buildable panel width. It reports how many results are identical vs improved, and the total panels removed. |
| 5 enforcement | Under `{enforce:true, minGapMm:20, maxGapMm:95}` every solved layout with a panel-to-panel junction has a rounded gap in [20, 80] — the widened pass stops at the planner's 80 mm spacing ceiling (`maxPanelSpacing`), so the enforced upper bound is min(95, 80) — `clamp.status === 'ok'`, the Small slug below 50 and Large from 50, and its geometry closes to within 0.5 mm when the mirrored config is re-run through `calculateGlassFencing()` (the mirror is cross-checked against `calculatePanels()` so a drift between the two shows up). |
| 6 no disturbance | Whenever the baseline solved with a rounded gap ≥ 20, the enforced output deep-equals the baseline — this is what pins the fewest-panel search to "only when the minimum actually rejected a layout": a run the strict pass would have failed anyway stays on the baseline's rescue / auto-fit path (1196 mm, both ends dynamic: the rescue's 1×1150 @ 23 must survive, not become 5×200). For baseline solves with a rounded gap < 20 it counts how many (a) re-solve under the strict minimum alone, (b) re-solve only through the widened pass, (c) fail — and, within (a), how many fell to the dynamic-end rescue's single panel under the strict minimum alone while the enforced run's widened pass found a clampable multi-panel layout instead (the minimum rejected the baseline's loop layout in those, so both outcomes are legitimate; a difference outside the rescue path is a failure). |
| 7 no-junction runs | 1745 / 1747 mm with a 890 gate + 800 hinge panel, both ends dynamic, spacing 30: zero regular panels and 18 / 19 mm end gaps. The rescue's junction gating leaves these byte-identical under enforcement. |
| 8 widened pass | 937 mm, ends 0/0, spacing 30, panel 1400: baseline is 3×300 @ 18.5. The strict minimum alone finds nothing (and names the minimum in its error); enforcement solves at 937 through the widened pass (2×450 @ 37, Small); the same request selected-but-not-enforced still returns the baseline layout classified `below_min`. |
| 9 fewest panels | 3527 mm, 890 gate + 800 hinge panel, ends 0/25, spacing 30, panel 1400: baseline is 7×250 @ 7.2. The widened candidates are brute-forced the way the solver builds them (3×550 @ 71.5, 4×400 @ 64.3, 6×250 @ 58.6; 2×850 @ 93 is over the 80 ceiling) and the enforced result must be the FEWEST-panel one — 3×550 @ 71.5, Large — never 2×850, which is over the 80 ceiling. Fewest panels is the owner's rule (Sept 2026): each extra panel is two more spigots and another clamp while the glass is priced per m², so panel count is what the customer pays for. The mirrored config with a 95 clamp maximum agrees, i.e. still stops at 80. |
| 10 no-junction gating | 1237 mm, dynamic left end / 25 fixed right, no gate, spacing 30, panel 1400 and 2000: baseline is a single 1200 panel with a 12 mm end gap. Nothing meets glass-to-glass, so the minimum has nothing to reject and the enforced output deep-equals the baseline (classified `below_min` for the consumers), from the main loop rather than the rescue (it used to become 5×200 @ 42.4 through the widened pass). With panel 1200 the same length is 2×600 @ 6 — a junction — and is legitimately re-solved inside the clamp range. |

The sweep (blocks 4-6): overall 900..12000 mm step 37 × default panel {1200, 1400, 2000} ×
max spacing {30, 50, 80} × ends {dynamic, 0, 25}² × gate {off, on: 890 wide, 800 hinge panel,
hinge 10 / latch 9}. Change `SWEEP` at the top of `run.js` to widen it.

## Why the baseline is pinned to `37dc446`

Block 4 compares the working tree against the solver **as it was before the clamp logic
landed**, read straight out of git (`git show 37dc446:public/assets/js/frontend/fences/calc/glass_pool.js`).
Comparing against `HEAD` would become a tautology the moment the clamp commit exists, and
comparing against a checked-in copy would let the copy drift. `37dc446` ("Fixed next button
function") is the last commit that touched nothing in the solver's behaviour before this work.

### Re-pinning after an intentional solver change

Only when the non-enforced output is *meant* to change (a fix to the solver proper, not to the
clamp logic):

1. Make the solver change and confirm block 4 fails only on the cases the change is supposed
   to affect (read the printed cases — they name overall / panel / spacing / ends / gate).
2. Commit the solver change.
3. Set `BASELINE_REV` in `run.js` to that commit and re-run: block 4 must pass again, and
   blocks 5-10 must still pass (they run against the working tree, not the baseline).
4. Commit the re-pin separately so the history shows which baseline each solver revision was
   proven against.

Never re-pin to make a failing block 4 go away for a clamp-only change: that failure means the
extraction or the enforcement leaked into non-enforced output.
