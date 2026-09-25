<?php
/**
 * FC Admin — Settings → Minify CSS & JS: one file row of a type's grid.
 *
 * Read-only template: SettingsPresenter::minifyStatus() shapes every key as final display text.
 * Slot names (data-fc-minify-cell) mirror those keys and minify-tab.js paintRow(): edit in pairs.
 * The variable is not called $file: View::includeFile() has a $file of its own and extract() skips it.
 *
 * @var array<string, mixed> $fcMinifyRow path, name, dir, full_path, state, state_label, chip_state, state_title,
 *   served, served_label, served_title, source_main, source_sub, source_title, source_newer,
 *   copy_main, copy_sub, copy_title, saved_main, saved_sub, saved_title, error, sort_json
 */
?>
<div class="fc-sites__row fc-minify__row" role="row" data-fc-minify-file="<?php echo e((string) $fcMinifyRow['path']); ?>" data-state="<?php echo e((string) $fcMinifyRow['state']); ?>" data-served="<?php echo $fcMinifyRow['served'] ? '1' : '0'; ?>" data-fc-minify-sort="<?php echo e((string) $fcMinifyRow['sort_json']); ?>">
    <div class="fc-sites__cell fc-sites__cell--site" role="cell" title="<?php echo e((string) $fcMinifyRow['full_path']); ?>" data-fc-minify-cell="full_path">
        <span class="fc-sites__name">
            <span class="fc-sites__label" data-fc-minify-cell="name"><?php echo e((string) $fcMinifyRow['name']); ?></span>
            <span class="fc-sites__key" data-fc-minify-cell="dir"><?php echo e((string) $fcMinifyRow['dir']); ?></span>
        </span>
    </div>
    <div class="fc-sites__cell" role="cell">
        <span class="fc-seo-chip" data-fc-minify-cell="status" data-state="<?php echo e((string) $fcMinifyRow['chip_state']); ?>" title="<?php echo e((string) $fcMinifyRow['state_title']); ?>"><?php echo e((string) $fcMinifyRow['state_label']); ?></span>
    </div>
    <div class="fc-sites__cell" role="cell">
        <span class="fc-minify__serving" data-fc-minify-cell="serving" data-served="<?php echo $fcMinifyRow['served'] ? '1' : '0'; ?>" title="<?php echo e((string) $fcMinifyRow['served_title']); ?>"><?php echo e((string) $fcMinifyRow['served_label']); ?></span>
    </div>
    <div class="fc-sites__cell" role="cell">
        <span class="fc-minify__stack">
            <span class="fc-minify__main<?php echo $fcMinifyRow['source_newer'] ? ' fc-minify__main--newer' : ''; ?>" data-fc-minify-cell="source_main" title="<?php echo e((string) $fcMinifyRow['source_title']); ?>"><?php echo e((string) $fcMinifyRow['source_main']); ?><span class="sr-only" data-fc-minify-cell="source_newer"><?php echo $fcMinifyRow['source_newer'] ? ' — newer than the copy' : ''; ?></span></span>
            <span class="fc-minify__sub" data-fc-minify-cell="source_sub"><?php echo e((string) $fcMinifyRow['source_sub']); ?></span>
        </span>
    </div>
    <div class="fc-sites__cell" role="cell">
        <span class="fc-minify__stack">
            <span class="fc-minify__main" data-fc-minify-cell="copy_main" title="<?php echo e((string) $fcMinifyRow['copy_title']); ?>"><?php echo e((string) $fcMinifyRow['copy_main']); ?></span>
            <span class="fc-minify__sub" data-fc-minify-cell="copy_sub"><?php echo e((string) $fcMinifyRow['copy_sub']); ?></span>
        </span>
    </div>
    <div class="fc-sites__cell" role="cell">
        <span class="fc-minify__stack" data-fc-minify-cell="saved" title="<?php echo e((string) $fcMinifyRow['saved_title']); ?>">
            <span class="fc-minify__main" data-fc-minify-cell="saved_main"><?php echo e((string) $fcMinifyRow['saved_main']); ?></span>
            <span class="fc-minify__sub" data-fc-minify-cell="saved_sub"><?php echo e((string) $fcMinifyRow['saved_sub']); ?></span>
        </span>
    </div>
    <div class="fc-sites__drawer fc-minify__error" role="cell" aria-colspan="6"<?php echo $fcMinifyRow['error'] === '' ? ' hidden' : ''; ?>>
        <span class="fc-sites__drawer-label">Build failed</span>
        <button type="button" class="fc-settings-field-copy" data-fc-minify-copy-error aria-label="Copy error" title="Copy to clipboard"><i class="fa-regular fa-copy" aria-hidden="true"></i></button>
        <code class="fc-sites__drawer-hint" data-fc-minify-cell="error"><?php echo e((string) $fcMinifyRow['error']); ?></code>
    </div>
</div>
