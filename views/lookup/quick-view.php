<?php
/**
 * @var array<string, mixed> $page
 * @var callable $h
 */

declare(strict_types=1);

$qv = is_array($page['quick_view'] ?? null) ? $page['quick_view'] : [];
$req = is_array($page['request'] ?? null) ? $page['request'] : [];
$closeUrl = fc_lookup_url($req, ['view' => null]);
$gallery = is_array($qv['gallery'] ?? null) ? $qv['gallery'] : [];
$permalink = (string) ($qv['permalink'] ?? '');
$sku = (string) ($qv['sku'] ?? '');
$name = (string) ($qv['name'] ?? '');
$categories = is_array($qv['categories'] ?? null) ? $qv['categories'] : [];
$tags = is_array($qv['tags'] ?? null) ? $qv['tags'] : [];
$attributes = is_array($qv['attributes'] ?? null) ? $qv['attributes'] : [];
$qvDescription = trim((string) ($qv['description'] ?? ''));
$rating = (float) ($qv['rating'] ?? 0);
$stockStatus = (string) ($qv['stock_status'] ?? '');
$stockLabel = (string) ($qv['stock_label'] ?? '');
$priceHtml = (string) ($qv['price_html'] ?? '');

$pricePlain = '';
if (($qv['sale_price'] ?? '') !== '') {
    $pricePlain = (string) $qv['sale_price'];
} elseif (($qv['regular_price'] ?? '') !== '') {
    $pricePlain = (string) $qv['regular_price'];
} else {
    $pricePlain = trim(html_entity_decode(strip_tags($priceHtml), ENT_QUOTES, 'UTF-8'));
}

$categoryNames = array_values(array_filter(array_map(static function ($c) {
    return is_array($c) ? trim((string) ($c['name'] ?? '')) : '';
}, $categories)));
$tagNames = array_values(array_filter(array_map(static function ($t) {
    return is_array($t) ? trim((string) ($t['name'] ?? '')) : '';
}, $tags)));

$descriptionPlain = trim(preg_replace(
    '/\s+/u',
    ' ',
    html_entity_decode(strip_tags($qvDescription), ENT_QUOTES | ENT_HTML5, 'UTF-8')
) ?? '');

/**
 * @param callable $h
 */
$renderRow = static function (
    string $label,
    string $copyValue,
    string $displayHtml,
    callable $h,
    string $copyLabel = 'Copied'
): void {
    if ($copyValue === '' && trim(strip_tags($displayHtml)) === '') {
        return;
    }
    $copyLabel = $copyLabel !== '' ? $copyLabel : ($label . ' copied');
    ?>
    <tr class="fc-lookup-qv-table__row">
        <th scope="row" class="fc-lookup-qv-table__label"><?php echo $h($label); ?></th>
        <td class="fc-lookup-qv-table__value">
            <div class="fc-lookup-qv-table__cell">
                <div class="fc-lookup-qv-table__text"><?php echo $displayHtml; ?></div>
                <?php if ($copyValue !== '') : ?>
                <button
                    type="button"
                    class="fc-lookup-qv-table__copy"
                    data-fc-lookup-copy="<?php echo $h($copyValue); ?>"
                    data-fc-lookup-copy-label="<?php echo $h($copyLabel); ?>"
                    title="Copy <?php echo $h($label); ?>"
                    aria-label="Copy <?php echo $h($label); ?>"
                >
                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                </button>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
};
?>
<div class="fc-lookup-qv" data-fc-lookup-qv role="dialog" aria-modal="true" aria-labelledby="fc-lookup-qv-title">
    <a class="fc-lookup-qv__backdrop" href="<?php echo $h($closeUrl); ?>" aria-label="Close quick view"></a>
    <div class="fc-lookup-qv__panel">
        <a class="fc-lookup-qv__close fencing-modal-close" href="<?php echo $h($closeUrl); ?>" aria-label="Close"></a>

        <?php if ($permalink !== '') : ?>
        <div class="fc-lookup-qv__actions">
            <a class="btn btn-sm btn-orange fw-semibold" href="<?php echo $h($permalink); ?>" target="_blank" rel="noopener">
                View product
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
            <button type="button" class="btn btn-sm btn-dark fw-semibold" data-fc-lookup-copy="<?php echo $h($permalink); ?>" data-fc-lookup-copy-label="Link copied">
                Copy link
            </button>
        </div>
        <?php endif; ?>

        <div class="fc-lookup-qv__body">
            <div class="fc-lookup-qv__main">
            <div class="fc-lookup-qv__grid">
                <div class="fc-lookup-qv__gallery">
                    <div class="fc-lookup-qv__gallery-stage" data-fc-lookup-qv-magnify>
                        <?php if ($gallery !== []) : ?>
                        <img class="fc-lookup-qv__hero" src="<?php echo $h((string) $gallery[0]); ?>" alt="<?php echo $h($name); ?>" data-fc-lookup-qv-hero>
                        <?php endif; ?>
                    </div>
                    <?php if (count($gallery) > 1) : ?>
                    <div class="fc-lookup-qv__thumbs">
                        <?php foreach ($gallery as $i => $img) : ?>
                        <button type="button" class="fc-lookup-qv__thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-fc-lookup-qv-thumb="<?php echo $h((string) $img); ?>">
                            <img src="<?php echo $h((string) $img); ?>" alt="" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="fc-lookup-qv__info">
                    <h2 id="fc-lookup-qv-title" class="fc-lookup-qv__title"><?php echo $h($name); ?></h2>

                    <table class="fc-lookup-qv-table">
                        <tbody>
                            <?php
                            if ($sku !== '') {
                                $renderRow('SKU', $sku, $h($sku), $h, 'SKU copied');
                            }

                            if ($priceHtml !== '' || $pricePlain !== '') {
                                $renderRow(
                                    'Price',
                                    $pricePlain,
                                    $priceHtml !== '' ? $priceHtml : $h($pricePlain),
                                    $h,
                                    'Price copied'
                                );
                            }

                            if ($stockLabel !== '') {
                                $stockHtml = '<span class="fc-lookup-qv-table__stock fc-lookup-stock fc-lookup-stock--'
                                    . $h($stockStatus)
                                    . '"><span class="fc-lookup-stock__dot" aria-hidden="true"></span>'
                                    . $h($stockLabel)
                                    . '</span>';
                                $renderRow('Stock', $stockLabel, $stockHtml, $h, 'Stock copied');
                            }

                            if ($rating > 0) {
                                $ratingText = number_format($rating, 1);
                                $reviews = (int) ($qv['review_count'] ?? 0);
                                $ratingCopy = $ratingText . ($reviews > 0 ? ' (' . $reviews . ' reviews)' : '');
                                $ratingHtml = '<span class="fc-lookup-qv-table__rating"><i class="fa-solid fa-star" aria-hidden="true"></i> '
                                    . $h($ratingText)
                                    . ($reviews > 0 ? ' <span class="fc-lookup-muted">(' . $reviews . ')</span>' : '')
                                    . '</span>';
                                $renderRow('Rating', $ratingCopy, $ratingHtml, $h, 'Rating copied');
                            }

                            if ($categoryNames !== []) {
                                $catsCopy = implode(', ', $categoryNames);
                                $catsHtml = '<ul class="fc-lookup-qv-table__chips">';
                                foreach ($categoryNames as $catName) {
                                    $catsHtml .= '<li>' . $h($catName) . '</li>';
                                }
                                $catsHtml .= '</ul>';
                                $renderRow('Categories', $catsCopy, $catsHtml, $h, 'Categories copied');
                            }

                            if ($tagNames !== []) {
                                $tagsCopy = implode(', ', $tagNames);
                                $tagsHtml = '<ul class="fc-lookup-qv-table__chips fc-lookup-qv-table__chips--muted">';
                                foreach ($tagNames as $tagName) {
                                    $tagsHtml .= '<li>' . $h($tagName) . '</li>';
                                }
                                $tagsHtml .= '</ul>';
                                $renderRow('Tags', $tagsCopy, $tagsHtml, $h, 'Tags copied');
                            }

                            foreach ($attributes as $attr) {
                                if (!is_array($attr)) {
                                    continue;
                                }
                                $label = trim((string) ($attr['label'] ?? ''));
                                $value = trim((string) ($attr['value'] ?? ''));
                                if ($label === '' || $value === '') {
                                    continue;
                                }
                                $renderRow($label, $value, $h($value), $h, $label . ' copied');
                            }
                            ?>
                        </tbody>
                    </table>

                    <?php if ($qvDescription !== '') : ?>
                    <section class="fc-lookup-qv__description" data-fc-lookup-qv-description>
                        <div class="fc-lookup-qv__description-head">
                            <h3 class="fc-lookup-qv__section-title">Description</h3>
                            <?php if ($descriptionPlain !== '') : ?>
                            <button
                                type="button"
                                class="fc-lookup-qv-table__copy fc-lookup-qv__description-copy"
                                data-fc-lookup-copy="<?php echo $h($descriptionPlain); ?>"
                                data-fc-lookup-copy-label="Description copied"
                                title="Copy description"
                                aria-label="Copy description"
                            >
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="fc-lookup-qv__description-body">
                            <?php echo $qvDescription; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>

        <?php if (!empty($qv['related'])) : ?>
        <div class="fc-lookup-qv__related">
            <h3 class="fc-lookup-qv__section-title">Related products</h3>
            <div class="fc-lookup-qv__related-grid">
                <?php foreach ($qv['related'] as $related) : ?>
                <?php
                    if (!is_array($related)) {
                        continue;
                    }
                    $rid = (int) ($related['id'] ?? 0);
                    $rSlug = (string) ($related['slug'] ?? '');
                    $rImg = (string) (($related['images'][0] ?? '') ?: '');
                    $rQuick = fc_lookup_url($req, ['view' => $rSlug !== '' ? $rSlug : (string) $rid]);
                ?>
                <a class="fc-lookup-related" href="<?php echo $h($rQuick); ?>" title="<?php echo $h((string) ($related['name'] ?? '')); ?>">
                    <?php if ($rImg !== '') : ?>
                    <img src="<?php echo $h($rImg); ?>" alt="<?php echo $h((string) ($related['name'] ?? '')); ?>" loading="lazy">
                    <?php endif; ?>
                    <span class="fc-lookup-related__name"><?php echo $h((string) ($related['name'] ?? '')); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
