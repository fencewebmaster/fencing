# Minified JS/CSS (frontend and admin)

`build.php` minifies the frontend's and the admin's own JavaScript and CSS with the
[esbuild](https://esbuild.github.io/) standalone binary (no Node/npm required) and writes the copies,
plus source maps, to `public/assets/min/`, at the same relative paths:

| Group (switch) | Source | Minified copy |
|---|---|---|
| Frontend CSS (`css`) | `public/assets/css/frontend/*.css` | `public/assets/min/css/frontend/*.css` |
| Frontend JS (`js`) | `public/assets/js/frontend/**/*.js` | `public/assets/min/js/frontend/**/*.js` |
| Admin CSS (`adminCss`) | `public/assets/css/admin/*.css` | `public/assets/min/css/admin/*.css` |
| Admin JS (`adminJs`) | `public/assets/js/admin/**/*.js` | `public/assets/min/js/admin/**/*.js` |

- Each file is minified **on its own — no bundling** — so every `<script>` keeps its own global
  scope and the load order in `partials/footer.php` / `fence-scripts.php` / the admin layout is
  untouched. esbuild does not rename top-level names in this mode, so the globals the files share
  keep working.
- A copy never needs a newer browser than its source. Left alone, esbuild rewrites into newer
  syntax than the source used (`??`, `catch {}`, `inset`, `#rrggbbaa` colours, two-position gradient
  stops); `MinifyService::SYNTAX_FLOOR` turns those rewrites off, so it writes the older equivalent,
  and the few places a source already uses one of them come out in the older form too.
- Vendor files and anything already named `*.min.js` / `*.min.css` are skipped (they ship minified).
- In the CSS copies, relative `url()`s get one extra `../` because the copies sit one folder deeper.
- Copies whose source was deleted are removed, so `public/assets/min/` always mirrors the sources.

## How the site picks a copy

`asset('public/assets/…')` (frontend), `asset('assets/…')` (admin) and the lookup / share-cart
asset closures all go through `AssetHelper::minified()`, which serves the `min/` copy **only while
its group's switch is on and the copy is at least as new as its source**. Edit a source and skip the
rebuild, and the site serves the edited source as written: never a stale copy, just an unminified
one until the next build. The Product Lookup page loads two admin stylesheets (`buttons.css`,
`theme.css`), so the Admin CSS switch covers them there too.

The admin switches default to **off** (the admin never served copies before), the frontend ones to
on. If a minified admin file ever breaks the back office so badly that the switch can't be reached,
delete `public/assets/min/css/admin/` or `min/js/admin/` (or just edit the source): with no fresh
copy, the original is served again.

The copies **are committed** (like `public/assets/css/vendor/tailwind.css`), so production needs no
build step.

## Font Awesome subset (`icons.php`, run by `build.php`)

The public pages use a few dozen of Font Awesome's ~1,900 icons. `icons.php` scans the frontend
views, JS and CSS for `fa-*` icon names (and glyphs CSS draws by codepoint, e.g. `content: "\f1de"`)
and writes, beside the stock files in `public/assets/fonts/fa/`:

| File | Loaded by `partials/head.php` | What it holds |
|---|---|---|
| `css/subset.min.css` | render-blocking | base rules, the used icons, a subset font face |
| `css/icons.min.css` | deferred | every icon's class rule, no fonts |
| `webfonts/fa-solid-900.subset.woff`, `fa-regular-400.subset.woff` | on demand | the used glyphs only |

The subset face is declared after the full one with a `unicode-range`, so browsers fetch only the
small file. An icon added since the last build **still shows**: its class rule arrives with
`icons.min.css` just after first paint, and the browser then fetches the full font for that glyph.
The admin and the lookup page keep the stock `all.min.css`.

Needs Python 3 with fontTools (`pip install fonttools`); without it `build.php` warns and keeps the
existing subset. Output is WOFF rather than WOFF2 because fontTools needs the `brotli` module for
WOFF2 (`pip install brotli` would shave a little more).

## Rebuild after editing frontend or admin JS or CSS

From the project root (`D:\xampp\htdocs\wp\fence\fc`):

```
php build/minify/build.php
```

It prints the before/after totals (raw and gzip) and exits non-zero if any file fails.

Or from the admin: **Settings → Developer → Minify CSS & JS** lists every frontend and admin file,
grouped by area and type, with the dates of its original and its minified copy, rebuilds any group
with the same `MinifyService::build()` the CLI uses (the icon subset is CLI-only; the build needs the
Console permission, `settings.dev_console`), and has a switch per group (`writable/theme.json` →
`minify`: `css`, `js`, `adminCss`, `adminJs`). A switch only chooses what is served: off serves that
group's sources; on serves each copy that is at least as new as its source.
Rebuilding is the separate Minify buttons (or `php build/minify/build.php`); a build never changes
the switches. One build runs at a time (`writable/storage/cache/minify.lock`), and the last build's
per-file errors are kept in `writable/storage/cache/minify-build.json` so the tab still shows them
after a reload.
On a server without the binary the tab says so and the committed copies keep being served.

## First-time setup (or if `esbuild.exe` is missing)

The binary is **not committed** (gitignored, ~10MB, platform-specific). Save the Windows x64 build as
`build/minify/esbuild.exe`: it is the single file `package/esbuild.exe` inside

```
https://registry.npmjs.org/@esbuild/win32-x64/-/win32-x64-0.25.5.tgz
```

(Mac/Linux: `@esbuild/darwin-arm64`, `@esbuild/linux-x64`, … from the same registry, saved as
`build/minify/esbuild`. Or point `ESBUILD_BIN` at any esbuild binary you already have.)
