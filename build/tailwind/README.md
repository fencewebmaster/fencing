# Tailwind CSS build (admin only)

Replaces the `https://cdn.tailwindcss.com` runtime/JIT script (not recommended for production —
ships the whole compiler to every visitor and recompiles on every page load) with one static,
purged, minified CSS file built via the [Tailwind standalone CLI](https://tailwindcss.com/blog/standalone-cli)
(no Node/npm required).

Only the admin area uses Tailwind — the frontend has its own hand-written CSS.

- `tailwind.config.js` — content globs (`app/views/admin/**/*.php`, `public/assets/js/admin/**/*.js`)
  plus the `sidebar` color extension that used to live in `main.php`'s inline `tailwind.config`.
- `input.css` — the three `@tailwind` directives.
- `tailwindcss.exe` — the downloaded binary. **Not committed** (gitignored, ~40MB, platform-specific).
- Output: `public/assets/css/vendor/tailwind.css`, loaded from `app/views/admin/layouts/main.php`
  and `app/views/admin/login.php` in the same position the CDN `<script>` tag used to be.

## First-time setup (or if `tailwindcss.exe` is missing)

Download the Windows x64 binary for the pinned version (matches what the CDN was serving —
Tailwind v3, not v4) and save it as `build/tailwind/tailwindcss.exe`:

```
https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.19/tailwindcss-windows-x64.exe
```

(Mac/Linux dev machines: grab `tailwindcss-macos-arm64`/`tailwindcss-linux-x64` etc. from the same
release page instead — same filename `tailwindcss.exe` works fine even without the `.exe` extension
on those platforms, or drop the extension and adjust the commands below.)

## Rebuild after changing admin markup/classes

From the project root (`D:\xampp\htdocs\wp\fence\fc`):

```
build\tailwind\tailwindcss.exe -c build\tailwind\tailwind.config.js -i build\tailwind\input.css -o public\assets\css\vendor\tailwind.css --minify
```

While actively developing, add `--watch` to rebuild automatically as you edit admin `.php`/`.js` files:

```
build\tailwind\tailwindcss.exe -c build\tailwind\tailwind.config.js -i build\tailwind\input.css -o public\assets\css\vendor\tailwind.css --watch
```

The built `public/assets/css/vendor/tailwind.css` **is committed** (like the other pre-built vendor
CSS in that folder) so the site works without anyone needing to run a build first.
