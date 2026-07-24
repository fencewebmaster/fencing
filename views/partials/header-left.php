<?php
if (!function_exists('fc_branding_get')) {
    require_once dirname(__DIR__, 2) . '/config/branding.php';
}
$fcBranding = fc_branding_get();
?>
<h2 class="fc-header-title"><?php echo htmlspecialchars($fcBranding['appName'], ENT_QUOTES, 'UTF-8'); ?></h2>

<p class="mb-2"><?php echo htmlspecialchars($fcBranding['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>

<a href="<?php echo $_SESSION["site"]['url']; ?>" class="btn btn-sm btn-dark px-3">
	<i class="fa fa-arrow-left"></i> Back to <b><?php echo $_SESSION["site"]['name']; ?></b> Site
</a>
