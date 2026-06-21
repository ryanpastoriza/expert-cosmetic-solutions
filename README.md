# Expert Cosmetic Solutions — Hello Elementor Child Theme

WordPress child theme for [expertcosmeticsolutions.com.au](https://expertcosmeticsolutions.com.au).

**Parent theme:** Hello Elementor

## Included fixes (CODE channel)

| ID | Description |
|----|-------------|
| P4-01 | GTM container scaffold (inactive until `ECS_GTM_ID` in wp-config) |
| P4-04 | Conversion tracking (`assets/js/ecs-tracking.js`) |
| P3-03 | Lazy-load below-fold images only (output buffer) |
| SEO-01 | Locale `en_AU` |
| SEO-06 | Instagram embed locale |
| SEO-07 | FAQPage JSON-LD consolidation (PERF-02 output buffer) |
| PERF-04 | Google Fonts non-blocking |
| UX-04 | Hero button layout (375–767px) |
| UX-05 | Instagram feed 3-col mobile |

## Install

1. Upload to `wp-content/themes/hello-elementor-child/`
2. Activate in WordPress admin
3. Optional: define `ECS_GTM_ID` in `wp-config.php` when GTM container is ready

## Local evidence workspace

`/ecs-evidence/` is gitignored (checklists, audit docs, dev tools — not deployed).
