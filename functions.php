<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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

    wp_enqueue_style(
        'ecs-child-style',
        get_stylesheet_uri(),
        [ 'hello-elementor-style' ],
        '1.0.0'
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

// --- SEO-01: Fix og:locale -> en_AU ------------------------------------------------

add_filter( 'locale', 'ecs_fix_locale' );
function ecs_fix_locale( $locale ) {
    if ( is_admin() ) {
        return $locale;
    }
    return 'en_AU';
}

add_filter( 'rank_math/opengraph/output/locale', function() {
    return 'en_AU';
} );

// --- PERF-04: Load Google Fonts non-blocking --------------------------------------
// Retire once Autoptimize or OMGF handles fonts (P3-04).

add_action( 'wp_head', 'ecs_google_fonts_preconnect', 1 );
function ecs_google_fonts_preconnect() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}

add_filter( 'style_loader_tag', 'ecs_async_google_fonts', 10, 4 );
function ecs_async_google_fonts( $html, $handle, $href, $media ) {
    if ( strpos( $href, 'fonts.googleapis.com' ) === false ) {
        return $html;
    }

    $async = str_replace(
        "rel='stylesheet'",
        "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"",
        $html
    );

    return $async . '<noscript>' . $html . '</noscript>';
}

// --- P3-03 / PERF-05: Lazy-load below-fold images only -----------------------------
// Logos (1-2): eager. LCP hero (3): eager + fetchpriority high. Rest: lazy.

function ecs_strip_image_loading_attrs( $html ) {
    while ( preg_match( '/\sloading=(["\']).*?\1/i', $html ) ) {
        $html = preg_replace( '/\sloading=(["\']).*?\1/i', '', $html, 1 );
    }

    while ( preg_match( '/\sfetchpriority=(["\']).*?\1/i', $html ) ) {
        $html = preg_replace( '/\sfetchpriority=(["\']).*?\1/i', '', $html, 1 );
    }

    return $html;
}

function ecs_apply_image_loading_attrs( $html, $position ) {
    $html = ecs_strip_image_loading_attrs( $html );

    if ( $position <= 2 ) {
        return str_replace( '<img ', '<img loading="eager" ', $html );
    }

    if ( 3 === $position ) {
        return str_replace(
            '<img ',
            '<img loading="eager" fetchpriority="high" ',
            $html
        );
    }

    return str_replace( '<img ', '<img loading="lazy" ', $html );
}

function ecs_optimize_final_html_images( $html ) {
    if ( false === strpos( $html, '<img' ) ) {
        return $html;
    }

    $index = 0;

    return preg_replace_callback(
        '/<img\b[^>]*>/i',
        function ( $matches ) use ( &$index ) {
            $index++;
            return ecs_apply_image_loading_attrs( $matches[0], $index );
        },
        $html
    );
}

add_action( 'template_redirect', 'ecs_start_image_output_buffer', 0 );
function ecs_start_image_output_buffer() {
    if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() ) {
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