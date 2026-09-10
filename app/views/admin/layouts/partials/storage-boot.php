<?php
/**
 * FC Admin — pre-paint client-storage boot.
 *
 * Inline and dependency-free on purpose: it must run before the first
 * stylesheet to stop a light-theme flash and a sidebar layout shift, so it
 * cannot wait for core/store.js. It re-implements the 'dark'/'light' whitelist
 * instead of trusting the stored value — a hand-edited envelope must never
 * reach setAttribute() raw. Shared by layouts/main.php and login.php so the
 * two copies cannot drift again (login's old inline copy had already lost the
 * sidebar branch). The sidebar class is inert on the login page, which loads
 * no stylesheet that styles it.
 *
 * The legacy-key else-branch covers the first load after the fc-admin
 * consolidation deploy, before core/store.js has migrated — without it,
 * dark-mode users get exactly the one light frame this script exists to
 * prevent. Transitional (deployed 2026-09-10): remove it together with
 * core/store.js's LEGACY_LOCAL list and session.consume() raw-key fallback,
 * one release later.
 *
 * No @vars — static output.
 */
?>
<script>
    (function(){try{var r=localStorage.getItem('fc-admin'),u;if(r){u=(JSON.parse(r)||{}).ui||{};}else{u={appearance:localStorage.getItem('fc-admin-appearance'),sidebarCollapsed:localStorage.getItem('fc-admin-sidebar-collapsed')==='1'};}document.documentElement.setAttribute('data-fc-admin-theme',u.appearance==='dark'?'dark':'light');if(u.sidebarCollapsed===true){document.documentElement.classList.add('fc-admin-sidebar-collapsed');}}catch(e){}})();
    </script>
