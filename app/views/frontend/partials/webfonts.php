<?php
/**
 * Web fonts for the public pages (BrandingSettings::webFonts()). The default trio (Inter, Poppins, League Gothic) is
 * served by the app itself: its @font-face rules are printed here, with a preload for the files the including page
 * first paints in. From Google those files only began downloading once every stylesheet was in, so each page painted
 * in a fallback face and then changed shape (Nov 2026). Their URLs stand on FrontendApplication::basePath(), not
 * asset(): /lookup/view/{slug} includes this partial too.
 *
 * Any other family chosen in Settings → Branding still comes from Google Fonts, linked straight from the HTML: as an
 * @import inside a local stylesheet the request could only start once that file had downloaded. One family per link:
 * Cloudflare Fonts on the live zones rewrites a Google Fonts link but keeps only its first family=. A family flagged
 * 'import' is an inline @import instead, which Cloudflare Fonts leaves alone (keep the raw & in it, not &amp;). The
 * links are crossorigin so the project plan's PNG/PDF capture (modern-screenshot) can read their @font-face rules;
 * without it every capture fell back to a system face. The preconnect matches, or the CORS request opens its own.
 *
 * @var list<string> $fcWebFontPreload Optional. The roles this page's first screen is set in ('heading', 'text',
 *                                     'ui'): their local files are preloaded. None when unset.
 */

use Fc\Admin\Core\FrontendApplication;
use Fc\Admin\Settings\BrandingSettings;

$fcWebFonts = BrandingSettings::webFonts('frontend');
$fcWebFontBase = FrontendApplication::basePath();
$fcWebFontPreload = $fcWebFontPreload ?? [];
?>
<?php foreach (BrandingSettings::fontPreloads($fcWebFonts, $fcWebFontPreload, $fcWebFontBase) as $fcWebFontHref) : ?>
<link rel="preload" as="font" type="font/woff2" href="<?php echo e($fcWebFontHref); ?>" crossorigin>
<?php endforeach; ?>
<?php if ($fcWebFonts['links'] !== [] || $fcWebFonts['imports'] !== []) : ?>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php endif; ?>
<?php foreach ($fcWebFonts['links'] as $fcWebFontHref) : ?>
<link rel="stylesheet" href="<?php echo e($fcWebFontHref); ?>" crossorigin>
<?php endforeach; ?>
<?php foreach ($fcWebFonts['imports'] as $fcWebFontHref) : ?>
<style>@import url("<?php echo $fcWebFontHref; ?>");</style>
<?php endforeach; ?>
<?php echo BrandingSettings::fontFaceCss($fcWebFonts, $fcWebFontBase); ?>
<?php echo $fcWebFonts['css']; ?>
