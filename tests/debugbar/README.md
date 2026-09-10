# Debugbar tests

Zero-dependency harnesses (same pattern as `tests/glass-pool-clamps`):

```
node tests/debugbar/run.js       # client core in a vm sandbox with browser stubs
php  tests/debugbar/run-php.php  # DebugRedactor + ConsoleSettings::normalize (read-only)
```

What they pin down:

- The public `debugbar.*` API never throws, whatever it's fed.
- Ring buffers cap, count drops, and clear.
- Snapshot/redaction: sensitive-key substring matching, string truncation, array capping,
  depth limits.
- **`call_fence_func` replacement semantics** — the critical one: a missing module hook
  still falls back to the base method with no warning (by design), a throwing hook still
  falls back AND surfaces exactly one warning, a working hook runs untouched.
- The `calculate_fences` wrapper returns the original result untouched, records
  tab/item/summary, and never double-wraps on repeated installs.
- Run grouping, pause (capture stops, calls still run), clear.
- BOM/state readers: slugged `cart_items-{tab}-{slug}` buckets only (the legacy flat
  `cart_items-{n}` blob is ignored), 0-based tab → 1-based section mapping.
- Server island config (verbose / maxEntries / redactKeys) is respected.
- PHP: SQL literal masking in both quote styles without breaking query shape, and
  `ConsoleSettings::normalize()` as the whitelist (coercions, clamps, unknown-key drops,
  empty redaction list restoring defaults).

The BOM-invariance acceptance check (Debug Mode off ⇒ identical calculator output) is a
live comparison, not a unit test: capture `cart_items-*` localStorage per section with
debug on, flip Debug Mode off, recalculate, and diff — byte-identical across all five
fence styles when this shipped. Known diff false positives if you script it: row order
and the `stock` flag are nondeterministic between *server cart* rebuilds (CLAUDE.md).
