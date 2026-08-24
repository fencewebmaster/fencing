<?php require_once dirname(__DIR__, 6) . '/app/src/Helpers/UrlHelper.php'; ?>
<?php
// Pre-select from the session so this modal's DOM state always matches the customer's
// last saved choice — without this, submitting any other form sharing this <form> (e.g.
// the project-plan "Customer Details" save) would resubmit these fields as unchecked and
// silently wipe out a previously saved selection.
$fc_other_items_session = isset($_SESSION['fc_data']) ? $_SESSION['fc_data'] : [];
$fc_other_items_nothing_extra = isset($fc_other_items_session['nothing_extra']) ? (string) $fc_other_items_session['nothing_extra'] : '';
$fc_other_items_extra = is_array($fc_other_items_session['extra'] ?? null)
    ? $fc_other_items_session['extra']
    : \Fc\Admin\Services\CartBuilderService::convertInputs((string) ($fc_other_items_session['extra'] ?? ''));
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
        $extra_checked = !$fc_other_items_is_nothing && in_array($extra_k, $fc_other_items_extra, true);
        ?>
        <div class="col-md-3 col-sm-4 col-6">
            <div class="fc-form-check-img fc-rounded mb-3">
                <label class="fc-form-check">
                <img class="fc-rounded" src="<?php echo htmlspecialchars($extra_image_url, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="checkbox" name="extra[]" value="<?php echo htmlspecialchars($extra_k, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $extra_checked ? ' checked' : ''; ?>>
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
                    <input type="radio" name="nothing_extra" value="nothing"<?php echo $fc_other_items_is_nothing ? ' checked' : ''; ?>>
                </label>
                <div class="text-center fw-bold py-2 small">NIL - Just Looking</div>
            </div>
        </div>

    </div>
</div>