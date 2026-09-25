<?php
/**
 * Google Fonts for the public pages, linked straight from the HTML: as an @import inside a local
 * stylesheet the request could only start once that file had downloaded. One family per link:
 * Cloudflare Fonts on the live zones rewrites a Google Fonts link but keeps only its first family=,
 * which left fencesperth.com with Inter and no League Gothic or Poppins. The admin still imports
 * the same families through public/assets/css/fonts.css — edit the two in pairs.
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=League+Gothic&amp;display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&amp;display=swap">
