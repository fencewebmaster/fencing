<?php
/**
 * FC Admin -- main layout (shell chrome: head/styles, sidebar, topbar, page-content
 * slot, scripts). Rendered by public/index.php via view('admin.layouts.main', $vars):
 * the extracted $vars are AdminContext::toLayoutVars() plus $fcAdminFillLayout and
 * $fcCan added by the front controller. The content slot renders the page view with
 * view('admin.<page>', get_defined_vars()) so pages keep seeing this full scope.
 */

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en" class="h-full" data-fc-admin-theme="light" data-fc-debug="<?php
    echo \Fc\Admin\Services\ConsoleSettings::debugMode() ? '1' : '0';
?>">
<head>
    <script>
    (function(){try{var t=localStorage.getItem('fc-admin-appearance');document.documentElement.setAttribute('data-fc-admin-theme',t==='dark'?'dark':'light');if(localStorage.getItem('fc-admin-sidebar-collapsed')==='1'){document.documentElement.classList.add('fc-admin-sidebar-collapsed');}}catch(e){}})();
    window.FC_DEBUG = document.documentElement.getAttribute('data-fc-debug') === '1';
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars($fcAdminBase . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <title>FC Admin</title>
    <?php if (preg_match('#^products/fence-styles/edit/#', $fcAdminRoute)) : ?>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js" as="script">
    <?php endif; ?>
    <?php echo \Fc\Admin\Services\ThemeSettings::cssBlock(); ?>
    <link href="assets/css/vendor/bootstrap/bootstrap.min.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/vendor/bootstrap/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($fcFontsHref, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/buttons.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/buttons.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/theme.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/theme.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/sidebar.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/sidebar.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/gallery.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/gallery.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/entries.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/entries.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/group-permissions.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/group-permissions.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/dashboard.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/dashboard.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/lazy.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/lazy.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/fence-styles.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/fence-styles.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/admin/store-products.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/admin/store-products.css'); ?>">
    <?php
    $fcFavicon = \Fc\Admin\Services\BrandingSettings::faviconUrl($fcAppBase ?? '');
    if ($fcFavicon !== '') : ?>
    <link rel="icon" href="<?php echo htmlspecialchars((string) $fcFavicon, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="assets/css/vendor/tailwind.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/vendor/tailwind.css'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/vendor/fontawesome/css/all.min.css?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/css/vendor/fontawesome/css/all.min.css'); ?>">
    <style>
        html,
        body {
            height: 100%;
            overflow: hidden;
        }
        body.fc-admin-sidebar-open {
            overflow: hidden;
        }
        html.fc-admin-scroll-lock,
        html.fc-admin-scroll-lock body {
            overflow: hidden !important;
        }
        #fc-admin-main.fc-admin-scroll-lock {
            overflow: hidden !important;
            overscroll-behavior: none;
        }
        #fc-admin-modal-root:not(:empty) {
            pointer-events: auto;
        }
        #fc-admin-shell {
            height: 100%;
            overflow: hidden;
        }
        /* Modal close — match /fc planner (orange circle + white X), compact for admin */
        .fencing-modal-close {
            appearance: none;
            -webkit-appearance: none;
            position: absolute;
            top: 10px;
            right: 10px;
            margin: 0;
            padding: 0;
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            border: 2px solid var(--fc-princeton-orange);
            border-radius: 50%;
            background: var(--fc-princeton-orange);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            box-shadow: 0 2px 8px var(--fc-a-orange-22);
            transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
            z-index: 6;
        }
        .fencing-modal-close::before,
        .fencing-modal-close::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 14px;
            height: 2px;
            margin-top: -1px;
            margin-left: -7px;
            border-radius: 1px;
            background-color: var(--fc-white);
            transition: background-color 0.2s ease, opacity 0.2s ease, transform 0.45s ease;
        }
        .fencing-modal-close::before {
            transform: rotate(45deg);
        }
        .fencing-modal-close::after {
            transform: rotate(-45deg);
        }
        .fencing-modal-close:hover {
            background: var(--fc-pumpkin);
            border-color: var(--fc-pumpkin);
            box-shadow: 0 4px 14px var(--fc-a-orange-38);
            transform: scale(1.05);
        }
        .fencing-modal-close:hover::before {
            transform: rotate(405deg);
        }
        .fencing-modal-close:hover::after {
            transform: rotate(315deg);
        }
        .fencing-modal-close:focus {
            outline: none;
        }
        .fencing-modal-close:focus-visible {
            outline: 2px solid var(--fc-white);
            outline-offset: 3px;
            box-shadow: 0 0 0 4px var(--fc-a-orange-25);
        }
        /* Fence Styles — match planner Step 1 (.fencing-style-item) */
        .fc-admin-fence-styles__grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .fc-admin-fence-styles__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 768px) {
            .fc-admin-fence-styles__grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .fc-admin-fence-styles__grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        @media (min-width: 1280px) {
            .fc-admin-fence-styles__grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }
        .fc-admin-fence-styles .fc-admin-fence-style-link {
            float: none;
            display: block;
            cursor: pointer;
            background: var(--fc-white, #fff);
            position: relative;
            text-decoration: none;
            color: inherit;
        }
        .fc-admin-fence-styles .fc-admin-fence-style-item:not(.fc-admin-fence-style-link) {
            float: none;
            cursor: default;
            background: var(--fc-white, #fff);
            position: relative;
        }
        .fc-admin-fence-styles .fc-admin-fence-style-item > div,
        .fc-admin-fence-styles .fc-admin-fence-style-link > div {
            position: relative;
            border-radius: 8px;
            transition: transform 0.35s cubic-bezier(0.34, 1.2, 0.64, 1), box-shadow 0.35s ease;
        }
        .fc-admin-fence-styles .fc-admin-fence-style-link:hover > div {
            transform: translateY(-6px);
            box-shadow: 0 14px 32px var(--fc-a-orange-22, rgba(246, 121, 37, 0.22)), 0 6px 16px rgba(0, 0, 0, 0.08);
        }
        .fc-admin-fence-styles .fc-admin-fence-style-link:hover .fencing-style-img {
            border-color: var(--fc-princeton-orange, #f67925);
        }
        .fc-admin-fence-styles .fc-admin-fence-style-link:hover .fencing-style-img img {
            transform: scale(1.06);
        }
        .fc-admin-fence-styles .fc-admin-fence-style-link:hover .fencing-style-title {
            border-color: var(--fc-princeton-orange, #f67925);
            background: var(--fc-white, #fff);
            color: var(--fc-princeton-orange, #f67925);
        }
        .fc-admin-fence-styles .fc-admin-fence-style-link:focus-visible {
            outline: 2px solid var(--fc-princeton-orange, #f67925);
            outline-offset: 3px;
            border-radius: 8px;
        }
        .fc-admin-fence-styles .fc-admin-fence-style-item .fencing-style-img,
        .fc-admin-fence-styles .fc-admin-fence-style-link .fencing-style-img {
            position: relative;
            opacity: 1;
            overflow: hidden;
            border: 2px solid var(--fc-alto-4, #ddd);
            border-radius: 8px 8px 0 0;
            padding: 10px;
            background: var(--fc-white, #fff);
            transition: opacity 0.35s ease, border-color 0.35s ease;
        }
        .fc-admin-fence-styles .fc-admin-fence-style-item .fencing-style-img img,
        .fc-admin-fence-styles .fc-admin-fence-style-link .fencing-style-img img {
            width: 100%;
            display: block;
            transition: transform 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .fc-admin-fence-styles .fc-admin-fence-style-item .fencing-style-title,
        .fc-admin-fence-styles .fc-admin-fence-style-link .fencing-style-title {
            text-align: center;
            font-size: 15px;
            border: 2px solid var(--fc-wild-sand, #f4f4f4);
            border-top: 0;
            background: var(--fc-alabaster, #fafafa);
            font-weight: 400;
            border-radius: 0 0 8px 8px;
            padding: 10px;
            color: inherit;
            transition: color 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
        }
        @media (max-width: 768px) {
            .fc-admin-fence-styles .fc-admin-fence-style-item .fencing-style-title {
                font-size: 14px;
            }
        }
        .fc-admin-fence-styles .fc-admin-fence-style-img-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 6rem;
            width: 100%;
        }
        .fc-admin-fence-styles .fc-admin-fence-style-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 2;
            border-radius: 9999px;
            padding: 0.125rem 0.5rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .fc-admin-fence-styles .fc-admin-fence-style-badge--live {
            background: rgba(25, 135, 84, 0.92);
            color: #fff;
        }
        .fc-admin-fence-styles .fc-admin-fence-style-badge--draft {
            background: rgba(100, 116, 139, 0.92);
            color: #fff;
        }
        /* Fence style edit — settings manager */
        .fc-fs-edit {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }
        .fc-fs-edit-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem 1rem;
            flex-shrink: 0;
            width: 100%;
            margin: 0;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04), 0 4px 12px rgba(15, 23, 42, 0.04);
            z-index: 30;
        }
        .fc-fs-edit-toolbar::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #ea580c;
            border-radius: 0 2px 2px 0;
        }
        .fc-fs-edit-toolbar__start {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem 1rem;
            min-width: 0;
            flex: 1 1 auto;
        }
        .fc-fs-edit-toolbar__tabs {
            min-width: 0;
        }
        .fc-fs-edit-toolbar__actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }
        .fc-fs-edit-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            gap: 1.25rem;
            padding: 1rem 1.25rem 1.5rem;
        }
        @media (min-width: 1024px) {
            .fc-fs-edit-body {
                grid-template-columns: minmax(0, 1fr) minmax(0, 18rem);
                align-items: start;
            }
        }
        .fc-fs-preview-col {
            position: sticky;
            top: 0;
            align-self: start;
        }
        .fc-fs-main-col {
            min-width: 0;
        }
        .fc-fs-panel-head {
            margin-bottom: 1rem;
        }
        .fc-fs-panel-head--row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem 1rem;
        }
        .fc-fs-panel-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }
        .fc-fs-panel-desc {
            margin: 0.25rem 0 0;
            font-size: 0.8125rem;
            color: #64748b;
        }
        .fc-fs-form-grid,
        .fc-fs-mini-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.85rem 1rem;
        }
        @media (min-width: 640px) {
            .fc-fs-form-grid,
            .fc-fs-mini-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .fc-fs-field__label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.35rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #646970;
        }
        .fc-fs-input {
            width: 100%;
            min-width: 0;
            border: 1px solid #8c8f94;
            border-radius: 3px;
            padding: 0.4rem 0.65rem;
            font-size: 0.875rem;
            line-height: 1.4;
            background: #fff;
            color: #2c3338;
            transition: border-color 0.12s ease, box-shadow 0.12s ease;
        }
        .fc-fs-input::placeholder {
            color: #94a3b8;
        }
        .fc-fs-input:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        .fc-fs-input--readonly {
            background: #f6f7f7;
            color: #646970;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            cursor: not-allowed;
        }
        .fc-fs-input--readonly:focus {
            border-color: #8c8f94;
            box-shadow: none;
        }

        .fc-fs-json-editor {
            display: block;
            width: 100%;
            min-height: 10rem;
            border: none;
            padding: 0.75rem 0.85rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            line-height: 1.45;
            color: #0f172a;
            background: #fff;
            resize: vertical;
        }
        .fc-fs-json-editor--full {
            min-height: 24rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
        }
        .fc-fs-json-editor--error {
            box-shadow: inset 0 0 0 2px #ef4444;
        }
        .fc-fs-gui-stack {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .fc-fs-gui-marker {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.375rem;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .fc-fs-gui-field {
            display: block;
        }
        .fc-fs-gui-field--span {
            grid-column: 1 / -1;
        }
        .fc-fs-gui-field__label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.35rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #646970;
        }
        .fc-fs-gui-field__hint {
            display: block;
            margin: 0.35rem 0 0;
            font-size: 0.75rem;
            line-height: 1.45;
            color: #646970;
        }
        .fc-fs-panel-group-tabs__list {
            display: inline-flex;
            gap: 0;
            padding: 0.2rem;
            border-radius: 0.625rem;
            background: #e2e8f0;
        }
        .fc-fs-panel-group-tabs__item {
            position: relative;
            margin: 0;
        }
        .fc-fs-panel-group-tabs__input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .fc-fs-panel-group-tabs__label {
            display: block;
            min-width: 6.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.45rem;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25;
            color: #64748b;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
            user-select: none;
        }
        .fc-fs-panel-group-tabs__input:focus-visible + .fc-fs-panel-group-tabs__label {
            outline: 2px solid var(--fc-princeton-orange, #f67925);
            outline-offset: 2px;
        }
        .fc-fs-panel-group-tabs__input:checked + .fc-fs-panel-group-tabs__label {
            background: var(--fc-princeton-orange, #f67925);
            color: #fff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
        }
        .fc-fs-panel-group-tabs__input:not(:checked) + .fc-fs-panel-group-tabs__label:hover {
            color: #334155;
        }
        .fc-fs-gui-field--wysiwyg .tox .tox-edit-area__iframe {
            background: #fff;
        }
        .fc-fs-input--mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8125rem;
        }
        .fc-fs-gui-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
            user-select: none;
        }
        .fc-fs-gui-toggle__input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .fc-fs-gui-toggle__track {
            width: 2.25rem;
            height: 1.25rem;
            border-radius: 9999px;
            background: #cbd5e1;
            position: relative;
            transition: background 0.2s;
            flex-shrink: 0;
        }
        .fc-fs-gui-toggle__track::after {
            content: '';
            position: absolute;
            top: 0.15rem;
            left: 0.15rem;
            width: 0.95rem;
            height: 0.95rem;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.18);
            transition: transform 0.2s;
        }
        .fc-fs-gui-toggle__input:checked + .fc-fs-gui-toggle__track {
            background: var(--fc-princeton-orange, #f67925);
        }
        .fc-fs-gui-toggle__input:checked + .fc-fs-gui-toggle__track::after {
            transform: translateX(1rem);
        }
        .fc-fs-style-preview__live-toggle .fc-fs-gui-toggle__input:checked + .fc-fs-gui-toggle__track {
            background: var(--fc-green, #04a725);
        }
        .fc-fs-gui-toggle__label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: #334155;
        }
        .fc-fs-gui-modals-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .fc-fs-gui-modals-layout {
                grid-template-columns: minmax(0, 11rem) minmax(0, 1fr);
            }
        }
        .fc-fs-gui-modals-nav {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 0;
            padding: 0.35rem;
            background: #f8fafc;
            position: sticky;
            top: 0;
            align-self: start;
            max-height: 100vh;
            overflow: auto;
        }
        .fc-fs-gui-modals-nav__item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.1rem;
            width: 100%;
            text-align: left;
            border: none;
            background: transparent;
            border-radius: 0;
            padding: 0.55rem 0.65rem;
            cursor: pointer;
        }
        .fc-fs-gui-modals-nav__item span {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #0f172a;
        }
        .fc-fs-gui-modals-nav__item small {
            font-size: 0.6875rem;
            color: #64748b;
        }
        .fc-fs-gui-modals-nav__item.is-active {
            background: #fff;
            box-shadow: 0 0 0 1px #fdba74;
        }

        .fc-fs-gui-fields-stack {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .fc-fs-gui-card--form-field .fc-fs-gui-card__head {
            align-items: center;
        }
        .fc-fs-kv-table {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            margin-bottom: 0.65rem;
        }
        .fc-fs-kv-row {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) minmax(0, 1fr) auto;
            gap: 0.5rem 0.65rem;
            align-items: end;
            padding: 0.65rem;
            border: 1px solid #e2e8f0;
            border-radius: 0;
            background: #f8fafc;
        }
        .fc-fs-kv-row__grip {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 2.25rem;
            margin-bottom: 0.1rem;
            border: none;
            border-radius: 0.35rem;
            background: transparent;
            color: #94a3b8;
            cursor: grab;
            touch-action: none;
            transition: color 0.15s, background 0.15s;
        }
        .fc-fs-kv-row__grip:hover {
            color: #6366f1;
            background: #eef2ff;
        }
        .fc-fs-kv-row__grip:active {
            cursor: grabbing;
        }
        .fc-fs-kv-row--dragging {
            opacity: 0.55;
            box-shadow: 0 0 0 2px #a5b4fc;
        }
        .fc-fs-kv-row--drag-over {
            border-color: #818cf8;
            background: #eef2ff;
        }
        @media (max-width: 639px) {
            .fc-fs-kv-row {
                grid-template-columns: auto minmax(0, 1fr);
            }
            .fc-fs-kv-row__grip {
                grid-row: 1 / span 3;
                align-self: center;
            }
        }
        .fc-fs-kv-row__remove {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            margin-bottom: 0.1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.45rem;
            background: #fff;
            color: #64748b;
            cursor: pointer;
            transition: color 0.15s, border-color 0.15s, background 0.15s;
        }
        .fc-fs-kv-row__remove:hover {
            color: #dc2626;
            border-color: #fecaca;
            background: #fef2f2;
        }
        .fc-fs-kv-block .fc-fs-kv-add {
            margin-top: 0.15rem;
        }
        .fc-fs-options {
            margin-top: 0.85rem;
            border-top: 1px dashed #e2e8f0;
            padding-top: 0.85rem;
        }
        .fc-fs-options__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.65rem;
        }
        .fc-fs-options__title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155;
        }
        .fc-fs-options__empty {
            font-size: 0.8125rem;
            color: #94a3b8;
            margin: 0;
        }
        .fc-fs-options__list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        .fc-fs-option-card {
            border: 1px solid #e2e8f0;
            border-radius: 0;
            background: #fafafa;
            overflow: hidden;
        }
        .fc-fs-option-card__main {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.75rem;
            padding-top: 0.875rem;
            align-items: stretch;
            position: relative;
        }
        .fc-fs-option-card__image-row {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
            width: 100%;
            padding-right: 1.75rem;
        }
        .fc-fs-option-card__featured-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .fc-fs-option-card__featured,
        .fc-fs-option-card__thumb {
            width: 7.5rem;
            height: 7.5rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .fc-fs-option-card__featured img,
        .fc-fs-option-card__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .fc-fs-option-card__featured--empty,
        .fc-fs-option-card__thumb--empty {
            color: #cbd5e1;
            font-size: 1.75rem;
            background: #f8fafc;
        }
        .fc-fs-option-card__image-path {
            min-width: 0;
        }
        .fc-fs-option-card__fields {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.55rem;
            width: 100%;
            padding-top: 0.15rem;
            border-top: 1px solid #e2e8f0;
        }
        @media (min-width: 640px) {
            .fc-fs-option-card__featured,
            .fc-fs-option-card__thumb {
                width: 9rem;
                height: 9rem;
            }
            .fc-fs-option-card__fields {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .fc-fs-option-card__remove {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 1;
            border: none;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
        }
        .fc-fs-option-card__remove:hover {
            color: #ef4444;
        }
        .fc-fs-option-card__size {
            border-top: 1px solid #e2e8f0;
            padding: 0.55rem 0.75rem 0.75rem;
        }
        .fc-fs-option-card__size summary {
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.45rem;
        }
    </style>
</head>
<body class="fc-admin-page bg-slate-100 text-slate-800 antialiased h-full" data-fc-admin-base="<?php echo htmlspecialchars($fcAdminBase, ENT_QUOTES, 'UTF-8'); ?>" data-fc-app-base="<?php echo htmlspecialchars($fcAppBase, ENT_QUOTES, 'UTF-8'); ?>" data-fc-admin-initial-route="<?php echo htmlspecialchars($fcAdminRoute, ENT_QUOTES, 'UTF-8'); ?>" data-fc-date-format="<?php echo htmlspecialchars((string) ($fcDateFormat ?? 'M. j, Y h:i A'), ENT_QUOTES, 'UTF-8'); ?>" data-fc-can-media-list="<?php echo $fcCan('media_library.view_list') ? '1' : '0'; ?>" data-fc-can-media-upload="<?php echo $fcCan('media_library.upload') ? '1' : '0'; ?>" data-fc-can-media-delete="<?php echo $fcCan('media_library.delete') ? '1' : '0'; ?>">
    <!-- Mobile sidebar backdrop -->
    <div
        id="fc-admin-sidebar-backdrop"
        class="fixed inset-0 z-40 bg-slate-900/60 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden"
        aria-hidden="true"
    ></div>

    <div id="fc-admin-shell" class="flex h-full w-full">
        <!-- Sidebar: fixed full height; does not scroll with main content -->
        <aside
            id="fc-admin-sidebar"
            class="fixed inset-y-0 left-0 z-50 flex h-full max-w-[85vw] -translate-x-full flex-col border-r transition-transform duration-200 ease-out lg:max-w-none lg:translate-x-0"
            aria-label="Admin navigation"
            aria-hidden="true"
        >
            <?php include __DIR__ . '/partials/sidebar-brand.php'; ?>

            <nav class="fc-sidebar-nav" id="fc-admin-nav" aria-label="Main">
                <ul class="fc-sidebar-nav__list">
                    <?php
                    $fcSiteSwitched = \Fc\Admin\Services\AdminSiteRegistry::isSiteSwitched();
                    $fcShowDashboard = $fcSiteSwitched
                        || \Fc\Admin\Services\PermissionService::canAny(\Fc\Admin\Models\GroupPermissionsModel::dashboardKeys());
                    ?>
                    <?php if ($fcShowDashboard) : ?>
                    <li>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/', ENT_QUOTES, 'UTF-8'); ?>"
                            data-nav
                            data-route="dashboard"
                            data-title="Dashboard"
                            class="fc-sidebar-nav__link<?php echo ($fcAdminRoute === 'dashboard' || $fcAdminRoute === '') ? ' is-active' : ''; ?>"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-gauge-high"></i></span>
                            <span class="fc-sidebar-nav__label">Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    $fcShowSystemProducts = !$fcSiteSwitched && $fcCan('products.system_products.view');
                    $fcShowStoreProducts = !$fcSiteSwitched && $fcCan('products.store_products.view');
                    $fcShowFenceStyles = !$fcSiteSwitched && $fcCan('products.fence_styles.view_list');
                    $fcShowProductsGroup = $fcShowSystemProducts || $fcShowStoreProducts || $fcShowFenceStyles;
                    $fcProductsRouteActive = str_starts_with((string) $fcAdminRoute, 'products/');
                    ?>
                    <?php if ($fcShowProductsGroup) : ?>
                    <li class="fc-sidebar-nav__group<?php echo $fcProductsRouteActive ? ' is-open' : ''; ?>" id="fc-admin-products-group">
                        <button
                            type="button"
                            class="fc-sidebar-nav__group-toggle<?php echo $fcProductsRouteActive ? ' is-expanded' : ''; ?>"
                            id="fc-admin-products-toggle"
                            aria-expanded="<?php echo $fcProductsRouteActive ? 'true' : 'false'; ?>"
                            aria-controls="fc-admin-products-submenu"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-cubes"></i></span>
                            <span class="fc-sidebar-nav__label">Products</span>
                            <i class="fa-solid fa-chevron-down fc-sidebar-nav__chevron" aria-hidden="true"></i>
                        </button>
                        <ul id="fc-admin-products-submenu" class="fc-sidebar-nav__submenu">
                            <?php if ($fcShowSystemProducts) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/products/system-products', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-route="products/system-products"
                                    data-title="System Products"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'products/system-products' ? ' is-active' : ''; ?>"
                                >
                                    System Products
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($fcShowStoreProducts) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/products/store-products', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-route="products/store-products"
                                    data-title="Store Products"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'products/store-products' ? ' is-active' : ''; ?>"
                                >
                                    Store Products
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($fcShowFenceStyles) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/products/fence-styles', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-route="products/fence-styles"
                                    data-title="Fence Styles"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'products/fence-styles' || str_starts_with((string) $fcAdminRoute, 'products/fence-styles/') ? ' is-active' : ''; ?>"
                                >
                                    Fence Styles
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php
                    $fcShowGallery = !$fcSiteSwitched && $fcCan('media_library.view_list');
                    $fcShowEntries = $fcSiteSwitched || $fcCan('planner_entries.view_list');
                    $fcShowContentSection = $fcShowGallery || $fcShowEntries;
                    ?>
                    <?php if ($fcShowContentSection) : ?>
                    <li class="fc-sidebar-nav__section-label" aria-hidden="true">Content</li>
                    <?php endif; ?>

                    <?php if ($fcShowGallery) : ?>
                    <li>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/gallery', ENT_QUOTES, 'UTF-8'); ?>"
                            data-nav
                            data-route="gallery"
                            data-title="Media Library"
                            class="fc-sidebar-nav__link<?php echo $fcAdminRoute === 'gallery' ? ' is-active' : ''; ?>"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-images"></i></span>
                            <span class="fc-sidebar-nav__label">Media Library</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($fcShowEntries) : ?>
                    <li>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/' . $fcPlannerEntriesRoute, ENT_QUOTES, 'UTF-8'); ?>"
                            data-nav
                            data-nav-full="1"
                            data-route="<?php echo htmlspecialchars($fcPlannerEntriesRoute, ENT_QUOTES, 'UTF-8'); ?>"
                            data-title="<?php echo htmlspecialchars($fcPlannerEntriesTitle, ENT_QUOTES, 'UTF-8'); ?>"
                            class="fc-sidebar-nav__link<?php echo $fcAdminRoute === $fcPlannerEntriesRoute || str_starts_with((string) $fcAdminRoute, $fcPlannerEntriesRoute . '/') ? ' is-active' : ''; ?>"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
                            <span class="fc-sidebar-nav__label"><?php echo htmlspecialchars($fcPlannerEntriesTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    $fcShowUsersList = !$fcSiteSwitched && $fcCan('users.view_list');
                    $fcShowGroupPerms = !$fcSiteSwitched && $fcCan('users.group_permissions');
                    $fcShowUsersGroup = $fcShowUsersList || $fcShowGroupPerms;
                    $fcShowSettings = !$fcSiteSwitched && $fcCan('settings.settings');
                    $fcShowSystemSection = $fcShowUsersGroup || $fcShowSettings;
                    $fcUsersRouteActive = $fcAdminRoute === 'users' || str_starts_with((string) $fcAdminRoute, 'users/');
                    ?>
                    <?php if ($fcShowSystemSection) : ?>
                    <li class="fc-sidebar-nav__section-label" aria-hidden="true">System</li>
                    <?php endif; ?>

                    <?php if ($fcShowUsersGroup) : ?>
                    <li class="fc-sidebar-nav__group<?php echo $fcUsersRouteActive ? ' is-open' : ''; ?>" id="fc-admin-users-group">
                        <button
                            type="button"
                            class="fc-sidebar-nav__group-toggle<?php echo $fcUsersRouteActive ? ' is-expanded' : ''; ?>"
                            id="fc-admin-users-toggle"
                            aria-expanded="<?php echo $fcUsersRouteActive ? 'true' : 'false'; ?>"
                            aria-controls="fc-admin-users-submenu"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
                            <span class="fc-sidebar-nav__label">Users</span>
                            <i class="fa-solid fa-chevron-down fc-sidebar-nav__chevron" aria-hidden="true"></i>
                        </button>
                        <ul id="fc-admin-users-submenu" class="fc-sidebar-nav__submenu">
                            <?php if ($fcShowUsersList) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/users', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-nav-full="1"
                                    data-route="users"
                                    data-title="All Users"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'users' ? ' is-active' : ''; ?>"
                                >
                                    All Users
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($fcShowGroupPerms) : ?>
                            <li>
                                <a
                                    href="<?php echo htmlspecialchars($fcAdminBase . '/users/group-permissions', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-nav-child
                                    data-nav-full="1"
                                    data-route="users/group-permissions"
                                    data-title="Group Permissions"
                                    class="fc-sidebar-nav__sublink<?php echo $fcAdminRoute === 'users/group-permissions' ? ' is-active' : ''; ?>"
                                >
                                    Group Permissions
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php if ($fcShowSettings) : ?>
                    <li>
                        <a
                            href="<?php echo htmlspecialchars($fcAdminBase . '/settings', ENT_QUOTES, 'UTF-8'); ?>"
                            data-nav
                            data-route="settings"
                            data-title="Settings"
                            class="fc-sidebar-nav__link<?php echo $fcAdminRoute === 'settings' ? ' is-active' : ''; ?>"
                        >
                            <span class="fc-sidebar-nav__icon" aria-hidden="true"><i class="fa-solid fa-sliders"></i></span>
                            <span class="fc-sidebar-nav__label">Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="fc-sidebar-footer">
                <?php if (!empty($fcAuthUser) && is_array($fcAuthUser)) : ?>
                <?php
                $fcSidebarUserName = (string) (($fcAuthUser['display_name'] ?? '') !== ''
                    ? $fcAuthUser['display_name']
                    : ($fcAuthUser['login'] ?? 'Admin'));
                $fcSidebarUserEmail = (string) ($fcAuthUser['email'] ?? '');
                $fcSidebarUserInitials = '';
                foreach (preg_split('/\s+/', trim($fcSidebarUserName)) ?: [] as $fcSidebarNamePart) {
                    if ($fcSidebarNamePart === '') {
                        continue;
                    }
                    $fcSidebarUserInitials .= strtoupper(substr($fcSidebarNamePart, 0, 1));
                    if (strlen($fcSidebarUserInitials) >= 2) {
                        break;
                    }
                }
                if ($fcSidebarUserInitials === '') {
                    $fcSidebarUserInitials = 'A';
                }
                ?>
                <div class="fc-sidebar-user" data-fc-sidebar-user-menu>
                    <button
                        type="button"
                        class="fc-sidebar-user__toggle"
                        id="fc-sidebar-user-toggle"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-controls="fc-sidebar-user-menu"
                    >
                        <span class="fc-sidebar-user__avatar" aria-hidden="true"><?php echo htmlspecialchars($fcSidebarUserInitials, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="fc-sidebar-user__info">
                            <span class="fc-sidebar-user__name"><?php echo htmlspecialchars($fcSidebarUserName, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($fcSidebarUserEmail !== '') : ?>
                            <span class="fc-sidebar-user__email"><?php echo htmlspecialchars($fcSidebarUserEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </span>
                        <i class="fa-solid fa-chevron-up fc-sidebar-user__caret" aria-hidden="true"></i>
                    </button>
                    <div
                        class="fc-sidebar-user__menu"
                        id="fc-sidebar-user-menu"
                        role="menu"
                        aria-labelledby="fc-sidebar-user-toggle"
                        hidden
                    >
                        <a
                            href="<?php echo htmlspecialchars($fcAppBase . '/planner', ENT_QUOTES, 'UTF-8'); ?>"
                            class="fc-sidebar-user__menu-item"
                            role="menuitem"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                            <span>Open planner</span>
                        </a>
                        <a
                            href="<?php echo htmlspecialchars(
                                \Fc\Admin\Models\AuthPresenter::logoutUrl($fcAdminBase),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            class="fc-sidebar-user__menu-item fc-sidebar-user__menu-item--danger"
                            role="menuitem"
                        >
                            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                            <span>Sign out</span>
                        </a>
                    </div>
                </div>
                <?php else : ?>
                <a
                    href="<?php echo htmlspecialchars($fcAppBase . '/planner', ENT_QUOTES, 'UTF-8'); ?>"
                    class="fc-sidebar-footer__planner"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="fc-sidebar-footer__planner-icon" aria-hidden="true">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </span>
                    <span class="fc-sidebar-footer__planner-text">
                        <span class="fc-sidebar-footer__planner-label">Open planner</span>
                        <span class="fc-sidebar-footer__planner-name"><?php echo htmlspecialchars($fcBranding['appName'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </a>
                <?php endif; ?>
                <div class="fc-sidebar-footer__meta">
                    <span>&copy; <?php echo date('Y'); ?></span>
                    <span class="fc-sidebar-footer__version"><?php echo htmlspecialchars($fcBranding['version'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </aside>

        <!-- Main: offset for fixed sidebar; only this column scrolls vertically -->
        <div class="fc-admin-main-column flex h-full min-h-0 min-w-0 flex-1 flex-col">
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
                    <?php if (!empty($fcAdminIsDashboard) && is_array($fcDashboardPage ?? null)) : ?>
                    <?php
                    // Pure reads: DashboardPresenter::pageData() guarantees the key;
                    // the partial escapes via the global e() helper.
                    $page = $fcDashboardPage;
                    $datePeriodOptions = $page['date_period_options'];
                    $fcDashboardDateDropdownContext = 'topbar';
                    require __DIR__ . '/../partials/dashboard-date-dropdown.php';
                    ?>
                    <?php endif; ?>
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
                    $fcCacheStats = \Fc\Admin\Services\CacheStorageService::cacheStats();
                    $fcCacheAllLabel = (string) ($fcCacheStats['all']['label'] ?? '0 items (0B)');
                    $fcCacheLookupLabel = (string) (($fcCacheStats['buckets']['lookup']['label'] ?? null) ?: '0 items (0B)');
                    $fcCacheProductsLabel = (string) (($fcCacheStats['buckets']['products']['label'] ?? null) ?: '0 items (0B)');
                    $fcCacheCloudflareLabel = (string) (($fcCacheStats['buckets']['cloudflare']['label'] ?? null) ?: 'CDN not ready');
                    $fcCacheCloudflareReady = !empty($fcCacheStats['buckets']['cloudflare']['configured']);
                    ?>
                    <div
                        class="fc-entries-date-dropdown fc-admin-cache-dropdown shrink-0"
                        data-fc-cache-purge-dropdown
                        data-fc-cache-api="api.php?module=cache"
                        data-fc-cache-csrf="<?php echo htmlspecialchars(\Fc\Admin\Services\AuthService::csrfToken(), ENT_QUOTES, 'UTF-8'); ?>"
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
                                <button
                                    type="button"
                                    class="fc-entries-date-dropdown__option fc-admin-cache-dropdown__option<?php echo $fcCacheCloudflareReady ? '' : ' is-empty'; ?>"
                                    role="menuitem"
                                    data-fc-cache-purge="cloudflare"
                                    data-fc-cache-configured="<?php echo $fcCacheCloudflareReady ? '1' : '0'; ?>"
                                    <?php echo $fcCacheCloudflareReady ? '' : ' disabled aria-disabled="true"'; ?>
                                >
                                    <span class="fc-admin-cache-dropdown__option-icon" aria-hidden="true">
                                        <i class="fa-solid fa-cloud"></i>
                                    </span>
                                    <span class="fc-admin-cache-dropdown__option-text">
                                        <span class="fc-admin-cache-dropdown__option-label">Cloudflare</span>
                                        <span class="fc-admin-cache-dropdown__option-meta" data-fc-cache-meta><?php echo htmlspecialchars($fcCacheCloudflareLabel, ENT_QUOTES, 'UTF-8'); ?></span>
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

            <main id="fc-admin-main" class="fc-admin-main relative min-h-0 flex-1 overflow-x-hidden bg-slate-50<?php echo !empty($fcAdminFillLayout) ? ' flex flex-col overflow-hidden p-0' : ' overflow-y-auto p-4 sm:p-6'; ?>">
                <?php if (!empty($fcAuthSwitchFrom) && is_array($fcAuthSwitchFrom) && !empty($fcAuthUser) && is_array($fcAuthUser)) : ?>
                <?php
                $fcSwitchAsName = (string) (($fcAuthUser['display_name'] ?? '') !== ''
                    ? $fcAuthUser['display_name']
                    : ($fcAuthUser['login'] ?? 'user'));
                $fcSwitchFromName = (string) (($fcAuthSwitchFrom['display_name'] ?? '') !== ''
                    ? $fcAuthSwitchFrom['display_name']
                    : ($fcAuthSwitchFrom['login'] ?? 'admin'));
                $fcSwitchBackUrl = rtrim((string) $fcAdminBase, '/') . '/users/switch-back?_token=' . rawurlencode(
                    \Fc\Admin\Services\AuthService::mintOneTimeToken('switch-back')
                );
                ?>
                <div class="fc-auth-switch-banner<?php echo empty($fcAdminFillLayout) ? ' fc-auth-switch-banner--full-bleed' : ''; ?>" role="status">
                    <span>
                        Logged in as <strong><?php echo htmlspecialchars($fcSwitchAsName, ENT_QUOTES, 'UTF-8'); ?></strong>.
                        Return to <strong><?php echo htmlspecialchars($fcSwitchFromName, ENT_QUOTES, 'UTF-8'); ?></strong>?
                    </span>
                    <a class="fc-auth-switch-banner__action" href="<?php echo htmlspecialchars($fcSwitchBackUrl, ENT_QUOTES, 'UTF-8'); ?>">Switch back</a>
                </div>
                <?php endif; ?>
                <div
                    id="fc-admin-page-content"
                    class="fc-admin-content-card relative<?php echo !empty($fcAdminFillLayout) ? ' fc-admin-content-card--fill fc-admin-content-card--no-pad flex flex-1 min-h-0 flex-col overflow-hidden p-0 bg-white' : ($fcAdminIsDashboard ? ' fc-admin-content-card--dashboard p-0 bg-transparent shadow-none ring-0' : ' min-h-full rounded-lg bg-white shadow-sm ring-1 ring-slate-200/60 sm:rounded-xl'); ?>"
                    <?php if ($fcAdminIsDashboard) : ?>
                    data-route="dashboard"
                    data-fc-dashboard-server="1"
                    <?php elseif ($fcAdminRoute === $fcPlannerEntriesRoute) : ?>
                    data-route="<?php echo htmlspecialchars($fcPlannerEntriesRoute, ENT_QUOTES, 'UTF-8'); ?>"
                    data-fc-entries-server="1"
                    <?php elseif (!empty($fcAdminIsUsers)) : ?>
                    data-route="users"
                    data-fc-users-server="1"
                    <?php elseif (!empty($fcAdminIsGroupPermissions)) : ?>
                    data-route="users/group-permissions"
                    data-fc-group-permissions-server="1"
                    <?php elseif ($fcAdminIsSettings) : ?>
                    data-route="settings"
                    data-fc-settings-server="1"
                    <?php elseif ($fcAdminIsGallery) : ?>
                    data-route="gallery"
                    data-fc-gallery-server="1"
                    <?php elseif ($fcAdminRoute === 'products/store-products' && is_array($fcSystemProductsPage)) : ?>
                    data-route="products/store-products"
                    data-fc-system-products-server="1"
                    <?php elseif ($fcAdminRoute === 'products/system-products' && is_array($fcStoreProductsPage)) : ?>
                    data-route="products/system-products"
                    data-fc-store-products-server="1"
                    <?php elseif ($fcAdminRoute === 'products/fence-styles') : ?>
                    data-route="products/fence-styles"
                    data-fc-fence-styles-server="1"
                    <?php elseif (preg_match('#^products/fence-styles/edit/#', (string) $fcAdminRoute)) : ?>
                    data-route="<?php echo htmlspecialchars($fcAdminRoute, ENT_QUOTES, 'UTF-8'); ?>"
                    <?php endif; ?>
                    aria-live="polite"
                >
                <?php
                // Page views render via the global view() helper (dot names under
                // app/views/admin/). get_defined_vars() hands each page the full
                // layout scope, exactly what the old same-scope include gave it.
                // The two products branches are deliberately cross-wired (view
                // filename says the opposite of the route it serves) — see
                // ProductsPageController before "fixing" either line.
                if ($fcAdminIsDashboard && is_array($fcDashboardPage)) : ?>
                    <?php view('admin.dashboard.index', get_defined_vars()); ?>
                <?php elseif ($fcAdminRoute === $fcPlannerEntriesRoute && is_array($fcEntriesDetailPage)) : ?>
                    <?php view('admin.entries.detail', get_defined_vars()); ?>
                <?php elseif ($fcAdminRoute === $fcPlannerEntriesRoute && is_array($fcEntriesPage)) : ?>
                    <?php view('admin.entries.index', get_defined_vars()); ?>
                <?php elseif (!empty($fcAdminIsUsers) && is_array($fcUsersPage)) : ?>
                    <?php view('admin.users.index', get_defined_vars()); ?>
                <?php elseif (!empty($fcAdminIsGroupPermissions) && is_array($fcGroupPermissionsPage)) : ?>
                    <?php view('admin.users.group-permissions', get_defined_vars()); ?>
                <?php elseif ($fcAdminIsSettings && is_array($fcSettingsPage)) : ?>
                    <?php view('admin.settings.index', get_defined_vars()); ?>
                <?php elseif ($fcAdminIsGallery && is_array($fcGalleryPage)) : ?>
                    <?php view('admin.gallery.index', get_defined_vars()); ?>
                <?php elseif ($fcAdminRoute === 'products/fence-styles' && is_array($fcFenceStylesPage)) : ?>
                    <?php view('admin.products.fence-styles', get_defined_vars()); ?>
                <?php elseif ($fcAdminRoute === 'products/store-products' && is_array($fcSystemProductsPage)) : ?>
                    <?php view('admin.products.system-products', get_defined_vars()); ?>
                <?php elseif ($fcAdminRoute === 'products/system-products' && is_array($fcStoreProductsPage)) : ?>
                    <?php view('admin.products.store-products', get_defined_vars()); ?>
                <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="fc-admin-modal-root" class="pointer-events-none fixed inset-0 z-[9999]" aria-hidden="true"></div>

    <script src="assets/js/admin/core/namespace.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/namespace.js'); ?>"></script>
    <script src="assets/js/admin/utils/dom.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/utils/dom.js'); ?>"></script>
    <script src="assets/js/admin/utils/clipboard.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/utils/clipboard.js'); ?>"></script>
    <script src="assets/js/admin/utils/toast-bridge.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/utils/toast-bridge.js'); ?>"></script>
    <script src="assets/js/admin/utils/flash.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/utils/flash.js'); ?>"></script>
    <script src="assets/js/admin/components/copy-field-button.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/components/copy-field-button.js'); ?>"></script>
    <script src="assets/js/admin/components/dropdown-registry.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/components/dropdown-registry.js'); ?>"></script>
    <script src="assets/js/admin/core/page-controller.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/page-controller.js'); ?>"></script>
    <script src="assets/js/admin/core/admin-ui.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/admin-ui.js'); ?>"></script>
    <script src="assets/js/admin/core/theme.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/theme.js'); ?>"></script>
    <script src="assets/js/admin/core/admin-appearance.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/admin-appearance.js'); ?>"></script>
    <script src="assets/js/admin/core/lazy.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/lazy.js'); ?>"></script>
    <script src="assets/js/admin/core/toast.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/toast.js'); ?>"></script>
    <script src="assets/js/admin/core/cache-purge.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/cache-purge.js'); ?>"></script>
    <?php if ($fcAdminIsDashboard) : ?>
    <script src="assets/js/admin/vendor/chart.umd.min.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/vendor/chart.umd.min.js'); ?>"></script>
    <script src="assets/js/admin/dashboard/entries-date-filter.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/dashboard/entries-date-filter.js'); ?>"></script>
    <script src="assets/js/admin/dashboard/dashboard.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/dashboard/dashboard.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php elseif ($fcAdminIsEntries) : ?>
    <script src="assets/js/admin/core/modal.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/modal.js'); ?>"></script>
    <script src="assets/js/admin/entries/filters.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/entries/filters.js'); ?>"></script>
    <script src="assets/js/admin/dashboard/entries-date-filter.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/dashboard/entries-date-filter.js'); ?>"></script>
    <script src="assets/js/admin/components/copy-tooltip.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/components/copy-tooltip.js'); ?>"></script>
    <script src="assets/js/admin/entries/planner-copy.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/entries/planner-copy.js'); ?>"></script>
    <script src="assets/js/admin/entries/bulk.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/entries/bulk.js'); ?>"></script>
    <script src="assets/js/admin/entries/detail-copy.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/entries/detail-copy.js'); ?>"></script>
    <?php if (is_array($fcEntriesDetailPage)) : ?>
    <script src="assets/js/admin/entries/cart-filters.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/entries/cart-filters.js'); ?>"></script>
    <script src="assets/js/admin/components/image-lightbox.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/components/image-lightbox.js'); ?>"></script>
    <script src="assets/js/admin/entries/cart-gallery.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/entries/cart-gallery.js'); ?>"></script>
    <?php endif; ?>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php elseif (!empty($fcAdminIsUsers)) : ?>
    <script src="assets/js/admin/users-presence.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/users-presence.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php elseif (!empty($fcAdminIsGroupPermissions)) : ?>
    <script src="assets/js/admin/group-permissions.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/group-permissions.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php elseif ($fcAdminIsSettings) : ?>
    <script src="assets/js/admin/core/modal.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/modal.js'); ?>"></script>
    <script src="assets/js/admin/core/gallery-upload-queue.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/gallery-upload-queue.js'); ?>"></script>
    <script src="assets/js/admin/gallery.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/gallery.js'); ?>"></script>
    <script src="assets/js/admin/core/media-picker.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/media-picker.js'); ?>"></script>
    <script src="assets/js/admin/components/image-lightbox.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/components/image-lightbox.js'); ?>"></script>
    <script src="assets/js/admin/settings.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/settings.js'); ?>"></script>
    <script src="assets/js/admin/pages/settings-tab-controller.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/settings-tab-controller.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/theme-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/theme-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/branding-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/branding-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/fence-colors-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/fence-colors-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/catalog-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/catalog-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/system-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/system-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/integration-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/integration-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/project-plan-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/project-plan-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/console-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/console-tab.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php elseif ($fcAdminIsGallery) : ?>
    <script src="assets/js/admin/core/modal.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/modal.js'); ?>"></script>
    <script src="assets/js/admin/core/gallery-upload-queue.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/gallery-upload-queue.js'); ?>"></script>
    <script src="assets/js/admin/gallery.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/gallery.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php elseif ($fcAdminRoute === 'products/store-products') : ?>
    <script src="assets/js/admin/core/modal.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/modal.js'); ?>"></script>
    <script src="assets/js/admin/components/image-lightbox.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/components/image-lightbox.js'); ?>"></script>
    <script src="assets/js/admin/products/system-products.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/products/system-products.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php elseif ($fcAdminRoute === 'products/system-products') : ?>
    <script src="assets/js/admin/core/modal.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/modal.js'); ?>"></script>
    <script src="assets/js/admin/products/store-products-color-filter.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/products/store-products-color-filter.js'); ?>"></script>
    <script src="assets/js/admin/products/store-products.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/products/store-products.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php elseif ($fcAdminRoute === 'products/fence-styles' || preg_match('#^products/fence-styles/edit/#', (string) $fcAdminRoute)) : ?>
    <script src="assets/js/admin/core/modal.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/modal.js'); ?>"></script>
    <script src="assets/js/admin/core/gallery-upload-queue.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/gallery-upload-queue.js'); ?>"></script>
    <script src="assets/js/admin/gallery.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/gallery.js'); ?>"></script>
    <script src="assets/js/admin/core/media-picker.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/media-picker.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/wysiwyg.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/wysiwyg.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/code-editor.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/code-editor.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/gui.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/gui.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/edit.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/edit.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/fence-styles.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/fence-styles.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php else : ?>
    <script src="assets/js/admin/core/modal.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/modal.js'); ?>"></script>
    <script src="assets/js/admin/components/image-lightbox.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/components/image-lightbox.js'); ?>"></script>
    <script src="assets/js/admin/products/store-products.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/products/store-products.js'); ?>"></script>
    <script src="assets/js/admin/products/system-products.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/products/system-products.js'); ?>"></script>
    <script src="assets/js/admin/core/gallery-upload-queue.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/gallery-upload-queue.js'); ?>"></script>
    <script src="assets/js/admin/gallery.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/gallery.js'); ?>"></script>
    <script src="assets/js/admin/core/media-picker.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/media-picker.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/wysiwyg.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/wysiwyg.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/code-editor.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/code-editor.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/gui.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/gui.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/edit.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/edit.js'); ?>"></script>
    <script src="assets/js/admin/fence-styles/fence-styles.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/fence-styles/fence-styles.js'); ?>"></script>
    <script src="assets/js/admin/settings.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/settings.js'); ?>"></script>
    <script src="assets/js/admin/pages/settings-tab-controller.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/settings-tab-controller.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/theme-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/theme-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/branding-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/branding-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/fence-colors-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/fence-colors-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/catalog-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/catalog-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/system-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/system-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/integration-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/integration-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/project-plan-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/project-plan-tab.js'); ?>"></script>
    <script src="assets/js/admin/pages/tabs/console-tab.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/pages/tabs/console-tab.js'); ?>"></script>
    <script src="assets/js/admin/core/app.js?v=<?php echo \Fc\Admin\Helpers\UrlHelper::assetVersion('assets/js/admin/core/app.js'); ?>"></script>
    <?php endif; ?>
</body>
</html>
