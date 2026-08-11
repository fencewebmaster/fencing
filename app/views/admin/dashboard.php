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

$h = static fn(string $v): string => \Fc\Admin\Helpers\StringHelper::escapeHtml($v);
$page = $fcDashboardPage;
$summary = is_array($page['summary'] ?? null) ? $page['summary'] : [];
$links = is_array($page['links'] ?? null) ? $page['links'] : [];
$auStates = is_array($page['au_states'] ?? null) ? $page['au_states'] : [];
$entriesBase = (string) ($links['entries'] ?? '');
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
                    <?php if ($entriesBase !== '') : ?>
                    <div class="fc-dashboard-card__head-aside">
                        <a class="fc-dashboard-card__link fc-dashboard-card__link--btn" href="<?php echo $h($entriesBase); ?>" data-nav-full="1" data-fc-dashboard-entries-all-link data-route="planner-entries">
                            View entries
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <?php endif; ?>
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
