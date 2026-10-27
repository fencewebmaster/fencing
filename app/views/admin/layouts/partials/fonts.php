<?php
/**
 * FC Admin — Settings → Branding → Admin font. Printed after the stylesheet whose font stack it
 * replaces: bootstrap.min.css in layouts/main.php, login.css in login.php. Every part is empty at
 * the default, where Bootstrap's own system stack applies. One family per link, because Cloudflare
 * Fonts on the live zones keeps only a link's first family; League Gothic comes as an @import, which
 * Cloudflare leaves alone (keep the raw & in it, not &amp;).
 *
 * @var array{links:list<string>,imports:list<string>,css:string} $fcFonts BrandingSettings::webFonts('admin')
 */
?>
<?php if ($fcFonts['links'] !== [] || $fcFonts['imports'] !== []) : ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php endif; ?>
<?php foreach ($fcFonts['links'] as $fcFontHref) : ?>
    <link rel="stylesheet" href="<?php echo e($fcFontHref); ?>" crossorigin>
<?php endforeach; ?>
<?php foreach ($fcFonts['imports'] as $fcFontHref) : ?>
    <style>@import url("<?php echo $fcFontHref; ?>");</style>
<?php endforeach; ?>
<?php echo $fcFonts['css']; ?>
