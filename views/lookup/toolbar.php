<?php
/**
 * Header controls: sort, layout, per page.
 *
 * @var array<string, mixed> $page
 * @var callable $h
 */

declare(strict_types=1);

$req = is_array($page['request'] ?? null) ? $page['request'] : [];
$layout = (($req['layout'] ?? 'grid') === 'list') ? 'list' : 'grid';
$orderbyOptions = is_array($page['orderby_options'] ?? null) ? $page['orderby_options'] : [];
$catalog = is_array($page['catalog'] ?? null) ? $page['catalog'] : [];
$defaultPerPage = function_exists('fc_catalog_clamp_results_per_page')
    ? fc_catalog_clamp_results_per_page($catalog['resultsPerPage'] ?? 12)
    : max(1, (int) ($catalog['resultsPerPage'] ?? 12));
$perPageOptions = is_array($page['per_page_options'] ?? null) ? $page['per_page_options'] : (
    function_exists('fc_catalog_results_per_page_choices')
        ? fc_catalog_results_per_page_choices($defaultPerPage)
        : [$defaultPerPage, $defaultPerPage * 2, $defaultPerPage * 3, $defaultPerPage * 4, $defaultPerPage * 5]
);
$currentPerPage = (int) ($req['per_page'] ?? $defaultPerPage);
if (!in_array($currentPerPage, array_map('intval', $perPageOptions), true)) {
    $currentPerPage = $defaultPerPage;
}

$gridUrl = fc_lookup_url($req, ['layout' => 'grid', 'view' => null, 'page' => null]);
$listUrl = fc_lookup_url($req, ['layout' => 'list', 'view' => null, 'page' => null]);
?>
<form class="fc-lookup-toolbar__controls" method="get" action="<?php echo $h(fc_lookup_base_path()); ?>">
    <?php
    if (($req['q'] ?? '') !== '') {
        echo '<input type="hidden" name="q" value="' . $h((string) $req['q']) . '">';
    }
    foreach ($req['cat'] ?? [] as $catId) {
        echo '<input type="hidden" name="cat[]" value="' . (int) $catId . '">';
    }
    if ($req['min_price'] !== null) {
        echo '<input type="hidden" name="min_price" value="' . $h((string) $req['min_price']) . '">';
    }
    if ($req['max_price'] !== null) {
        echo '<input type="hidden" name="max_price" value="' . $h((string) $req['max_price']) . '">';
    }
    foreach ($req['color'] ?? [] as $colorId) {
        echo '<input type="hidden" name="color[]" value="' . (int) $colorId . '">';
    }
    foreach ($req['attr'] ?? [] as $slug => $ids) {
        foreach ((array) $ids as $tid) {
            echo '<input type="hidden" name="attr[' . $h((string) $slug) . '][]" value="' . (int) $tid . '">';
        }
    }
    foreach ($req['stock'] ?? [] as $stock) {
        echo '<input type="hidden" name="stock[]" value="' . $h((string) $stock) . '">';
    }
    if (($req['sale'] ?? '') !== '') {
        echo '<input type="hidden" name="sale" value="' . $h((string) $req['sale']) . '">';
    }
    if (!empty($req['featured'])) {
        echo '<input type="hidden" name="featured" value="1">';
    }
    foreach ($req['tag'] ?? [] as $tagId) {
        echo '<input type="hidden" name="tag[]" value="' . (int) $tagId . '">';
    }
    ?>
    <input type="hidden" name="layout" value="<?php echo $h($layout); ?>" data-fc-lookup-layout-input>

    <label class="fc-lookup-toolbar__field">
        <span class="fc-lookup-toolbar__label">Sort by</span>
        <select name="orderby" class="fc-settings-field" aria-label="Sort by" onchange="this.form.submit()">
            <?php foreach ($orderbyOptions as $value => $label) : ?>
            <option value="<?php echo $h((string) $value); ?>"<?php echo ($req['orderby'] ?? '') === $value ? ' selected' : ''; ?>>
                <?php echo $h((string) $label); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="fc-lookup-view-switch" role="group" aria-label="View mode">
        <a class="fc-lookup-view-switch__btn<?php echo $layout === 'grid' ? ' is-active' : ''; ?>" href="<?php echo $h($gridUrl); ?>" data-fc-lookup-layout="grid" aria-current="<?php echo $layout === 'grid' ? 'true' : 'false'; ?>" title="Grid view">
            <i class="fa-solid fa-table-cells" aria-hidden="true"></i>
            <span class="visually-hidden">Grid</span>
        </a>
        <a class="fc-lookup-view-switch__btn<?php echo $layout === 'list' ? ' is-active' : ''; ?>" href="<?php echo $h($listUrl); ?>" data-fc-lookup-layout="list" aria-current="<?php echo $layout === 'list' ? 'true' : 'false'; ?>" title="List view">
            <i class="fa-solid fa-list" aria-hidden="true"></i>
            <span class="visually-hidden">List</span>
        </a>
    </div>

    <label class="fc-lookup-toolbar__field">
        <span class="fc-lookup-toolbar__label">Per page</span>
        <select name="per_page" class="fc-settings-field" aria-label="Results per page" onchange="this.form.submit()">
            <?php foreach ($perPageOptions as $opt) : ?>
                <option value="<?php echo (int) $opt; ?>"<?php echo $currentPerPage === (int) $opt ? ' selected' : ''; ?>>
                <?php echo (int) $opt; ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
</form>
