            <header class="fc-admin-topbar z-40 flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:gap-4 sm:px-6">
                <button
                    type="button"
                    id="fc-admin-menu-toggle"
                    class="fc-admin-topbar__menu-btn flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 lg:hidden"
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="fc-admin-sidebar"
                >
                    <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
                </button>
                <h1 id="fc-admin-page-title" class="fc-admin-topbar__title min-w-0 flex-1 truncate text-lg font-semibold text-slate-900 sm:text-xl">
                    <?php echo htmlspecialchars($pageTitle); ?>
                </h1>
                <div class="fc-admin-topbar__actions shrink-0">
                    <div class="fc-admin-theme-switcher shrink-0" role="group" aria-label="Appearance">
                        <button
                            type="button"
                            class="fc-admin-theme-switcher__btn fc-admin-theme-switcher__btn--active"
                            data-fc-admin-theme-set="light"
                            aria-label="Light mode"
                            aria-pressed="true"
                            title="Light mode"
                        >
                            <i class="fa-solid fa-sun text-sm" aria-hidden="true"></i>
                        </button>
                        <button
                            type="button"
                            class="fc-admin-theme-switcher__btn"
                            data-fc-admin-theme-set="dark"
                            aria-label="Dark mode"
                            aria-pressed="false"
                            title="Dark mode"
                        >
                            <i class="fa-solid fa-moon text-sm" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php
                    if (!function_exists('fc_storage_cache_stats')) {
                        require_once FC_ROOT . '/config/storage.php';
                    }
                    $fcCacheStats = fc_storage_cache_stats();
                    $fcCacheAllLabel = (string) ($fcCacheStats['all']['label'] ?? '0 items (0B)');
                    $fcCacheLookupLabel = (string) (($fcCacheStats['buckets']['lookup']['label'] ?? null) ?: '0 items (0B)');
                    $fcCacheProductsLabel = (string) (($fcCacheStats['buckets']['products']['label'] ?? null) ?: '0 items (0B)');
                    ?>
                    <div
                        class="fc-entries-date-dropdown fc-admin-cache-dropdown shrink-0"
                        data-fc-cache-purge-dropdown
                        data-fc-cache-api="api.php?module=cache"
                        data-fc-cache-csrf="<?php echo htmlspecialchars(function_exists('fc_auth_csrf_token') ? fc_auth_csrf_token() : '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <button
                            type="button"
                            class="fc-admin-topbar__menu-btn fc-entries-date-dropdown__toggle fc-admin-cache-dropdown__toggle flex shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                            id="fc-admin-cache-toggle"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            aria-controls="fc-admin-cache-panel"
                            aria-label="Purge caches"
                            title="Purge caches"
                        >
                            <i class="fa-solid fa-database fc-entries-date-dropdown__icon" aria-hidden="true"></i>
                            <i class="fa-solid fa-chevron-down fc-entries-date-dropdown__caret fc-admin-cache-dropdown__caret" aria-hidden="true"></i>
                        </button>
                        <div
                            class="fc-entries-date-dropdown__panel fc-admin-cache-dropdown__panel"
                            id="fc-admin-cache-panel"
                            role="menu"
                            aria-labelledby="fc-admin-cache-toggle"
                            hidden
                        >
                            <div class="fc-admin-cache-dropdown__head">
                                <span class="fc-admin-cache-dropdown__head-title">Clear cache</span>
                                <span class="fc-admin-cache-dropdown__head-hint">Choose a cache to purge</span>
                            </div>
                            <div class="fc-entries-date-dropdown__presets fc-admin-cache-dropdown__presets">
                                <button type="button" class="fc-entries-date-dropdown__option fc-admin-cache-dropdown__option" role="menuitem" data-fc-cache-purge="lookup">
                                    <span class="fc-admin-cache-dropdown__option-icon" aria-hidden="true">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <span class="fc-admin-cache-dropdown__option-text">
                                        <span class="fc-admin-cache-dropdown__option-label">Lookup</span>
                                        <span class="fc-admin-cache-dropdown__option-meta" data-fc-cache-meta><?php echo htmlspecialchars($fcCacheLookupLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </button>
                                <button type="button" class="fc-entries-date-dropdown__option fc-admin-cache-dropdown__option" role="menuitem" data-fc-cache-purge="products">
                                    <span class="fc-admin-cache-dropdown__option-icon" aria-hidden="true">
                                        <i class="fa-solid fa-box"></i>
                                    </span>
                                    <span class="fc-admin-cache-dropdown__option-text">
                                        <span class="fc-admin-cache-dropdown__option-label">Products</span>
                                        <span class="fc-admin-cache-dropdown__option-meta" data-fc-cache-meta><?php echo htmlspecialchars($fcCacheProductsLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </button>
                            </div>
                            <div class="fc-admin-cache-dropdown__divider" role="separator"></div>
                            <div class="fc-admin-cache-dropdown__footer">
                                <button type="button" class="fc-entries-date-dropdown__option fc-admin-cache-dropdown__option fc-admin-cache-dropdown__option--all" role="menuitem" data-fc-cache-purge="all">
                                    <span class="fc-admin-cache-dropdown__option-icon" aria-hidden="true">
                                        <i class="fa-solid fa-broom"></i>
                                    </span>
                                    <span class="fc-admin-cache-dropdown__option-text">
                                        <span class="fc-admin-cache-dropdown__option-label">Purge All</span>
                                        <span class="fc-admin-cache-dropdown__option-meta" data-fc-cache-meta><?php echo htmlspecialchars($fcCacheAllLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
