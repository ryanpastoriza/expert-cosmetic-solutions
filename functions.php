<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// --- Preview tunnel: noindex while cloud link is active ----------------------------

add_action( 'wp_head', 'ecs_preview_noindex', 0 );
function ecs_preview_noindex() {
	if ( ! defined( 'ECS_PREVIEW_TUNNEL' ) || ! ECS_PREVIEW_TUNNEL ) {
		return;
	}

	echo '<meta name="robots" content="noindex, nofollow">' . "\n";
}

add_filter( 'robots_txt', 'ecs_preview_robots_txt', 99, 2 );
function ecs_preview_robots_txt( $output, $public ) {
	if ( defined( 'ECS_PREVIEW_TUNNEL' ) && ECS_PREVIEW_TUNNEL ) {
		return "User-agent: *\nDisallow: /\n";
	}

	return $output;
}

// --- Enqueue parent + child styles ------------------------------------------------

add_action( 'wp_enqueue_scripts', 'ecs_enqueue_styles' );
function ecs_enqueue_styles() {
    $parent_version = wp_get_theme( 'hello-elementor' )->get( 'Version' );

    wp_enqueue_style(
        'hello-elementor-style',
        get_template_directory_uri() . '/style.css',
        [],
        $parent_version
    );

    $child = get_stylesheet_directory() . '/style.css';

    wp_enqueue_style(
        'ecs-child-style',
        get_stylesheet_uri(),
        [ 'hello-elementor-style' ],
        file_exists( $child ) ? (string) filemtime( $child ) : '1.0.0'
    );
}

// --- P4-04: Conversion tracking script --------------------------------------------

add_action( 'wp_enqueue_scripts', 'ecs_enqueue_tracking' );
function ecs_enqueue_tracking() {
    $path = get_stylesheet_directory() . '/assets/js/ecs-tracking.js';

    if ( ! file_exists( $path ) ) {
        return;
    }

    wp_enqueue_script(
        'ecs-tracking',
        get_stylesheet_directory_uri() . '/assets/js/ecs-tracking.js',
        [],
        (string) filemtime( $path ),
        true
    );
}

// --- S1-14: Google Consent Mode v2 defaults ----------------------------------------
// Fires at priority 0 (before GTM at priority 1).
// Sets all consent states to 'denied' by default so analytics/ads fire only after
// the user accepts via CookieYes. CookieYes updates these on accept/decline when
// Consent Mode is enabled in: CookieYes → Settings → Integrations → Google Consent Mode.
// Reference: https://developers.google.com/tag-platform/security/guides/consent

add_action( 'wp_head', 'ecs_consent_mode_defaults', 0 );
function ecs_consent_mode_defaults() {
    if ( ! ecs_gtm_is_configured() && ! ecs_meta_pixel_is_configured() ) {
        return;
    }

    ?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
    'analytics_storage':  'denied',
    'ad_storage':         'denied',
    'ad_user_data':       'denied',
    'ad_personalization': 'denied',
    'wait_for_update':    500
});
gtag('set', 'url_passthrough', true);
</script>
    <?php
}

// --- P4-01: Google Tag Manager (inactive until ECS_GTM_ID is set) -----------------

function ecs_gtm_id() {
    $id = defined( 'ECS_GTM_ID' ) ? ECS_GTM_ID : 'GTM-XXXXXXX';
    return apply_filters( 'ecs_gtm_id', $id );
}

function ecs_gtm_is_configured() {
    $id = ecs_gtm_id();
    return $id && 'GTM-XXXXXXX' !== $id && preg_match( '/^GTM-[A-Z0-9]+$/', $id );
}

add_action( 'wp_head', 'ecs_gtm_head', 1 );
function ecs_gtm_head() {
    if ( ! ecs_gtm_is_configured() ) {
        return;
    }

    $id = ecs_gtm_id();
    ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $id ); ?>');</script>
<!-- End Google Tag Manager -->
    <?php
}

add_action( 'wp_body_open', 'ecs_gtm_body', 1 );
function ecs_gtm_body() {
    if ( ! ecs_gtm_is_configured() ) {
        return;
    }

    $id = esc_attr( ecs_gtm_id() );
    ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $id; ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <?php
}

// --- CRO-01: Meta (Facebook) Pixel (inactive until ECS_META_PIXEL_ID is set) -------
// Define in wp-config.php: define( 'ECS_META_PIXEL_ID', '1234567890' );
// Respects Consent Mode: only fires after ad_storage is granted via CookieYes.

function ecs_meta_pixel_id() {
    $id = defined( 'ECS_META_PIXEL_ID' ) ? ECS_META_PIXEL_ID : '';
    return apply_filters( 'ecs_meta_pixel_id', $id );
}

function ecs_meta_pixel_is_configured() {
    $id = ecs_meta_pixel_id();
    return $id && preg_match( '/^\d{8,20}$/', (string) $id );
}

add_action( 'wp_head', 'ecs_meta_pixel_head', 2 );
function ecs_meta_pixel_head() {
    if ( ! ecs_meta_pixel_is_configured() ) {
        return;
    }

    $id = esc_js( ecs_meta_pixel_id() );
    ?>
<!-- Meta Pixel -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window,document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('consent', 'revoke');
fbq('init', '<?php echo $id; ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo esc_attr( ecs_meta_pixel_id() ); ?>&ev=PageView&noscript=1"/></noscript>
<!-- End Meta Pixel -->
    <?php
}

// --- SEO-01: Fix og:locale -> en_AU ------------------------------------------------

add_filter( 'locale', 'ecs_fix_locale' );
function ecs_fix_locale( $locale ) {
    if ( is_admin() ) {
        return $locale;
    }
    return apply_filters( 'ecs_frontend_locale', 'en_AU' );
}

add_filter( 'rank_math/opengraph/output/locale', function() {
    return 'en_AU';
} );

// --- PERF-04: Local Google Fonts via OMGF (fonts self-hosted; theme preload filters retired P3-04) ---

// --- PERF-02: Serve WebP sidecars when a .webp file exists (page-cache safe) --------

function ecs_lcp_hero_id() {
	return (int) apply_filters( 'ecs_lcp_hero_id', 529 );
	// 529 = current Home (page 11) hero attachment; override via the 'ecs_lcp_hero_id' filter.
}

function ecs_lcp_hero_basename() {
	$id   = ecs_lcp_hero_id();
	$file = $id ? get_attached_file( $id ) : '';

	return $file ? pathinfo( $file, PATHINFO_FILENAME ) : '';
}

add_action( 'wp_head', 'ecs_preload_lcp_hero', 2 );
function ecs_preload_lcp_hero() {
	if ( ! is_front_page() ) {
		return;
	}

	$hero_id = ecs_lcp_hero_id();

	if ( ! $hero_id ) {
		return;
	}

	// Use imagesrcset + imagesizes so the browser picks the same source it
	// selects from the <img> srcset, avoiding a wasted download on desktop.
	$srcset = wp_get_attachment_image_srcset( $hero_id, 'large' );
	$sizes  = wp_get_attachment_image_sizes( $hero_id, 'large' );

	if ( ! $srcset || ! $sizes ) {
		// Fallback: single-URL preload for the large size.
		$hero_url = wp_get_attachment_image_url( $hero_id, 'large' );
		if ( ! $hero_url ) {
			return;
		}
		echo '<link rel="preload" as="image" href="' . esc_url( ecs_url_to_webp( $hero_url ) ) . '" fetchpriority="high">' . "\n";
		return;
	}

	// Rewrite each URL in the srcset to its .webp sidecar when available.
	$webp_srcset = preg_replace_callback(
		'/(\S+\.(?:jpe?g|png))(\s+\d+[wx])/i',
		function ( $m ) {
			return esc_url( ecs_url_to_webp( $m[1] ) ) . $m[2];
		},
		$srcset
	) ?? $srcset;

	echo '<link rel="preload" as="image" imagesrcset="' . esc_attr( $webp_srcset ) . '" imagesizes="' . esc_attr( $sizes ) . '" fetchpriority="high">' . "\n";
}

function ecs_upload_path_for_url( $url ) {
	$uploads = wp_get_upload_dir();

	if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
		return '';
	}

	$path      = wp_parse_url( $url, PHP_URL_PATH );
	$base_path = wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );

	if ( ! $path || ! $base_path || 0 !== strpos( $path, $base_path ) ) {
		return '';
	}

	$relative = substr( $path, strlen( $base_path ) );

	return wp_normalize_path( $uploads['basedir'] . $relative );
}

function ecs_url_to_webp( $url ) {
	if ( ! preg_match( '/\.(jpe?g|png)(\?.*)?$/i', $url ) ) {
		return $url;
	}

	$webp_url  = preg_replace( '/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $url );
	$webp_path = ecs_upload_path_for_url( strtok( $webp_url, '?' ) );

	if ( $webp_path && is_file( $webp_path ) ) {
		return $webp_url;
	}

	return $url;
}

function ecs_replace_img_webp_sources( $html ) {
	// Guard: preg_replace_callback returns null on PCRE error; fall back to $html.
	$result = preg_replace_callback(
		'/\ssrc=(["\'])([^"\']+)\1/i',
		function ( $matches ) {
			return ' src=' . $matches[1] . esc_url( ecs_url_to_webp( $matches[2] ) ) . $matches[1];
		},
		$html
	);
	$html = $result ?? $html;

	$result = preg_replace_callback(
		'/\ssrcset=(["\'])([^"\']+)\1/i',
		function ( $matches ) {
			$parts   = array_map( 'trim', explode( ',', $matches[2] ) );
			$updated = array();

			foreach ( $parts as $part ) {
				if ( preg_match( '/^(\S+)\s+(.+)$/', $part, $piece ) ) {
					$updated[] = esc_url( ecs_url_to_webp( $piece[1] ) ) . ' ' . $piece[2];
				} else {
					$updated[] = esc_url( ecs_url_to_webp( $part ) );
				}
			}

			return ' srcset=' . $matches[1] . implode( ', ', $updated ) . $matches[1];
		},
		$html
	);

	return $result ?? $html;
}

// --- P3-03 / PERF-05: Lazy-load below-fold images only -----------------------------
// LCP hero: eager + fetchpriority high (matched by attachment basename). Rest: lazy.

function ecs_strip_image_loading_attrs( $html ) {
	// Replace all occurrences in a single pass (no loop needed).
	$html = preg_replace( '/\sloading=(["\']).*?\1/i', '', $html ) ?? $html;
	$html = preg_replace( '/\sfetchpriority=(["\']).*?\1/i', '', $html ) ?? $html;
	return $html;
}

function ecs_apply_image_loading_attrs( $html, $is_hero ) {
    $html = ecs_strip_image_loading_attrs( $html );

    if ( $is_hero ) {
        return str_replace( '<img ', '<img loading="eager" fetchpriority="high" ', $html );
    }

    return str_replace( '<img ', '<img loading="lazy" ', $html );
}

// --- SEO-07: Consolidate duplicate FAQPage JSON-LD (PERF-02 output buffer) ---------

function ecs_schema_node_is_type( $node, $type ) {
	if ( ! is_array( $node ) || ! isset( $node['@type'] ) ) {
		return false;
	}

	$types = is_array( $node['@type'] ) ? $node['@type'] : array( $node['@type'] );

	return in_array( $type, $types, true );
}

function ecs_schema_contains_faqpage( $data ) {
	if ( ! is_array( $data ) ) {
		return false;
	}

	if ( ecs_schema_node_is_type( $data, 'FAQPage' ) ) {
		return true;
	}

	if ( ! empty( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
		foreach ( $data['@graph'] as $node ) {
			if ( ecs_schema_node_is_type( $node, 'FAQPage' ) ) {
				return true;
			}
		}
	}

	return false;
}

function ecs_extract_faq_main_entities( $data ) {
	$entities = array();

	if ( ! is_array( $data ) ) {
		return $entities;
	}

	if ( ecs_schema_node_is_type( $data, 'FAQPage' ) && ! empty( $data['mainEntity'] ) && is_array( $data['mainEntity'] ) ) {
		$entities = array_merge( $entities, $data['mainEntity'] );
	}

	if ( ! empty( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
		foreach ( $data['@graph'] as $node ) {
			if ( ecs_schema_node_is_type( $node, 'FAQPage' ) && ! empty( $node['mainEntity'] ) && is_array( $node['mainEntity'] ) ) {
				$entities = array_merge( $entities, $node['mainEntity'] );
			}
		}
	}

	return $entities;
}

function ecs_faq_question_key( $entity ) {
	if ( ! is_array( $entity ) || empty( $entity['name'] ) ) {
		return '';
	}

	return strtolower( trim( wp_strip_all_tags( (string) $entity['name'] ) ) );
}

function ecs_merge_faq_entities( array $entities ) {
	$merged = array();
	$seen   = array();

	foreach ( $entities as $entity ) {
		$key = ecs_faq_question_key( $entity );

		if ( '' !== $key && isset( $seen[ $key ] ) ) {
			continue;
		}

		if ( '' !== $key ) {
			$seen[ $key ] = true;
		}

		$merged[] = $entity;
	}

	return $merged;
}

function ecs_apply_merged_faq_entities( array $data, array $merged ) {
	if ( ecs_schema_node_is_type( $data, 'FAQPage' ) ) {
		$data['mainEntity'] = $merged;
		return $data;
	}

	if ( empty( $data['@graph'] ) || ! is_array( $data['@graph'] ) ) {
		return $data;
	}

	$found     = false;
	$new_graph = array();

	foreach ( $data['@graph'] as $node ) {
		if ( ecs_schema_node_is_type( $node, 'FAQPage' ) ) {
			if ( ! $found ) {
				$node['mainEntity'] = $merged;
				$new_graph[]        = $node;
				$found              = true;
			}
			continue;
		}

		$new_graph[] = $node;
	}

	if ( $found ) {
		$data['@graph'] = $new_graph;
	}

	return $data;
}

function ecs_build_faq_schema_script_tag( $full_script_tag, $json ) {
	if ( preg_match( '/^<script\b[^>]*>/is', $full_script_tag, $open_match ) ) {
		return $open_match[0] . $json . '</script>';
	}

	return '<script type="application/ld+json">' . $json . '</script>';
}

function ecs_consolidate_faq_schema( $html ) {
	if ( false === strpos( $html, 'FAQPage' ) ) {
		return $html;
	}

	$pattern = '/<script\b[^>]*\btype=(["\'])application\/ld\+json\1[^>]*>(.*?)<\/script>/is';

	if ( ! preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}

	$faq_blocks = array();

	foreach ( $matches as $match ) {
		$json_text = trim( $match[2][0] );
		$data      = json_decode( $json_text, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! ecs_schema_contains_faqpage( $data ) ) {
			continue;
		}

		$faq_blocks[] = array(
			'full'       => $match[0][0],
			'full_start' => $match[0][1],
			'full_len'   => strlen( $match[0][0] ),
			'data'       => $data,
		);
	}

	if ( count( $faq_blocks ) <= 1 ) {
		return $html;
	}

	$all_entities = array();

	foreach ( $faq_blocks as $block ) {
		$all_entities = array_merge( $all_entities, ecs_extract_faq_main_entities( $block['data'] ) );
	}

	$merged_entities = ecs_merge_faq_entities( $all_entities );
	$keeper          = ecs_apply_merged_faq_entities( $faq_blocks[0]['data'], $merged_entities );
	$keeper_json     = wp_json_encode( $keeper, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	if ( false === $keeper_json ) {
		return $html;
	}

	$replacement  = ecs_build_faq_schema_script_tag( $faq_blocks[0]['full'], $keeper_json );
	$replacements = array(
		array(
			'start' => $faq_blocks[0]['full_start'],
			'end'   => $faq_blocks[0]['full_start'] + $faq_blocks[0]['full_len'],
			'text'  => $replacement,
		),
	);

	for ( $i = 1, $count = count( $faq_blocks ); $i < $count; $i++ ) {
		$replacements[] = array(
			'start' => $faq_blocks[ $i ]['full_start'],
			'end'   => $faq_blocks[ $i ]['full_start'] + $faq_blocks[ $i ]['full_len'],
			'text'  => '',
		);
	}

	usort(
		$replacements,
		function ( $a, $b ) {
			return $b['start'] - $a['start'];
		}
	);

	foreach ( $replacements as $rep ) {
		$html = substr_replace( $html, $rep['text'], $rep['start'], $rep['end'] - $rep['start'] );
	}

	return $html;
}

function ecs_optimize_final_html_images( $html ) {
	// Guard: ob_start callback returning null causes PHP to send an empty body
	// (null coerces to ""), which drops the response. Always return a string.
	if ( ! is_string( $html ) ) {
		return '';
	}

	if ( function_exists( 'ecs_preview_upgrade_http_urls' ) ) {
		$html = ecs_preview_upgrade_http_urls( $html );
	}

	$html = ecs_consolidate_faq_schema( $html );

	if ( false === strpos( $html, '<img' ) ) {
		return $html;
	}

    $hero = ecs_lcp_hero_basename();

    // preg_replace_callback returns null on PCRE error — fall back to original HTML
    // so the ob_start buffer is never silently discarded.
    $result = preg_replace_callback(
        '/<img\b[^>]*>/i',
        function ( $matches ) use ( $hero ) {
            $tag     = ecs_replace_img_webp_sources( $matches[0] );
            $is_hero = ( '' !== $hero && false !== stripos( $tag, $hero ) );

            return ecs_apply_image_loading_attrs( $tag, $is_hero );
        },
        $html
    );

    return $result ?? $html;
}

add_action( 'template_redirect', 'ecs_start_image_output_buffer', 0 );
function ecs_start_image_output_buffer() {
    // Skip for admin, AJAX, JSON, feeds, and 404 pages.
    // On 404 pages the ob_start callback could fail (PCRE on large error templates)
    // and return null, which causes PHP to send an empty body → connection closed.
    // Image optimisation on 404 pages is not worth the risk.
    if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_404() ) {
        return;
    }

    ob_start( 'ecs_optimize_final_html_images' );
}

// --- SEO-06: Remove non-AU Instagram embed locale params ---------------------------

add_filter( 'embed_oembed_html', 'ecs_fix_instagram_locale', 10, 4 );
function ecs_fix_instagram_locale( $html, $url, $attr, $post_id ) {
    if ( strpos( $url, 'instagram.com' ) !== false ) {
        $html = str_replace( 'locale=en_US', 'locale=en_AU', $html );
    }
    return $html;
}
