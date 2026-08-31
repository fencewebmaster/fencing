<?php

use Fc\Admin\Settings\BrandingSettings;

$fcBranding = BrandingSettings::get();
?>
<h2 class="fc-header-title"><?php echo e($fcBranding['appName']); ?></h2>

<p class="mb-2"><?php echo e($fcBranding['tagline']); ?></p>

<a href="<?php echo $_SESSION["site"]['url']; ?>" class="btn btn-sm btn-dark px-3 fc-back-to-site">
	<i class="fa fa-arrow-left"></i> Back to <b><?php echo $_SESSION["site"]['name']; ?></b> Site
</a>
