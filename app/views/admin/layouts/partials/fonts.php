<?php
/**
 * FC Admin — Settings → Branding → Admin font. Printed after the stylesheet whose font stack it
 * replaces: bootstrap.min.css in layouts/main.php, login.css in login.php. Both parts are empty at
 * the default, where Bootstrap's own system stack applies. One family per link, because Cloudflare
 * Fonts on the live zones keeps only a link's first family.
 *
 * @var array{links:list<string>,css:string} $fcFonts BrandingSettings::webFonts('admin')
 */
?>
<?php if ($fcFonts['links'] !== []) : ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php endif; ?>
<?php foreach ($fcFonts['links'] as $fcFontHref) : ?>
    <link rel="stylesheet" href="<?php echo e($fcFontHref); ?>" crossorigin>
<?php endforeach; ?>
<?php echo $fcFonts['css']; ?>
