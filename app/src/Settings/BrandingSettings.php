<?php

declare(strict_types=1);

namespace Fc\Admin\Settings;

/**
 * FC branding — logo, favicon, app name, tagline and the frontend, heading and admin fonts (saved to
 * writable/theme.json). The app version is not a setting: it is the newest published release in
 * Releases (AppVersionService::label()).
 */
final class BrandingSettings
{
    /** Bootstrap's own system stack: the admin's font before any choice, and the System UI choice. */
    private const SYSTEM_STACK = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"';

    /**
     * Every font dropdown offers this one list, alphabetically after System UI. 'family' is the Google
     * Fonts name ('' = the device's own font, nothing to load) and 'weights' only those the family has:
     * Google drops a missing weight from a list, but answers 400 when none of them exist. 'import' loads
     * the family through an inline @import, which Cloudflare Fonts leaves alone: its rewrite of League
     * Gothic's link points that width-axis font at a /wght/ file that returns 502.
     *
     * @var array<string, array{label:string,family:string,weights:string,import?:bool}>
     */
    private const FONTS = [
        'system' => ['label' => 'System UI', 'family' => '', 'weights' => ''],
        'anton' => ['label' => 'Anton', 'family' => 'Anton', 'weights' => '400'],
        'antonio' => ['label' => 'Antonio', 'family' => 'Antonio', 'weights' => '400;500;600;700'],
        'barlow' => ['label' => 'Barlow', 'family' => 'Barlow', 'weights' => '400;500;600;700;800'],
        'barlow-condensed' => ['label' => 'Barlow Condensed', 'family' => 'Barlow Condensed', 'weights' => '400;500;600;700;800'],
        'bebas-neue' => ['label' => 'Bebas Neue', 'family' => 'Bebas Neue', 'weights' => '400'],
        'big-shoulders-display' => ['label' => 'Big Shoulders Display', 'family' => 'Big Shoulders Display', 'weights' => '400;500;600;700;800'],
        'dm-sans' => ['label' => 'DM Sans', 'family' => 'DM Sans', 'weights' => '400;500;600;700;800'],
        'figtree' => ['label' => 'Figtree', 'family' => 'Figtree', 'weights' => '400;500;600;700;800'],
        'fjalla-one' => ['label' => 'Fjalla One', 'family' => 'Fjalla One', 'weights' => '400'],
        'inter' => ['label' => 'Inter', 'family' => 'Inter', 'weights' => '400;500;600;700;800'],
        'lato' => ['label' => 'Lato', 'family' => 'Lato', 'weights' => '400;700'],
        'league-gothic' => ['label' => 'League Gothic', 'family' => 'League Gothic', 'weights' => '', 'import' => true],
        'manrope' => ['label' => 'Manrope', 'family' => 'Manrope', 'weights' => '400;500;600;700;800'],
        'montserrat' => ['label' => 'Montserrat', 'family' => 'Montserrat', 'weights' => '400;500;600;700;800'],
        'nunito-sans' => ['label' => 'Nunito Sans', 'family' => 'Nunito Sans', 'weights' => '400;500;600;700;800'],
        'open-sans' => ['label' => 'Open Sans', 'family' => 'Open Sans', 'weights' => '400;500;600;700;800'],
        'oswald' => ['label' => 'Oswald', 'family' => 'Oswald', 'weights' => '400;500;600;700'],
        'pathway-gothic-one' => ['label' => 'Pathway Gothic One', 'family' => 'Pathway Gothic One', 'weights' => '400'],
        'poppins' => ['label' => 'Poppins', 'family' => 'Poppins', 'weights' => '400;500;600;700;800'],
        'roboto' => ['label' => 'Roboto', 'family' => 'Roboto', 'weights' => '400;500;600;700;800'],
        'source-sans-3' => ['label' => 'Source Sans 3', 'family' => 'Source Sans 3', 'weights' => '400;500;600;700;800'],
    ];

    /** The planner's own pairing while Frontend font stays at its default: Inter for UI, Poppins for text. */
    private const FRONTEND_PAIRING = [['Inter', '400;500;600;700', 'ui'], ['Poppins', '400;500;600;700;800', 'text']];

    /**
     * Families the frontend serves itself from LOCAL_FONT_DIR instead of Google (latin woff2, the files Google
     * ships, all SIL Open Font License): the default trio. From Google a file only began downloading once every
     * stylesheet was in, so each page painted in a fallback face and then changed shape (Nov 2026). 'files' is
     * weight => file, 'variable' one file for every weight asked for. 'fallback' gives Arial the family's own width
     * and line, as [size-adjust, ascent, descent, line-gap] in % for regular then bold text, so words painted early
     * keep their place.
     *
     * @var array<string, array{files?:array<int,string>,variable?:string,fallback?:list<list<float|int>>}>
     */
    private const LOCAL_FONTS = [
        'Inter' => ['variable' => 'inter.woff2', 'fallback' => [[106, 91.39, 22.75, 0], [102, 94.98, 23.65, 0]]],
        'League Gothic' => ['files' => ['400' => 'league-gothic.woff2']],
        'Poppins' => [
            'files' => ['400' => 'poppins-400.woff2', '500' => 'poppins-500.woff2', '600' => 'poppins-600.woff2', '700' => 'poppins-700.woff2', '800' => 'poppins-800.woff2'],
            'fallback' => [[110, 95.45, 31.82, 9.09], [100, 105, 35, 10]],
        ],
    ];

    private const LOCAL_FONT_DIR = 'public/assets/fonts/webfonts/';

    /** The weights each role's first screen is painted in: only those files are worth a preload. */
    private const FIRST_PAINT_WEIGHTS = ['heading' => ['400'], 'text' => ['400', '600', '700'], 'ui' => ['400', '600', '700']];

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'logo' => '',
            'favicon' => '',
            'appName' => 'Fencing Calculator',
            'tagline' => 'Calculate your fence cost and the materials needed.',
            'fontFrontend' => 'poppins',
            'fontHeading' => 'league-gothic',
            'fontAdmin' => 'system',
        ];
    }

    /**
     * @return array<string, array{label:string,type:string,placeholder?:string,help?:string,choices?:array<string,array{label:string,stack:string,href:string}>}>
     */
    public static function schema(): array
    {
        return [
            'logo' => [
                'label' => 'Logo',
                'type' => 'image',
                'placeholder' => 'public/assets/uploads/logo.png',
                'help' => 'Shown on the admin login page, and in the sidebar header when the active site has no logo of its own. SVG, PNG, JPG, WebP, or GIF recommended.',
            ],
            'favicon' => [
                'label' => 'Favicon',
                'type' => 'image',
                'placeholder' => 'public/assets/uploads/favicon.png',
                'help' => 'Small site icon shown in browser tabs. ICO, PNG, or SVG recommended (16×16 or 32×32).',
            ],
            'appName' => [
                'label' => 'App name',
                'type' => 'text',
                'placeholder' => 'Fencing Calculator',
                'help' => 'Shown in the page title, header, footer, and admin sidebar.',
            ],
            'tagline' => [
                'label' => 'Tagline',
                'type' => 'text',
                'placeholder' => 'Calculate your fence cost and the materials needed.',
                'help' => 'Short description under the title on the planner header.',
            ],
            'fontFrontend' => [
                'label' => 'Frontend font',
                'type' => 'font',
                'help' => 'Text, buttons and measurements on the planner, project plan, Product Lookup and Releases pages. The default keeps Inter on buttons and measurements; headings have their own font below.',
                'choices' => self::fontChoices(),
            ],
            'fontHeading' => [
                'label' => 'Heading font',
                'type' => 'font',
                'help' => 'Step titles, section tabs, popup titles and the loading screen on the planner and project plan. These headings are sized for League Gothic, a narrow face, so a wide font can wrap the longer titles.',
                'choices' => self::fontChoices('heading'),
            ],
            'fontAdmin' => [
                'label' => 'Admin font',
                'type' => 'font',
                'help' => 'Every admin page and the sign-in page. System UI is the device\'s own font, with nothing to download.',
                'choices' => self::fontChoices(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function get(): array
    {
        $defaults = self::defaults();
        $saved = ThemeSettings::section('branding');

        $merged = $defaults;
        foreach ($saved as $key => $value) {
            if (!array_key_exists($key, $defaults) || !is_scalar($value)) {
                continue;
            }
            $normalized = self::normalizeField($key, (string) $value);
            if ($normalized !== null) {
                $merged[$key] = $normalized;
            }
        }

        return $merged;
    }

    public static function normalizeField(string $key, string $value): ?string
    {
        $value = trim($value);

        if ($key === 'logo') {
            if ($value === '') {
                return '';
            }

            if (mb_strlen($value) > 500) {
                $value = mb_substr($value, 0, 500);
            }

            if (!self::isValidLogoPath($value)) {
                return null;
            }

            return str_replace('\\', '/', $value);
        }
        if ($key === 'favicon') {
            if ($value === '') {
                return '';
            }

            if (mb_strlen($value) > 500) {
                $value = mb_substr($value, 0, 500);
            }

            if (!self::isValidLogoPath($value)) {
                return null;
            }

            return str_replace('\\', '/', $value);
        }

        if ($key === 'fontFrontend' || $key === 'fontHeading' || $key === 'fontAdmin') {
            return isset(self::FONTS[$value]) ? $value : null;
        }

        if ($value === '') {
            return null;
        }

        $limits = [
            'appName' => 120,
            'tagline' => 500,
        ];

        $max = $limits[$key] ?? 500;
        if (mb_strlen($value) > $max) {
            $value = mb_substr($value, 0, $max);
        }

        return $value;
    }

    public static function isValidLogoPath(string $value): bool
    {
        if (preg_match('/^https?:\/\//i', $value) || preg_match('/^data:image\//i', $value)) {
            return true;
        }

        if (str_contains($value, '..') || str_contains($value, "\0")) {
            return false;
        }

        $path = ltrim(str_replace('\\', '/', $value), '/');

        return (bool) preg_match('#^(?:public/assets/uploads|public/assets/img)/[^/]+$#', $path);
    }

    /**
     * @param array<string, string>|null $branding
     */
    public static function faviconUrl(string $appBase, ?array $branding = null): string
    {
        return self::brandingAssetUrl('favicon', $appBase, $branding);
    }

    /**
     * @param array<string, string>|null $branding
     */
    public static function logoUrl(string $appBase, ?array $branding = null): string
    {
        return self::brandingAssetUrl('logo', $appBase, $branding);
    }

    /**
     * Resolve a stored branding asset path ('favicon' or 'logo') to a browser URL.
     *
     * @param array<string, string>|null $branding
     */
    private static function brandingAssetUrl(string $key, string $appBase, ?array $branding): string
    {
        if ($branding === null) {
            $branding = self::get();
        }

        $path = trim((string) ($branding[$key] ?? ''));
        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path) || preg_match('/^data:/i', $path) || str_starts_with($path, '//')) {
            return $path;
        }

        $base = rtrim(str_replace('\\', '/', $appBase), '/');
        $rel = ltrim(str_replace('\\', '/', $path), '/');

        return $base !== '' ? $base . '/' . $rel : $rel;
    }

    /**
     * @param array{img_class?:string,fallback_icon?:string} $options
     * @param array<string, string>|null $branding
     */
    public static function logoMarkup(string $appBase, ?array $branding = null, array $options = []): string
    {
        $imgClass = (string) ($options['img_class'] ?? '');
        $fallbackIcon = (string) ($options['fallback_icon'] ?? 'fa-solid fa-border-all');

        $logoUrl = self::logoUrl($appBase, $branding);
        if ($logoUrl !== '') {
            return '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="" class="' . htmlspecialchars($imgClass, ENT_QUOTES, 'UTF-8') . '" decoding="async">';
        }

        return '<i class="' . htmlspecialchars($fallbackIcon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
    }

    /**
     * The font dropdowns' options, the same list for every dropdown: label, CSS font stack ('heading' gives
     * the heading fallback), and the Google Fonts stylesheet ('' for System UI).
     *
     * @return array<string, array{label:string,stack:string,href:string}>
     */
    public static function fontChoices(string $role = 'text'): array
    {
        $choices = [];
        foreach (self::FONTS as $slug => $font) {
            $choices[$slug] = [
                'label' => $font['label'],
                'stack' => self::fontStack($font['family'], $role === 'heading'),
                'href' => $font['family'] === '' ? '' : self::googleFontsUrl($font['family'], $font['weights']),
            ];
        }

        return $choices;
    }

    /**
     * What a page head prints for one side's fonts ('frontend' or 'admin'): Google Fonts links, one family
     * each (Cloudflare Fonts keeps only a link's first family), @imports for a family that must stay out of
     * Cloudflare's reach, and the CSS that points the page at the chosen faces. A family loads once however
     * many roles use it. The frontend serves a LOCAL_FONTS family itself: 'faces' and 'fallbacks' are the rows
     * fontFaceCss() and fontPreloads() print, and no Google request is left at the defaults. The admin always
     * links Google, and prints nothing at its default.
     *
     * @param array<string, string>|null $branding
     * @return array{links:list<string>,imports:list<string>,faces:list<array<string,mixed>>,fallbacks:list<array<string,mixed>>,css:string}
     */
    public static function webFonts(string $side, ?array $branding = null): array
    {
        if ($branding === null) {
            $branding = self::get();
        }
        $defaults = self::defaults();
        $fonts = ['links' => [], 'imports' => [], 'faces' => [], 'fallbacks' => [], 'css' => ''];
        $loaded = [];

        if ($side === 'admin') {
            $slug = self::fontSlug($branding, 'fontAdmin');
            if ($slug !== $defaults['fontAdmin']) {
                self::addFontStylesheet($fonts, $loaded, self::FONTS[$slug]);
                $stack = self::fontStack(self::FONTS[$slug]['family'], false);
                // Bootstrap's stack, which the admin's body, controls and tooltips all read.
                $fonts['css'] = '<style id="fc-admin-font">:root{--bs-font-sans-serif:' . $stack . ';--fc-login-font:' . $stack . '}</style>' . "\n";
            }

            return $fonts;
        }

        // Each of style.css's three font tokens: the family it reads, and whether that is the stylesheet's own default.
        $roles = [];
        $slug = self::fontSlug($branding, 'fontFrontend');
        if ($slug === $defaults['fontFrontend']) {
            foreach (self::FRONTEND_PAIRING as [$family, $weights, $role]) {
                self::addFontStylesheet($fonts, $loaded, ['family' => $family, 'weights' => $weights], true);
                $roles[$role] = [$family, true];
            }
        } else {
            self::addFontStylesheet($fonts, $loaded, self::FONTS[$slug], true);
            $roles['text'] = $roles['ui'] = [self::FONTS[$slug]['family'], false];
        }

        $slug = self::fontSlug($branding, 'fontHeading');
        self::addFontStylesheet($fonts, $loaded, self::FONTS[$slug], true);
        $roles['heading'] = [self::FONTS[$slug]['family'], $slug === $defaults['fontHeading']];

        foreach ($loaded as $family => $weights) {
            if (is_string($weights)) {
                $familyRoles = array_keys(array_filter($roles, static fn (array $role): bool => $role[0] === $family));
                self::addLocalFaces($fonts, (string) $family, explode(';', $weights), $familyRoles);
            }
        }

        // html:root outranks style.css's :root defaults, which load after the webfonts partial. A default family
        // is named again only to add its fallback face to the token.
        $vars = [];
        foreach (['text', 'ui', 'heading'] as $role) {
            [$family, $isDefault] = $roles[$role];
            $hasFallback = in_array($family . ' Fallback', array_column($fonts['fallbacks'], 'family'), true);
            if (!$isDefault || $hasFallback) {
                $vars[] = '--fc-font-' . $role . ':' . self::fontStack($family, $isDefault || $role === 'heading', $hasFallback);
            }
        }
        if ($vars !== []) {
            $fonts['css'] = '<style id="fc-font-vars">html:root{' . implode(';', $vars) . '}</style>' . "\n";
        }

        return $fonts;
    }

    /**
     * The <style> block for webFonts()'s local families: their @font-face rules and the Arial fallback faces.
     * $base is the app's web path (FrontendApplication::basePath()), so a nested route resolves the files too.
     *
     * @param array{faces?:list<array<string,mixed>>,fallbacks?:list<array<string,mixed>>} $fonts
     */
    public static function fontFaceCss(array $fonts, string $base): string
    {
        $css = '';
        foreach ($fonts['faces'] ?? [] as $face) {
            $css .= '@font-face{font-family:"' . $face['family'] . '";font-style:normal;font-weight:' . $face['weight']
                . ';font-display:' . $face['display'] . ';src:url("' . self::fontFileUrl($face, $base) . '") format("woff2")}';
        }
        foreach ($fonts['fallbacks'] ?? [] as $fallback) {
            [$size, $ascent, $descent, $lineGap] = $fallback['metrics'];
            $css .= '@font-face{font-family:"' . $fallback['family'] . '";font-weight:' . $fallback['weight'] . ';src:' . $fallback['src']
                . ';size-adjust:' . $size . '%;ascent-override:' . $ascent . '%;descent-override:' . $descent . '%;line-gap-override:' . $lineGap . '%}';
        }

        return $css === '' ? '' : '<style id="fc-font-faces">' . $css . '</style>' . "\n";
    }

    /**
     * The local font files worth a preload on a page whose first screen is set in $roles ('heading', 'text',
     * 'ui'). Each is the @font-face rule's own URL, version included: any other spelling is fetched twice.
     *
     * @param array{faces?:list<array<string,mixed>>} $fonts
     * @param list<string> $roles
     * @return list<string>
     */
    public static function fontPreloads(array $fonts, array $roles, string $base): array
    {
        $hrefs = [];
        foreach ($fonts['faces'] ?? [] as $face) {
            if (array_intersect($face['preload'], $roles) !== []) {
                $hrefs[] = self::fontFileUrl($face, $base);
            }
        }

        return $hrefs;
    }

    /**
     * Queues one family: its own files where the frontend has them all ($local), else a Google stylesheet - a link,
     * or an @import for a family that must stay out of Cloudflare's reach. A family already queued, and System
     * UI (nothing to load), are skipped. A local family is held as its weights until its roles are known.
     *
     * @param array{links:list<string>,imports:list<string>,css:string} $fonts
     * @param array<string, bool|string> $loaded
     * @param array{family:string,weights:string,import?:bool} $font
     */
    private static function addFontStylesheet(array &$fonts, array &$loaded, array $font, bool $local = false): void
    {
        $family = $font['family'];
        if ($family === '' || isset($loaded[$family])) {
            return;
        }
        if ($local && self::hasLocalFiles($family)) {
            $loaded[$family] = $font['weights'] === '' ? '400' : $font['weights'];

            return;
        }
        $loaded[$family] = true;
        $fonts[empty($font['import']) ? 'links' : 'imports'][] = self::googleFontsUrl($family, $font['weights']);
    }

    /**
     * One local family's @font-face rows and, where it sets text, its Arial fallback faces. A family that only
     * sets headings hides its words until the file is in (block): no system face is near League Gothic's width.
     *
     * @param array{faces:list<array<string,mixed>>,fallbacks:list<array<string,mixed>>} $fonts
     * @param list<string> $weights
     * @param list<string> $roles
     */
    private static function addLocalFaces(array &$fonts, string $family, array $weights, array $roles): void
    {
        $spec = self::LOCAL_FONTS[$family];
        $setsText = array_intersect($roles, ['text', 'ui']) !== [];
        $face = ['family' => $family, 'display' => $setsText ? 'swap' : 'block'];

        if (isset($spec['variable'])) {
            $range = count($weights) > 1 ? min($weights) . ' ' . max($weights) : $weights[0];
            $fonts['faces'][] = $face + self::localFontFile($spec['variable']) + ['weight' => $range, 'preload' => $roles];
        }
        foreach ($spec['files'] ?? [] as $weight => $file) {
            if (in_array((string) $weight, $weights, true)) {
                $firstPaint = array_values(array_filter($roles, static fn (string $role): bool => in_array((string) $weight, self::FIRST_PAINT_WEIGHTS[$role], true)));
                $fonts['faces'][] = $face + self::localFontFile($file) + ['weight' => (string) $weight, 'preload' => $firstPaint];
            }
        }

        if ($setsText && isset($spec['fallback'])) {
            [$regular, $bold] = $spec['fallback'];
            $fonts['fallbacks'][] = ['family' => $family . ' Fallback', 'weight' => '100 500', 'src' => 'local("Arial"),local("ArialMT")', 'metrics' => $regular];
            $fonts['fallbacks'][] = ['family' => $family . ' Fallback', 'weight' => '600 900', 'src' => 'local("Arial Bold"),local("Arial-BoldMT")', 'metrics' => $bold];
        }
    }

    /**
     * Whether every file a LOCAL_FONTS family lists is on disk. A deploy that missed one (a new file left out of
     * the commit) would set that family's words in Arial for good, so the family loads from Google until it is in.
     */
    private static function hasLocalFiles(string $family): bool
    {
        $spec = self::LOCAL_FONTS[$family] ?? null;
        if ($spec === null) {
            return false;
        }
        foreach (array_merge(array_values($spec['files'] ?? []), isset($spec['variable']) ? [$spec['variable']] : []) as $file) {
            if (!is_file(FC_ROOT . '/' . self::LOCAL_FONT_DIR . $file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * A local font file's app-relative path and its cache-busting version.
     *
     * @return array{src:string,v:int}
     */
    private static function localFontFile(string $file): array
    {
        $src = self::LOCAL_FONT_DIR . $file;

        return ['src' => $src, 'v' => (int) @filemtime(FC_ROOT . '/' . $src)];
    }

    /** @param array<string,mixed> $face */
    private static function fontFileUrl(array $face, string $base): string
    {
        return $base . '/' . $face['src'] . '?v=' . $face['v'];
    }

    /**
     * The saved slug for one font key, or that key's default when the slug is not in the list.
     *
     * @param array<string, string> $branding
     */
    private static function fontSlug(array $branding, string $key): string
    {
        $slug = (string) ($branding[$key] ?? '');

        return isset(self::FONTS[$slug]) ? $slug : self::defaults()[$key];
    }

    /**
     * The family first, then a fallback while it loads: the device's own font for text, any sans-serif for headings
     * and for style.css's own defaults. $fallback names the family's size-matched Arial face ahead of that.
     */
    private static function fontStack(string $family, bool $heading, bool $fallback = false): string
    {
        if ($family === '') {
            return self::SYSTEM_STACK;
        }

        return '"' . $family . '", ' . ($fallback ? '"' . $family . ' Fallback", ' : '') . ($heading ? 'sans-serif' : self::SYSTEM_STACK);
    }

    private static function googleFontsUrl(string $family, string $weights): string
    {
        return 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $family)
            . ($weights === '' ? '' : ':wght@' . $weights) . '&display=swap';
    }

    /**
     * @param array<string, mixed> $branding
     * @return array{ok:bool,branding?:array<string,string>,error?:string}
     */
    public static function save(array $branding): array
    {
        $defaults = self::defaults();
        $next = $defaults;

        foreach ($branding as $key => $value) {
            if (!is_string($key) || !array_key_exists($key, $defaults)) {
                continue;
            }
            $normalized = self::normalizeField($key, (string) $value);
            if ($normalized === null) {
                return [
                    'ok' => false,
                    'error' => 'Invalid value for ' . $key . '.',
                ];
            }
            $next[$key] = $normalized;
        }

        $result = ThemeSettings::writeSection('branding', $next);
        if (!$result['ok']) {
            return $result;
        }

        return [
            'ok' => true,
            'branding' => $next,
        ];
    }

    /**
     * @return array{ok:bool,branding:array<string,string>,defaults:array<string,string>,schema:array<string,array{label:string,type:string,placeholder?:string,help?:string,choices?:array<string,array{label:string,stack:string,href:string}>}>,updatedAt?:string}
     */
    public static function apiPayload(): array
    {
        return [
            'ok' => true,
            'branding' => self::get(),
            'defaults' => self::defaults(),
            'schema' => self::schema(),
            'updatedAt' => ThemeSettings::updatedAt(),
        ];
    }
}
