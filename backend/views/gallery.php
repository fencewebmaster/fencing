<?php
/**
 * FC Admin — Media gallery (server-rendered).
 *
 * @var array<string, mixed> $fcGalleryPage
 */

declare(strict_types=1);

if (!isset($fcGalleryPage) || !is_array($fcGalleryPage)) {
    return;
}

$h = 'fc_gallery_admin_h';
$page = $fcGalleryPage;
?>
<div
    class="<?php echo $h((string) $page['page_class']); ?>"
    data-fc-gallery-page
    data-fc-gallery-server="1"
    data-fc-gallery-initial-tab="<?php echo $h((string) $page['initial_tab']); ?>"
>
    <script type="application/json" id="fc-gallery-bootstrap"><?php echo $page['bootstrap_json']; ?></script>

    <div class="fc-gallery-page__tabs" role="tablist" aria-label="Gallery sections">
        <?php foreach ($page['tabs'] as $tab) : ?>
        <button
            type="button"
            role="tab"
            data-fc-gallery-tab="<?php echo $h((string) ($tab['id'] ?? '')); ?>"
            aria-selected="<?php echo !empty($tab['is_active']) ? 'true' : 'false'; ?>"
            class="fc-gallery-page__tab<?php echo !empty($tab['is_active']) ? ' is-active' : ''; ?>"
        ><?php echo $h((string) ($tab['label'] ?? '')); ?></button>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($page['is_library_tab'])) : ?>
    <div class="fc-gallery-page__toolbar" data-fc-admin-sticky-header>
        <div class="fc-gallery-page__toolbar-row">
            <div class="fc-gallery-view-toggle" role="group" aria-label="View mode">
                <button
                    type="button"
                    class="fc-gallery-view-toggle__btn is-active"
                    data-fc-gallery-view="grid"
                    aria-pressed="true"
                    aria-label="Grid view"
                    title="Grid view"
                >
                    <i class="fa-solid fa-grip" aria-hidden="true"></i>
                </button>
                <button
                    type="button"
                    class="fc-gallery-view-toggle__btn"
                    data-fc-gallery-view="list"
                    aria-pressed="false"
                    aria-label="List view"
                    title="List view"
                >
                    <i class="fa-solid fa-list" aria-hidden="true"></i>
                </button>
            </div>
            <div class="fc-gallery-page__search-wrap">
                <i class="fa-solid fa-magnifying-glass fc-gallery-page__search-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    class="fc-gallery-page__search"
                    placeholder="Search media…"
                    value=""
                    autocomplete="off"
                >
            </div>
            <span class="fc-gallery-page__count"><?php echo $h((string) ($page['count_label'] ?? '')); ?></span>
        </div>
    </div>

    <div class="fc-gallery-page__content" data-fc-lazy-root>
        <?php if (($page['error'] ?? '') !== '') : ?>
        <div class="fc-gallery-error">
            <p class="fc-gallery-error__title">Could not load gallery</p>
            <p class="fc-gallery-error__message"><?php echo $h((string) $page['error']); ?></p>
        </div>
        <?php elseif (empty($page['has_items'])) : ?>
        <div class="fc-gallery-empty">
            <i class="fa-regular fa-image fc-gallery-empty__icon" aria-hidden="true"></i>
            <p class="fc-gallery-empty__title">No media files yet</p>
            <p class="fc-gallery-empty__hint">Switch to <strong>Add New</strong> to upload files.</p>
        </div>
        <?php else : ?>
        <ul class="fc-gallery-grid" data-fc-gallery-grid>
            <?php foreach ($page['item_rows'] as $row) : ?>
            <li class="fc-gallery-grid__item">
                <div class="fc-gallery-item-wrap" data-fc-gallery-wrap="<?php echo $h((string) ($row['path'] ?? '')); ?>">
                    <?php if (!empty($page['can_delete'])) : ?>
                    <label class="fc-gallery-item__check" data-fc-gallery-check-wrap="<?php echo $h((string) ($row['path'] ?? '')); ?>">
                        <input
                            type="checkbox"
                            class="fc-gallery-item__check-input"
                            data-fc-gallery-select="<?php echo $h((string) ($row['path'] ?? '')); ?>"
                            aria-label="Select file"
                        >
                        <span class="fc-gallery-item__check-ui" aria-hidden="true">
                            <i class="fa-solid fa-check fc-gallery-item__check-icon" aria-hidden="true"></i>
                        </span>
                    </label>
                    <?php endif; ?>
                    <button
                        type="button"
                        class="fc-gallery-item"
                        data-fc-gallery-item="<?php echo $h((string) ($row['path'] ?? '')); ?>"
                        title="<?php echo $h((string) ($row['name'] ?? '')); ?>"
                    >
                        <span class="fc-gallery-item__thumb">
                            <span class="fc-gallery-item__badge"><?php echo $h((string) ($row['type_badge'] ?? '')); ?></span>
                            <img
                                alt=""
                                class="fc-lazy"
                                data-fc-lazy
                                data-fc-lazy-src="<?php echo $h((string) ($row['asset_url'] ?? '')); ?>"
                                decoding="async"
                            >
                        </span>
                    </button>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php else : ?>
    <div class="fc-gallery-page__upload-panel">
        <div
            class="fc-gallery-page__dropzone fc-gallery-page__dropzone--full"
            data-fc-gallery-dropzone
            tabindex="0"
            role="button"
            aria-label="Upload files"
        >
            <i class="fa-solid fa-cloud-arrow-up fc-gallery-page__dropzone-icon" aria-hidden="true"></i>
            <p class="fc-gallery-page__dropzone-title">Drop files here to upload</p>
            <p class="fc-gallery-page__dropzone-text">or click anywhere in this area to browse your computer</p>
            <p class="fc-gallery-page__dropzone-hint">JPG, PNG, GIF, WebP, or SVG · saved to <code>assets/uploads</code></p>
            <input
                type="file"
                class="fc-gallery-page__file-input"
                accept="<?php echo $h((string) ($page['accept_types'] ?? '')); ?>"
                multiple
                hidden
            >
        </div>
    </div>
    <?php endif; ?>
</div>
