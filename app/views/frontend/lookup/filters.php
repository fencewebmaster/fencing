<?php
/**
 * Read-only template: LookupPageModel::filtersData() supplies every value —
 * price-slider math, selected filters, group open/count state and all clear
 * URLs. Only the two rendering closures below ($renderCats/$groupHead, which
 * echo markup) stay template-side.
 *
 * @var array<string, mixed> $page
 * @var array<string, mixed> $filters
 * @var callable $h
 */

$req            = $page['request'];
$facets         = $filters['facets'];
$price          = $filters['price'];
$priceMinBound  = $price['min_bound'];
$priceMaxBound  = $price['max_bound'];
$minPriceVal    = $price['min_val'];
$maxPriceVal    = $price['max_val'];
$selectedCats   = $filters['selected_cats'];
$selectedColors = $filters['selected_colors'];
$selectedTags   = $filters['selected_tags'];
$selectedStock  = $filters['selected_stock'];
$colorTerms     = $filters['color_terms'];

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

?>
<form class="fc-lookup-filters" method="get" action="<?php echo $h($filters['action_url']); ?>" data-fc-lookup-filters>
    <input type="hidden" name="layout" value="<?php echo $h((string) ($req['layout'] ?? 'grid')); ?>">
    <input type="hidden" name="orderby" value="<?php echo $h((string) ($req['orderby'] ?? 'default')); ?>">
    <?php
    // Deliberate empty block: the four spaces before its open tag are emitted onto
    // the hidden input's rendered line; deleting the block changes the emitted bytes.
    ?>
    <input type="hidden" name="per_page" value="<?php echo (int) $filters['per_page_hidden']; ?>">

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
        <a class="fc-lookup-filters__search-clear" href="<?php echo $h($filters['clear_urls']['q']); ?>">Clear</a>
        <?php endif; ?>
    </div>

    <div class="fc-lookup-filters__stack">
        <div class="fc-lookup-filter-group is-open" data-fc-lookup-filter-group>
            <?php $groupHead('Categories', $filters['cats_count'], $filters['clear_urls']['cats'], true); ?>
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
            <?php $groupHead('Price', $filters['price_active_count'], $filters['clear_urls']['price'], true); ?>
            <div class="fc-lookup-filter-group__body">
                <?php
                    $currency = $price['currency'];
                    $sliderMin = $price['slider_min'];
                    $sliderMax = $price['slider_max'];
                    $pctMin = $price['pct_min'];
                    $pctMax = $price['pct_max'];
                ?>
                <div class="fc-lookup-price" data-fc-lookup-price
                    data-min="<?php echo $h((string) (int) $priceMinBound); ?>"
                    data-max="<?php echo $h((string) (int) $priceMaxBound); ?>"
                    data-currency="<?php echo $h($currency); ?>"
                    style="--price-min: <?php echo $h(number_format($pctMin, 2, '.', '')); ?>%; --price-max: <?php echo $h(number_format($pctMax, 2, '.', '')); ?>%;">
                    <div class="fc-lookup-price__display" aria-live="polite">
                        <span class="fc-lookup-price__display-val" data-fc-lookup-price-selected-min><?php echo $h($price['display_min']); ?></span>
                        <span class="fc-lookup-price__display-sep" aria-hidden="true">–</span>
                        <span class="fc-lookup-price__display-val" data-fc-lookup-price-selected-max><?php echo $h($price['display_max']); ?></span>
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
                        <span><?php echo $h($price['bound_min_label']); ?></span>
                        <span><?php echo $h($price['bound_max_label']); ?></span>
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

        <?php if ($colorTerms !== []) : ?>
        <?php $colorOpen = $filters['color_open']; ?>
        <div class="fc-lookup-filter-group<?php echo $colorOpen ? ' is-open' : ''; ?>" data-fc-lookup-filter-group>
            <?php $groupHead('Color', $filters['colors_count'], $filters['clear_urls']['color'], $colorOpen); ?>
            <div class="fc-lookup-filter-group__body"<?php echo $colorOpen ? '' : ' hidden'; ?>>
                <div class="fc-lookup-swatches">
                    <?php foreach ($colorTerms as $term) : ?>
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

        <?php foreach ($filters['attr_groups'] as $attrGroup) : ?>
        <?php $attrOpen = $attrGroup['open']; ?>
        <div class="fc-lookup-filter-group<?php echo $attrOpen ? ' is-open' : ''; ?>" data-fc-lookup-filter-group>
            <?php $groupHead($attrGroup['label'], $attrGroup['count'], $attrGroup['clear_url'], $attrOpen); ?>
            <div class="fc-lookup-filter-group__body"<?php echo $attrOpen ? '' : ' hidden'; ?>>
                <div class="fc-lookup-options">
                    <?php foreach ($attrGroup['terms'] as $term) : ?>
                    <?php $tid = (int) ($term['id'] ?? 0); ?>
                    <label class="fc-lookup-check">
                        <input type="checkbox" name="attr[<?php echo $h($attrGroup['name']); ?>][]" value="<?php echo $tid; ?>" <?php echo in_array($tid, $attrGroup['selected'], true) ? 'checked' : ''; ?>>
                        <span class="fc-lookup-check__label"><?php echo $h((string) ($term['name'] ?? '')); ?></span>
                        <span class="fc-lookup-check__count"><?php echo number_format((int) ($term['count'] ?? 0)); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php $availOpen = $filters['avail_open']; ?>
        <div class="fc-lookup-filter-group<?php echo $availOpen ? ' is-open' : ''; ?>" data-fc-lookup-filter-group>
            <?php $groupHead('Availability', $filters['avail_count'], $filters['clear_urls']['avail'], $availOpen); ?>
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
        <?php $tagsOpen = $filters['tags_open']; ?>
        <div class="fc-lookup-filter-group<?php echo $tagsOpen ? ' is-open' : ''; ?>" data-fc-lookup-filter-group>
            <?php $groupHead('Tags', $filters['tags_count'], $filters['clear_urls']['tags'], $tagsOpen); ?>
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
        $hasActive = $filters['has_active'];
        $clearUrl = $filters['clear_all_url'];
        ?>
        <?php if ($hasActive) : ?>
        <a class="btn btn-sm btn-dark fw-semibold w-100 fc-lookup-filters__clear-all" href="<?php echo $h($clearUrl); ?>">Clear all</a>
        <?php else : ?>
        <button type="button" class="btn btn-sm btn-dark fw-semibold w-100 fc-lookup-filters__clear-all is-muted" disabled aria-disabled="true">Clear all</button>
        <?php endif; ?>
    </div>
</form>
