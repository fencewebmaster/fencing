<?php
/**
 * FC Admin — sidebar brand + site switcher.
 *
 * @var string $fcAdminBase
 * @var string $fcAppBase
 * @var string $fcAdminRoute
 * @var array<string, mixed> $fcBranding
 */

declare(strict_types=1);

$fcAdminSites = \Fc\Admin\Services\AdminSiteRegistry::sitesList();
$fcAdminCurrentSite = \Fc\Admin\Services\AdminSiteRegistry::currentSite();
$fcAdminHomeSite = \Fc\Admin\Services\AdminSiteRegistry::homeSite();
$fcSiteHomeKey = is_array($fcAdminHomeSite) ? (string) ($fcAdminHomeSite['key'] ?? '') : '';
$fcSiteSwitchToken = \Fc\Admin\Services\AuthService::csrfToken();
$fcSiteCurrentKey = is_array($fcAdminCurrentSite) ? (string) ($fcAdminCurrentSite['key'] ?? '') : '';
$fcSiteCurrentName = is_array($fcAdminCurrentSite) ? (string) ($fcAdminCurrentSite['name'] ?? 'FC Admin') : 'FC Admin';
$fcSiteCurrentLogo = is_array($fcAdminCurrentSite) ? (string) ($fcAdminCurrentSite['logo'] ?? '') : '';
$fcSiteCurrentLogoUrl = ($fcSiteCurrentLogo !== '' && isset($fcAppBase))
    ? rtrim((string) $fcAppBase, '/') . '/' . ltrim($fcSiteCurrentLogo, '/')
    : '';
$fcBrandingLogoUrl = isset($fcAppBase)
    ? \Fc\Admin\Services\BrandingSettings::logoUrl((string) $fcAppBase, is_array($fcBranding ?? null) ? $fcBranding : null)
    : '';
// Prefer the admin branding logo in the header mark; fall back to the active site logo.
$fcHeaderLogoUrl = $fcBrandingLogoUrl !== '' ? $fcBrandingLogoUrl : $fcSiteCurrentLogoUrl;
$fcSiteRedirectPath = rtrim((string) ($fcAdminBase ?? ''), '/') . '/dashboard';

$fcFormatSiteUrl = static function (string $domain): string {
    $domain = trim($domain);
    if ($domain === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $domain)) {
        return $domain;
    }
    if ($domain === 'localhost' || str_starts_with($domain, '127.0.0.1')) {
        return 'http://' . $domain;
    }

    return 'https://' . $domain;
};

$fcFormatSiteHost = static function (string $domain) use ($fcFormatSiteUrl): string {
    $url = $fcFormatSiteUrl($domain);
    if ($url === '') {
        return '';
    }
    $host = parse_url($url, PHP_URL_HOST);
    if (is_string($host) && $host !== '') {
        $port = parse_url($url, PHP_URL_PORT);
        if ($port) {
            return $host . ':' . $port;
        }

        return $host;
    }

    return preg_replace('#^https?://#i', '', $url) ?: $url;
};

$fcBrandSubtitle = trim((string) (($fcBranding['appName'] ?? '') !== ''
    ? ($fcBranding['appName'] ?? '')
    : ($fcBranding['tagline'] ?? '')));
if ($fcBrandSubtitle === '') {
    $fcBrandSubtitle = 'Fencing Calculator';
}

// Original (login) site gets a dedicated quick-switch; remove it from the regular list.
$fcAdminSites = array_values(array_filter(
    $fcAdminSites,
    static fn(array $site): bool => (string) ($site['key'] ?? '') !== $fcSiteHomeKey
));
$fcSiteCount = count($fcAdminSites);

$fcHomeName = is_array($fcAdminHomeSite) ? (string) ($fcAdminHomeSite['name'] ?? 'Home') : 'Home';
$fcHomeDomain = is_array($fcAdminHomeSite) ? (string) ($fcAdminHomeSite['domain'] ?? '') : '';
$fcHomeHost = $fcFormatSiteHost($fcHomeDomain);
$fcHomeUrl = $fcFormatSiteUrl($fcHomeDomain);
$fcHomeSupplier = is_array($fcAdminHomeSite)
    ? strtoupper(trim((string) ($fcAdminHomeSite['supplier'] ?? '')))
    : '';
$fcHomeLogo = is_array($fcAdminHomeSite) ? (string) ($fcAdminHomeSite['logo'] ?? '') : '';
$fcHomeLogoUrl = ($fcHomeLogo !== '' && isset($fcAppBase))
    ? rtrim((string) $fcAppBase, '/') . '/' . ltrim($fcHomeLogo, '/')
    : '';
$fcHomeIsCurrent = $fcSiteHomeKey !== '' && $fcSiteHomeKey === $fcSiteCurrentKey;
$fcHomeSwitchHref = $fcSiteHomeKey !== ''
    ? rtrim((string) $fcAdminBase, '/') . '/users/switch-site?' . http_build_query([
        'site' => $fcSiteHomeKey,
        '_token' => $fcSiteSwitchToken,
        'redirect' => $fcSiteRedirectPath,
    ])
    : '';
?>
<div class="fc-sidebar-brand" data-fc-sidebar-site-switcher>
    <span class="fc-sidebar-brand__mark<?php echo $fcHeaderLogoUrl !== '' ? ' fc-sidebar-brand__mark--image' : ''; ?>" aria-hidden="true">
        <?php if ($fcHeaderLogoUrl !== '') : ?>
        <img class="fc-sidebar-brand__logo-img" src="<?php echo htmlspecialchars($fcHeaderLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" decoding="async">
        <?php else : ?>
        <i class="fa-solid fa-border-all"></i>
        <?php endif; ?>
    </span>
    <div class="fc-sidebar-brand__text">
        <button
            type="button"
            class="fc-sidebar-site__toggle"
            id="fc-sidebar-site-toggle"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="fc-sidebar-site-menu"
            aria-label="Switch site. Current: <?php echo htmlspecialchars($fcSiteCurrentName, ENT_QUOTES, 'UTF-8'); ?>"
        >
            <span class="fc-sidebar-site__label">
                <span class="fc-sidebar-brand__title"><?php echo htmlspecialchars($fcSiteCurrentName, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="fc-sidebar-brand__subtitle"><?php echo htmlspecialchars($fcBrandSubtitle, ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
            <span class="fc-sidebar-site__caret-wrap" aria-hidden="true">
                <i class="fa-solid fa-chevron-down fc-sidebar-site__caret"></i>
            </span>
        </button>
        <div
            class="fc-sidebar-site__menu"
            id="fc-sidebar-site-menu"
            role="listbox"
            aria-labelledby="fc-sidebar-site-toggle"
            hidden
        >
            <div class="fc-sidebar-site__menu-head">
                <span class="fc-sidebar-site__menu-title">Switch site</span>
                <?php if ($fcSiteCount > 0) : ?>
                <span class="fc-sidebar-site__menu-count"><?php echo (int) $fcSiteCount; ?></span>
                <?php endif; ?>
            </div>

            <?php if ($fcSiteHomeKey !== '' || $fcSiteCount > 0) : ?>
            <div class="fc-sidebar-site__search-wrap">
                <i class="fa-solid fa-magnifying-glass fc-sidebar-site__search-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    class="fc-sidebar-site__search"
                    id="fc-sidebar-site-search"
                    placeholder="Search sites…"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="Search sites"
                >
            </div>
            <?php endif; ?>

            <?php if ($fcSiteHomeKey !== '' && $fcHomeSwitchHref !== '') : ?>
            <div class="fc-sidebar-site__quick" data-fc-site-section="home">
                <span class="fc-sidebar-site__quick-label">Main site</span>
                <a
                    href="<?php echo htmlspecialchars($fcHomeSwitchHref, ENT_QUOTES, 'UTF-8'); ?>"
                    class="fc-sidebar-site__item fc-sidebar-site__item--home<?php echo $fcHomeIsCurrent ? ' is-active' : ''; ?>"
                    role="option"
                    aria-selected="<?php echo $fcHomeIsCurrent ? 'true' : 'false'; ?>"
                    title="<?php echo htmlspecialchars(trim($fcHomeName . ($fcHomeSupplier !== '' ? ' (' . $fcHomeSupplier . ')' : '') . ($fcHomeUrl !== '' ? ' — ' . $fcHomeUrl : '')), ENT_QUOTES, 'UTF-8'); ?>"
                    data-fc-site-search="<?php echo htmlspecialchars(strtolower(trim($fcHomeName . ' ' . $fcHomeHost . ' ' . $fcHomeDomain . ' ' . $fcHomeSupplier)), ENT_QUOTES, 'UTF-8'); ?>"
                    data-nav-full="1"
                    <?php echo $fcHomeIsCurrent ? ' tabindex="-1"' : ''; ?>
                >
                    <span class="fc-sidebar-site__item-logo<?php echo $fcHomeLogoUrl !== '' ? ' fc-sidebar-site__item-logo--image' : ''; ?>" aria-hidden="true">
                        <?php if ($fcHomeLogoUrl !== '') : ?>
                        <img src="<?php echo htmlspecialchars($fcHomeLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" decoding="async">
                        <?php else : ?>
                        <i class="fa-solid fa-house"></i>
                        <?php endif; ?>
                    </span>
                    <span class="fc-sidebar-site__item-text">
                        <span class="fc-sidebar-site__item-name"><?php echo htmlspecialchars($fcHomeName, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($fcHomeHost !== '') : ?>
                        <span class="fc-sidebar-site__item-domain"><?php echo htmlspecialchars($fcHomeHost, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="fc-sidebar-site__item-meta">
                        <?php if ($fcHomeIsCurrent) : ?>
                        <span class="fc-sidebar-site__item-badge">Current</span>
                        <?php else : ?>
                        <span class="fc-sidebar-site__item-badge fc-sidebar-site__item-badge--quick">Main</span>
                        <?php endif; ?>
                        <?php if ($fcHomeSupplier !== '') : ?>
                        <span class="fc-sidebar-site__item-supplier"><?php echo htmlspecialchars($fcHomeSupplier, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($fcSiteCount > 0) : ?>
            <div class="fc-sidebar-site__menu-list" data-fc-site-section="other">
                <span class="fc-sidebar-site__list-label">Other sites (<?php echo (int) $fcSiteCount; ?>)</span>
                <?php foreach ($fcAdminSites as $site) : ?>
                <?php
                $siteKey = (string) ($site['key'] ?? '');
                $siteName = (string) ($site['name'] ?? $siteKey);
                $siteDomain = (string) ($site['domain'] ?? '');
                $siteUrl = $fcFormatSiteUrl($siteDomain);
                $siteHost = $fcFormatSiteHost($siteDomain);
                $siteSupplier = strtoupper(trim((string) ($site['supplier'] ?? '')));
                $siteLogo = (string) ($site['logo'] ?? '');
                $siteLogoUrl = $siteLogo !== ''
                    ? rtrim((string) $fcAppBase, '/') . '/' . ltrim($siteLogo, '/')
                    : '';
                $isCurrent = $siteKey !== '' && $siteKey === $fcSiteCurrentKey;
                $switchHref = rtrim((string) $fcAdminBase, '/') . '/users/switch-site?' . http_build_query([
                    'site' => $siteKey,
                    '_token' => $fcSiteSwitchToken,
                    'redirect' => $fcSiteRedirectPath,
                ]);
                $siteSearch = strtolower(trim($siteName . ' ' . $siteHost . ' ' . $siteDomain . ' ' . $siteKey . ' ' . $siteSupplier));
                ?>
                <a
                    href="<?php echo htmlspecialchars($switchHref, ENT_QUOTES, 'UTF-8'); ?>"
                    class="fc-sidebar-site__item<?php echo $isCurrent ? ' is-active' : ''; ?>"
                    role="option"
                    aria-selected="<?php echo $isCurrent ? 'true' : 'false'; ?>"
                    title="<?php echo htmlspecialchars(trim($siteName . ($siteSupplier !== '' ? ' (' . $siteSupplier . ')' : '') . ($siteUrl !== '' ? ' — ' . $siteUrl : '')), ENT_QUOTES, 'UTF-8'); ?>"
                    data-fc-site-search="<?php echo htmlspecialchars($siteSearch, ENT_QUOTES, 'UTF-8'); ?>"
                    data-nav-full="1"
                    <?php echo $isCurrent ? ' tabindex="-1"' : ''; ?>
                >
                    <span class="fc-sidebar-site__item-logo<?php echo $siteLogoUrl !== '' ? ' fc-sidebar-site__item-logo--image' : ''; ?>" aria-hidden="true">
                        <?php if ($siteLogoUrl !== '') : ?>
                        <img src="<?php echo htmlspecialchars($siteLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" decoding="async">
                        <?php else : ?>
                        <i class="fa-solid fa-globe"></i>
                        <?php endif; ?>
                    </span>
                    <span class="fc-sidebar-site__item-text">
                        <span class="fc-sidebar-site__item-name"><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($siteHost !== '') : ?>
                        <span class="fc-sidebar-site__item-domain"><?php echo htmlspecialchars($siteHost, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="fc-sidebar-site__item-meta">
                        <?php if ($isCurrent) : ?>
                        <span class="fc-sidebar-site__item-badge">Current</span>
                        <?php endif; ?>
                        <?php if ($siteSupplier !== '') : ?>
                        <span class="fc-sidebar-site__item-supplier"><?php echo htmlspecialchars($siteSupplier, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <?php if (!$isCurrent) : ?>
                        <i class="fa-solid fa-arrow-right fc-sidebar-site__item-go" aria-hidden="true"></i>
                        <?php endif; ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($fcSiteHomeKey !== '' || $fcSiteCount > 0) : ?>
            <p class="fc-sidebar-site__empty" hidden>No other sites match</p>
            <?php endif; ?>
        </div>
    </div>
    <button
        type="button"
        id="fc-admin-sidebar-close"
        class="fc-sidebar-brand__close lg:hidden"
        aria-label="Close menu"
    >
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
    <button
        type="button"
        id="fc-admin-sidebar-collapse"
        class="fc-sidebar-collapse-toggle"
        aria-label="Collapse sidebar"
        aria-expanded="true"
        aria-controls="fc-admin-sidebar"
        title="Collapse sidebar"
    >
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>
</div>
