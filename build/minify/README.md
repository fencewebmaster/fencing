# Minified frontend JS/CSS (public site only)

`build.php` minifies the public site's own JavaScript and CSS with the
[esbuild](https://esbuild.github.io/) standalone binary (no Node/npm required) and writes the copies,
plus source maps, to `public/assets/min/`, at the same relative paths:

| Source | Minified copy |
|---|---|
| `public/assets/js/frontend/**/*.js` | `public/assets/min/js/frontend/**/*.js` |
| `public/assets/css/frontend/*.css` | `public/assets/min/css/frontend/*.css` |

- Each file is minified **on its own — no bundling** — so every `<script>` keeps its own global
  scope and the load order in `partials/footer.php` / `fence-scripts.php` is untouched. esbuild does
  not rename top-level names in this mode, so the globals the files share keep working.
- Vendor files and anything already named `*.min.js` / `*.min.css` are skipped (they ship minified).
- In the CSS copies, relative `url()`s get one extra `../` because the copies sit one folder deeper.
- Copies whose source was deleted are removed, so `public/assets/min/` always mirrors the sources.

## How the site picks a copy

`asset('public/assets/…')` and the lookup / share-cart asset closures go through
`AssetHelper::minified()`, which serves the `min/` copy **only while it is at least as new as its
source**. Edit a source and skip the rebuild, and the site serves the edited source as written:
never a stale copy, just an unminified one until the next build.

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

## Rebuild after editing frontend JS or CSS

From the project root (`D:\xampp\htdocs\wp\fence\fc`):

```
php build/minify/build.php
```

It prints the before/after totals (raw and gzip) and exits non-zero if any file fails.

## First-time setup (or if `esbuild.exe` is missing)

The binary is **not committed** (gitignored, ~10MB, platform-specific). Save the Windows x64 build as
`build/minify/esbuild.exe`: it is the single file `package/esbuild.exe` inside

```
https://registry.npmjs.org/@esbuild/win32-x64/-/win32-x64-0.25.5.tgz
```

(Mac/Linux: `@esbuild/darwin-arm64`, `@esbuild/linux-x64`, … from the same registry, saved as
`build/minify/esbuild`. Or point `ESBUILD_BIN` at any esbuild binary you already have.)
