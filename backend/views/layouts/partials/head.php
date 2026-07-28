<head>
    <script>
    (function(){try{var t=localStorage.getItem('fc-admin-appearance');document.documentElement.setAttribute('data-fc-admin-theme',t==='dark'?'dark':'light');}catch(e){}})();
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
    <?php echo fc_theme_css_block(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($fcFontsHref, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" type="text/css" href="assets/css/fc-admin-buttons.css">
    <link rel="stylesheet" type="text/css" href="assets/css/fc-admin-theme.css">
    <link rel="stylesheet" type="text/css" href="assets/css/fc-admin-sidebar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/gallery-admin.css">
    <link rel="stylesheet" type="text/css" href="assets/css/entries-admin.css">
    <link rel="stylesheet" type="text/css" href="assets/css/fc-lazy.css">
    <link rel="stylesheet" type="text/css" href="assets/css/fence-styles-admin.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        sidebar: {
                            DEFAULT: '#0f172a',
                            hover: '#1e293b',
                            active: '#334155',
                            border: '#1e293b'
                        }
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
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
        /* Modal close â€” match /fc planner (orange circle + white X), compact for admin */
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
        .fc-store-products-row--drag-over {
            outline: 2px dashed #6366f1;
            outline-offset: -2px;
        }
        #fc-store-products-table-wrap .fc-sp-sticky {
            position: sticky;
            z-index: 10;
        }
        #fc-store-products-table-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #f6f7f7;
            color: #646970;
            box-shadow: 0 1px 0 #c3c4c7;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        #fc-store-products-table-wrap thead .fc-sp-sticky-col {
            z-index: 35;
        }
        #fc-store-products-table-wrap thead .fc-sp-sticky-grip {
            z-index: 40;
        }
        #fc-store-products-table-wrap .fc-sp-sticky-grip {
            left: 0;
            min-width: 2.5rem;
            width: 2.5rem;
        }
        #fc-store-products-table-wrap .fc-sp-sticky-col {
            left: 0;
            min-width: 8rem;
            box-shadow: 4px 0 6px -2px rgba(15, 23, 42, 0.1);
        }
        #fc-store-products-table-wrap .fc-sp-has-grip .fc-sp-sticky-col {
            left: 2.5rem;
        }
        #fc-store-products-table-wrap .fc-sp-sticky-col::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: -1px;
            width: 1px;
            background: #e2e8f0;
            pointer-events: none;
        }
        #fc-store-products-table-wrap tbody tr:hover td.fc-sp-sticky,
        #fc-store-products-table-wrap tbody tr.fc-store-products-row--drag-over td.fc-sp-sticky {
            background-color: rgb(238 242 255 / 0.55);
        }
        #fc-store-products-table-wrap tbody tr.fc-store-products-row--clickable {
            cursor: pointer;
        }
        #fc-store-products-table-wrap .fc-sp-table-layout {
            min-height: 0;
        }
        #fc-store-products-table-wrap .fc-store-products-scroll {
            overflow-x: hidden;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }
        #fc-store-products-table-wrap .fc-store-products-scroll::-webkit-scrollbar {
            width: 8px;
            height: 0;
        }
        #fc-store-products-table-wrap .fc-store-products-scroll::-webkit-scrollbar:horizontal {
            display: none;
            height: 0;
        }
        #fc-store-products-table-wrap .fc-store-products-scroll::-webkit-scrollbar-thumb {
            border-radius: 4px;
            background: #cbd5e1;
        }
        #fc-store-products-table-wrap .fc-sp-bottom-scrollbar {
            flex-shrink: 0;
            height: 18px;
            overflow-x: auto;
            overflow-y: hidden;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.06);
        }
        #fc-store-products-table-wrap .fc-sp-bottom-scrollbar::-webkit-scrollbar {
            height: 14px;
        }
        #fc-store-products-table-wrap .fc-sp-bottom-scrollbar::-webkit-scrollbar-thumb {
            border-radius: 6px;
            background: #94a3b8;
        }
        #fc-store-products-table-wrap .fc-sp-bottom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        #fc-store-products-table-wrap .fc-sp-bottom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }
        [data-fc-store-products-php] #fc-store-products-table-wrap .fc-sp-bottom-scrollbar {
            display: none;
        }
        [data-fc-store-products-php] #fc-store-products-table-wrap .fc-sp-table-layout {
            flex: none;
            min-height: 0;
        }
        [data-fc-store-products-php] #fc-store-products-table-wrap .fc-store-products-scroll {
            overflow: visible;
        }
        [data-fc-store-products-php] .fc-store-products-body {
            overflow: auto;
            min-height: 0;
        }
        [data-fc-store-products-php] #fc-store-products-table-wrap .fc-store-products-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
        }
        [data-fc-store-products-php] .fc-store-products-footer {
            margin-top: 0;
        }
        #fc-store-products-table-wrap .fc-sys-product-colors {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 0.75rem;
        }
        #fc-store-products-table-wrap .fc-sys-product-color {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }
        #fc-store-products-table-wrap .fc-sys-product-color__swatch {
            display: inline-block;
            width: 0.875rem;
            height: 0.875rem;
            border-radius: 9999px;
            border: 1px solid rgba(15, 23, 42, 0.18);
            flex-shrink: 0;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
        }
        #fc-store-products-table-wrap .fc-sys-product-color__label {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            color: #475569;
        }
        #fc-store-products-table-wrap .fc-sys-product-desc-cell {
            max-width: 18rem;
            white-space: normal;
            line-height: 1.35;
        }
        #fc-store-products-table-wrap .fc-sys-product-colors-col {
            min-width: 10rem;
        }
        #fc-store-products-table-wrap .fc-sys-product-skus-col {
            min-width: 4.5rem;
        }
        #fc-store-products-table-wrap .fc-sp-skus-summary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8125rem;
            font-variant-numeric: tabular-nums;
            color: #475569;
            line-height: 1;
        }
        #fc-store-products-table-wrap .fc-sp-skus-summary--incomplete {
            color: #64748b;
        }
        #fc-store-products-table-wrap .fc-sp-skus-summary--complete {
            color: #334155;
        }
        #fc-store-products-table-wrap .fc-sp-sku-status,
        #fc-sp-edit-modal .fc-sp-sku-status {
            display: inline-block;
            width: 0.5rem;
            height: 0.5rem;
            flex-shrink: 0;
            border-radius: 9999px;
            background: #94a3b8;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
        }
        #fc-store-products-table-wrap .fc-sp-sku-status--missing,
        #fc-sp-edit-modal .fc-sp-sku-status--missing {
            background: #94a3b8;
        }
        #fc-store-products-table-wrap .fc-sp-sku-status--found,
        #fc-sp-edit-modal .fc-sp-sku-status--found {
            background: #22c55e;
            box-shadow: inset 0 0 0 1px rgba(21, 128, 61, 0.2);
        }
        html[data-fc-admin-theme='dark'] #fc-store-products-table-wrap .fc-sp-skus-summary {
            color: #cbd5e1;
        }
        html[data-fc-admin-theme='dark'] #fc-store-products-table-wrap .fc-sp-skus-summary--incomplete {
            color: #94a3b8;
        }
        html[data-fc-admin-theme='dark'] #fc-store-products-table-wrap .fc-sp-sku-status--missing,
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-status--missing {
            background: #64748b;
            box-shadow: none;
        }
        html[data-fc-admin-theme='dark'] #fc-store-products-table-wrap .fc-sp-sku-status--found,
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-status--found {
            background: #22c55e;
            box-shadow: none;
        }
        .fc-sp-toolbar {
            justify-content: space-between;
        }
        .fc-sp-toolbar .fc-entries-page__count {
            flex-shrink: 0;
        }
        .fc-sp-toolbar__clear {
            border: 0;
            background: transparent;
            color: #2271b1;
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            padding: 0.15rem 0.35rem;
            white-space: nowrap;
        }
        .fc-sp-toolbar__clear:hover {
            color: #135e96;
            text-decoration: underline;
        }
        html[data-fc-admin-theme='dark'] .fc-sp-toolbar__clear {
            color: #7cc0f5;
        }
        .fc-sys-toolbar-tabs {
            flex-shrink: 0;
        }
        .fc-sys-toolbar-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.125rem;
        }
        .fc-sys-toolbar-meta__file {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.6875rem;
            color: #94a3b8;
            max-width: 14rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        html[data-fc-admin-theme='dark'] .fc-sys-toolbar-meta__file {
            color: var(--fc-admin-text-subtle);
        }
        #fc-system-products-table-wrap .fc-sp-sticky {
            position: sticky;
            z-index: 10;
        }
        #fc-system-products-table-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #f6f7f7;
            color: #646970;
            box-shadow: 0 1px 0 #c3c4c7;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        #fc-system-products-table-wrap thead .fc-sp-sticky-col {
            z-index: 35;
        }
        #fc-system-products-table-wrap .fc-sp-sticky-col {
            left: 0;
            min-width: 5rem;
            box-shadow: 4px 0 6px -2px rgba(15, 23, 42, 0.1);
        }
        #fc-system-products-table-wrap .fc-sp-sticky-col::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: -1px;
            width: 1px;
            background: #e2e8f0;
            pointer-events: none;
        }
        #fc-system-products-table-wrap tbody tr:hover td.fc-sp-sticky {
            background-color: rgb(238 242 255 / 0.55);
        }
        #fc-system-products-table-wrap .fc-sp-table-layout {
            min-height: 0;
        }
        #fc-system-products-table-wrap .fc-system-products-scroll {
            overflow-x: hidden;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }
        #fc-system-products-table-wrap .fc-system-products-scroll::-webkit-scrollbar {
            width: 8px;
            height: 0;
        }
        #fc-system-products-table-wrap .fc-system-products-scroll::-webkit-scrollbar:horizontal {
            display: none;
            height: 0;
        }
        #fc-system-products-table-wrap .fc-system-products-scroll::-webkit-scrollbar-thumb {
            border-radius: 4px;
            background: #cbd5e1;
        }
        #fc-system-products-table-wrap .fc-sp-bottom-scrollbar {
            flex-shrink: 0;
            height: 18px;
            overflow-x: auto;
            overflow-y: hidden;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.06);
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }
        #fc-system-products-table-wrap .fc-sp-bottom-scrollbar::-webkit-scrollbar {
            height: 14px;
        }
        #fc-system-products-table-wrap .fc-sp-bottom-scrollbar::-webkit-scrollbar-thumb {
            border-radius: 6px;
            background: #94a3b8;
        }
        #fc-system-products-table-wrap .fc-sp-bottom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        #fc-system-products-tab:focus-visible,
        .fc-system-products-tab:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 2px;
        }
        #fc-system-products-table-wrap col.fc-sys-images-col {
            width: 5.5rem;
        }
        #fc-system-products-table-wrap .fc-sys-images-cell {
            width: 5.5rem;
            min-width: 5.5rem;
            height: 1px;
            padding: 0;
            vertical-align: top;
            background: #f6f7f7;
            border-right: 1px solid #f0f0f1;
        }
        #fc-system-products-table-wrap .fc-sys-images-cell--head {
            padding: 0;
        }
        #fc-system-products-table-wrap .fc-sys-images-fill {
            position: relative;
            height: 100%;
            min-height: 4.75rem;
        }
        #fc-system-products-table-wrap .fc-sys-images-thumb {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: none;
            background: #fff;
        }
        #fc-system-products-table-wrap .fc-sys-images-trigger {
            position: absolute;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            padding: 0;
            border: none;
            background: #f6f7f7;
            cursor: zoom-in;
            line-height: 0;
            overflow: hidden;
        }
        #fc-system-products-table-wrap .fc-sys-images-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.42);
            color: #fff;
            font-size: 1.125rem;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        #fc-system-products-table-wrap .fc-sys-images-trigger:hover .fc-sys-images-overlay,
        #fc-system-products-table-wrap .fc-sys-images-trigger:focus-visible .fc-sys-images-overlay {
            opacity: 1;
        }
        #fc-system-products-table-wrap .fc-sys-images-trigger:focus {
            outline: none;
        }
        #fc-system-products-table-wrap .fc-sys-images-trigger:focus-visible {
            outline: 2px solid #2271b1;
            outline-offset: -2px;
        }
        #fc-system-products-table-wrap .fc-sys-images-badge {
            position: absolute;
            right: 0;
            bottom: 0;
            padding: 0.1rem 0.35rem;
            border-top-left-radius: 3px;
            background: rgba(15, 23, 42, 0.78);
            color: #fff;
            font-size: 0.625rem;
            font-weight: 700;
            line-height: 1.35;
            pointer-events: none;
        }
        #fc-system-products-table-wrap .fc-sys-images-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: #f6f7f7;
            color: #a7aaad;
            font-size: 1.125rem;
        }
        html[data-fc-admin-theme='dark'] #fc-system-products-table-wrap .fc-sys-images-cell,
        html[data-fc-admin-theme='dark'] #fc-system-products-table-wrap .fc-sys-images-trigger,
        html[data-fc-admin-theme='dark'] #fc-system-products-table-wrap .fc-sys-images-placeholder {
            background: var(--fc-admin-surface-muted);
            border-color: var(--fc-admin-border);
        }
        html[data-fc-admin-theme='dark'] #fc-system-products-table-wrap .fc-sys-images-thumb {
            background: var(--fc-admin-surface);
        }
        html[data-fc-admin-theme='dark'] #fc-system-products-table-wrap .fc-sys-images-placeholder {
            color: var(--fc-admin-text-subtle);
        }
        #fc-system-products-table-wrap col.fc-sys-sku-col {
            width: 300px;
            max-width: 300px;
        }
        #fc-system-products-table-wrap .fc-sys-sku-cell {
            max-width: 300px;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }
        [data-fc-system-products-php] #fc-system-products-table-wrap .fc-sp-bottom-scrollbar {
            display: none;
        }
        [data-fc-system-products-php] #fc-system-products-table-wrap .fc-sp-table-layout {
            flex: none;
            min-height: 0;
        }
        [data-fc-system-products-php] #fc-system-products-table-wrap .fc-system-products-scroll {
            overflow: visible;
        }
        [data-fc-system-products-php] .fc-system-products-body {
            overflow: auto;
            min-height: 0;
        }
        [data-fc-system-products-php] #fc-system-products-table-wrap .fc-system-products-table thead th,
        [data-fc-system-products-php] #fc-system-products-table-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 20;
        }
        [data-fc-system-products-php] .fc-system-products-footer {
            margin-top: 0;
        }
        #fc-sp-edit-modal {
            display: flex;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.28s ease, visibility 0s linear 0.28s;
        }
        #fc-sp-edit-modal.fc-sp-edit-modal--visible {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
            transition: opacity 0.28s ease, visibility 0s;
        }
        #fc-sp-edit-modal .fc-sp-edit-backdrop {
            opacity: 0;
            transition: opacity 0.28s ease;
        }
        #fc-sp-edit-modal.fc-sp-edit-modal--visible .fc-sp-edit-backdrop {
            opacity: 1;
        }
        #fc-sp-edit-modal .fc-sp-edit-panel {
            opacity: 0;
            transform: scale(0.96) translateY(0.75rem);
            transition: opacity 0.28s ease, transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
        }
        #fc-sp-edit-modal.fc-sp-edit-modal--visible .fc-sp-edit-panel {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        @media (prefers-reduced-motion: reduce) {
            #fc-sp-edit-modal,
            #fc-sp-edit-modal .fc-sp-edit-backdrop,
            #fc-sp-edit-modal .fc-sp-edit-panel {
                transition-duration: 0.01ms !important;
            }
        }
        #fc-sp-edit-modal .fc-sp-edit-panels {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f8fafc;
        }
        #fc-sp-edit-modal .fc-sp-edit-panels::-webkit-scrollbar {
            width: 8px;
        }
        #fc-sp-edit-modal .fc-sp-edit-panels::-webkit-scrollbar-thumb {
            border-radius: 4px;
            background: #cbd5e1;
        }
        #fc-sp-edit-modal .fc-sp-edit-tab:focus-visible {
            outline: 2px solid #2271b1;
            outline-offset: 2px;
        }
        #fc-sp-edit-modal .fc-sp-edit-section-title {
            margin: 0.5rem 0 0.35rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #646970;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-edit-section-title {
            border-top-color: var(--fc-admin-border);
            color: var(--fc-admin-text-muted);
        }
        #fc-sp-edit-modal .fc-sp-field-grid,
        #fc-gallery-attach-modal .fc-sp-field-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.875rem 1rem;
        }
        @media (min-width: 768px) {
            #fc-sp-edit-modal .fc-sp-field-grid,
            #fc-gallery-attach-modal .fc-sp-field-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            #fc-sp-edit-modal .fc-sp-field-grid--sku,
            #fc-gallery-attach-modal .fc-sp-field-grid--sku {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        #fc-sp-edit-modal .fc-sp-field--wide,
        #fc-gallery-attach-modal .fc-sp-field--wide {
            grid-column: 1 / -1;
        }
        #fc-sp-edit-modal .fc-sp-field__label,
        #fc-gallery-attach-modal .fc-sp-field__label {
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
        #fc-sp-edit-modal .fc-sp-field__lock,
        #fc-gallery-attach-modal .fc-sp-field__lock {
            font-size: 0.625rem;
            color: #94a3b8;
        }
        #fc-sp-edit-modal .fc-sp-field__help,
        #fc-sp-edit-modal .fc-sp-field__intro,
        #fc-gallery-attach-modal .fc-sp-field__help,
        #fc-gallery-attach-modal .fc-sp-field__intro {
            margin: 0.35rem 0 0;
            font-size: 0.75rem;
            line-height: 1.45;
            color: #646970;
        }
        #fc-sp-edit-modal .fc-sp-field__intro,
        #fc-gallery-attach-modal .fc-sp-field__intro {
            margin-bottom: 0.875rem;
        }
        #fc-sp-edit-modal .fc-sp-field-input-wrap,
        #fc-gallery-attach-modal .fc-sp-field-input-wrap {
            display: flex;
            align-items: stretch;
            gap: 0.5rem;
            min-width: 0;
        }
        #fc-sp-edit-modal .fc-sp-field-input-wrap--sku {
            position: relative;
            align-items: stretch;
        }
        #fc-sp-edit-modal .fc-sp-sku-check {
            position: absolute;
            left: 0.3rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 1.5rem;
            height: 1.5rem;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.12s ease;
        }
        #fc-sp-edit-modal .fc-sp-sku-check:hover,
        #fc-sp-edit-modal .fc-sp-sku-check:focus-visible {
            background: transparent;
            outline: none;
            color: #64748b;
        }
        #fc-sp-edit-modal .fc-sp-sku-check:focus-visible {
            color: #2271b1;
        }
        #fc-sp-edit-modal .fc-sp-sku-check--empty {
            color: #94a3b8;
        }
        #fc-sp-edit-modal .fc-sp-sku-check--empty:hover,
        #fc-sp-edit-modal .fc-sp-sku-check--empty:focus-visible {
            color: #64748b;
        }
        #fc-sp-edit-modal .fc-sp-sku-check--missing {
            color: #dc2626;
        }
        #fc-sp-edit-modal .fc-sp-sku-check--missing:hover,
        #fc-sp-edit-modal .fc-sp-sku-check--missing:focus-visible {
            color: #b91c1c;
        }
        #fc-sp-edit-modal .fc-sp-sku-check--found {
            color: #16a34a;
            background: transparent;
            border: 0;
            box-shadow: none;
        }
        #fc-sp-edit-modal .fc-sp-sku-check--found:hover,
        #fc-sp-edit-modal .fc-sp-sku-check--found:focus-visible {
            color: #15803d;
        }
        #fc-sp-edit-modal .fc-sp-sku-check i {
            font-size: 0.75rem;
            line-height: 1;
        }
        #fc-sp-edit-modal .fc-sp-field-input-wrap--sku .fc-sp-field-control--sku {
            padding-left: 2rem;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest {
            position: absolute;
            top: calc(100% + 0.25rem);
            left: 0;
            right: 0;
            z-index: 40;
            display: flex;
            flex-direction: column;
            max-height: 32.5rem;
            overflow: hidden;
            padding: 0;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            background: #fff;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14);
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest--floating {
            position: fixed;
            top: auto;
            left: auto;
            right: auto;
            z-index: 130;
            min-width: 26rem;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.2);
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__toolbar {
            flex: 0 0 auto;
            order: 2;
            padding: 0.55rem 0.6rem;
            border-top: 1px solid #e2e8f0;
            background: #fff;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__list {
            flex: 1 1 auto;
            order: 1;
            min-height: 0;
            overflow: auto;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__filter-label {
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
        #fc-sp-edit-modal .fc-sp-sku-suggest__filter {
            width: 100%;
            border: 1px solid #8c8f94;
            border-radius: 0;
            padding: 0.4rem 0.65rem;
            font-size: 0.8125rem;
            line-height: 1.35;
            color: #2c3338;
            background: #fff;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__filter:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__empty {
            padding: 0.85rem 0.75rem;
            font-size: 0.75rem;
            color: #64748b;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.55rem 0.65rem;
            border-radius: 0;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__row:nth-child(odd) {
            background: #fff;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__row:nth-child(even) {
            background: #f1f5f9;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__row:hover {
            background: #e2e8f0;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__thumb {
            flex-shrink: 0;
            width: 3.75rem;
            height: 3.75rem;
            margin-top: 0;
            border-radius: 0;
            object-fit: cover;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            display: block;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__thumb-btn {
            flex-shrink: 0;
            margin: 0.1rem 0 0;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: zoom-in;
            line-height: 0;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__thumb-btn:focus-visible {
            outline: 2px solid #2271b1;
            outline-offset: 1px;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__thumb--empty {
            display: inline-block;
            background:
                linear-gradient(135deg, #f8fafc 25%, transparent 25%) -0.5rem 0 / 1rem 1rem,
                linear-gradient(225deg, #f8fafc 25%, transparent 25%) -0.5rem 0 / 1rem 1rem,
                linear-gradient(315deg, #f8fafc 25%, transparent 25%) 0 0 / 1rem 1rem,
                linear-gradient(45deg, #f8fafc 25%, #e2e8f0 25%) 0 0 / 1rem 1rem;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest.is-preview-open .fc-sp-sku-suggest__list,
        #fc-sp-edit-modal .fc-sp-sku-suggest.is-preview-open .fc-sp-sku-suggest__toolbar {
            display: none;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__preview {
            flex: 1 1 auto;
            order: 1;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            background: #fff;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__preview-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            padding: 0.75rem;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__preview-media {
            flex: 1 1 auto;
            min-height: 10rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__preview-image {
            display: block;
            width: 100%;
            height: 100%;
            max-height: 18rem;
            object-fit: contain;
            background: #fff;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__preview-image--empty {
            min-height: 10rem;
            background:
                linear-gradient(135deg, #f8fafc 25%, transparent 25%) -0.5rem 0 / 1rem 1rem,
                linear-gradient(225deg, #f8fafc 25%, transparent 25%) -0.5rem 0 / 1rem 1rem,
                linear-gradient(315deg, #f8fafc 25%, transparent 25%) 0 0 / 1rem 1rem,
                linear-gradient(45deg, #f8fafc 25%, #e2e8f0 25%) 0 0 / 1rem 1rem;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__preview-name {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.35;
            color: #0f172a;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__preview-sku {
            display: block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 1rem;
            line-height: 1.25;
            color: var(--fc-princeton-orange);
            background: transparent;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__preview-footer {
            flex: 0 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.5rem;
            padding: 0.55rem 0.75rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__meta {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__name {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.35;
            color: #0f172a;
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: normal;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__sku {
            display: block;
            min-width: 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 1rem;
            line-height: 1.25;
            color: var(--fc-princeton-orange);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            background: transparent;
        }
        #fc-sp-edit-modal .fc-sp-sku-suggest__use {
            flex-shrink: 0;
            align-self: center;
            margin-top: 0.15rem;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check {
            border: 0;
            background: transparent;
            box-shadow: none;
            color: #94a3b8;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check:hover,
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check:focus-visible {
            background: transparent;
            color: #cbd5e1;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check--empty {
            color: #94a3b8;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check--missing {
            color: #f87171;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check--missing:hover,
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check--missing:focus-visible {
            color: #fca5a5;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check--found {
            color: #4ade80;
            border: 0;
            background: transparent;
            box-shadow: none;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check--found:hover,
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-check--found:focus-visible {
            color: #86efac;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest {
            border-color: #334155;
            background: #0f172a;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__toolbar {
            border-top-color: #334155;
            background: #0f172a;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__filter {
            border-color: var(--fc-admin-border);
            background: var(--fc-admin-surface-muted);
            color: var(--fc-admin-text-heading);
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__empty {
            color: #94a3b8;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__row:nth-child(odd) {
            background: #0f172a;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__row:nth-child(even) {
            background: #1e293b;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__row:hover {
            background: rgba(148, 163, 184, 0.18);
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__thumb {
            border-color: #334155;
            background: #1e293b;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__preview {
            background: #0f172a;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__preview-media {
            border-color: #334155;
            background: #1e293b;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__preview-image {
            background: #0f172a;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__preview-name {
            color: #f1f5f9;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__preview-sku {
            color: var(--fc-princeton-orange);
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__preview-footer {
            border-top-color: #334155;
            background: #1e293b;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__name {
            color: #f1f5f9;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-sku-suggest__sku {
            color: var(--fc-princeton-orange);
        }
        #fc-sp-edit-modal .fc-sp-field-input-wrap--textarea,
        #fc-gallery-attach-modal .fc-sp-field-input-wrap--textarea {
            position: relative;
        }
        #fc-sp-edit-modal .fc-sp-field-control,
        #fc-gallery-attach-modal .fc-sp-field-control {
            width: 100%;
            min-width: 0;
            flex: 1 1 auto;
            border: 1px solid #8c8f94;
            border-radius: 3px;
            padding: 0.4rem 0.65rem;
            font-size: 0.875rem;
            line-height: 1.4;
            background: #fff;
            color: #2c3338;
            transition: border-color 0.12s ease, box-shadow 0.12s ease;
        }
        #fc-sp-edit-modal .fc-sp-field-control::placeholder,
        #fc-gallery-attach-modal .fc-sp-field-control::placeholder {
            color: #94a3b8;
        }
        #fc-sp-edit-modal .fc-sp-field-control:focus,
        #fc-gallery-attach-modal .fc-sp-field-control:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        #fc-sp-edit-modal .fc-sp-field-control--textarea,
        #fc-gallery-attach-modal .fc-sp-field-control--textarea {
            resize: vertical;
            min-height: 6.5rem;
            padding-right: 2.75rem;
        }
        #fc-sp-edit-modal .fc-sp-field-control--readonly,
        #fc-gallery-attach-modal .fc-sp-field-control--readonly {
            background: #f6f7f7;
            color: #646970;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            cursor: not-allowed;
        }
        #fc-sp-edit-modal .fc-sp-field-control--readonly:focus,
        #fc-gallery-attach-modal .fc-sp-field-control--readonly:focus {
            border-color: #8c8f94;
            box-shadow: none;
        }
        #fc-sp-edit-modal .fc-sp-field-control--sku,
        #fc-gallery-attach-modal .fc-sp-field-control--sku {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        #fc-sp-edit-modal .fc-sp-field-control--sku.fc-sp-field-control--empty,
        #fc-gallery-attach-modal .fc-sp-field-control--sku.fc-sp-field-control--empty {
            border-style: dashed;
            color: #94a3b8;
        }
        #fc-sp-edit-modal .fc-sp-field-copy,
        #fc-gallery-attach-modal .fc-sp-field-copy {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 2.125rem;
            height: 2.125rem;
            border: 1px solid #8c8f94;
            border-radius: 3px;
            background: #fff;
            color: #646970;
            font-size: 0.8125rem;
            cursor: pointer;
            transition: border-color 0.12s ease, color 0.12s ease, background-color 0.12s ease;
        }
        #fc-sp-edit-modal .fc-sp-field-copy:hover,
        #fc-gallery-attach-modal .fc-sp-field-copy:hover {
            border-color: #2271b1;
            color: #2271b1;
            background: #f6f7f7;
        }
        #fc-sp-edit-modal .fc-sp-field-copy:focus-visible,
        #fc-gallery-attach-modal .fc-sp-field-copy:focus-visible {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        #fc-sp-edit-modal .fc-sp-field-copy--compact,
        #fc-gallery-attach-modal .fc-sp-field-copy--compact {
            position: absolute;
            top: 0.4rem;
            right: 0.4rem;
            width: 2rem;
            height: 2rem;
            font-size: 0.75rem;
        }
        #fc-sp-edit-modal .fc-sp-field-control:focus-visible,
        #fc-gallery-attach-modal .fc-sp-field-control:focus-visible {
            outline: none;
        }
        #fc-sp-edit-modal .fc-sp-field-copy--copied,
        #fc-gallery-attach-modal .fc-sp-field-copy--copied {
            border-color: #198754 !important;
            background: #f0fdf4 !important;
            color: #15803d !important;
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-field__label,
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-field__help,
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-field__intro,
        html[data-fc-admin-theme='dark'] #fc-gallery-attach-modal .fc-sp-field__label,
        html[data-fc-admin-theme='dark'] #fc-gallery-attach-modal .fc-sp-field__help,
        html[data-fc-admin-theme='dark'] #fc-gallery-attach-modal .fc-sp-field__intro {
            color: var(--fc-admin-text-muted);
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-field-control,
        html[data-fc-admin-theme='dark'] #fc-gallery-attach-modal .fc-sp-field-control {
            background: var(--fc-admin-surface-muted);
            border-color: var(--fc-admin-border);
            color: var(--fc-admin-text-heading);
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-field-control--readonly,
        html[data-fc-admin-theme='dark'] #fc-gallery-attach-modal .fc-sp-field-control--readonly {
            background: var(--fc-admin-surface-subtle);
            color: var(--fc-admin-text-muted);
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-field-copy,
        html[data-fc-admin-theme='dark'] #fc-gallery-attach-modal .fc-sp-field-copy {
            background: var(--fc-admin-surface-muted);
            border-color: var(--fc-admin-border);
            color: var(--fc-admin-text-muted);
        }
        html[data-fc-admin-theme='dark'] #fc-sp-edit-modal .fc-sp-field-copy:hover,
        html[data-fc-admin-theme='dark'] #fc-gallery-attach-modal .fc-sp-field-copy:hover {
            background: var(--fc-admin-surface-subtle);
            border-color: var(--fc-admin-link);
            color: var(--fc-admin-link);
        }
        /* Fence Styles â€” match planner Step 1 (.fencing-style-item) */
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
        .fc-admin-fence-styles .fc-admin-fence-style-item--preview:hover > div {
            transform: none;
            box-shadow: none;
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
        /* Fence style edit â€” settings manager */
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
        .fc-fs-dirty-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.6875rem;
            font-weight: 700;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 9999px;
            padding: 0.3rem 0.65rem;
            white-space: nowrap;
        }
        .fc-fs-dirty-badge i {
            font-size: 0.625rem;
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
        .fc-fs-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .fc-fs-tab {
            border: 1px solid transparent;
            background: transparent;
            color: #64748b;
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.45rem 0.85rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: color 0.15s, background 0.15s, border-color 0.15s;
        }
        .fc-fs-tab:hover {
            color: #334155;
            background: #f8fafc;
        }
        .fc-fs-tab.is-active {
            color: #ea580c;
            background: #fff7ed;
            border-color: #fdba74;
        }
        .fc-fs-tab-panel {
            animation: fcFsFadeIn 0.2s ease;
        }
        @keyframes fcFsFadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
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
        .fc-fs-field--span {
            grid-column: 1 / -1;
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
        .fc-fs-search-wrap {
            position: relative;
            min-width: 12rem;
        }
        .fc-fs-search-icon {
            position: absolute;
            left: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.75rem;
            pointer-events: none;
        }
        .fc-fs-search {
            width: 100%;
            border: 1px solid #8c8f94;
            border-radius: 3px;
            padding: 0.4rem 0.65rem 0.4rem 1.85rem;
            font-size: 0.875rem;
            line-height: 1.4;
            background: #fff;
            color: #2c3338;
            transition: border-color 0.12s ease, box-shadow 0.12s ease;
        }
        .fc-fs-search:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        .fc-fs-settings-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
            min-height: 18rem;
        }
        @media (min-width: 768px) {
            .fc-fs-settings-layout {
                grid-template-columns: minmax(0, 11rem) minmax(0, 1fr);
            }
        }
        .fc-fs-settings-nav {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
            padding: 0.35rem;
            background: #f8fafc;
            max-height: 28rem;
            overflow: auto;
        }
        .fc-fs-settings-nav__item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.1rem;
            width: 100%;
            text-align: left;
            border: none;
            background: transparent;
            border-radius: 0.45rem;
            padding: 0.5rem 0.6rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .fc-fs-settings-nav__item:hover {
            background: #fff;
        }
        .fc-fs-settings-nav__item.is-active {
            background: #fff;
            box-shadow: 0 0 0 1px #fdba74;
        }
        .fc-fs-settings-nav__key {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #0f172a;
        }
        .fc-fs-settings-nav__sub {
            font-size: 0.6875rem;
            color: #64748b;
            line-height: 1.3;
        }
        .fc-fs-settings-main {
            min-width: 0;
        }
        .fc-fs-settings-section__head {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.35rem 0.75rem;
            margin-bottom: 0.85rem;
        }
        .fc-fs-fields-list,
        .fc-fs-form-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        .fc-fs-json-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
            background: #fff;
            overflow: hidden;
        }
        .fc-fs-json-card--section {
            margin-top: 1rem;
        }
        .fc-fs-json-card__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem 0.75rem;
            padding: 0.65rem 0.85rem;
            cursor: pointer;
            list-style: none;
            background: #f8fafc;
            border-bottom: 1px solid transparent;
        }
        .fc-fs-json-card[open] .fc-fs-json-card__head {
            border-bottom-color: #e2e8f0;
        }
        .fc-fs-json-card__head::-webkit-details-marker {
            display: none;
        }
        .fc-fs-json-card__title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #0f172a;
        }
        .fc-fs-json-card__meta {
            font-size: 0.6875rem;
            color: #64748b;
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
        .fc-fs-json-editor--block {
            min-height: 20rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
        }
        .fc-fs-json-editor--error {
            box-shadow: inset 0 0 0 2px #ef4444;
        }
        /* GUI / DEV mode toggle */
        .fc-fs-mode-toggle {
            display: inline-flex;
            padding: 0.2rem;
            border-radius: 0.625rem;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            gap: 0.15rem;
        }
        .fc-fs-mode-toggle__btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            border-radius: 0.45rem;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, box-shadow 0.15s;
        }
        .fc-fs-mode-toggle__btn:hover {
            color: #334155;
        }
        .fc-fs-mode-toggle__btn.is-active {
            background: #fff;
            color: #ea580c;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }
        /* GUI editor â€” tab bar in toolbar (Tailwind classes in JS) */
        .fc-fs-gui-panel__intro {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            margin-bottom: 1.25rem;
            padding: 1rem 1.15rem;
            border: 1px solid #fed7aa;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #fff7ed 0%, #fff 55%);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .fc-fs-gui-panel__intro-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.625rem;
            background: #fff;
            color: #ea580c;
            border: 1px solid #fed7aa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.05rem;
            box-shadow: 0 1px 2px rgba(234, 88, 12, 0.08);
        }
        .fc-fs-gui-panel__step {
            display: inline-block;
            margin-bottom: 0.35rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #c2410c;
            background: #ffedd5;
            border: 1px solid #fdba74;
            border-radius: 9999px;
            padding: 0.15rem 0.55rem;
        }
        .fc-fs-gui-panel__intro .fc-fs-panel-title {
            font-size: 1.0625rem;
        }
        .fc-fs-gui-overview-card,
        .fc-fs-gui-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #fff;
            overflow: hidden;
        }
        .fc-fs-gui-overview-card {
            padding: 1rem;
        }
        .fc-fs-gui-stack {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .fc-fs-gui-card {
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .fc-fs-gui-card__head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .fc-fs-gui-card__head--accent {
            padding: 1rem 1.1rem;
            background: linear-gradient(90deg, #fff7ed 0%, #f8fafc 72%);
            border-bottom: 1px solid #fed7aa;
            border-left: 4px solid #ea580c;
        }
        .fc-fs-gui-card__head--field {
            border-left-color: #6366f1;
            background: linear-gradient(90deg, #eef2ff 0%, #f8fafc 72%);
            border-bottom-color: #c7d2fe;
        }
        .fc-fs-gui-card__index {
            width: 1.85rem;
            height: 1.85rem;
            border-radius: 9999px;
            background: #ea580c;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 1px 2px rgba(234, 88, 12, 0.25);
        }
        .fc-fs-gui-card__title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem 0.65rem;
        }
        .fc-fs-gui-type-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.6875rem;
            font-weight: 700;
            line-height: 1;
            color: #9a3412;
            background: #ffedd5;
            border: 1px solid #fdba74;
            border-radius: 9999px;
            padding: 0.28rem 0.55rem;
            white-space: nowrap;
        }
        .fc-fs-gui-type-badge--indigo {
            color: #3730a3;
            background: #e0e7ff;
            border-color: #c7d2fe;
        }
        .fc-fs-gui-subsection {
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
            background: #fafafa;
            overflow: hidden;
        }
        .fc-fs-gui-subsection__head {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.7rem 0.9rem;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }
        .fc-fs-gui-subsection__head i {
            color: #64748b;
            font-size: 0.875rem;
        }
        .fc-fs-gui-subsection__title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.01em;
        }
        .fc-fs-gui-subsection__body {
            padding: 0.85rem;
        }
        .fc-fs-gui-subsection__body > .fc-fs-gui-field__hint:first-child {
            margin-top: 0;
        }
        .fc-fs-gui-card__icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.5rem;
            background: #fff7ed;
            color: #ea580c;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
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
        .fc-fs-gui-card__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.01em;
        }
        .fc-fs-gui-card__sub {
            margin: 0.15rem 0 0;
            font-size: 0.75rem;
            color: #64748b;
        }
        .fc-fs-gui-card__body {
            padding: 1rem;
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
            outline: 2px solid #6366f1;
            outline-offset: 2px;
        }
        .fc-fs-panel-group-tabs__input:checked + .fc-fs-panel-group-tabs__label {
            background: #fff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
        }
        .fc-fs-panel-group-tabs__input:not(:checked) + .fc-fs-panel-group-tabs__label:hover {
            color: #334155;
        }
        .fc-fs-gui-field--wysiwyg .tox-tinymce {
            border-radius: 3px;
            border-color: #8c8f94 !important;
            overflow: hidden;
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
            background: #6366f1;
        }
        .fc-fs-gui-toggle__input:checked + .fc-fs-gui-toggle__track::after {
            transform: translateX(1rem);
        }
        .fc-fs-gui-toggle__label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: #334155;
        }
        .fc-fs-color-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 0.65rem;
        }
        .fc-fs-color-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.55rem;
            border-radius: 9999px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 0.75rem;
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .fc-fs-color-tag__remove {
            border: none;
            background: transparent;
            color: #6366f1;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            padding: 0;
        }
        .fc-fs-color-add {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .fc-fs-color-add__input {
            flex: 1 1 12rem;
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
            border-radius: 0.625rem;
            padding: 0.35rem;
            background: #f8fafc;
            max-height: 28rem;
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
            border-radius: 0.45rem;
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
        .fc-fs-gui-section__hero {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.85rem 1rem;
            padding: 1rem 1.1rem;
            border: 1px solid #c7d2fe;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #eef2ff 0%, #fff 60%);
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        @media (min-width: 768px) {
            .fc-fs-gui-section__hero {
                grid-template-columns: auto 1fr auto;
            }
        }
        .fc-fs-gui-section__hero-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.625rem;
            background: #fff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.05rem;
        }
        .fc-fs-gui-section__key {
            display: inline-block;
            margin-bottom: 0.45rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #4338ca;
            background: #e0e7ff;
            border: 1px solid #c7d2fe;
            border-radius: 9999px;
            padding: 0.15rem 0.55rem;
        }
        .fc-fs-gui-section__toggles {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            grid-column: 1 / -1;
        }
        @media (min-width: 768px) {
            .fc-fs-gui-section__toggles {
                grid-column: auto;
            }
        }
        .fc-fs-gui-callout {
            display: flex;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 0.625rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            margin-bottom: 0.85rem;
            color: #1e40af;
        }
        .fc-fs-gui-callout--gate {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #9a3412;
        }
        .fc-fs-gui-fields-stack {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .fc-fs-form-panel__toolbar {
            margin-bottom: 0.85rem;
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
            border-radius: 0.5rem;
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
            border-radius: 0.625rem;
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
