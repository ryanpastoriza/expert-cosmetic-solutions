# Expert Cosmetic Solutions — Hello Elementor Child Theme

WordPress child theme for [expertcosmeticsolutions.com.au](https://expertcosmeticsolutions.com.au).

**Parent theme:** Hello Elementor

## Included fixes (CODE channel)

| ID | Description |
|----|-------------|
| P4-01 | GTM container scaffold (inactive until `ECS_GTM_ID` in wp-config) |
| P4-04 | Conversion tracking (`assets/js/ecs-tracking.js`) |
| P3-03 | Lazy-load below-fold images only (output buffer) |
| F1 | Dynamic LCP hero — filter `ecs_lcp_hero_id` (default attachment 74); hero `loading="eager"` + `fetchpriority="high"` matched by image basename, not DOM order |
| F3 | Consent Mode v2 defaults (gated until GTM or Meta Pixel configured) |
| F4 | Child style cache busting via `filemtime()` |
| F5 | Locale `en_AU` via filter `ecs_frontend_locale` |
| F6 | Booking hosts overridable via `window.ECS_BOOKING_HOSTS` |
| SEO-01 | Locale `en_AU` |
| SEO-06 | Instagram embed locale |
| SEO-07 | FAQPage JSON-LD consolidation (PERF-02 output buffer) |
| PERF-04 | Google Fonts non-blocking |
| PERF-07 | Disable WP emoji (removes s.w.org DNS prefetch + detection JS) |
| PERF-08 | Remove RSD, WLW manifest, generator, shortlink, extra feed links from `<head>` |
| PERF-09 | WPForms global assets disabled (load on demand only) |
| PERF-10 | Dequeue wp-components + block-library CSS on frontend |
| PERF-11 | Hero slideshow LCP: inject discoverable `<img>` server-side (filter `ecs_hero_slideshow_image_id`, default 74) |
| UX-04 | Hero button layout (375–767px) |
| UX-05 | Instagram feed 3-col mobile |

## Filters

### `ecs_hero_slideshow_image_id`

Returns the attachment ID for the server-side hero `<img>` injected by PERF-11. Default: `74` (homepage hero slideshow slide 1). Override when the hero image changes:

```php
add_filter( 'ecs_hero_slideshow_image_id', function () {
    return 123; // new attachment ID
} );
```

### `ecs_lcp_hero_id`

Returns the attachment ID used for basename-matching eager image loading on the front page. Default: `74`. In practice the homepage hero is a CSS background (never an `<img>`), so this basename match never fires — kept filterable for forward-compatibility. The active LCP fix is PERF-11 above.

### `ecs_frontend_locale`

Override the front-end locale string (default `en_AU`).

## WebP delivery

WebP sidecar delivery is **theme-owned** via the output buffer in `functions.php` (`ecs_replace_img_webp_sources`, LCP preload). ShortPixel must be configured **GENERATE-ONLY** — no picture-tag or `.htaccess` delivery from ShortPixel. This theme does **not** ship a `# BEGIN ECS WebP` `.htaccess` block; Apache rewrites are not required for Local/staging.

## Install

1. Upload to `wp-content/themes/hello-elementor-child/`
2. Activate in WordPress admin
3. Optional: define `ECS_GTM_ID` in `wp-config.php` when GTM container is ready

## Local evidence workspace

`/ecs-evidence/` is gitignored (checklists, audit docs, dev tools — not deployed).
