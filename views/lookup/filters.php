<?php
/**
 * @var array<string, mixed> $page
 * @var callable $h
 */

declare(strict_types=1);

$req = is_array($page['request'] ?? null) ? $page['request'] : [];
$facets = is_array($page['facets'] ?? null) ? $page['facets'] : [];
$price = is_array($facets['price'] ?? null) ? $facets['price'] : ['min' => 0, 'max' => 0];
$priceMinBound = (float) ($price['min'] ?? 0);
$priceMaxBound = (float) ($price['max'] ?? 0);
if ($priceMaxBound <= $priceMinBound) {
    $priceMaxBound = $priceMinBound + 1;
}
$minPriceVal = $req['min_price'] !== null ? (string) $req['min_price'] : '';
$maxPriceVal = $req['max_price'] !== null ? (string) $req['max_price'] : '';
$colorFacet = is_array($facets['color'] ?? null) ? $facets['color'] : ['terms' => []];
$selectedColors = array_map('intval', $req['color'] ?? []);
$selectedCats = array_map('intval', $req['cat'] ?? []);
$selectedTags = array_map('intval', $req['tag'] ?? []);
$selectedStock = array_map('strval', $req['stock'] ?? []);
$selectedAttrs = is_array($req['attr'] ?? null) ? $req['attr'] : [];

$priceActive = ($req['min_price'] !== null || $req['max_price'] !== null) ? 1 : 0;
$saleActive = (($req['sale'] ?? '') !== '') ? 1 : 0;
$featuredActive = !empty($req['featured']) ? 1 : 0;

/**
 * @param list<array<string, mixed>> $nodes
 * @param list<int> $selected
 */
$renderCats = static function (array $nodes, array $selected, callable $h, int $depth = 0) use (&$renderCats): void {
    foreach ($nodes as $node) {
        $id = (int) ($node['id'] ?? 0);
        $checked = in_array($id, $selected, true);
        $children = is_array($node['children'] ?? null) ? $node['children'] : [];
        $hasChildren = $children !== [];
        ?>
        <div class="fc-lookup-cat<?php echo $hasChildren ? ' has-children' : ''; ?>" style="--depth:<?php echo (int) $depth; ?>">
            <div class="fc-lookup-cat__row">
                <label class="fc-lookup-check">
                    <input type="checkbox" name="cat[]" value="<?php echo (int) $id; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                    <span class="fc-lookup-check__label"><?php echo $h((string) ($node['name'] ?? '')); ?></span>
                    <span class="fc-lookup-check__count"><?php echo number_format((int) ($node['count'] ?? 0)); ?></span>
                </label>
                <?php if ($hasChildren) : ?>
                <button type="button" class="fc-lookup-cat__toggle" data-fc-lookup-cat-toggle aria-expanded="false" aria-label="Toggle subcategories">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php if ($hasChildren) : ?>
            <div class="fc-lookup-cat__children" hidden>
                <?php $renderCats($children, $selected, $h, $depth + 1); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
};

$groupHead = static function (
    string $label,
    int $count,
    string $clearUrl,
    bool $expanded
) use ($h): void {
    $badgeClass = 'fc-lookup-filter-group__badge' . ($count > 0 ? ' is-active' : ' is-muted');
    $expandedAttr = $expanded ? 'true' : 'false';
    ?>
    <div class="fc-lookup-filter-group__head">
        <button type="button" class="fc-lookup-filter-group__toggle" data-fc-lookup-group-toggle aria-expanded="<?php echo $expandedAttr; ?>">
            <span class="<?php echo $h($badgeClass); ?>"><?php echo number_format(max(0, $count)); ?></span>
            <span class="fc-lookup-filter-group__label"><?php echo $h($label); ?></span>
        </button>
        <?php if ($count > 0 && $clearUrl !== '') : ?>
        <a class="fc-lookup-filter-group__clear" href="<?php echo $h($clearUrl); ?>">Clear</a>
        <?php endif; ?>
        <button type="button" class="fc-lookup-filter-group__chevron" data-fc-lookup-group-toggle aria-expanded="<?php echo $expandedAttr; ?>" aria-label="Toggle <?php echo $h($label); ?>">
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
    </div>
    <?php
};

$clearOverrides = static function (array $overrides) use ($req): string {
    $overrides['page'] = null;
    $overrides['view'] = null;

    return fc_lookup_url($req, $overrides);
};
?>
<form class="fc-lookup-filters" method="get" action="<?php echo $h(fc_lookup_base_path()); ?>" data-fc-lookup-filters>
    <input type="hidden" name="layout" value="<?php echo $h((string) ($req['layout'] ?? 'grid')); ?>">
    <input type="hidden" name="orderby" value="<?php echo $h((string) ($req['orderby'] ?? 'default')); ?>">
    <?php
        $catalog = is_array($page['catalog'] ?? null) ? $page['catalog'] : [];
        $defaultPerPage = (int) ($catalog['resultsPerPage'] ?? 12);
    ?>
    <input type="hidden" name="per_page" value="<?php echo (int) ($req['per_page'] ?? $defaultPerPage); ?>">

    <div class="fc-lookup-filters__head">
        <h2 class="fc-lookup-filters__title">Filters</h2>
        <div class="fc-lookup-filters__head-actions">
            <button type="button" class="fc-lookup-filters__close" data-fc-lookup-filters-close aria-label="Close filters">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="fc-lookup-filters__search">
        <label class="fc-lookup-field fc-lookup-field--search">
            <span class="fc-lookup-field__control">
                <span class="fc-lookup-field__addon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="q" class="fc-settings-field" value="<?php echo $h((string) ($req['q'] ?? '')); ?>" placeholder="Search name or SKU…" autocomplete="off">
            </span>
        </label>
        <?php if (($req['q'] ?? '') !== '') : ?>
        <a class="fc-lookup-filters__search-clear" href="<?php echo $h($clearOverrides(['q' => null])); ?>">Clear</a>
        <?php endif; ?>
    </div>

    <div class="fc-lookup-filters__stack">
        <div class="fc-lookup-filter-group is-open" data-fc-lookup-filter-group>
            <?php $groupHead('Categories', count($selectedCats), $clearOverrides(['cat' => null]), true); ?>
            <div class="fc-lookup-filter-group__body">
                <label class="fc-lookup-field">
                    <input type="search" class="fc-settings-field" placeholder="Find category…" data-fc-lookup-cat-search autocomplete="off">
                </label>
                <div class="fc-lookup-options" data-fc-lookup-cat-list>
                    <?php
                    $cats = is_array($facets['categories'] ?? null) ? $facets['categories'] : [];
                    if ($cats === []) {
                        echo '<p class="fc-lookup-muted">No categories found.</p>';
                    } else {
                        $renderCats($cats, $selectedCats, $h);
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="fc-lookup-filter-group is-open" data-fc-lookup-filter-group>
            <?php $groupHead('Price', $priceActive, $clearOverrides(['min_price' => null, 'max_price' => null]), true); ?>
            <div class="fc-lookup-filter-group__body">
                <?php
                    $currency = function_exists('fc_lookup_currency_symbol') ? fc_lookup_currency_symbol() : '$';
                    $sliderMin = $minPriceVal !== '' ? (float) $minPriceVal : $priceMinBound;
                    $sliderMax = $maxPriceVal !== '' ? (float) $maxPriceVal : $priceMaxBound;
                    $sliderMin = max($priceMinBound, min($priceMaxBound, $sliderMin));
                    $sliderMax = max($priceMinBound, min($priceMaxBound, $sliderMax));
                    if ($sliderMin > $sliderMax) {
                        $tmp = $sliderMin;
                        $sliderMin = $sliderMax;
                        $sliderMax = $tmp;
                    }
                    $span = max(1.0, $priceMaxBound - $priceMinBound);
                    $pctMin = (($sliderMin - $priceMinBound) / $span) * 100;
                    $pctMax = (($sliderMax - $priceMinBound) / $span) * 100;
                    $fmtPrice = static function (float $n) use ($currency): string {
                        return $currency . number_format($n, 0);
                    };
                ?>
                <div class="fc-lookup-price" data-fc-lookup-price
                    data-min="<?php echo $h((string) (int) $priceMinBound); ?>"
                    data-max="<?php echo $h((string) (int) $priceMaxBound); ?>"
                    data-currency="<?php echo $h($currency); ?>"
                    style="--price-min: <?php echo $h(number_format($pctMin, 2, '.', '')); ?>%; --price-max: <?php echo $h(number_format($pctMax, 2, '.', '')); ?>%;">
                    <div class="fc-lookup-price__display" aria-live="polite">
                        <span class="fc-lookup-price__display-val" data-fc-lookup-price-selected-min><?php echo $h($fmtPrice($sliderMin)); ?></span>
                        <span class="fc-lookup-price__display-sep" aria-hidden="true">–</span>
                        <span class="fc-lookup-price__display-val" data-fc-lookup-price-selected-max><?php echo $h($fmtPrice($sliderMax)); ?></span>
                    </div>

                    <div class="fc-lookup-price__slider" data-fc-lookup-price-range>
                        <div class="fc-lookup-price__rail" aria-hidden="true">
                            <div class="fc-lookup-price__fill"></div>
                        </div>
                        <input type="range"
                            class="fc-lookup-price__thumb fc-lookup-price__thumb--min"
                            min="<?php echo (int) $priceMinBound; ?>"
                            max="<?php echo (int) $priceMaxBound; ?>"
                            step="1"
                            value="<?php echo (int) round($sliderMin); ?>"
                            data-fc-lookup-price-min-range
                            aria-label="Minimum price">
                        <input type="range"
                            class="fc-lookup-price__thumb fc-lookup-price__thumb--max"
                            min="<?php echo (int) $priceMinBound; ?>"
                            max="<?php echo (int) $priceMaxBound; ?>"
                            step="1"
                            value="<?php echo (int) round($sliderMax); ?>"
                            data-fc-lookup-price-max-range
                            aria-label="Maximum price">
                    </div>

                    <div class="fc-lookup-price__bounds" aria-hidden="true">
                        <span><?php echo $h($fmtPrice($priceMinBound)); ?></span>
                        <span><?php echo $h($fmtPrice($priceMaxBound)); ?></span>
                    </div>

                    <div class="fc-lookup-price__inputs">
                        <label class="fc-lookup-field">
                            <span class="fc-lookup-field__label">Min</span>
                            <span class="fc-lookup-field__control">
                                <span class="fc-lookup-field__addon"><?php echo $h($currency); ?></span>
                                <input type="number"
                                    name="min_price"
                                    class="fc-settings-field"
                                    step="1"
                                    min="<?php echo (int) $priceMinBound; ?>"
                                    max="<?php echo (int) $priceMaxBound; ?>"
                                    value="<?php echo $h($minPriceVal !== '' ? (string) (int) round((float) $minPriceVal) : ''); ?>"
                                    placeholder="<?php echo $h((string) (int) $priceMinBound); ?>"
                                    inputmode="numeric"
                                    data-fc-lookup-price-min-input>
                            </span>
                        </label>
                        <label class="fc-lookup-field">
                            <span class="fc-lookup-field__label">Max</span>
                            <span class="fc-lookup-field__control">
                                <span class="fc-lookup-field__addon"><?php echo $h($currency); ?></span>
                                <input type="number"
                                    name="max_price"
                                    class="fc-settings-field"
                                    step="1"
                                    min="<?php echo (int) $priceMinBound; ?>"
                                    max="<?php echo (int) $priceMaxBound; ?>"
                                    value="<?php echo $h($maxPriceVal !== '' ? (string) (int) round((float) $maxPriceVal) : ''); ?>"
                                    placeholder="<?php echo $h((string) (int) $priceMaxBound); ?>"
                                    inputmode="numeric"
                                    data-fc-lookup-price-max-input>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($colorFacet['terms'])) : ?>
        <?php $colorOpen = $selectedColors !== []; ?>
        <div class="fc-lookup-filter-group<?php echo $colorOpen ? ' is-open' : ''; ?>" data-fc-lookup-filter-group>
            <?php $groupHead('Color', count($selectedColors), $clearOverrides(['color' => null]), $colorOpen); ?>
            <div class="fc-lookup-filter-group__body"<?php echo $colorOpen ? '' : ' hidden'; ?>>
                <div class="fc-lookup-swatches">
                    <?php foreach ($colorFacet['terms'] as $term) : ?>
                    <?php
                        $tid = (int) ($term['id'] ?? 0);
                        $checked = in_array($tid, $selectedColors, true);
                        $hex = (string) ($term['hex'] ?? '#cbd5e1');
                    ?>
                    <label class="fc-lookup-swatch<?php echo $checked ? ' is-selected' : ''; ?>" title="<?php echo $h((string) ($term['name'] ?? '')); ?>">
                        <input type="checkbox" name="color[]" value="<?php echo $tid; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                        <span class="fc-lookup-swatch__chip" style="--swatch:<?php echo $h($hex); ?>"></span>
                        <span class="fc-lookup-swatch__name"><?php echo $h((string) ($term['name'] ?? '')); ?></span>
                        <span class="fc-lookup-swatch__count"><?php echo number_format((int) ($term['count'] ?? 0)); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php foreach (($facets['attributes'] ?? []) as $attrGroup) : ?>
        <?php
            if (!is_array($attrGroup)) {
                continue;
            }
            $attrName = (string) ($attrGroup['name'] ?? '');
            $attrLabel = (string) ($attrGroup['label'] ?? $attrName);
            $terms = is_array($attrGroup['terms'] ?? null) ? $attrGroup['terms'] : [];
            if ($attrName === '' || $terms === []) {
                continue;
            }
            $sel = array_map('intval', $selectedAttrs[$attrName] ?? []);
            $attrOpen = $sel !== [];
            $attrsWithout = $selectedAttrs;
            unset($attrsWithout[$attrName]);
        ?>
        <div class="fc-lookup-filter-group<?php echo $attrOpen ? ' is-open' : ''; ?>" data-fc-lookup-filter-group>
            <?php $groupHead($attrLabel, count($sel), $clearOverrides(['attr' => $attrsWithout !== [] ? $attrsWithout : null]), $attrOpen); ?>
            <div class="fc-lookup-filter-group__body"<?php echo $attrOpen ? '' : ' hidden'; ?>>
                <div class="fc-lookup-options">
                    <?php foreach ($terms as $term) : ?>
                    <?php $tid = (int) ($term['id'] ?? 0); ?>
                    <label class="fc-lookup-check">
                        <input type="checkbox" name="attr[<?php echo $h($attrName); ?>][]" value="<?php echo $tid; ?>" <?php echo in_array($tid, $sel, true) ? 'checked' : ''; ?>>
                        <span class="fc-lookup-check__label"><?php echo $h((string) ($term['name'] ?? '')); ?></span>
                        <span class="fc-lookup-check__count"><?php echo number_format((int) ($term['count'] ?? 0)); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php $availCount = count($selectedStock) + $saleActive + $featuredActive; $availOpen = $availCount > 0; ?>
        <div class="fc-lookup-filter-group<?php echo $availOpen ? ' is-open' : ''; ?>" data-fc-lookup-filter-group>
            <?php $groupHead('Availability', $availCount, $clearOverrides(['stock' => null, 'sale' => null, 'featured' => null]), $availOpen); ?>
            <div class="fc-lookup-filter-group__body"<?php echo $availOpen ? '' : ' hidden'; ?>>
                <p class="fc-lookup-section-label">Stock</p>
                <div class="fc-lookup-inline-checks">
                    <?php foreach (($facets['stock'] ?? []) as $stockOpt) : ?>
                    <?php
                        $val = (string) ($stockOpt['value'] ?? '');
                        $label = (string) ($stockOpt['label'] ?? $val);
                    ?>
                    <label class="fc-lookup-check fc-lookup-check--compact">
                        <input type="checkbox" name="stock[]" value="<?php echo $h($val); ?>" <?php echo in_array($val, $selectedStock, true) ? 'checked' : ''; ?>>
                        <span class="fc-lookup-check__label"><?php echo $h($label); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p class="fc-lookup-section-label">Sale</p>
                <div class="fc-lookup-seg" role="group" aria-label="Sale status">
                    <label class="fc-lookup-seg__item">
                        <input type="radio" name="sale" value="" <?php echo ($req['sale'] ?? '') === '' ? 'checked' : ''; ?>>
                        <span>Any</span>
                    </label>
                    <label class="fc-lookup-seg__item">
                        <input type="radio" name="sale" value="on" <?php echo ($req['sale'] ?? '') === 'on' ? 'checked' : ''; ?>>
                        <span>On sale</span>
                    </label>
                    <label class="fc-lookup-seg__item">
                        <input type="radio" name="sale" value="regular" <?php echo ($req['sale'] ?? '') === 'regular' ? 'checked' : ''; ?>>
                        <span>Regular</span>
                    </label>
                </div>

                <label class="fc-lookup-check fc-lookup-check--featured">
                    <input type="checkbox" name="featured" value="1" <?php echo !empty($req['featured']) ? 'checked' : ''; ?>>
                    <span class="fc-lookup-check__label">Featured only</span>
                </label>
            </div>
        </div>

        <?php if (!empty($facets['tags'])) : ?>
        <?php $tagsOpen = $selectedTags !== []; ?>
        <div class="fc-lookup-filter-group<?php echo $tagsOpen ? ' is-open' : ''; ?>" data-fc-lookup-filter-group>
            <?php $groupHead('Tags', count($selectedTags), $clearOverrides(['tag' => null]), $tagsOpen); ?>
            <div class="fc-lookup-filter-group__body"<?php echo $tagsOpen ? '' : ' hidden'; ?>>
                <label class="fc-lookup-field">
                    <input type="search" class="fc-settings-field" placeholder="Find tag…" data-fc-lookup-tag-search autocomplete="off">
                </label>
                <div class="fc-lookup-options" data-fc-lookup-tag-list>
                    <?php foreach ($facets['tags'] as $tag) : ?>
                    <?php $tid = (int) ($tag['id'] ?? 0); ?>
                    <label class="fc-lookup-check" data-fc-lookup-tag-item data-name="<?php echo $h(strtolower((string) ($tag['name'] ?? ''))); ?>">
                        <input type="checkbox" name="tag[]" value="<?php echo $tid; ?>" <?php echo in_array($tid, $selectedTags, true) ? 'checked' : ''; ?>>
                        <span class="fc-lookup-check__label"><?php echo $h((string) ($tag['name'] ?? '')); ?></span>
                        <span class="fc-lookup-check__count"><?php echo number_format((int) ($tag['count'] ?? 0)); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="fc-lookup-filters__actions">
        <button type="submit" class="btn btn-sm btn-orange fw-semibold w-100">Apply</button>
        <?php
        $hasActive = !empty($page['has_active_filters']);
        $clearUrl = (string) ($page['clear_url'] ?? fc_lookup_base_path());
        ?>
        <?php if ($hasActive) : ?>
        <a class="btn btn-sm btn-dark fw-semibold w-100 fc-lookup-filters__clear-all" href="<?php echo $h($clearUrl); ?>">Clear all</a>
        <?php else : ?>
        <button type="button" class="btn btn-sm btn-dark fw-semibold w-100 fc-lookup-filters__clear-all is-muted" disabled aria-disabled="true">Clear all</button>
        <?php endif; ?>
    </div>
</form>
