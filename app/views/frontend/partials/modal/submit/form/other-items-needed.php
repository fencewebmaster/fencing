<?php

use Fc\Admin\Services\CartBuilderService;
use Fc\Admin\Settings\PlannerOptionSettings;

// Pre-select from session so other forms sharing this <form> don't wipe saved choices
$fc_other_items_session = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
$fc_other_items_nothing_extra = isset($fc_other_items_session['nothing_extra']) ? (string) $fc_other_items_session['nothing_extra'] : '';
$fc_other_items_extra = is_array($fc_other_items_session['extra'] ?? null)
    ? $fc_other_items_session['extra']
    : CartBuilderService::convertInputs((string) ($fc_other_items_session['extra'] ?? ''));
if (!is_array($fc_other_items_extra)) {
    if (is_string($fc_other_items_extra) && trim($fc_other_items_extra) !== '' && trim($fc_other_items_extra) !== 'nothing') {
        $fc_other_items_extra = array_filter(array_map('trim', explode(',', $fc_other_items_extra)));
    } else {
        $fc_other_items_extra = [];
    }
}
$fc_other_items_is_nothing = $fc_other_items_nothing_extra === 'nothing'
    || (empty($fc_other_items_extra) && in_array((string) ($fc_other_items_session['extra'] ?? ''), ['nothing', '[]', ''], true));
?>
<div class="fc-other-products fc-form-group">
    <div class="row">
        <input type="hidden" name="extra" value="">
        <?php foreach( PlannerOptionSettings::extraItems() as $extra_item ): ?>
        <?php
        $extra_k = (string) ($extra_item['slug'] ?? '');
        $extra_v = (string) ($extra_item['label'] ?? '');
        $extra_image = trim((string) ($extra_item['image'] ?? ''));
        if ($extra_image === '') {
            $extra_image = trim((string) ($extra_item['imageDefault'] ?? ''));
        }
        $extra_image_is_remote = preg_match('#^https?://#i', $extra_image) === 1 || str_starts_with($extra_image, 'data:');
        $extra_image_exists = $extra_image !== ''
            && ($extra_image_is_remote || is_file(FC_ROOT . '/' . ltrim($extra_image, '/')));
        $extra_image_url = $extra_image_exists
            ? ($extra_image_is_remote ? $extra_image : url() . $extra_image)
            : '';
        $extra_checked = !$fc_other_items_is_nothing && in_array($extra_k, $fc_other_items_extra, true);
        // The tile caption is a <label for>, so the input needs an id; slugs are unique per item
        // but are author-entered, hence the scrub.
        $extra_id = 'fc-extra-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $extra_k);
        ?>
        <div class="col-md-3 col-sm-4 col-6">
            <div class="fc-form-check-img fc-rounded mb-3">
                <label class="fc-form-check">
                <?php if ($extra_image_url !== '') : ?>
                <img class="fc-rounded" src="<?php echo e($extra_image_url); ?>">
                <?php else : ?>
                <div class="fc-empty-img fc-rounded"><span>No image</span></div>
                <?php endif; ?>
                <input type="checkbox" id="<?php echo e($extra_id); ?>" name="extra[]" value="<?php echo e($extra_k); ?>"<?php echo $extra_checked ? ' checked' : ''; ?>>
                </label>
                <label class="d-block text-center fw-bold py-2 small" for="<?php echo e($extra_id); ?>"><?php echo e($extra_v); ?></label>
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
                    <input type="radio" id="fc-extra-nothing" name="nothing_extra" value="nothing"<?php echo $fc_other_items_is_nothing ? ' checked' : ''; ?>>
                </label>
                <label class="d-block text-center fw-bold py-2 small" for="fc-extra-nothing">NIL - Just Looking</label>
            </div>
        </div>

    </div>
</div>