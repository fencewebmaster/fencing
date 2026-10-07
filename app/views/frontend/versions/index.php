<?php
/**
 * Public version history (/versions).
 *
 * Rendered by Controllers\Frontend\VersionsController from VersionsPageModel::build(). A standalone
 * page: no planner partials, no script, one stylesheet. notes_html is HtmlSanitizer output and is
 * the only unescaped value.
 *
 * @var string                     $fcVersionsTitle
 * @var string                     $fcVersionsDescription
 * @var string                     $fcVersionsRobots      Settings → SEO rules (SeoSettings::robots()).
 * @var string                     $fcVersionsLang
 * @var string                     $fcVersionsCanonical
 * @var string                     $fcVersionsAppName
 * @var string                     $fcVersionsLogoUrl     '' when Branding has no logo.
 * @var string                     $fcVersionsFaviconUrl  '' when Branding has no favicon.
 * @var string                     $fcVersionsPlannerUrl
 * @var list<array<string, mixed>> $fcVersionsFilters     label, count, href, is_active
 * @var bool                       $fcVersionsShowFilters false while only one type has releases
 * @var list<array<string, mixed>> $fcVersionsReleases    anchor, version_label, type, type_label, level, level_label, date_label, date_iso, is_latest, notes_html
 * @var string                     $fcVersionsEmptyText
 * @var string                     $fcVersionsMoreUrl     '' when every release is listed
 * @var int                        $fcVersionsRemaining
 * @var string                     $fcVersionsYear
 */

declare(strict_types=1);

use Fc\Admin\Settings\ThemeSettings;
?><!DOCTYPE html>
<html lang="<?php echo e($fcVersionsLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($fcVersionsTitle); ?></title>
    <meta name="description" content="<?php echo e($fcVersionsDescription); ?>">
    <meta name="robots" content="<?php echo e($fcVersionsRobots); ?>">
    <link rel="canonical" href="<?php echo e($fcVersionsCanonical); ?>">
    <?php if ($fcVersionsFaviconUrl !== '') : ?>
    <link rel="icon" href="<?php echo e($fcVersionsFaviconUrl); ?>">
    <?php endif; ?>
    <?php echo ThemeSettings::cssBlock(); ?>
    <?php include view_path('frontend.partials.webfonts'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('public/assets/css/frontend/versions.css')); ?>">
</head>
<body class="fc-vh">
    <header class="fc-vh-header">
        <div class="fc-vh-shell fc-vh-header__inner">
            <a class="fc-vh-brand" href="<?php echo e($fcVersionsPlannerUrl); ?>">
                <?php if ($fcVersionsLogoUrl !== '') : ?>
                <img class="fc-vh-brand__logo" src="<?php echo e($fcVersionsLogoUrl); ?>" alt="<?php echo e($fcVersionsAppName); ?>" decoding="async">
                <?php else : ?>
                <span class="fc-vh-brand__name"><?php echo e($fcVersionsAppName); ?></span>
                <?php endif; ?>
            </a>
            <a class="fc-vh-header__link" href="<?php echo e($fcVersionsPlannerUrl); ?>">
                <span>Open planner</span>
                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 3h7v7M13 3 4 12" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </header>

    <main class="fc-vh-shell fc-vh-main">
        <div class="fc-vh-intro">
            <p class="fc-vh-intro__eyebrow">Release history</p>
            <h1 class="fc-vh-intro__title">Version History</h1>
            <p class="fc-vh-intro__text">What has changed in <?php echo e($fcVersionsAppName); ?>, newest release first.</p>
        </div>

        <?php if ($fcVersionsShowFilters) : ?>
        <nav class="fc-vh-filters" aria-label="Filter releases by type">
            <?php foreach ($fcVersionsFilters as $filter) : ?>
            <a
                class="fc-vh-filters__item"
                href="<?php echo e((string) $filter['href']); ?>"
                <?php echo $filter['is_active'] ? 'aria-current="page"' : ''; ?>
            >
                <span><?php echo e((string) $filter['label']); ?></span>
                <span class="fc-vh-filters__count"><?php echo (int) $filter['count']; ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <?php if ($fcVersionsReleases === []) : ?>
        <div class="fc-vh-empty">
            <p class="fc-vh-empty__title">Nothing to show yet</p>
            <p class="fc-vh-empty__text"><?php echo e($fcVersionsEmptyText); ?></p>
        </div>
        <?php else : ?>
        <ol class="fc-vh-timeline">
            <?php foreach ($fcVersionsReleases as $release) : ?>
            <li class="fc-vh-release" id="<?php echo e((string) $release['anchor']); ?>">
                <div class="fc-vh-release__when">
                    <time datetime="<?php echo e((string) $release['date_iso']); ?>"><?php echo e((string) $release['date_label']); ?></time>
                </div>
                <article class="fc-vh-release__card">
                    <header class="fc-vh-release__head">
                        <h2 class="fc-vh-release__version"><?php echo e((string) $release['version_label']); ?></h2>
                        <div class="fc-vh-release__tags">
                            <span class="fc-vh-tag fc-vh-tag--<?php echo e((string) $release['type']); ?>"><?php echo e((string) $release['type_label']); ?></span>
                            <span class="fc-vh-tag fc-vh-tag--level"><?php echo e((string) $release['level_label']); ?></span>
                            <?php if ($release['is_latest']) : ?>
                            <span class="fc-vh-tag fc-vh-tag--latest">Latest</span>
                            <?php endif; ?>
                        </div>
                    </header>
                    <?php if ($release['notes_html'] !== '') : ?>
                    <div class="fc-vh-notes"><?php echo $release['notes_html']; ?></div>
                    <?php endif; ?>
                </article>
            </li>
            <?php endforeach; ?>
        </ol>

        <?php if ($fcVersionsMoreUrl !== '') : ?>
        <p class="fc-vh-more">
            <a class="fc-vh-more__link" href="<?php echo e($fcVersionsMoreUrl); ?>">Show older releases (<?php echo (int) $fcVersionsRemaining; ?>)</a>
        </p>
        <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer class="fc-vh-footer">
        <div class="fc-vh-shell">&copy; <?php echo e($fcVersionsYear); ?> <?php echo e($fcVersionsAppName); ?></div>
    </footer>
</body>
</html>
