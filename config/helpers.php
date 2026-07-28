<?php
function base_url($param ='') {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
    $path = dirname($_SERVER["REQUEST_URI"].'?');

    if ($path === '\\' || $path === '.') {
        $path = '';
    }

	return sprintf(
		"%s://%s%s/%s",
		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
		$host,
		$path,
		$param
	);
}

/**
 * Shareable planner URL for a saved quote (e.g. https://fencesperth.com/fc?qid=2C3A4M).
 *
 * @param string|null $planner_id
 * @return string
 */
function fc_planner_qid_share_url( $planner_id = null ) {
	$planner_id = $planner_id !== null && $planner_id !== ''
		? (string) $planner_id
		: (string) ( $_SESSION['planner_id'] ?? '' );
	$planner_id = trim( $planner_id );
	if ( $planner_id === '' ) {
		return '';
	}

	$scheme = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ? 'https' : 'http';
	$host   = $_SERVER['HTTP_HOST'] ?? '';
	$script = str_replace( '\\', '/', (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ) );
	$fc_root = rtrim( dirname( $script ), '/' );

	return $scheme . '://' . $host . $fc_root . '?qid=' . rawurlencode( $planner_id );
}

//----------------------------------------------------------------------------------

function toURL($url){
    if( isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    } else {
        $protocol = 'https';
    }
    return $protocol . "://" . $url;
}

/**
 * WordPress/WooCommerce base URL when the planner runs on localhost.
 * Derived from the current request path: everything before "/fc".
 * e.g. /wp/fencing/fc/project-plan → http://localhost/wp/fencing
 *
 * @return string|null Local WP site URL, or null on production hosts.
 */
function fc_wp_site_url() {
    $host_header = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host        = parse_url( '//' . $host_header, PHP_URL_HOST );

    if ( ! $host ) {
        $host = $host_header;
    }

    if ( ! in_array( $host, array( 'localhost', '127.0.0.1' ), true ) ) {
        return null;
    }

    $scheme = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ? 'https' : 'http';

    $path = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    if ( ! is_string( $path ) || $path === '' ) {
        $path = str_replace( '\\', '/', (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ) );
    }

    $path     = '/' . trim( $path, '/' );
    $fc_pos   = stripos( $path, '/fc' );

    if ( $fc_pos === false ) {
        return null;
    }

    $wp_base = substr( $path, 0, $fc_pos );

    return $scheme . '://' . $host_header . rtrim( $wp_base, '/' );
}

/**
 * Replace localhost site URL with the path-derived WP base when applicable.
 *
 * @param array $row Site row from sites().
 * @return array
 */
function fc_apply_localhost_site_url( array $row ) {
    $domain_host = parse_url( '//' . ( $row['domain'] ?? '' ), PHP_URL_HOST );
    if ( ! $domain_host ) {
        $domain_host = $row['domain'] ?? '';
    }

    if ( in_array( $domain_host, array( 'localhost', '127.0.0.1' ), true ) ) {
        $local_wp = fc_wp_site_url();
        if ( $local_wp ) {
            $row['url'] = $local_wp;
        }
    }

    return $row;
}

//----------------------------------------------------------------------------------

function get_uid($l=10) {
    return strtoupper(substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, $l));
}

//----------------------------------------------------------------------------------

function in_uri_segment($keys) {
    $uri_segments = explode('/', trim(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH), '/'));
    foreach ($uri_segments as $segment) {
        if( in_array($segment, $keys) ) {
            return TRUE;
        }
    }
}

//----------------------------------------------------------------------------------

function query_vars($query ='') {
    $qs = $_SERVER['QUERY_STRING'];
    $vars = array();

    if($query == '') return $qs;

    parse_str($_SERVER['QUERY_STRING'], $qs);
    
    foreach ($qs as $key => $value) {     
        $vars[$key] = $value;

        if($value == '0') {
            unset($vars[$key]);   
        }
    }

    return $vars;
}

//----------------------------------------------------------------------------------

function demo_stages() {
    return [
        'html',
        'dev', 
        'demo', 
        'staging', 
        'test'
    ];
}

//----------------------------------------------------------------------------------

/**
 * Config site key derived from a domain (matches admin mysql key rules).
 * Staging hosts (staging.example.com) resolve to the production key (example).
 */
function fc_site_key_from_domain(string $domain): string
{
    $host = parse_url('//' . trim($domain), PHP_URL_HOST);
    if (!$host) {
        $host = trim($domain);
    }
    $host = strtolower((string) $host);
    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '') {
        return 'localhost';
    }

    $pathinfo = pathinfo($host);
    $key = (string) ($pathinfo['filename'] ?? '');
    if (str_starts_with($key, 'staging.')) {
        $key = substr($key, strlen('staging.'));
    }

    return $key;
}

/**
 * Site-keyed map from config.php (e.g. supplier, gtag_id, gtm_id).
 *
 * @return array<string, string>
 */
function fc_config_site_map(string $section): array
{
    static $cache = [];
    $section = trim($section);
    if ($section === '') {
        return [];
    }
    if (array_key_exists($section, $cache)) {
        return $cache[$section];
    }

    $map = [];
    $cfg = config();
    $sectionValue = $cfg->{$section} ?? null;
    if (is_object($sectionValue) || is_array($sectionValue)) {
        foreach ((array) $sectionValue as $key => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $normalized = trim((string) $value);
            if ($normalized === '') {
                continue;
            }
            $map[(string) $key] = $normalized;
        }
    }

    $cache[$section] = $map;

    return $map;
}

/**
 * Read one site value from a config.php site map.
 */
function fc_config_site_value(string $section, string $siteKey, string $fallback = ''): string
{
    $siteKey = trim($siteKey);
    if ($siteKey === '') {
        return trim($fallback);
    }

    $map = fc_config_site_map($section);
    if (isset($map[$siteKey]) && $map[$siteKey] !== '') {
        return $map[$siteKey];
    }

    return trim($fallback);
}

/**
 * Supplier code for a site key (or domain) from config.php `supplier`.
 */
function fc_site_supplier(string $siteKeyOrDomain, string $fallback = ''): string
{
    $input = trim($siteKeyOrDomain);
    if ($input === '') {
        return strtoupper(trim($fallback));
    }

    $siteKey = $input;
    if (str_contains($input, '.') || strcasecmp($input, 'localhost') === 0) {
        // Domain-like input → resolve config key dynamically.
        $siteKey = fc_site_key_from_domain($input);
    }

    $value = fc_config_site_value('supplier', $siteKey, $fallback);

    return strtoupper(trim($value));
}

function sites($key = '', $value = 'id', $search = false) {
    $data = [
        [
            'id'       => 999999,
            'domain'   => "localhost",
            'url'      => '',
            'logo'     => "assets/img/logo/fencesperth.webp",
            'name'     => "Localhost Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencesperth,
            'gtmID'   => config()->gtm_id->fencesperth,
            'restrict' => [
                'left_raked',
                'right_raked'
            ]
        ],
        [
            'id'       => 1,
            'domain'   => "fencesperth.com",
            'url'      => toURL('fencesperth.com'),
            'logo'     => "assets/img/logo/fencesperth.webp",
            'name'     => "Perth's Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencesperth,
            'gtmID'   => config()->gtm_id->fencesperth,
        ],
        [
            'id'       => 1.1,
            'domain'   => "staging.fencesperth.com",
            'url'      => toURL('staging.fencesperth.com'),
            'logo'     => "assets/img/logo/fencesperth.webp",
            'name'     => "Perth's Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencesperth,
            'gtmID'   => config()->gtm_id->fencesperth,
        ],
        [
            'id'       => 2,
            'domain'   => "fencesbrisbane.au",
            'url'      => toURL('fencesbrisbane.au'),
            'logo'     => "assets/img/logo/fencesbrisbane.webp",
            'name'     => "Brisbane's Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencesbrisbane,
            'gtmID'   => config()->gtm_id->fencesbrisbane,
        ],
        [
            'id'       => 2.1,
            'domain'   => "staging.fencesbrisbane.au",
            'url'      => toURL('staging.fencesbrisbane.au'),
            'logo'     => "assets/img/logo/fencesbrisbane.webp",
            'name'     => "Brisbane's Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencesbrisbane,
            'gtmID'   => config()->gtm_id->fencesbrisbane,
        ],
        [
            'id'       => 3,
            'domain'   => "fencingwarehouse.au",
            'url'      => toURL('fencingwarehouse.au'),
            'logo'     => "assets/img/logo/fencesperth.webp",
            'name'     => "Fencing Warehouse"
        ],
        [
            'id'       => 4,
            'domain'   => "fencinggoldcoast.au",
            'url'      => toURL('fencinggoldcoast.au'),
            'logo'     => "assets/img/logo/fencinggoldcoast.webp",
            'name'     => "Gold Coast's Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencinggoldcoast,
            'gtmID'   => config()->gtm_id->fencinggoldcoast,
        ],
        [
            'id'       => 5,
            'domain'   => "fencesadelaide.au",
            'url'      => toURL('fencesadelaide.au'),
            'logo'     => "assets/img/logo/fencesadelaide.webp",
            'name'     => "Adelaide's Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencesadelaide,
            'gtmID'   => config()->gtm_id->fencesadelaide,
        ],
        [
            'id'       => 6,
            'domain'   => "fencessydney.au",
            'url'      => toURL('fencessydney.au'),
            'logo'     => "assets/img/logo/fencessydney.webp",
            'name'     => "Sydney's Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencessydney,
            'gtmID'   => config()->gtm_id->fencessydney,
        ],
        [
            'id'       => 7,
            'domain'   => "fencesmelbourne.au",
            'url'      => toURL('fencesmelbourne.au'),
            'logo'     => "assets/img/logo/fencesmelbourne.webp",
            'name'     => "Melbourne's Fencing Outlet",
            'gtagID'   => config()->gtag_id->fencesmelbourne,
            'gtmID'   => config()->gtm_id->fencesmelbourne,
        ],
        [
            'id'       => 8,
            'domain'   => "fencesnewcastle.au",
            'url'      => toURL('fencesnewcastle.au'),
            'logo'     => "assets/img/logo/fencesnewcastle.webp",
            'name'     => "",
            'gtagID'   => config()->gtag_id->fencesnewcastle,
            'gtmID'   => config()->gtm_id->fencesnewcastle,
        ]
    ];

    foreach ($data as &$row) {
        $row['supplier'] = fc_site_supplier((string) ($row['domain'] ?? ''), 'JG');
    }

    unset($row);

    if( $search ) {
        if( $value === 'domain' ) {
            $search_host = parse_url('//' . $key, PHP_URL_HOST);
            if( !$search_host ) {
                $search_host = $key;
            }

            foreach( $data as $row ) {
                $row_host = parse_url('//' . $row['domain'], PHP_URL_HOST);
                if( !$row_host ) {
                    $row_host = $row['domain'];
                }

                if( $search_host === $row_host ) {
                    return fc_apply_localhost_site_url( $row );
                }
            }

            return FALSE;
        }

        $key = array_search($key, array_column($data, $value));

        if( !empty($key) || $key === 0 ) {
            return fc_apply_localhost_site_url( $data[ $key ] );
        }

        return FALSE;
    }

    return $data;
}

//----------------------------------------------------------------------------------

function selected_fences($fences, $column = 'slug') {
    $info = $_SESSION['fc_data'];

    $fence_data = array();

    foreach (fc_convert_inputs($info['fences']) as $fence) {
        $slug = $fence['form'][0]['fence'];
        $fence_data[$slug] = $fences[$slug][$column];
    }   

    return $fence_data; 
}

/**
 * Match JS `normalizeFenceStyleSlug` for planner section keys vs catalog.
 *
 * @param string $slug Raw slug from `form[0]`.
 * @return string
 */
function fc_normalize_planner_fence_slug( $slug ) {
    $slug = is_string( $slug ) ? $slug : (string) $slug;
    return $slug === 'slat_fence' ? 'slat' : $slug;
}

/**
 * Human-readable fence style label for cart / item list (matches planner `fc_data[slug].title`).
 *
 * @param string     $slug   Fence slug from cart row (`barr`, `slat`, legacy `slat_fence`, etc.).
 * @param array|null $fences Fence catalog from `data/settings.php`.
 * @return string
 */
function fc_fence_style_title_from_slug( $slug, $fences = null ) {
    if ( $fences === null && isset( $GLOBALS['fences'] ) ) {
        $fences = $GLOBALS['fences'];
    }
    if ( ! is_array( $fences ) ) {
        $fences = array();
    }

    $raw  = is_string( $slug ) ? trim( $slug ) : trim( (string) $slug );
    $norm = fc_normalize_planner_fence_slug( $raw );

    foreach ( array( $norm, $raw ) as $key ) {
        if ( $key === '' || ! isset( $fences[ $key ] ) || ! is_array( $fences[ $key ] ) ) {
            continue;
        }
        $row = $fences[ $key ];
        if ( ! empty( $row['title'] ) ) {
            return (string) $row['title'];
        }
        if ( ! empty( $row['name'] ) ) {
            return (string) $row['name'];
        }
    }

    if ( $raw === '' ) {
        return '';
    }

    return ucwords( str_replace( array( '_', '-' ), ' ', $raw ) );
}

/**
 * Fence style label for a cart line (prefers stored title from `post_product_skus`).
 *
 * @param array      $cart_item Cart row from `$_SESSION['fc_cart']['items']`.
 * @param array|null $fences    Fence catalog.
 * @return string
 */
function fc_cart_item_fence_style_label( $cart_item, $fences = null ) {
    if ( ! is_array( $cart_item ) ) {
        return '';
    }
    if ( ! empty( $cart_item['fence_style_title'] ) ) {
        return (string) $cart_item['fence_style_title'];
    }
    if ( empty( $cart_item['fence'] ) ) {
        return '';
    }
    return fc_fence_style_title_from_slug( $cart_item['fence'], $fences );
}

/**
 * Normalise planner colour rows from session / POST (never pass arrays through convert_inputs).
 *
 * @param array|string|null $override_colors Optional POST / caller-supplied colour rows.
 * @return array
 */
function fc_planner_color_rows_from_session( $override_colors = null ) {
    if ( is_array( $override_colors ) ) {
        return $override_colors;
    }

    if ( is_string( $override_colors ) && $override_colors !== '' ) {
        $decoded = json_decode( $override_colors, true );
        if ( is_array( $decoded ) ) {
            return $decoded;
        }
    }

    $info   = isset( $_SESSION['fc_data'] ) ? $_SESSION['fc_data'] : array();
    $colors = array();

    if ( ! empty( $info['color'] ) ) {
        if ( is_array( $info['color'] ) ) {
            $colors = $info['color'];
        } else {
            $colors = fc_convert_inputs( $info['color'] );
            if ( is_string( $colors ) ) {
                $decoded = json_decode( $colors, true );
                $colors  = is_array( $decoded ) ? $decoded : array();
            }
        }
    }

    if ( ( ! is_array( $colors ) || $colors === array() ) && ! empty( $info['project_plans'] ) ) {
        $pp = is_array( $info['project_plans'] ) ? $info['project_plans'] : json_decode( (string) $info['project_plans'], true );
        if ( is_array( $pp ) && ! empty( $pp['color'] ) && is_array( $pp['color'] ) ) {
            $colors = $pp['color'];
        }
    }

    return is_array( $colors ) ? $colors : array();
}

/**
 * Parse cart row id from localStorage (`flat_top-0`, `barr-1`).
 *
 * @param string $cart_item_key
 * @return array{fence:string,section:int|null}
 */
function fc_parse_planner_cart_row_key( $cart_item_key ) {
    $key = (string) $cart_item_key;
    if ( preg_match( '/-(\d+)$/', $key, $m ) ) {
        $fence = substr( $key, 0, -strlen( $m[0] ) );
        return array(
            'fence'   => fc_normalize_planner_fence_slug( $fence ),
            'section' => (int) $m[1],
        );
    }

    return array(
        'fence'   => fc_normalize_planner_fence_slug( $key ),
        'section' => null,
    );
}

/**
 * Resolve colour for a planner section (per-section row first, then by fence style).
 *
 * @param string   $fence_slug
 * @param int|null $section_index
 * @param array    $colors
 * @return string
 */
function fc_resolve_planner_cart_fence_color( $fence_slug, $section_index, $colors ) {
    if ( ! is_array( $colors ) ) {
        return '';
    }

    $norm = fc_normalize_planner_fence_slug( (string) $fence_slug );

    if ( $section_index !== null && isset( $colors[ $section_index ] ) && is_array( $colors[ $section_index ] ) ) {
        $row_fence = fc_normalize_planner_fence_slug( (string) ( $colors[ $section_index ]['fence'] ?? '' ) );
        if ( $row_fence === $norm && ! empty( $colors[ $section_index ]['color'] ) ) {
            return (string) $colors[ $section_index ]['color'];
        }
    }

    foreach ( $colors as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }
        $row_fence = fc_normalize_planner_fence_slug( (string) ( $row['fence'] ?? '' ) );
        if ( $row_fence === $norm && ! empty( $row['color'] ) ) {
            return (string) $row['color'];
        }
    }

    return '';
}

/**
 * Default colour column for SKU lookup when session colour rows are missing.
 *
 * @param string $fence_slug
 * @return string
 */
function fc_planner_default_color_for_fence_slug( $fence_slug ) {
    global $fences;

    $norm = fc_normalize_planner_fence_slug( (string) $fence_slug );
    if ( isset( $fences ) && is_array( $fences ) && isset( $fences[ $norm ]['color'] ) && is_array( $fences[ $norm ]['color'] ) ) {
        $first = reset( $fences[ $norm ]['color'] );
        if ( is_string( $first ) && $first !== '' ) {
            return $first;
        }
    }

    return 'black';
}

/**
 * Merge section cart BOM lines into fence+colour buckets for `get_product_skus()`.
 *
 * @param array $cart_items_grouped
 * @param array $colors
 * @return array<string, array<string, int[]>>
 */
function fc_regroup_planner_cart_items_for_skus( $cart_items_grouped, $colors ) {
    $regrouped = array();

    if ( ! is_array( $cart_items_grouped ) ) {
        return $regrouped;
    }

    foreach ( $cart_items_grouped as $cart_item ) {
        if ( ! is_array( $cart_item ) ) {
            continue;
        }
        foreach ( $cart_item as $cart_item_key => $ci_items ) {
            if ( ! is_array( $ci_items ) ) {
                continue;
            }

            $parsed = fc_parse_planner_cart_row_key( $cart_item_key );
            $color  = fc_resolve_planner_cart_fence_color( $parsed['fence'], $parsed['section'], $colors );
            if ( $color === '' ) {
                $color = fc_planner_default_color_for_fence_slug( $parsed['fence'] );
            }
            if ( $color === '' ) {
                continue;
            }

            $bucket_id = $parsed['fence'] . '+' . $color;
            foreach ( $ci_items as $ci_v ) {
                if ( empty( $ci_v['slug'] ) ) {
                    continue;
                }

                if ( ! empty( $ci_v['optional'] ) ) {
                    $suggested = (int) ( $ci_v['suggested_qty'] ?? 0 );
                    if ( $suggested <= 0 ) {
                        continue;
                    }
                    $slug = (string) $ci_v['slug'];
                    if (
                        ! isset( $regrouped[ $bucket_id ][ $slug ] )
                        || ! is_array( $regrouped[ $bucket_id ][ $slug ] )
                        || empty( $regrouped[ $bucket_id ][ $slug ]['optional'] )
                    ) {
                        $regrouped[ $bucket_id ][ $slug ] = array(
                            'optional'      => true,
                            'qty'           => 0,
                            'suggested_qty' => 0,
                        );
                    }
                    $regrouped[ $bucket_id ][ $slug ]['suggested_qty'] += $suggested;
                    continue;
                }

                $qty = (int) ( $ci_v['qty'] ?? 0 );
                if ( $qty <= 0 ) {
                    continue;
                }
                $regrouped[ $bucket_id ][ $ci_v['slug'] ][] = $qty;
            }
        }
    }

    return $regrouped;
}

/**
 * Build `post_product_skus()` input from regrouped slug/qty buckets.
 *
 * @param array       $regrouped
 * @param string|null $fences_json
 * @return array
 */
function fc_format_regrouped_cart_items_for_product_skus( $regrouped, $fences_json = null ) {
    $cart_items_data = array();

    if ( ! is_array( $regrouped ) ) {
        return $cart_items_data;
    }

    foreach ( $regrouped as $cir_k => $cir_items ) {
        $cart_items_formatted = array();
        foreach ( $cir_items as $ciri_k => $ciri_v ) {
            if ( is_array( $ciri_v ) && ! empty( $ciri_v['optional'] ) ) {
                $cart_items_formatted[] = array(
                    'slug'          => $ciri_k,
                    'qty'           => 0,
                    'optional'      => true,
                    'suggested_qty' => (int) ( $ciri_v['suggested_qty'] ?? 0 ),
                );
                continue;
            }
            $cart_items_formatted[] = array(
                'slug' => $ciri_k,
                'qty'  => array_sum( (array) $ciri_v ),
            );
        }

        $parts      = explode( '+', (string) $cir_k, 2 );
        $fence_slug = $parts[0];
        $max_h      = function_exists( 'fc_planner_max_fence_height_mm_for_fence_slug' )
            ? fc_planner_max_fence_height_mm_for_fence_slug( $fence_slug, $fences_json )
            : 0;

        $cart_items_data[ $cir_k ] = array(
            'slug'                => $fence_slug,
            'color'               => $parts[1] ?? '',
            'items'               => $cart_items_formatted,
            'max_fence_height_mm' => $max_h,
        );
    }

    return $cart_items_data;
}

/**
 * Sort cart rows: fence style title (A–Z), then product name (A–Z).
 *
 * @param array      $cart
 * @param array|null $fences
 */
function fc_sort_cart_items_by_fence_style_and_name( array &$cart, $fences = null ) {
    if ( $fences === null && isset( $GLOBALS['fences'] ) ) {
        $fences = $GLOBALS['fences'];
    }
    if ( ! is_array( $fences ) ) {
        $fences = array();
    }

    usort(
        $cart,
        function ( $a, $b ) use ( $fences ) {
            $style_a = fc_cart_item_fence_style_label( is_array( $a ) ? $a : array(), $fences );
            $style_b = fc_cart_item_fence_style_label( is_array( $b ) ? $b : array(), $fences );
            $cmp     = strcasecmp( $style_a, $style_b );
            if ( $cmp !== 0 ) {
                return $cmp;
            }
            return strcasecmp(
                (string) ( is_array( $a ) ? ( $a['name'] ?? '' ) : '' ),
                (string) ( is_array( $b ) ? ( $b['name'] ?? '' ) : '' )
            );
        }
    );
}

/**
 * Fence types in session `fc_data['fences']`, with section counts (first-seen order).
 *
 * @param array $fences Global fence catalog.
 * @return array List of rows: slug, name, count.
 */
function fc_fence_section_types_with_counts( $fences ) {
    $info = isset( $_SESSION['fc_data'] ) ? $_SESSION['fc_data'] : array();
    if ( empty( $info['fences'] ) ) {
        return array();
    }
    $rows = fc_convert_inputs( $info['fences'] );
    if ( ! is_array( $rows ) ) {
        return array();
    }
    $order  = array();
    $counts = array();
    foreach ( $rows as $fence ) {
        if ( ! is_array( $fence ) || empty( $fence['form'] ) || ! isset( $fence['form'][0] ) || ! is_array( $fence['form'][0] ) ) {
            continue;
        }
        $tab0 = $fence['form'][0];
        $raw  = '';
        if ( ! empty( $tab0['fence'] ) ) {
            $raw = $tab0['fence'];
        } elseif ( ! empty( $tab0['style'] ) ) {
            $raw = $tab0['style'];
        }
        if ( $raw === '' || $raw === null ) {
            continue;
        }
        $norm = fc_normalize_planner_fence_slug( $raw );
        if ( ! isset( $counts[ $norm ] ) ) {
            $counts[ $norm ] = 0;
            $order[]         = $norm;
        }
        $counts[ $norm ]++;
    }
    $out = array();
    foreach ( $order as $norm ) {
        $name = '';
        if ( isset( $fences[ $norm ]['name'] ) && (string) $fences[ $norm ]['name'] !== '' ) {
            $name = $fences[ $norm ]['name'];
        } elseif ( isset( $fences[ $norm ]['title'] ) && (string) $fences[ $norm ]['title'] !== '' ) {
            $name = $fences[ $norm ]['title'];
        } else {
            $name = $norm;
        }
        $out[] = array(
            'slug'  => $norm,
            'name'  => $name,
            'count' => $counts[ $norm ],
        );
    }
    return $out;
}

/**
 * How many planner sections use this fence style (session `fc_data['fences']`), slug-normalized like
 * {@see fc_fence_section_types_with_counts()}.
 *
 * @param string $slug Fence slug from colour row / project_plans colour entry.
 * @return int
 */
function fc_planner_section_count_for_fence_slug( $slug ) {
    $info = isset( $_SESSION['fc_data'] ) ? $_SESSION['fc_data'] : array();
    if ( empty( $info['fences'] ) ) {
        return 0;
    }
    $rows = fc_convert_inputs( $info['fences'] );
    if ( ! is_array( $rows ) ) {
        return 0;
    }
    $target = fc_normalize_planner_fence_slug( (string) $slug );
    $n      = 0;
    foreach ( $rows as $fence ) {
        if ( ! is_array( $fence ) || empty( $fence['form'] ) || ! isset( $fence['form'][0] ) || ! is_array( $fence['form'][0] ) ) {
            continue;
        }
        $tab0 = $fence['form'][0];
        $raw  = '';
        if ( ! empty( $tab0['fence'] ) ) {
            $raw = $tab0['fence'];
        } elseif ( ! empty( $tab0['style'] ) ) {
            $raw = $tab0['style'];
        }
        if ( $raw === '' || $raw === null ) {
            continue;
        }
        $norm = fc_normalize_planner_fence_slug( $raw );
        if ( $norm === $target ) {
            $n++;
        }
    }
    return $n;
}

//----------------------------------------------------------------------------------

/**
 * Map a callback over one value or a list (FC-prefixed to avoid WP plugin collisions).
 *
 * @param string       $key  Callable name
 * @param mixed        $items
 * @param bool         $list Wrap each result in <li>
 * @return string|array
 */
function fc_get_items($key, $items, $list = false) {
    if( !is_array($items) ) {
      return call_user_func_array($key, [$items]);
    }

    if( $list ) {
      $data = '';
      foreach( $items as $row ) {
        $data .= '<li>'.call_user_func_array($key, [$row]).'</li>';
      }      
      return $data;
    }

    foreach( $items as $row ) {
      $data[] = call_user_func_array($key, [$row]);
    }

    return implode(', ', $data);    
}

//----------------------------------------------------------------------------------

function array_to_json($val='') {
    if( is_array($val) ) {
      return json_encode($val);    
    }

    return $val;
}

//----------------------------------------------------------------------------------

/**
 * Store mobile as a string; never coerce to number (leading 0 must be preserved).
 */
function fc_normalize_mobile_for_storage( $mobile ) {
    if ( $mobile === null || $mobile === '' ) {
        return '';
    }

    return trim( (string) $mobile );
}

//----------------------------------------------------------------------------------


/**
 * Normalize JSON / dates / scalars for planner form data (FC-prefixed; do not clash with WP plugins).
 *
 * @param mixed $val
 * @return mixed
 */
function fc_convert_inputs($val='') {
    if( is_array($val) ) {
        return json_encode($val);
    }

    // Digit strings with a leading zero must stay strings (json_decode would drop the zero).
    if ( is_string( $val ) && preg_match( '/^0\d+$/', trim( $val ) ) ) {
        return trim( $val );
    }

    if ( $data = json_decode($val, true) ) {
        return $data;
    }

    if (preg_match("/\d{4}\-\d{2}-\d{2}/", $val) || preg_match("/\d{2}\-\d{2}-\d{4}/", $val) ) {
        
        if( strlen($val) > 10 ) {
            return $val;            
        } else {
            return date_formatted_b($val);
        }

    }

    return $val;
}

//----------------------------------------------------------------------------------

if (!function_exists('dd')) {
    function dd($data ='') {
        echo '<pre>';
        print_r( $data );
        exit;
    }
}

//----------------------------------------------------------------------------------

function fc_deliver_options() {
    $data = [
		[
			'value'   => 'shipping_1',
			'label'   => 'Warehouse Pickup',
			'price'   => 0,
			'default' => TRUE,
		],
		[
			'value'   => 'shipping_2',
			'label'   => 'Deliver to Site (Metro $89)',
			'price'   => 89,
			'default' => FALSE,
		],
		[
			'value'   => 'shipping_3',
			'label'   => 'Deliver to Site (Rural - $TBA)',
			'price'   => 0,
			'default' => FALSE,
		]
    ];

    return $data;
}

//----------------------------------------------------------------------------------

function load_csv($file = '') {
    if( ! file_exists($file) ) {
        return FALSE;
    }

    $handle = fopen($file, "r");

    $i = $h = 0;
    while (($data = fgetcsv($handle)) !== FALSE) {
        if( $i == 0) {
            $header = $data;                               
        } else {
            $e=0;
            foreach ($data as $d) {                     
                if( @$header[$e] ) {  
                    $col = str_replace([' ', '-'], ['_', ''], strtolower( rtrim( $header[$e] ) ) );
                    $order_info[$col] = $d;
                    $e++;
                }
            }

            $rows[$i-1] = $order_info;    
        }

        $i++;
    } 

    return $rows;
}

//----------------------------------------------------------------------------------

/**
 * Maps fence planner slug to products.csv STYLE column (see data/products.csv).
 */
function fc_products_csv_style_for_fence($fence_slug) {
	$s = (string) $fence_slug;
	if ($s === 'slat_fence_infill') {
		return 'slat_infill';
	}
	if ($s === 'slat_fence') {
		return 'slat';
	}
	return $s;
}

/**
 * Main Slat: stock post length tier (mm) from Step 2 Fence Height.
 * <=1800 → 1800, <=2400 → 2400, <=2700 → 2700, else 6000.
 */
function fc_slat_post_height_tier_mm($fence_height_mm) {
	$h = (int) $fence_height_mm;
	if ($h <= 0) {
		return 2400;
	}
	if ($h <= 1800) {
		return 1800;
	}
	if ($h <= 2400) {
		return 2400;
	}
	if ($h <= 2700) {
		return 2700;
	}
	return 6000;
}

function fc_slat_post_catalog_slug_from_fence_height_mm($fence_height_mm) {
	$tier = fc_slat_post_height_tier_mm($fence_height_mm);
	return 'slat_post+50x50_' . $tier;
}

/** Read max_fence_height from a planner tab row (`custom_fence-{n}` shape). */
function fc_read_max_fence_height_mm_from_form_row($row) {
	if (! is_array($row)) {
		return 0;
	}
	$try_style_keys = array('slat', 'slat_fence');
	$fbs = isset($row['fieldsByStyle']) && is_array($row['fieldsByStyle']) ? $row['fieldsByStyle'] : array();
	foreach ($try_style_keys as $sk) {
		if (empty($fbs[ $sk ]) || ! is_array($fbs[ $sk ])) {
			continue;
		}
		foreach ($fbs[ $sk ] as $f) {
			if (! is_array($f)) {
				continue;
			}
			if (($f['name'] ?? '') === 'max_fence_height' && ($f['value'] ?? '') !== '') {
				return (int) $f['value'];
			}
		}
	}
	if (! empty($row['fields']) && is_array($row['fields'])) {
		foreach ($row['fields'] as $f) {
			if (! is_array($f)) {
				continue;
			}
			if (($f['name'] ?? '') === 'max_fence_height' && ($f['value'] ?? '') !== '') {
				return (int) $f['value'];
			}
		}
	}
	return 0;
}

/**
 * Highest Step 2 Fence Height (mm) among planner sections for main Slat (`slat` / `slat_fence`).
 */
function fc_planner_max_fence_height_mm_for_fence_slug($fence_slug, $fences_json = null) {
	$want_style = fc_products_csv_style_for_fence((string) $fence_slug);
	if ($want_style !== 'slat') {
		return 0;
	}
	if ($fences_json === null) {
		$fences_json = isset($_SESSION['fc_data']['fences']) ? $_SESSION['fc_data']['fences'] : '[]';
	}
	$sections = is_string($fences_json) ? json_decode($fences_json, true) : $fences_json;
	if (! is_array($sections)) {
		return 0;
	}
	$max = 0;
	foreach ($sections as $section) {
		if (! is_array($section)) {
			continue;
		}
		$form = $section['form'] ?? array();
		$row  = (is_array($form) && isset($form[0])) ? $form[0] : array();
		$row_style = fc_products_csv_style_for_fence((string) ($row['style'] ?? $row['fence'] ?? ''));
		if ($row_style !== 'slat') {
			continue;
		}
		$h = fc_read_max_fence_height_mm_from_form_row($row);
		if ($h > $max) {
			$max = $h;
		}
	}
	return $max;
}

/**
 * Maps planner/cart line slugs (e.g. panel_post+opt-1) to products.csv SLUG for main Slat.
 * Post row follows Fence Height tiers (1800 / 2400 / 2700 / 6000 mm stock lengths).
 *
 * Slat Infill: FSQ infill mode uses no fence posts — do not map to slat_post+*.
 *
 * @param int|null $fence_height_mm Step 2 Fence Height; resolved from session when omitted.
 */
function fc_slat_catalog_slug_for_planner_line($item_slug, $fence_slug, $color_column_key, $fence_height_mm = null) {
	$style = fc_products_csv_style_for_fence((string) $fence_slug);
	if ($style !== 'slat' && $style !== 'slat_infill') {
		return (string) $item_slug;
	}
	if ($style === 'slat_infill') {
		return (string) $item_slug;
	}
	$s = (string) $item_slug;
	$height = $fence_height_mm !== null ? (int) $fence_height_mm : 0;
	if ($height <= 0) {
		$height = fc_planner_max_fence_height_mm_for_fence_slug($fence_slug);
	}
	$post_cat = fc_slat_post_catalog_slug_from_fence_height_mm($height);
	if (preg_match('/^panel_post\+opt-(\d+(?:-\d+)?)(?:\+(\d+))?$/', $s)) {
		return $post_cat;
	}
	if (preg_match('/^raked_post\+opt-(\d+(?:-\d+)?)(?:\+(\d+))?$/', $s)) {
		return $post_cat;
	}
	return $s;
}

//----------------------------------------------------------------------------------

function get_product_skus($data = array()) {

	$products = $skus = array();
 	$the_products = load_csv('data/products.csv');

    foreach ($data as $d) {

        $column = 'slug';
     	$items = $d['items'];
     	$color = $d['color'];
        $supplier = $_SESSION['site']['supplier'];
        $style_key = fc_products_csv_style_for_fence($d['slug']);

    	foreach ($items as $item) {

			// Infill panels: no aluminium posts in BOM (see slat-fence-app.html infill matrix → No Post).
			if ($style_key === 'slat_infill' && preg_match('/^(panel_post|raked_post)\+/', (string) $item['slug'])) {
				continue;
			}

			$fence_height_mm = isset($d['max_fence_height_mm']) ? (int) $d['max_fence_height_mm'] : 0;
			if ($fence_height_mm <= 0) {
				$fence_height_mm = fc_planner_max_fence_height_mm_for_fence_slug($d['slug']);
			}
			$lookup_slug = fc_slat_catalog_slug_for_planner_line(
				$item['slug'],
				$d['slug'],
				$color,
				$fence_height_mm
			);
			$try_slugs = ($lookup_slug !== $item['slug']) ? array($lookup_slug, $item['slug']) : array($item['slug']);

			$filtered_product = array();
			$resolved_slug = $item['slug'];
			foreach ($try_slugs as $try_slug) {
				$filtered_product = array_filter($the_products, function($val) use($try_slug, $supplier, $style_key){
					return ( $val['slug'] == $try_slug && $val['supplier'] == $supplier && $style_key == $val['style']);
				});
				if ($filtered_product) {
					$resolved_slug = $try_slug;
					break;
				}
			}

            if( $filtered_product ) {
                $key = array_keys($filtered_product)[0];

                // Some rows may not have all color columns (csv header can evolve).
                // Treat missing/unset values as OFF so SKU resolution stays robust.
                $sku = isset($the_products[$key][$color]) ? $the_products[$key][$color] : 'off';
                if (!is_string($sku) || $sku === '') {
                    $sku = 'off';
                }

                if( $key !== false && strtolower($sku) != 'off' ){
                  $products[] = [
                    'sku'   => $sku,
                    'qty'   => (int) ( $item['qty'] ?? 0 ),
                    'slug'  => $resolved_slug,
                    'fence' => $d['slug'],
                    'color' => $d['color'],
                    'product_name' => $the_products[$key]['product'] ?? '',
                    'optional' => ! empty( $item['optional'] ),
                    'suggested_qty' => (int) ( $item['suggested_qty'] ?? ( $item['qty'] ?? 0 ) ),
                  ]; 
                }
                
                $skus[] = $sku;
            }

    	}

    }

	$_SESSION['custom_fence_products'] = $products;

	return $products;
}

//----------------------------------------------------------------------------------

//----------------------------------------------------------------------------------

/**
 * Stable key for optional cart line opt-in (Barr base_plate+dynabolts, etc.).
 */
function fc_optional_cart_item_key( array $product ) {
    return implode(
        '|',
        array(
            fc_normalize_planner_fence_slug( (string) ( $product['fence'] ?? '' ) ),
            (string) ( $product['color'] ?? '' ),
            (string) ( $product['slug'] ?? '' ),
        )
    );
}

/**
 * Remember optional line opt-in across cart rebuilds.
 *
 * @return array<string, bool>
 */
function fc_cart_optional_included_snapshot() {
    $snapshot = array();
    if ( empty( $_SESSION['fc_cart']['items'] ) || ! is_array( $_SESSION['fc_cart']['items'] ) ) {
        return $snapshot;
    }
    foreach ( $_SESSION['fc_cart']['items'] as $row ) {
        if ( ! is_array( $row ) || empty( $row['optional'] ) ) {
            continue;
        }
        $key = ! empty( $row['optional_key'] )
            ? (string) $row['optional_key']
            : fc_optional_cart_item_key( $row );
        if ( $key !== '' ) {
            $snapshot[ $key ] = ! empty( $row['optional_included'] );
        }
    }
    return $snapshot;
}

/**
 * Count cart lines with qty > 0 (optional lines excluded when not added).
 */
function fc_cart_included_item_count( array $items ) {
    $count = 0;
    foreach ( $items as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }
        if ( (int) ( $row['qty'] ?? 0 ) > 0 ) {
            $count++;
        }
    }
    return $count;
}

function post_product_skus($cart_items = array()) {
    global $fences;
    $supplier = strtoupper($_SESSION['site']['supplier']);
	$items = $carts = array();
    $skus = get_product_skus($cart_items);

    $post_query = array();

    foreach ($skus as $sku) {
        $post_query[] = $sku;
    }

 	$the_products = load_csv('data/wc-products-'.$supplier.'.csv');
    if ( ! is_array( $the_products ) ) {
        $the_products = array();
    }

    foreach ($post_query as $query) {

		$key = array_search($query['sku'], array_column($the_products, 'sku'), true);

        $image = fc_cart_image_url_for_sku( $query['sku'], $supplier );
        $wc_name = '';
        if ( $image === '' && $key !== false && isset( $the_products[ $key ] ) ) {
            $image = fc_wc_product_first_image_url( $the_products[ $key ] );
        }
        if ($key !== false && isset($the_products[$key])) {
            $wc_name = $the_products[$key]['name'] ?? '';
        }

        $items[]  = [
            'sku'   => $query['sku'],
            'name'  => $wc_name,
            'slug'  => $query['slug'],
            'color' => $query['color'],
            'fence' => $query['fence'],
            'image' => $image,
        ];

    }

    $count = count($items);
    $rand  = rand(2, $count);

    $custom_fence_products = $_SESSION['custom_fence_products'];
    $optional_included     = fc_cart_optional_included_snapshot();

    $i=1;
    foreach ($custom_fence_products as $custom_fence_product) {

        $key = array_search($custom_fence_product['sku'], array_column($items, 'sku'), true);

        $is_optional  = ! empty( $custom_fence_product['optional'] );
        $suggested_qty = (int) ( $custom_fence_product['suggested_qty'] ?? 0 );
        $opt_key      = fc_optional_cart_item_key( $custom_fence_product );
        $included     = $is_optional && ! empty( $optional_included[ $opt_key ] );
        $line_qty     = $is_optional
            ? ( $included ? max( $suggested_qty, 0 ) : 0 )
            : (int) ( $custom_fence_product['qty'] ?? 0 );

        if ( ! $is_optional && empty( $line_qty ) ) {
            $i++;
            continue;
        }

        if ( $is_optional && $suggested_qty <= 0 ) {
            $i++;
            continue;
        }

        if ( $line_qty || $is_optional ) {

            $sku = $custom_fence_product['sku'];
            $display_name = ($key !== false && !empty($items[$key]['name']))
                ? $items[$key]['name']
                : ($custom_fence_product['product_name'] ?? $sku);
            $display_image = ($key !== false) ? ($items[$key]['image'] ?? '') : '';
            if ( $display_image === '' ) {
                $display_image = fc_cart_image_url_for_sku( $sku, $supplier );
            }
            if ( $display_image === '' && $key !== false ) {
                $display_image = $items[ $key ]['image'] ?? '';
            }

            $carts[] = [
                'name'  => $display_name,
                'image' => $display_image,
                'sku'   => $sku,
                'slug'  => $custom_fence_product['slug'],
                'color' => $custom_fence_product['color'],
                'fence' => $custom_fence_product['fence'],
                'fence_style_title' => fc_fence_style_title_from_slug(
                    $custom_fence_product['fence'],
                    isset( $fences ) ? $fences : array()
                ),
                '_dedupe_key' => implode(
                    '|',
                    array(
                        (string) $sku,
                        fc_normalize_planner_fence_slug( (string) ( $custom_fence_product['fence'] ?? '' ) ),
                        (string) ( $custom_fence_product['color'] ?? '' ),
                    )
                ),
                'stock' => $i == 1 || $i == $rand ? 'low' : 'yes',
                'qty'   => $line_qty,
                'original_qty' => $line_qty,
                'optional' => $is_optional,
                'optional_included' => $included,
                'optional_key' => $opt_key,
                'suggested_qty' => $suggested_qty,
            ];
            $i++;
        }
    }

    $cart = unique_multidim_array( $carts, '_dedupe_key', array( 'qty', 'original_qty', 'suggested_qty' ) );

    foreach ( $cart as $ck => $crow ) {
        if ( ! is_array( $crow ) ) {
            continue;
        }
        if ( ! empty( $crow['optional'] ) ) {
            $opt_key = ! empty( $crow['optional_key'] )
                ? (string) $crow['optional_key']
                : fc_optional_cart_item_key( $crow );
            $included = ! empty( $optional_included[ $opt_key ] );
            $suggested = (int) ( $crow['suggested_qty'] ?? 0 );
            $cart[ $ck ]['optional_included'] = $included;
            $cart[ $ck ]['qty']              = $included ? $suggested : 0;
            $cart[ $ck ]['original_qty']      = $cart[ $ck ]['qty'];
        }
        if ( array_key_exists( '_dedupe_key', $crow ) ) {
            unset( $cart[ $ck ]['_dedupe_key'] );
        }
    }

    fc_sort_cart_items_by_fence_style_and_name( $cart, isset( $fences ) ? $fences : array() );

    $_SESSION['fc_cart']['items'] = $cart;
}

//----------------------------------------------------------------------------------

function unique_multidim_array($array, $key, $addedKeys) { 
    $temp_array = [];
    $key_array = []; 
    $i = 0;  

    foreach($array as $val) { 
        if (!in_array($val[$key], $key_array)) { 
            $key_array[$i] = $val[$key]; 
            $temp_array[$i] = $val; 
        }else{
            $pkey = array_search($val[$key],$key_array);

            foreach ($addedKeys as $addedKey) {
                $temp_array[$pkey][$addedKey] += $val[$addedKey];
            }

            // die;
        }
        $i++; 
    } 
    return $temp_array; 
} 

//----------------------------------------------------------------------------------

function is_localhost($whitelist = ['127.0.0.1', '::1']) {
    return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
}

//----------------------------------------------------------------------------------

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key => $row) {
        $sort_col[$key] = $row[$col];
    }
    array_multisort($sort_col, $dir, $arr);
}

//----------------------------------------------------------------------------------

/**
 * WooCommerce product CSV rows for a supplier (cached per request).
 *
 * @param string|null $supplier JG|GO
 * @return array
 */
function fc_wc_products_csv_rows( $supplier = null ) {
    static $cache = array();

    if ( $supplier === null ) {
        $supplier = isset( $_SESSION['site']['supplier'] ) ? strtoupper( (string) $_SESSION['site']['supplier'] ) : 'JG';
    } else {
        $supplier = strtoupper( (string) $supplier );
    }

    if ( ! isset( $cache[ $supplier ] ) ) {
        $rows = load_csv( 'data/wc-products-' . $supplier . '.csv' );
        $cache[ $supplier ] = is_array( $rows ) ? $rows : array();
    }

    return $cache[ $supplier ];
}

/**
 * Find a WooCommerce CSV row by SKU (site supplier first, then JG/GO fallback).
 *
 * @param string      $sku
 * @param string|null $supplier
 * @return array|null
 */
function fc_wc_product_row_by_sku( $sku, $supplier = null ) {
    $sku = trim( (string) $sku );
    if ( $sku === '' ) {
        return null;
    }

    $primary = $supplier !== null ? strtoupper( (string) $supplier ) : null;
    $try     = array();
    if ( $primary ) {
        $try[] = $primary;
    }
    foreach ( array( 'JG', 'GO' ) as $sup ) {
        if ( ! in_array( $sup, $try, true ) ) {
            $try[] = $sup;
        }
    }

    foreach ( $try as $sup ) {
        foreach ( fc_wc_products_csv_rows( $sup ) as $row ) {
            if ( isset( $row['sku'] ) && (string) $row['sku'] === $sku ) {
                return $row;
            }
        }
    }

    return null;
}

/**
 * First product image URL from a WooCommerce CSV row.
 *
 * @param array|null $row
 * @return string
 */
function fc_wc_product_first_image_url( $row ) {
    if ( ! is_array( $row ) ) {
        return '';
    }

    $raw = isset( $row['images'] ) ? (string) $row['images'] : '';
    if ( trim( $raw ) === '' ) {
        return '';
    }

    $parts = preg_split( '/\s*,\s*/', trim( $raw ), 2 );

    return isset( $parts[0] ) ? trim( $parts[0] ) : '';
}

/**
 * Resolve cart line image URL from SKU via WooCommerce CSV.
 *
 * @param string      $sku
 * @param string|null $supplier
 * @return string
 */
function fc_cart_image_url_for_sku( $sku, $supplier = null ) {
    return fc_wc_product_first_image_url( fc_wc_product_row_by_sku( $sku, $supplier ) );
}

/**
 * URL for cart thumbnail display (full image — CSS scales; avoids missing WP -150x150 sizes).
 *
 * @param string $url
 * @return string
 */
function fc_cart_display_image_url( $url ) {
    $url = trim( (string) $url );
    return $url;
}

/**
 * Fill missing `image` keys on cart rows from WooCommerce CSV.
 *
 * @param array       $items
 * @param string|null $supplier
 */
function fc_cart_ensure_items_have_images( array &$items, $supplier = null ) {
    foreach ( $items as $idx => $row ) {
        if ( ! is_array( $row ) || ! empty( $row['image'] ) ) {
            continue;
        }
        $sku = isset( $row['sku'] ) ? (string) $row['sku'] : '';
        if ( $sku === '' ) {
            continue;
        }
        $img = fc_cart_image_url_for_sku( $sku, $supplier );
        if ( $img !== '' ) {
            $items[ $idx ]['image'] = $img;
        }
    }
}

//----------------------------------------------------------------------------------

function add_filepath_last($filename, $add ='') {
    if ( ! is_string( $filename ) || trim( $filename ) === '' ) {
        return '';
    }

    $arr = pathinfo( $filename );

    if ( empty( $arr['filename'] ) || empty( $arr['extension'] ) ) {
        return $filename;
    }

    $file = [
        $arr['dirname'] . '/',
        $arr['filename'],
        $add,
        '.' . $arr['extension'],
    ];

    return implode( '', array_filter( $file ) );
}

//----------------------------------------------------------------------------------

/**
 * Shape expected by planner / project-plan JS (`fc_fence_info`).
 *
 * @param object|array|null $row planners table row.
 * @return object
 */
function fc_planner_row_to_js_fence_info( $row ) {
    if ( ! $row || ! is_object( $row ) ) {
        return (object) array();
    }

    $fence_data = isset( $row->fence_data ) ? $row->fence_data : '';
    if ( is_array( $fence_data ) ) {
        $fence_data = json_encode( $fence_data );
    }

    $cart_items = isset( $row->cart_items_data ) ? $row->cart_items_data : '[]';
    if ( is_array( $cart_items ) ) {
        $cart_items = json_encode( $cart_items );
    }

    $project_plans = isset( $row->project_plans_data ) ? $row->project_plans_data : '';
    if ( is_array( $project_plans ) ) {
        $project_plans = json_encode( $project_plans );
    }

    $section_count = isset( $row->section_count ) ? (int) $row->section_count : 0;
    if ( $section_count < 1 && is_string( $fence_data ) && $fence_data !== '' ) {
        $decoded = json_decode( $fence_data, true );
        if ( is_array( $decoded ) ) {
            $section_count = count( $decoded );
        }
    }

    return (object) array(
        'fence_data'         => $fence_data,
        'cart_items_data'    => $cart_items ? $cart_items : '[]',
        'project_plans_data' => $project_plans,
        'section_count'      => $section_count,
    );
}

/**
 * Restore $_SESSION['fc_data'] (and cart) from a saved planners row so load-quote + project-plan work.
 *
 * @param object $row planners table row.
 */
function fc_hydrate_planner_quote_session_from_row( $row ) {
    if ( ! $row || ! is_object( $row ) ) {
        return;
    }

    if ( empty( $_SESSION['site'] ) ) {
        $site = sites( $_SERVER['HTTP_HOST'], 'domain', true );
        if ( $site ) {
            $_SESSION['site'] = $site;
        }
    }

    if ( empty( $_SESSION['fc_data'] ) || ! is_array( $_SESSION['fc_data'] ) ) {
        $_SESSION['fc_data'] = array();
    }

    $fd = isset( $row->fence_data ) ? $row->fence_data : '';
    if ( $fd !== '' && $fd !== null ) {
        $_SESSION['fc_data']['fences'] = is_string( $fd ) ? $fd : json_encode( $fd );
    }

    $cart = isset( $row->cart_items_data ) ? $row->cart_items_data : '';
    if ( $cart !== '' && $cart !== null ) {
        $_SESSION['fc_data']['cart_items'] = is_string( $cart ) ? $cart : json_encode( $cart );
    }

    $cart_data = isset( $row->cart_data ) ? $row->cart_data : '';
    if ( $cart_data !== '' && $cart_data !== null ) {
        $decoded_cart = is_string( $cart_data ) ? json_decode( $cart_data, true ) : $cart_data;
        if ( is_array( $decoded_cart ) ) {
            $_SESSION['fc_cart']['items'] = $decoded_cart;
        }
    }

    $pp = isset( $row->project_plans_data ) ? $row->project_plans_data : '';
    if ( $pp !== '' && $pp !== null ) {
        $_SESSION['fc_data']['project_plans'] = is_string( $pp ) ? $pp : json_encode( $pp );
    }

    $color = isset( $row->color_data ) ? $row->color_data : '';
    if ( $color !== '' && $color !== null ) {
        $_SESSION['fc_data']['color'] = is_string( $color ) ? $color : json_encode( $color );
    }

    foreach ( array( 'name', 'mobile', 'email', 'address', 'postcode', 'state', 'notes', 'timeframe', 'extra' ) as $col ) {
        if ( isset( $row->$col ) && $row->$col !== '' && $row->$col !== null ) {
            $val = $row->$col;
            if ( $col === 'mobile' ) {
                $val = fc_normalize_mobile_for_storage( $val );
            }
            $_SESSION['fc_data'][ $col ] = $val;
        }
    }

    $products = isset( $row->products_data ) ? $row->products_data : '';
    if ( $products !== '' && $products !== null ) {
        $decoded_products = is_string( $products ) ? json_decode( $products, true ) : $products;
        if ( is_array( $decoded_products ) ) {
            $_SESSION['custom_fence_products'] = $decoded_products;
        }
    }
}

/**
 * Build `project-plans` localStorage JSON from the active PHP session (planner ← project-plan sync).
 */
function fc_planner_client_project_plans_from_session(): string {
    $fc = isset( $_SESSION['fc_data'] ) && is_array( $_SESSION['fc_data'] ) ? $_SESSION['fc_data'] : array();
    $pp   = array();

    if ( ! empty( $fc['project_plans'] ) ) {
        $raw     = $fc['project_plans'];
        $decoded = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );
        if ( is_array( $decoded ) ) {
            $pp = $decoded;
        }
    }

    foreach ( array( 'name', 'mobile', 'email', 'address', 'postcode', 'state', 'notes', 'timeframe' ) as $k ) {
        if ( array_key_exists( $k, $fc ) && $fc[ $k ] !== null && $fc[ $k ] !== '' ) {
            $pp[ $k ] = ( $k === 'mobile' )
                ? fc_normalize_mobile_for_storage( $fc[ $k ] )
                : $fc[ $k ];
        }
    }

    if ( ! empty( $fc['nothing_extra'] ) ) {
        $pp['nothing_extra'] = $fc['nothing_extra'];
    } elseif ( ! empty( $fc['extra'] ) ) {
        $extra = $fc['extra'];
        if ( is_array( $extra ) ) {
            $pp['extra'] = $extra;
        } elseif ( is_string( $extra ) ) {
            $trimmed = trim( $extra );
            if ( $trimmed === 'nothing' ) {
                $pp['nothing_extra'] = 'nothing';
            } else {
                $decoded_extra = json_decode( $extra, true );
                $pp['extra']   = is_array( $decoded_extra ) ? $decoded_extra : $extra;
            }
        } else {
            $pp['extra'] = $extra;
        }
    }

    if ( ! empty( $pp['nothing_extra'] ) ) {
        unset( $pp['extra'] );
    }

    if ( ! empty( $fc['color'] ) ) {
        $color  = $fc['color'];
        $colors = is_array( $color ) ? $color : json_decode( (string) $color, true );
        if ( is_array( $colors ) ) {
            $pp['color'] = $colors;
        }
    }

    return json_encode( $pp, JSON_UNESCAPED_UNICODE );
}

function clear_planner_sessions() {
    $sessions = [
        'fc_data',
        'custom_fence_products',
        'fc_cart',
        'planner_id',
        'site'
    ];

    foreach ( $sessions as $session ) {
        unset($_SESSION[$session]);  
    }
}

//----------------------------------------------------------------------------------

function load_file($file) {
    return base_url($file).'?v='.filemtime(realpath($file));
}

/**
 * Load stylesheet without blocking first paint (noscript fallback included).
 */
function fc_defer_stylesheet( $href, $crossorigin = false ) {
    $href = htmlspecialchars( (string) $href, ENT_QUOTES, 'UTF-8' );
    $cross = $crossorigin ? ' crossorigin="anonymous"' : '';
    echo '<link rel="stylesheet" href="' . $href . '" media="print" onload="this.media=\'all\'"' . $cross . '>' . "\n";
    echo '<noscript><link rel="stylesheet" href="' . $href . '"' . $cross . '></noscript>' . "\n";
}

/**
 * Current entry script filename (e.g. planner.php).
 */
function fc_page_script() {
    return basename( (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ) );
}

//----------------------------------------------------------------------------------

function minifiy_css($file ='') {
    if( !file_exists($file) ) return FALSE;

    $css = file_get_contents( $file );

    $css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
    $css = preg_replace('/\s{2,}/', ' ', $css);
    $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
    $css = preg_replace('/;}/', '}', $css);

    $min_file = str_replace('.css', '.min.css', $file);

    file_put_contents($min_file, $css);
}

//----------------------------------------------------------------------------------

function config($val = '') {    
    include dirname(__FILE__, 2).'/config.php';
    return json_decode(json_encode($config));
}

//----------------------------------------------------------------------------------

