<?php
/**
 * FC Admin — dashboard date filter dropdown.
 *
 * Expects: $page (dashboard page array), $datePeriodOptions (array).
 * Optional: $fcDashboardDateDropdownContext (string) for unique ids when needed.
 *
 * Prop defaults and the two derived element ids below are this partial's own
 * parameterization — the only computation an include-partial keeps. Escaping is
 * the global e() helper; the caller gates rendering.
 */

$fcDashboardDateDropdownContext = (string) ($fcDashboardDateDropdownContext ?? 'main');
$toggleId = 'fc-dashboard-date-toggle' . ($fcDashboardDateDropdownContext !== 'main' ? '-' . $fcDashboardDateDropdownContext : '');
$panelId = 'fc-dashboard-date-panel' . ($fcDashboardDateDropdownContext !== 'main' ? '-' . $fcDashboardDateDropdownContext : '');
?>
<div
    class="fc-entries-date-dropdown fc-dashboard-date-dropdown<?php echo ($page['date_period'] ?? '') !== '' ? ' is-active' : ''; ?><?php echo ($page['date_period'] ?? '') === 'custom' ? ' is-custom' : ''; ?>"
    data-fc-entries-date-dropdown
    data-fc-dashboard-date-dropdown
>
    <input type="hidden" value="<?php echo e((string) ($page['date_period'] ?? '')); ?>" data-fc-entries-date-period>
    <input type="hidden" value="<?php echo e((string) ($page['date_from'] ?? '')); ?>" data-fc-entries-date-from>
    <input type="hidden" value="<?php echo e((string) ($page['date_to'] ?? '')); ?>" data-fc-entries-date-to>
    <button
        type="button"
        class="fc-dashboard-toolbar-btn fc-entries-date-dropdown__toggle"
        id="<?php echo e($toggleId); ?>"
        aria-haspopup="listbox"
        aria-expanded="false"
        aria-controls="<?php echo e($panelId); ?>"
        aria-label="Filter charts by date"
    >
        <i class="fa-regular fa-calendar-days fc-entries-date-dropdown__icon" aria-hidden="true"></i>
        <span class="fc-entries-date-dropdown__label" data-fc-entries-date-label><?php echo e((string) ($page['date_filter_label'] ?? 'All dates')); ?></span>
        <i class="fa-solid fa-chevron-down fc-entries-date-dropdown__caret" aria-hidden="true"></i>
    </button>
    <div
        class="fc-entries-date-dropdown__panel"
        id="<?php echo e($panelId); ?>"
        role="listbox"
        aria-labelledby="<?php echo e($toggleId); ?>"
        hidden
    >
        <div class="fc-entries-date-dropdown__presets">
            <?php foreach ($datePeriodOptions as $periodKey => $periodLabel) : ?>
            <?php if ($periodKey === 'custom') {
                continue;
            } ?>
            <button
                type="button"
                class="fc-entries-date-dropdown__option<?php echo ($page['date_period'] ?? '') === $periodKey ? ' is-selected' : ''; ?>"
                data-fc-entries-date-preset="<?php echo e((string) $periodKey); ?>"
                role="option"
                aria-selected="<?php echo ($page['date_period'] ?? '') === $periodKey ? 'true' : 'false'; ?>"
            >
                <span><?php echo e((string) $periodLabel); ?></span>
                <i class="fa-solid fa-check fc-entries-date-dropdown__check" aria-hidden="true"></i>
            </button>
            <?php endforeach; ?>
        </div>
        <div class="fc-entries-date-dropdown__custom-wrap">
            <button
                type="button"
                class="fc-entries-date-dropdown__option fc-entries-date-dropdown__option--custom<?php echo ($page['date_period'] ?? '') === 'custom' ? ' is-selected' : ''; ?>"
                data-fc-entries-date-preset="custom"
                role="option"
                aria-selected="<?php echo ($page['date_period'] ?? '') === 'custom' ? 'true' : 'false'; ?>"
            >
                <span><?php echo e((string) ($datePeriodOptions['custom'] ?? 'Custom')); ?></span>
                <i class="fa-solid fa-check fc-entries-date-dropdown__check" aria-hidden="true"></i>
            </button>
            <div
                class="fc-entries-date-dropdown__custom"
                data-fc-entries-date-custom
                <?php echo ($page['date_period'] ?? '') === 'custom' ? '' : 'hidden'; ?>
            >
                <div class="fc-entries-date-dropdown__custom-fields">
                    <label class="fc-entries-date-dropdown__field">
                        <span class="fc-entries-date-dropdown__field-label">From</span>
                        <input
                            type="date"
                            class="fc-entries-date-dropdown__input"
                            data-fc-entries-date-custom-from
                            value="<?php echo e((string) ($page['date_from'] ?? '')); ?>"
                        >
                    </label>
                    <label class="fc-entries-date-dropdown__field">
                        <span class="fc-entries-date-dropdown__field-label">To</span>
                        <input
                            type="date"
                            class="fc-entries-date-dropdown__input"
                            data-fc-entries-date-custom-to
                            value="<?php echo e((string) ($page['date_to'] ?? '')); ?>"
                        >
                    </label>
                </div>
                <button type="button" class="btn btn-sm btn-orange fw-semibold fc-entries-date-dropdown__apply-custom" data-fc-entries-date-apply-custom>
                    Apply range
                </button>
            </div>
        </div>
        <div class="fc-entries-date-dropdown__footer">
            <button type="button" class="fc-entries-date-dropdown__clear" data-fc-entries-date-clear>
                Clear dates
            </button>
        </div>
    </div>
</div>
