=== SEV Rewrite-Free WebP for W3TC ===
Contributors: hfranz
Tags: webp, images, w3-total-cache, performance, optimization
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replaces image URLs with WebP versions when supported and available through W3 Total Cache ImageService.

== Description ==

SEV Rewrite-Free WebP for W3TC is a lightweight companion plugin for [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/) that automatically serves WebP images to browsers that support them.

This is an independent, unofficial add-on and is not affiliated with, endorsed by, or sponsored by BoldGrid / W3 EDGE, the makers of W3 Total Cache. "W3 Total Cache" is a trademark of its respective owner and is used here only to describe compatibility.

**How it works:**

1. When a visitor requests a page, the plugin checks the HTTP `Accept` header to determine whether the browser supports WebP.
2. For each image URL found in the post content (`src`, `data-src`, `srcset`, `data-srcset`), it checks whether W3TC ImageService has already converted that image to WebP.
3. If yes, the image URL in the HTML is rewritten to point to the `.webp` file instead.
4. A `Vary: Accept` response header is sent so that CDNs and reverse proxies cache WebP and non-WebP versions separately.
5. The W3TC page cache key is extended with `:webp` / `:no-webp` so that W3TC itself also caches both variants independently.

**Requirements:**

* W3 Total Cache must be installed and active.
* Images must have been converted via the W3TC ImageService (Media → W3TC Image Service).

== Installation ==

1. Upload the `sev-rewrite-free-webp-for-w3tc` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure W3 Total Cache is installed and that images have been converted via W3TC ImageService.

== Frequently Asked Questions ==

= Does this plugin convert images to WebP? =

No. Conversion is handled entirely by W3 Total Cache ImageService. This plugin only rewrites URLs in the page HTML for browsers that already support WebP.

= Will non-WebP browsers be affected? =

No. The plugin only rewrites URLs when the browser sends an `Accept` header that includes `image/webp`.

= Does it work with lazy-loading plugins? =

Yes. The plugin replaces both `src` / `srcset` and `data-src` / `data-srcset` attributes.

== Changelog ==

= 2.0.0 =
* Rename plugin to "SEV Rewrite-Free WebP for W3TC" to avoid confusion with W3 Total Cache plugin.
* Add translation support and German translation.

= 1.0.0 =
* Initial release.