<?php
/**
 * FC Admin — Fence styles list (server-rendered).
 *
 * @var array<string, mixed> $fcFenceStylesPage
 */

declare(strict_types=1);

if (!isset($fcFenceStylesPage) || !is_array($fcFenceStylesPage)) {
    return;
}

$h = static fn(string $v): string => \Fc\Admin\Helpers\StringHelper::escapeHtml($v);
$page = $fcFenceStylesPage;
?>
<div class="fc-fs-styles-page" data-fc-fence-styles-server="1">
    <script type="application/json" id="fc-fence-styles-bootstrap"><?php echo $page['bootstrap_json']; ?></script>

    <?php if (($page['error'] ?? '') !== '') : ?>
    <div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
        <p class="font-semibold">Could not load fence styles</p>
        <p class="mt-1 text-sm"><?php echo $h((string) $page['error']); ?></p>
    </div>
    <?php else : ?>
    <?php if (empty($page['has_styles'])) : ?>
    <div class="p-8 text-center text-sm text-slate-500">No fence styles found in writable/fences.</div>
    <?php else : ?>
    <div class="fc-admin-fence-styles">
        <div class="fc-admin-fence-styles__grid">
            <?php foreach ($page['cards'] as $card) : ?>
            <div class="fc-admin-fence-style-card">
            <?php if (!empty($card['can_view'])) : ?>
            <a
                href="<?php echo $h((string) ($card['edit_href'] ?? '#')); ?>"
                class="fencing-style-item fc-admin-fence-style-item fc-admin-fence-style-link"
                data-route="<?php echo $h((string) ($card['edit_route'] ?? '')); ?>"
                data-title="<?php echo $h((!empty($card['can_edit']) ? 'Edit ' : 'View ') . ($card['title'] ?? '')); ?>"
                aria-label="<?php echo $h((!empty($card['can_edit']) ? 'Edit ' : 'View ') . ($card['title'] ?? '')); ?>"
            >
            <?php else : ?>
            <div
                class="fencing-style-item fc-admin-fence-style-item"
                aria-label="<?php echo $h((string) ($card['title'] ?? '')); ?>"
            >
            <?php endif; ?>
                <div>
                    <div class="fencing-style-img">
                        <?php if (!empty($card['has_image'])) : ?>
                        <img src="<?php echo $h((string) ($card['image_url'] ?? '')); ?>" alt="" loading="lazy" decoding="async">
                        <?php else : ?>
                        <span class="fc-admin-fence-style-img-placeholder" aria-hidden="true">
                            <i class="fa-solid fa-image text-2xl text-slate-300"></i>
                        </span>
                        <?php endif; ?>
                        <span class="fc-admin-fence-style-badge <?php echo $h((string) ($card['badge_class'] ?? '')); ?>">
                            <?php echo $h((string) ($card['badge_label'] ?? '')); ?>
                        </span>
                    </div>
                    <div class="fencing-style-title fw-bold"><?php echo $h((string) ($card['title'] ?? '')); ?></div>
                </div>
            <?php if (!empty($card['can_view'])) : ?>
            </a>
            <?php else : ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($card['can_edit'])) : ?>
            <div class="fc-fs-card-controls">
                <label class="fc-fs-card-check" title="<?php echo $h('Select ' . (string) ($card['title'] ?? '') . ' (Ctrl+Click a style to select it)'); ?>">
                    <input
                        type="checkbox"
                        class="fc-fs-card-check-input"
                        data-fc-fs-card-select
                        data-slug="<?php echo $h((string) ($card['slug'] ?? '')); ?>"
                        aria-label="<?php echo $h('Select ' . (string) ($card['title'] ?? '')); ?>"
                    >
                    <span class="fc-fs-card-check-ui" aria-hidden="true">
                        <i class="fa-solid fa-check fc-fs-card-check-icon" aria-hidden="true"></i>
                    </span>
                </label>
            </div>
            <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($page['can_edit'])) : ?>
    <div class="fc-fs-bulk-bar fc-entries-page__footer" data-fc-fs-bulk-bar>
        <div class="fc-entries-page__footer-row">
            <div class="fc-entries-page__bulk" data-fc-fs-bulk>
                <label class="fc-entries-page__bulk-label" for="fc-fs-bulk-action">Bulk actions</label>
                <select id="fc-fs-bulk-action" class="fc-entries-page__bulk-select" data-fc-fs-bulk-action disabled>
                    <option value="">Bulk actions</option>
                    <option value="mark-live">Mark as Live</option>
                    <option value="mark-draft">Mark as Draft</option>
                    <option value="export">Export as JSON</option>
                </select>
                <button type="button" class="btn btn-sm btn-dark fw-semibold fc-entries-toolbar-menu__toggle" data-fc-fs-bulk-apply disabled>Apply</button>
                <div class="fc-fs-card-gear fc-fs-bulk-bar__gear" data-fc-fs-card-gear data-slug="">
                    <button
                        type="button"
                        class="btn btn-sm btn-dark fw-semibold fc-products-download-trigger fc-entries-toolbar-menu__toggle"
                        data-fc-fs-card-gear-toggle
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-label="Import or export fence styles"
                        title="Import or export fence styles"
                    >
                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                    </button>
                    <div class="fc-products-download-dropdown__panel fc-fs-card-gear__panel" role="menu" hidden>
                        <button type="button" class="fc-products-download-dropdown__option" role="menuitem" data-fc-fs-bulk-import-trigger>
                            <span>Import Fence Styles</span>
                        </button>
                        <button type="button" class="fc-products-download-dropdown__option" role="menuitem" data-fc-fs-bulk-export-all>
                            <span>Export Fence Styles</span>
                        </button>
                    </div>
                    <input
                        type="file"
                        class="sr-only"
                        accept="application/json,.json"
                        data-fc-fs-bulk-import-input
                        tabindex="-1"
                        aria-hidden="true"
                    >
                </div>
                <span class="fc-entries-page__bulk-count" data-fc-fs-bulk-count hidden>0 selected</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
