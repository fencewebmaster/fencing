<?php require_once dirname(__DIR__, 6) . '/app/src/Helpers/UrlHelper.php'; ?>
<div class="fc-other-products fc-form-group">
    <div class="row">
        <input type="hidden" name="extra" value="">
        <?php foreach( \Fc\Admin\Services\PlannerOptionSettings::extraItems() as $extra_item ): ?>
        <?php
        $extra_k = (string) ($extra_item['slug'] ?? '');
        $extra_v = (string) ($extra_item['label'] ?? '');
        $extra_image = trim((string) ($extra_item['image'] ?? ''));
        if ($extra_image === '') {
            $extra_image = trim((string) ($extra_item['imageDefault'] ?? ''));
        }
        $extra_image_url = preg_match('#^https?://#i', $extra_image) || str_starts_with($extra_image, 'data:')
            ? $extra_image
            : \Fc\Admin\Helpers\UrlHelper::baseUrl() . $extra_image;
        ?>
        <div class="col-md-3 col-sm-4 col-6">
            <div class="fc-form-check-img fc-rounded mb-3">
                <label class="fc-form-check">
                <img class="fc-rounded" src="<?php echo htmlspecialchars($extra_image_url, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="checkbox" name="extra[]" value="<?php echo htmlspecialchars($extra_k, ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <div class="text-center fw-bold py-2 small"><?php echo htmlspecialchars($extra_v, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="col-md-3 col-sm-4 col-6">
            <div class="fc-form-check-img fc-form-check-empty fc-rounded mb-3">
                <label class="fc-form-check">
                    <div class="fc-empty-img">
                        <span>Nothing Extra<br>
                        Just Fencing</span>
                    </div>
                    <input type="radio" name="nothing_extra" value="nothing">
                </label>
                <div class="text-center fw-bold py-2 small">NIL - Just Looking</div>
            </div>
        </div>

    </div>
</div>