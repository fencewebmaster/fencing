<?php

declare(strict_types=1);

namespace Fc\Admin\Settings;

use Fc\Admin\Services\SiteRegistryService;

/**
 * FC SEO settings — search engine visibility, how the planner is listed in search results, which
 * public pages may be indexed, social sharing cards, structured data and webmaster verification
 * (saved to writable/theme.json as seo).
 */
final class SeoSettings
{
    /** The planner's structured data prices it in this currency; every site sells in Australia. */
    public const CURRENCY = 'AUD';

    /** Roughly where Google truncates a title and a description in its results. */
    public const TITLE_LIMIT = 60;
    public const DESCRIPTION_LIMIT = 160;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'searchEngineVisible' => true,
            'siteName' => '',
            'siteLogo' => '',
            'titleTemplate' => '{app_name} – Fence Cost & Materials List | {site_name}',
            'descriptionTemplate' => 'Plan your fence online with {site_name}. Choose a fence style, enter your measurements and get a full materials list and cost estimate – free.',
            'indexPlanner' => true,
            'indexLookup' => false,
            'canonicalEnabled' => true,
            'canonicalUrl' => '',
            'language' => 'en-AU',
            'maxImagePreview' => 'large',
            'maxSnippet' => -1,
            'socialEnabled' => true,
            'socialTitle' => '',
            'socialDescription' => '',
            'socialImage' => '',
            'twitterCard' => 'summary_large_image',
            'twitterSite' => '',
            'schemaEnabled' => true,
            'schemaUrl' => '',
            'schemaLogo' => '',
            'schemaPhone' => '',
            'schemaEmail' => '',
            'schemaSameAs' => [],
            'verifyGoogle' => '',
            'verifyBing' => '',
            'verifyPinterest' => '',
        ];
    }

    /**
     * @return array<string, string> BCP 47 tag => label
     */
    public static function languageChoices(): array
    {
        return [
            'en-AU' => 'English (Australia)',
            'en-NZ' => 'English (New Zealand)',
            'en-GB' => 'English (United Kingdom)',
            'en-US' => 'English (United States)',
        ];
    }

    /**
     * @return array<string, string> max-image-preview value => label
     */
    public static function imagePreviewChoices(): array
    {
        return [
            'large' => 'Large',
            'standard' => 'Standard',
            'none' => 'None',
        ];
    }

    /**
     * @return array<string, string> twitter:card value => label
     */
    public static function twitterCardChoices(): array
    {
        return [
            'summary_large_image' => 'Large image',
            'summary' => 'Small image',
        ];
    }

    /**
     * Webmaster tools that verify ownership with a <meta> tag on the planner.
     *
     * @return array<string, array{label:string, meta:string, hint:string}> setting key => service
     */
    public static function verificationServices(): array
    {
        return [
            'verifyGoogle' => [
                'label' => 'Google Search Console',
                'meta' => 'google-site-verification',
                'hint' => 'Settings → Ownership verification → HTML tag.',
            ],
            'verifyBing' => [
                'label' => 'Bing Webmaster Tools',
                'meta' => 'msvalidate.01',
                'hint' => 'Add a site → HTML Meta Tag.',
            ],
            'verifyPinterest' => [
                'label' => 'Pinterest',
                'meta' => 'p:domain_verify',
                'hint' => 'Settings → Claimed accounts → Websites → Add HTML tag.',
            ],
        ];
    }

    /**
     * Placeholders the title and description accept.
     *
     * @return array<string, string> token => what it stands for
     */
    public static function placeholders(): array
    {
        return [
            '{app_name}' => 'App name from Branding',
            '{site_name}' => 'Site name',
            '{tagline}' => 'Tagline from Branding',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return self::normalize(array_merge(self::defaults(), ThemeSettings::section('seo')));
    }

    /**
     * The whitelist. A missing or malformed value falls back to its default (blank for the optional
     * fields), so a hand-edited theme.json can never print a broken tag.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $defaults = self::defaults();

        $title = self::text($input['titleTemplate'] ?? '', 200);
        $description = self::text($input['descriptionTemplate'] ?? '', 500);

        $normalized = [
            'searchEngineVisible' => self::bool($input['searchEngineVisible'] ?? null, $defaults['searchEngineVisible']),
            'siteName' => self::text($input['siteName'] ?? '', 120),
            'siteLogo' => self::imagePath($input['siteLogo'] ?? ''),
            'titleTemplate' => $title !== '' ? $title : $defaults['titleTemplate'],
            'descriptionTemplate' => $description !== '' ? $description : $defaults['descriptionTemplate'],
            'indexPlanner' => self::bool($input['indexPlanner'] ?? null, $defaults['indexPlanner']),
            'indexLookup' => self::bool($input['indexLookup'] ?? null, $defaults['indexLookup']),
            'canonicalEnabled' => self::bool($input['canonicalEnabled'] ?? null, $defaults['canonicalEnabled']),
            'canonicalUrl' => self::absoluteUrl($input['canonicalUrl'] ?? ''),
            'language' => self::choice($input['language'] ?? null, self::languageChoices(), $defaults['language']),
            'maxImagePreview' => self::choice($input['maxImagePreview'] ?? null, self::imagePreviewChoices(), $defaults['maxImagePreview']),
            'maxSnippet' => self::snippetLength($input['maxSnippet'] ?? null, $defaults['maxSnippet']),
            'socialEnabled' => self::bool($input['socialEnabled'] ?? null, $defaults['socialEnabled']),
            'socialTitle' => self::text($input['socialTitle'] ?? '', 200),
            'socialDescription' => self::text($input['socialDescription'] ?? '', 500),
            'socialImage' => self::imagePath($input['socialImage'] ?? ''),
            'twitterCard' => self::choice($input['twitterCard'] ?? null, self::twitterCardChoices(), $defaults['twitterCard']),
            'twitterSite' => self::twitterHandle($input['twitterSite'] ?? ''),
            'schemaEnabled' => self::bool($input['schemaEnabled'] ?? null, $defaults['schemaEnabled']),
            'schemaUrl' => self::absoluteUrl($input['schemaUrl'] ?? ''),
            'schemaLogo' => self::imagePath($input['schemaLogo'] ?? ''),
            'schemaPhone' => self::phone($input['schemaPhone'] ?? ''),
            'schemaEmail' => self::email($input['schemaEmail'] ?? ''),
            'schemaSameAs' => self::urlList($input['schemaSameAs'] ?? []),
        ];

        foreach (array_keys(self::verificationServices()) as $key) {
            $normalized[$key] = self::verificationCode($input[$key] ?? '');
        }

        return $normalized;
    }

    /**
     * Why normalize() would drop a submitted value, so a save can say so instead of silently
     * blanking what the admin typed. Null when everything is usable.
     *
     * @param array<string, mixed> $input
     */
    public static function validationError(array $input): ?string
    {
        $checks = [
            'canonicalUrl' => [
                static fn (mixed $value): string => self::absoluteUrl($value),
                'Custom canonical URL must be a full address starting with https://.',
            ],
            'siteLogo' => [
                static fn (mixed $value): string => self::imagePath($value),
                'Site logo must be a PNG, JPG, GIF or WebP from the media library, or a full https:// image address.',
            ],
            'socialImage' => [
                static fn (mixed $value): string => self::imagePath($value),
                'Share image must be a PNG, JPG, GIF or WebP from the media library, or a full https:// image address.',
            ],
            'twitterSite' => [
                static fn (mixed $value): string => self::twitterHandle($value),
                'X username can only use letters, numbers and underscores (15 at most).',
            ],
            'schemaUrl' => [
                static fn (mixed $value): string => self::absoluteUrl($value),
                'Business website must be a full address starting with https://.',
            ],
            'schemaLogo' => [
                static fn (mixed $value): string => self::imagePath($value),
                'Logo must be a PNG, JPG, GIF or WebP from the media library, or a full https:// image address.',
            ],
            'schemaPhone' => [
                static fn (mixed $value): string => self::phone($value),
                'Phone can only use digits, spaces and + ( ) -.',
            ],
            'schemaEmail' => [
                static fn (mixed $value): string => self::email($value),
                'Email is not a valid address.',
            ],
        ];

        foreach ($checks as $key => [$normalizer, $message]) {
            $raw = $input[$key] ?? '';
            if (is_scalar($raw) && trim((string) $raw) !== '' && $normalizer($raw) === '') {
                return $message;
            }
        }

        foreach (self::lines($input['schemaSameAs'] ?? []) as $line) {
            if (self::absoluteUrl($line) === '') {
                return 'Social profile "' . $line . '" is not a full https:// address.';
            }
        }

        foreach (self::verificationServices() as $key => $service) {
            $raw = $input[$key] ?? '';
            if (is_scalar($raw) && trim((string) $raw) !== '' && self::verificationCode($raw) === '') {
                return $service['label'] . ' code does not look right. Paste just its content value, or the whole <meta> tag.';
            }
        }

        return null;
    }

    public static function searchEngineVisible(): bool
    {
        return (bool) self::get()['searchEngineVisible'];
    }

    public static function language(): string
    {
        return (string) self::get()['language'];
    }

    /**
     * Whether this request's site may be indexed at all: the visibility switch, and never on
     * localhost, staging hosts or test paths (SiteRegistryService::searchBlockReason()).
     *
     * @param array<string, mixed>|null $seo
     */
    public static function siteIndexable(?array $seo = null): bool
    {
        $seo ??= self::get();

        return !empty($seo['searchEngineVisible']) && SiteRegistryService::isSearchIndexable();
    }

    /**
     * Robots meta content for a public page. A page kept out of results still lets its links be
     * followed (the lookup's lead to the store); a hidden site or a visitor's own quote shuts both.
     *
     * @param array<string, mixed> $seo
     */
    public static function robots(array $seo, bool $pageIndexable, bool $private = false): string
    {
        if ($private || !self::siteIndexable($seo)) {
            return 'noindex, nofollow';
        }
        if (!$pageIndexable) {
            return 'noindex, follow';
        }

        return 'index, follow, max-image-preview:' . $seo['maxImagePreview'] . ', max-snippet:' . $seo['maxSnippet'];
    }

    /**
     * {site_name} when none is typed: the site registry's name for this host, else the host itself
     * (fencesnewcastle.au has no registry name).
     *
     * @param array<string, mixed>|null $siteInfo
     */
    public static function autoSiteName(?array $siteInfo): string
    {
        $registered = trim((string) ($siteInfo['name'] ?? ''));
        if ($registered !== '') {
            return $registered;
        }

        $host = strtolower((string) parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    /**
     * The typed site name with its placeholders filled; inside it {site_name} is the automatic name,
     * so "{site_name} – {app_name}" builds on it. Blank (or blank once filled) falls back to that name.
     * Mirrored by vars() in public/assets/js/admin/pages/tabs/seo-tab.js — edit in pairs.
     *
     * @param array<string, mixed> $seo
     * @param array<string, mixed>|null $siteInfo
     */
    public static function siteName(array $seo, ?array $siteInfo): string
    {
        $auto = self::autoSiteName($siteInfo);
        if ((string) $seo['siteName'] === '') {
            return $auto;
        }

        $branding = BrandingSettings::get();
        $name = self::renderTemplate((string) $seo['siteName'], [
            'app_name' => (string) $branding['appName'],
            'site_name' => $auto,
            'tagline' => (string) $branding['tagline'],
        ]);

        return $name !== '' ? $name : $auto;
    }

    /**
     * Fill a title or description template. Each placeholder is replaced once (a value that itself
     * reads "{site_name}" stays literal), then a separator stranded at either end is dropped.
     * Mirrored by renderTemplate() in public/assets/js/admin/pages/tabs/seo-tab.js — edit in pairs.
     *
     * @param array{app_name?:string, site_name?:string, tagline?:string} $vars
     */
    public static function renderTemplate(string $template, array $vars): string
    {
        $text = strtr($template, [
            '{app_name}' => (string) ($vars['app_name'] ?? ''),
            '{site_name}' => (string) ($vars['site_name'] ?? ''),
            '{tagline}' => (string) ($vars['tagline'] ?? ''),
        ]);
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim((string) preg_replace('/^[\s|:·•,–—-]+|[\s|:·•,–—-]+$/u', '', $text));
    }

    /**
     * @param array<string, mixed> $seo
     * @return array{ok:bool, seo?:array<string, mixed>, error?:string}
     */
    public static function save(array $seo): array
    {
        $error = self::validationError($seo);
        if ($error !== null) {
            return ['ok' => false, 'error' => $error];
        }

        $next = self::normalize($seo);

        $result = ThemeSettings::writeSection('seo', $next);
        if (!$result['ok']) {
            return $result;
        }

        return [
            'ok' => true,
            'seo' => $next,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function apiPayload(): array
    {
        return [
            'ok' => true,
            'seo' => self::get(),
            'defaults' => self::defaults(),
            'languageChoices' => self::languageChoices(),
            'imagePreviewChoices' => self::imagePreviewChoices(),
            'twitterCardChoices' => self::twitterCardChoices(),
            'verificationServices' => self::verificationServices(),
            'placeholders' => self::placeholders(),
            'updatedAt' => ThemeSettings::updatedAt(),
        ];
    }

    private static function bool(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    /**
     * One line of text: whitespace runs (newlines included) collapse to a space, then it is capped.
     */
    private static function text(mixed $value, int $max): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        $value = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        return mb_strlen($value) > $max ? rtrim(mb_substr($value, 0, $max)) : $value;
    }

    /**
     * @param array<string, string> $choices
     */
    private static function choice(mixed $value, array $choices, string $default): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return array_key_exists($value, $choices) ? $value : $default;
    }

    /**
     * -1 lets Google pick the snippet length, 0 asks for no text snippet, anything else caps it.
     */
    private static function snippetLength(mixed $value, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return max(-1, min(5000, (int) $value));
    }

    private static function absoluteUrl(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || mb_strlen($value) > 500 || !preg_match('#^https?://#i', $value)) {
            return '';
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : '';
    }

    /**
     * An uploaded or bundled image (public/assets/uploads|img/…) or a full http(s) address. Social
     * sites and search engines fetch these from outside, so data: URIs and SVG are refused.
     */
    private static function imagePath(mixed $value): string
    {
        $value = is_scalar($value) ? trim(str_replace('\\', '/', (string) $value)) : '';
        if ($value === '' || mb_strlen($value) > 500 || str_contains($value, "\0")) {
            return '';
        }
        if (preg_match('#^https?://#i', $value)) {
            return filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : '';
        }

        $path = ltrim($value, '/');
        if (str_contains($path, '..') || !preg_match('#^public/assets/(?:uploads|img)/.+\.(?:png|jpe?g|gif|webp)$#i', $path)) {
            return '';
        }

        return $path;
    }

    /**
     * Stored without the @. A pasted profile address (x.com/handle, twitter.com/handle) is cut
     * down to the handle.
     */
    private static function twitterHandle(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if (preg_match('#^(?:https?://)?(?:www\.)?(?:x|twitter)\.com/([^/?\#]+)#i', $value, $match)) {
            $value = $match[1];
        }
        $value = ltrim($value, '@');

        return preg_match('/^[A-Za-z0-9_]{1,15}$/', $value) ? $value : '';
    }

    private static function phone(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) preg_replace('/\s+/', ' ', (string) $value)) : '';

        return preg_match('/^\+?[0-9 ()\-]{3,30}$/', $value) ? $value : '';
    }

    private static function email(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' && mb_strlen($value) <= 254 && filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? $value : '';
    }

    /**
     * Non-blank trimmed lines from a list or a newline-separated string (the textarea's shape).
     *
     * @return list<string>
     */
    private static function lines(mixed $value): array
    {
        if (is_array($value)) {
            $lines = $value;
        } else {
            $lines = preg_split('/\r\n|\r|\n/', is_scalar($value) ? (string) $value : '') ?: [];
        }

        $out = [];
        foreach ($lines as $line) {
            $line = is_scalar($line) ? trim((string) $line) : '';
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function urlList(mixed $value): array
    {
        $urls = [];
        foreach (self::lines($value) as $line) {
            $url = self::absoluteUrl($line);
            if ($url !== '' && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return array_slice($urls, 0, 20);
    }

    /**
     * A pasted <meta … content="…"> tag is cut down to its content value.
     */
    private static function verificationCode(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if (preg_match('/content\s*=\s*(["\'])(.*?)\1/is', $value, $match)) {
            $value = trim($match[2]);
        }

        return preg_match('#^[A-Za-z0-9._:=+/-]{1,200}$#', $value) ? $value : '';
    }
}
