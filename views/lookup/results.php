<?php
/**
 * @var array<string, mixed> $page
 * @var callable $h
 */

declare(strict_types=1);

$req = is_array($page['request'] ?? null) ? $page['request'] : [];
$products = is_array($page['products'] ?? null) ? $page['products'] : [];
$layout = (($req['layout'] ?? 'grid') === 'list') ? 'list' : 'grid';

$badgeLabels = [
    'sale' => 'Sale',
    'featured' => 'Featured',
    'new' => 'New',
    'outofstock' => 'Out of stock',
];
?>
<div class="fc-lookup-results fc-lookup-results--<?php echo $h($layout); ?>" data-fc-lookup-results>
    <?php foreach ($products as $product) : ?>
    <?php
        if (!is_array($product)) {
            continue;
        }
        $id = (int) ($product['id'] ?? 0);
        $slug = (string) ($product['slug'] ?? '');
        $images = is_array($product['images'] ?? null) ? $product['images'] : [];
        $primary = (string) ($images[0] ?? '');
        $quickUrl = fc_lookup_url($req, ['view' => $slug !== '' ? $slug : (string) $id]);
        $sku = (string) ($product['sku'] ?? '');
        $stock = (string) ($product['stock_status'] ?? '');
        $rating = (float) ($product['rating'] ?? 0);
        $cats = is_array($product['categories'] ?? null) ? $product['categories'] : [];
        $colors = is_array($product['colors'] ?? null) ? $product['colors'] : [];
        $name = (string) ($product['name'] ?? '');
    ?>
    <article class="fc-lookup-card" data-fc-lookup-card>
        <a class="fc-lookup-card__media" href="<?php echo $h($quickUrl); ?>" data-fc-lookup-gallery aria-label="<?php echo $h('Quick view ' . $name); ?>">
            <?php if ($primary !== '') : ?>
            <img
                class="fc-lookup-card__img is-active"
                src="<?php echo $h($primary); ?>"
                alt="<?php echo $h($name); ?>"
                loading="lazy"
                data-gallery-index="0"
            >
            <?php
            $gi = 1;
            foreach (array_slice($images, 1) as $img) :
            ?>
            <img
                class="fc-lookup-card__img"
                src="<?php echo $h((string) $img); ?>"
                alt=""
                loading="lazy"
                data-gallery-index="<?php echo (int) $gi; ?>"
                hidden
            >
            <?php
                $gi++;
            endforeach;
            ?>
            <?php else : ?>
            <span class="fc-lookup-card__media-fallback" aria-hidden="true"><i class="fa-solid fa-image"></i></span>
            <?php endif; ?>

            <?php if (!empty($product['badges'])) : ?>
            <div class="fc-lookup-card__badges">
                <?php foreach ($product['badges'] as $badge) : ?>
                <span class="fc-lookup-badge fc-lookup-badge--<?php echo $h((string) $badge); ?>">
                    <?php echo $h($badgeLabels[(string) $badge] ?? (string) $badge); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <span class="fc-lookup-card__quick" aria-hidden="true">
                <i class="fa-solid fa-expand"></i>
                Quick view
            </span>
        </a>

        <div class="fc-lookup-card__body">
            <?php if ($cats !== []) : ?>
            <div class="fc-lookup-card__cats">
                <?php foreach (array_slice($cats, 0, 2) as $cat) : ?>
                <span class="fc-lookup-card__chip"><?php echo $h((string) ($cat['name'] ?? '')); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h3 class="fc-lookup-card__name">
                <a href="<?php echo $h($quickUrl); ?>"><?php echo $h($name); ?></a>
            </h3>

            <?php if ($sku !== '') : ?>
            <p class="fc-lookup-card__sku">
                <span class="fc-lookup-card__sku-label">SKU</span>
                <button type="button" class="fc-lookup-copy" data-fc-lookup-copy="<?php echo $h($sku); ?>" data-fc-lookup-copy-label="SKU copied" title="Copy SKU">
                    <?php echo $h($sku); ?>
                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                </button>
            </p>
            <?php endif; ?>

            <div class="fc-lookup-card__price-row">
                <div class="fc-lookup-card__price"><?php echo $product['price_html'] ?? ''; ?></div>
                <span class="fc-lookup-stock fc-lookup-stock--<?php echo $h($stock); ?>">
                    <span class="fc-lookup-stock__dot" aria-hidden="true"></span>
                    <?php echo $h((string) ($product['stock_label'] ?? '')); ?>
                </span>
            </div>

            <?php if ($rating > 0 || $colors !== []) : ?>
            <div class="fc-lookup-card__footer">
                <?php if ($rating > 0) : ?>
                <span class="fc-lookup-card__rating" title="Average rating">
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    <strong><?php echo $h(number_format($rating, 1)); ?></strong>
                    <span class="fc-lookup-muted"><?php echo (int) ($product['review_count'] ?? 0); ?></span>
                </span>
                <?php endif; ?>

                <?php if ($colors !== []) : ?>
                <div class="fc-lookup-card__colors" aria-label="Colors">
                    <?php foreach (array_slice($colors, 0, 6) as $color) : ?>
                    <span
                        class="fc-lookup-dot"
                        style="--swatch:<?php echo $h((string) ($color['hex'] ?? '#cbd5e1')); ?>"
                        title="<?php echo $h((string) ($color['name'] ?? '')); ?>"
                    ></span>
                    <?php endforeach; ?>
                    <?php if (count($colors) > 6) : ?>
                    <span class="fc-lookup-card__colors-more">+<?php echo count($colors) - 6; ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (($product['short_description'] ?? '') !== '') : ?>
            <p class="fc-lookup-card__desc"><?php echo $h((string) $product['short_description']); ?></p>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
