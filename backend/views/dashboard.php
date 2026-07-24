<?php
/**
 * FC Admin — Dashboard (server-rendered shell + lazy charts).
 *
 * @var array<string, mixed> $fcDashboardPage
 */

declare(strict_types=1);

if (!isset($fcDashboardPage) || !is_array($fcDashboardPage)) {
    return;
}

$h = 'fc_dashboard_admin_h';
$page = $fcDashboardPage;
$summary = is_array($page['summary'] ?? null) ? $page['summary'] : [];
$links = is_array($page['links'] ?? null) ? $page['links'] : [];
$auStates = is_array($page['au_states'] ?? null) ? $page['au_states'] : [];
$entriesBase = (string) ($links['entries'] ?? '');
$datePeriodOptions = is_array($page['date_period_options'] ?? null) ? $page['date_period_options'] : [];
$displayName = '';
if (!empty($fcAuthUser['display_name'])) {
    $displayName = (string) $fcAuthUser['display_name'];
} elseif (!empty($fcAuthUser['login'])) {
    $displayName = (string) $fcAuthUser['login'];
}
$greetingName = $displayName !== '' ? explode(' ', trim($displayName))[0] : 'there';
$todayLabel = (new DateTime('now'))->format('l, j M Y');
$widgets = is_array($page['widgets_visible'] ?? null) ? $page['widgets_visible'] : [];
$showWidget = static function (string $id) use ($widgets): bool {
    return !array_key_exists($id, $widgets) || !empty($widgets[$id]);
};
?>
<div
    class="fc-dashboard-page"
    data-fc-dashboard-server
    data-fc-dashboard-api="<?php echo $h((string) ($page['api_url'] ?? 'api.php?module=dashboard')); ?>"
    data-fc-dashboard-entries="<?php echo $h($entriesBase); ?>"
>
    <div class="fc-dashboard-page__sticky">
        <header class="fc-dashboard-hero">
            <div class="fc-dashboard-hero__main">
                <p class="fc-dashboard-hero__eyebrow"><?php echo $h($todayLabel); ?></p>
                <h2 class="fc-dashboard-hero__title">Good <?php echo (int) date('G') < 12 ? 'morning' : ((int) date('G') < 17 ? 'afternoon' : 'evening'); ?>, <?php echo $h($greetingName); ?></h2>
            </div>
            <div class="fc-dashboard-hero__actions">
                <div
                    class="fc-entries-date-dropdown fc-dashboard-date-dropdown<?php echo ($page['date_period'] ?? '') !== '' ? ' is-active' : ''; ?><?php echo ($page['date_period'] ?? '') === 'custom' ? ' is-custom' : ''; ?>"
                    data-fc-entries-date-dropdown
                    data-fc-dashboard-date-dropdown
                >
                    <input type="hidden" value="<?php echo $h((string) ($page['date_period'] ?? '')); ?>" data-fc-entries-date-period>
                    <input type="hidden" value="<?php echo $h((string) ($page['date_from'] ?? '')); ?>" data-fc-entries-date-from>
                    <input type="hidden" value="<?php echo $h((string) ($page['date_to'] ?? '')); ?>" data-fc-entries-date-to>
                    <button
                        type="button"
                        class="fc-dashboard-toolbar-btn fc-entries-date-dropdown__toggle"
                        id="fc-dashboard-date-toggle"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="fc-dashboard-date-panel"
                        aria-label="Filter charts by date"
                    >
                        <i class="fa-regular fa-calendar-days fc-entries-date-dropdown__icon" aria-hidden="true"></i>
                        <span class="fc-entries-date-dropdown__label" data-fc-entries-date-label><?php echo $h((string) ($page['date_filter_label'] ?? 'All dates')); ?></span>
                        <i class="fa-solid fa-chevron-down fc-entries-date-dropdown__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-entries-date-dropdown__panel"
                        id="fc-dashboard-date-panel"
                        role="listbox"
                        aria-labelledby="fc-dashboard-date-toggle"
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
                                data-fc-entries-date-preset="<?php echo $h((string) $periodKey); ?>"
                                role="option"
                                aria-selected="<?php echo ($page['date_period'] ?? '') === $periodKey ? 'true' : 'false'; ?>"
                            >
                                <span><?php echo $h((string) $periodLabel); ?></span>
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
                                <span><?php echo $h((string) ($datePeriodOptions['custom'] ?? 'Custom')); ?></span>
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
                                            value="<?php echo $h((string) ($page['date_from'] ?? '')); ?>"
                                        >
                                    </label>
                                    <label class="fc-entries-date-dropdown__field">
                                        <span class="fc-entries-date-dropdown__field-label">To</span>
                                        <input
                                            type="date"
                                            class="fc-entries-date-dropdown__input"
                                            data-fc-entries-date-custom-to
                                            value="<?php echo $h((string) ($page['date_to'] ?? '')); ?>"
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
            </div>
        </header>
    </div>

    <?php if ($showWidget('kpis')) : ?>
    <?php
    $fcKpiCards = [
        [
            'period' => 'today',
            'label' => "Today's entries",
            'icon' => 'fa-clipboard-list',
            'entries' => (int) ($summary['today_entries'] ?? 0),
            'meta_label' => "Today's customers",
            'customers' => (int) ($summary['today_customers'] ?? 0),
        ],
        [
            'period' => 'yesterday',
            'label' => "Yesterday's entries",
            'icon' => 'fa-clock-rotate-left',
            'entries' => (int) ($summary['yesterday_entries'] ?? 0),
            'meta_label' => "Yesterday's customers",
            'customers' => (int) ($summary['yesterday_customers'] ?? 0),
        ],
        [
            'period' => 'this_week',
            'label' => 'This week entries',
            'icon' => 'fa-calendar-week',
            'entries' => (int) ($summary['week_entries'] ?? 0),
            'meta_label' => 'This week customers',
            'customers' => (int) ($summary['week_customers'] ?? 0),
        ],
        [
            'period' => 'this_month',
            'label' => 'This month entries',
            'icon' => 'fa-calendar-days',
            'entries' => (int) ($summary['month_entries'] ?? 0),
            'meta_label' => 'This month customers',
            'customers' => (int) ($summary['month_customers'] ?? 0),
        ],
        [
            'period' => 'this_year',
            'label' => 'This year entries',
            'icon' => 'fa-calendar',
            'entries' => (int) ($summary['year_entries'] ?? 0),
            'meta_label' => 'This year customers',
            'customers' => (int) ($summary['year_customers'] ?? 0),
        ],
    ];
    ?>
    <section class="fc-dashboard-kpis" data-fc-dashboard-widget="kpis">
        <?php foreach ($fcKpiCards as $kpi) : ?>
        <?php
        $kpiHref = $entriesBase !== ''
            ? $entriesBase . '?' . http_build_query(['date_period' => $kpi['period']])
            : '#';
        ?>
        <a
            class="fc-dashboard-kpi"
            href="<?php echo $h($kpiHref); ?>"
            data-nav-full="1"
            data-route="planner-entries"
            aria-label="<?php echo $h($kpi['label'] . ' — open planner entries'); ?>"
        >
            <div class="fc-dashboard-kpi__header">
                <p class="fc-dashboard-kpi__label"><?php echo $h($kpi['label']); ?></p>
                <span class="fc-dashboard-kpi__icon" aria-hidden="true"><i class="fa-solid <?php echo $h($kpi['icon']); ?>"></i></span>
            </div>
            <p class="fc-dashboard-kpi__value"><?php echo number_format($kpi['entries']); ?></p>
            <p class="fc-dashboard-kpi__meta">
                <span class="fc-dashboard-kpi__meta-label"><?php echo $h($kpi['meta_label']); ?></span>
                <span class="fc-dashboard-kpi__meta-value"><?php echo number_format($kpi['customers']); ?></span>
            </p>
        </a>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <div class="fc-dashboard-grid">
        <?php if ($showWidget('trend')) : ?>
        <section class="fc-dashboard-card fc-dashboard-card--span-full fc-dashboard-card--indigo" data-fc-dashboard-widget="trend">
            <header class="fc-dashboard-card__head fc-dashboard-card__head--rich">
                <div class="fc-dashboard-card__head-top">
                    <div class="fc-dashboard-card__head-main">
                        <span class="fc-dashboard-card__icon fc-dashboard-card__icon--indigo fc-dashboard-card__icon--lg" aria-hidden="true">
                            <i class="fa-solid fa-chart-area"></i>
                        </span>
                        <div class="fc-dashboard-card__copy">
                            <p class="fc-dashboard-card__eyebrow">Trend analytics</p>
                            <h3 class="fc-dashboard-card__title">Planner submissions</h3>
                            <p class="fc-dashboard-card__desc">Daily submission volume over the selected period</p>
                        </div>
                    </div>
                    <div class="fc-dashboard-card__head-aside">
                        <span class="fc-dashboard-period-badge" data-fc-dashboard-trend-period>
                            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i>
                            <span data-fc-dashboard-trend-period-label><?php echo $h((string) ($page['date_filter_label'] ?? 'All dates')); ?></span>
                        </span>
                        <?php if ($entriesBase !== '') : ?>
                        <a class="fc-dashboard-card__link fc-dashboard-card__link--btn" href="<?php echo $h($entriesBase); ?>" data-nav-full="1" data-fc-dashboard-entries-all-link data-route="planner-entries">
                            View entries
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="fc-dashboard-viz-summary fc-dashboard-viz-summary--header" data-fc-dashboard-trend-summary hidden></div>
            </header>
            <div class="fc-dashboard-card__body">
                <div class="fc-dashboard-viz" data-fc-dashboard-viz="trend">
                    <div class="fc-dashboard-chart-wrap fc-dashboard-chart-wrap--tall">
                        <canvas id="fc-dashboard-chart-trend" aria-label="Planner submissions trend chart"></canvas>
                        <div class="fc-dashboard-skeleton fc-dashboard-skeleton--chart" data-fc-dashboard-skeleton="trend"></div>
                        <div class="fc-dashboard-viz-empty" data-fc-dashboard-empty="trend" hidden>No submissions in this period.</div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($showWidget('states')) : ?>
        <section class="fc-dashboard-card fc-dashboard-card--cyan" data-fc-dashboard-widget="states">
            <header class="fc-dashboard-card__head fc-dashboard-card__head--rich">
                <div class="fc-dashboard-card__head-top">
                    <div class="fc-dashboard-card__head-main">
                        <span class="fc-dashboard-card__icon fc-dashboard-card__icon--cyan fc-dashboard-card__icon--lg" aria-hidden="true">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </span>
                        <div class="fc-dashboard-card__copy">
                            <p class="fc-dashboard-card__eyebrow">Geographic mix</p>
                            <h3 class="fc-dashboard-card__title">Entries by state</h3>
                            <p class="fc-dashboard-card__desc">Geographic distribution across Australia</p>
                        </div>
                    </div>
                </div>
                <div class="fc-dashboard-viz-summary fc-dashboard-viz-summary--header" data-fc-dashboard-states-summary hidden></div>
            </header>
            <div class="fc-dashboard-card__body">
                <div class="fc-dashboard-viz" data-fc-dashboard-viz="states">
                    <div class="fc-dashboard-chart-wrap fc-dashboard-chart-wrap--ranked" data-fc-dashboard-chart-host="states">
                        <canvas id="fc-dashboard-chart-states" aria-label="Entries by state chart"></canvas>
                        <div class="fc-dashboard-skeleton fc-dashboard-skeleton--chart" data-fc-dashboard-skeleton="states"></div>
                        <div class="fc-dashboard-viz-empty" data-fc-dashboard-empty="states" hidden>No state data in this period.</div>
                    </div>
                    <div class="fc-dashboard-au-map" data-fc-dashboard-au-map aria-label="Toggle states on chart">
                        <?php foreach ($auStates as $stateCode) : ?>
                        <button
                            type="button"
                            class="fc-dashboard-au-map__state"
                            data-state="<?php echo $h($stateCode); ?>"
                            aria-pressed="true"
                        >
                            <span class="fc-dashboard-au-map__accent" aria-hidden="true"></span>
                            <span class="fc-dashboard-au-map__body">
                                <span class="fc-dashboard-au-map__code"><?php echo $h($stateCode); ?></span>
                                <strong class="fc-dashboard-au-map__count">0</strong>
                            </span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($showWidget('performance')) : ?>
        <section class="fc-dashboard-card fc-dashboard-card--amber" data-fc-dashboard-widget="performance">
            <header class="fc-dashboard-card__head fc-dashboard-card__head--rich">
                <div class="fc-dashboard-card__head-top">
                    <div class="fc-dashboard-card__head-main">
                        <span class="fc-dashboard-card__icon fc-dashboard-card__icon--amber fc-dashboard-card__icon--lg" aria-hidden="true">
                            <i class="fa-solid fa-gauge-high"></i>
                        </span>
                        <div class="fc-dashboard-card__copy">
                            <p class="fc-dashboard-card__eyebrow">Usage insights</p>
                            <h3 class="fc-dashboard-card__title">Performance</h3>
                            <p class="fc-dashboard-card__desc">Peak hours, devices, and browsers</p>
                        </div>
                    </div>
                </div>
                <div class="fc-dashboard-viz-summary fc-dashboard-viz-summary--header" data-fc-dashboard-hours-summary hidden></div>
            </header>
            <div class="fc-dashboard-card__body">
                <div class="fc-dashboard-viz" data-fc-dashboard-viz="performance">
                    <div class="fc-dashboard-chart-wrap fc-dashboard-chart-wrap--hours">
                        <canvas id="fc-dashboard-chart-hours" aria-label="Peak usage hours chart"></canvas>
                        <div class="fc-dashboard-skeleton fc-dashboard-skeleton--chart" data-fc-dashboard-skeleton="hours"></div>
                        <div class="fc-dashboard-viz-empty" data-fc-dashboard-empty="hours" hidden>No session timing data yet.</div>
                    </div>
                    <div class="fc-dashboard-breakdown">
                        <div class="fc-dashboard-breakdown__panel">
                            <p class="fc-dashboard-breakdown__title"><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i> Devices</p>
                            <div class="fc-dashboard-breakdown__body" data-fc-dashboard-device-bars>
                                <div class="fc-dashboard-skeleton fc-dashboard-skeleton--list" data-fc-dashboard-skeleton="devices"></div>
                            </div>
                        </div>
                        <div class="fc-dashboard-breakdown__panel">
                            <p class="fc-dashboard-breakdown__title"><i class="fa-brands fa-chrome" aria-hidden="true"></i> Browsers</p>
                            <div class="fc-dashboard-breakdown__body" data-fc-dashboard-browser-bars>
                                <div class="fc-dashboard-skeleton fc-dashboard-skeleton--list" data-fc-dashboard-skeleton="browsers"></div>
                            </div>
                        </div>
                        <div class="fc-dashboard-breakdown__panel fc-dashboard-breakdown__panel--wide">
                            <p class="fc-dashboard-breakdown__title"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Device and browser combinations</p>
                            <div class="fc-dashboard-breakdown__body" data-fc-dashboard-combination-bars>
                                <div class="fc-dashboard-skeleton fc-dashboard-skeleton--list" data-fc-dashboard-skeleton="device-browser-combinations"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($showWidget('fence-styles')) : ?>
        <section class="fc-dashboard-card fc-dashboard-card--pink" data-fc-dashboard-widget="fence-styles">
            <header class="fc-dashboard-card__head fc-dashboard-card__head--rich">
                <div class="fc-dashboard-card__head-top">
                    <div class="fc-dashboard-card__head-main">
                        <span class="fc-dashboard-card__icon fc-dashboard-card__icon--pink fc-dashboard-card__icon--lg" aria-hidden="true">
                            <i class="fa-solid fa-border-all"></i>
                        </span>
                        <div class="fc-dashboard-card__copy">
                            <p class="fc-dashboard-card__eyebrow">Style demand</p>
                            <h3 class="fc-dashboard-card__title">Popular fence styles</h3>
                            <p class="fc-dashboard-card__desc">Total fence sections selected across planners</p>
                        </div>
                    </div>
                </div>
                <div class="fc-dashboard-viz-summary fc-dashboard-viz-summary--header" data-fc-dashboard-fences-summary hidden></div>
            </header>
            <div class="fc-dashboard-card__body">
                <div class="fc-dashboard-viz" data-fc-dashboard-viz="fences">
                    <div class="fc-dashboard-fence-styles" data-fc-dashboard-fence-bars>
                        <div class="fc-dashboard-skeleton fc-dashboard-skeleton--list" data-fc-dashboard-skeleton="fences"></div>
                    </div>
                    <div class="fc-dashboard-viz-empty fc-dashboard-viz-empty--static" data-fc-dashboard-empty="fences" hidden>No fence style data yet.</div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($showWidget('insights')) : ?>
        <section class="fc-dashboard-card fc-dashboard-card--rose" data-fc-dashboard-widget="insights">
            <header class="fc-dashboard-card__head fc-dashboard-card__head--rich">
                <div class="fc-dashboard-card__head-top">
                    <div class="fc-dashboard-card__head-main">
                        <span class="fc-dashboard-card__icon fc-dashboard-card__icon--rose fc-dashboard-card__icon--lg" aria-hidden="true">
                            <i class="fa-solid fa-swatchbook"></i>
                        </span>
                        <div class="fc-dashboard-card__copy">
                            <p class="fc-dashboard-card__eyebrow">Colour choices</p>
                            <h3 class="fc-dashboard-card__title">Product selections</h3>
                            <p class="fc-dashboard-card__desc">Top colours selected across planners</p>
                        </div>
                    </div>
                </div>
                <div class="fc-dashboard-viz-summary fc-dashboard-viz-summary--header" data-fc-dashboard-insights-summary hidden></div>
            </header>
            <div class="fc-dashboard-card__body">
                <div class="fc-dashboard-viz" data-fc-dashboard-viz="insights">
                    <div class="fc-dashboard-insights" data-fc-dashboard-insights>
                        <div class="fc-dashboard-skeleton fc-dashboard-skeleton--list" data-fc-dashboard-skeleton="insights"></div>
                    </div>
                    <div class="fc-dashboard-viz-empty fc-dashboard-viz-empty--static" data-fc-dashboard-empty="insights" hidden>No colour selection data yet.</div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($showWidget('recent')) : ?>
        <section class="fc-dashboard-card fc-dashboard-card--span-full fc-dashboard-card--violet" data-fc-dashboard-widget="recent">
            <header class="fc-dashboard-card__head fc-dashboard-card__head--rich">
                <div class="fc-dashboard-card__head-top">
                    <div class="fc-dashboard-card__head-main">
                        <span class="fc-dashboard-card__icon fc-dashboard-card__icon--violet fc-dashboard-card__icon--lg" aria-hidden="true">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </span>
                        <div class="fc-dashboard-card__copy">
                            <p class="fc-dashboard-card__eyebrow">Recent activity</p>
                            <h3 class="fc-dashboard-card__title">Latest entries</h3>
                            <p class="fc-dashboard-card__desc">Most recently updated planner records</p>
                        </div>
                    </div>
                    <div class="fc-dashboard-card__head-aside">
                        <?php if ($entriesBase !== '') : ?>
                        <a class="fc-dashboard-card__link fc-dashboard-card__link--btn" href="<?php echo $h($entriesBase); ?>" data-nav-full="1" data-fc-dashboard-entries-all-link data-route="planner-entries">
                            All entries
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="fc-dashboard-card__body fc-dashboard-card__body--flush">
                <div class="fc-dashboard-customers" data-fc-dashboard-recent-entries>
                    <div class="fc-dashboard-skeleton fc-dashboard-skeleton--list" data-fc-dashboard-skeleton="recent"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($showWidget('customers')) : ?>
        <section class="fc-dashboard-card fc-dashboard-card--span-full fc-dashboard-card--emerald" data-fc-dashboard-widget="customers">
            <header class="fc-dashboard-card__head fc-dashboard-card__head--rich">
                <div class="fc-dashboard-card__head-top">
                    <div class="fc-dashboard-card__head-main">
                        <span class="fc-dashboard-card__icon fc-dashboard-card__icon--emerald fc-dashboard-card__icon--lg" aria-hidden="true">
                            <i class="fa-solid fa-user-group"></i>
                        </span>
                        <div class="fc-dashboard-card__copy">
                            <p class="fc-dashboard-card__eyebrow">Customer insight</p>
                            <h3 class="fc-dashboard-card__title">Customer analytics</h3>
                            <p class="fc-dashboard-card__desc">Identified by email · ranked by planner activity</p>
                        </div>
                    </div>
                    <div class="fc-dashboard-card__head-aside">
                        <?php if ($entriesBase !== '') : ?>
                        <a class="fc-dashboard-card__link fc-dashboard-card__link--btn" href="<?php echo $h($entriesBase); ?>" data-nav-full="1" data-fc-dashboard-entries-all-link data-route="planner-entries">
                            All entries
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="fc-dashboard-card__body fc-dashboard-card__body--flush">
                <div class="fc-dashboard-customers" data-fc-dashboard-top-customers>
                    <div class="fc-dashboard-skeleton fc-dashboard-skeleton--list" data-fc-dashboard-skeleton="customers"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>
