<?php
/**
 * Google Fonts for the public pages, linked straight from the HTML: as an @import inside a local
 * stylesheet the request could only start once that file had downloaded. One family per link:
 * Cloudflare Fonts on the live zones rewrites a Google Fonts link but keeps only its first family=,
 * which left fencesperth.com with Inter and no League Gothic or Poppins. League Gothic is an inline
 * @import instead, which Cloudflare Fonts leaves alone: its rewrite of that link points the
 * width-axis font at a /wght/ file that returns 502 (keep the raw & in it, not &amp;). The families follow
 * Settings → Branding (BrandingSettings::webFonts()): Inter, Poppins and League Gothic at the defaults, otherwise
 * the chosen ones plus the block that points style.css's --fc-font-text, --fc-font-ui and --fc-font-heading at them.
 * The links are crossorigin so the project plan's
 * PNG/PDF capture (modern-screenshot) can read their @font-face rules; without it every capture fell back to a
 * system face, which also wrapped the section head's text. The preconnect matches, or the CORS request opens its own.
 */

use Fc\Admin\Settings\BrandingSettings;

$fcWebFonts = BrandingSettings::webFonts('frontend');
?>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php foreach ($fcWebFonts['links'] as $fcWebFontHref) : ?>
<link rel="stylesheet" href="<?php echo e($fcWebFontHref); ?>" crossorigin>
<?php endforeach; ?>
<?php foreach ($fcWebFonts['imports'] as $fcWebFontHref) : ?>
<style>@import url("<?php echo $fcWebFontHref; ?>");</style>
<?php endforeach; ?>
<?php echo $fcWebFonts['css']; ?>
