=== SEV Rewrite-Free WebP for W3TC ===
Contributors: hfranz
Tags: webp, w3-total-cache, performance, optimization, plesk
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replaces image URLs with WebP versions when supported and available through W3 Total Cache ImageService.

== Description ==

SEV Rewrite-Free WebP for W3TC is a lightweight companion plugin for [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/) that automatically serves WebP images to browsers that support them.

Unlike traditional WebP delivery methods, this plugin does **not** rely on Apache or Nginx rewrite rules. No changes to `.htaccess`, `mod_rewrite`, or web server configuration are required. Instead, it rewrites image URLs directly in the generated HTML before the page is sent to the visitor.

This makes the plugin particularly useful on managed hosting environments, including many Plesk installations, where configuring or relying on web server rewrite rules is difficult, restricted, or simply not practical. If W3 Total Cache ImageService has already generated a WebP version of an image, this plugin serves it without requiring any server-side rewrite configuration.

This is an independent, unofficial add-on and is not affiliated with, endorsed by, or sponsored by BoldGrid / W3 EDGE, the makers of W3 Total Cache. "W3 Total Cache" is a trademark of its respective owner and is used here only to describe compatibility.

**Features**

* No Apache or Nginx rewrite rules required.
* No `.htaccess` modifications.
* Automatically detects WebP support via the browser's `Accept` header.
* Rewrites image URLs only when a corresponding WebP image exists.
* Supports `src`, `srcset`, `data-src`, and `data-srcset` attributes.
* Sends a `Vary: Accept` header for correct browser, CDN, and proxy caching.
* Extends the W3 Total Cache page cache key so WebP and non-WebP pages are cached separately.
* Lightweight and requires no configuration.

**How it works**

1. A visitor requests a page.
2. The plugin checks the HTTP `Accept` header to determine whether the browser supports WebP.
3. It scans the post content for image URLs (`src`, `srcset`, `data-src`, and `data-srcset`).
4. For each image, it checks whether W3 Total Cache ImageService has already generated a WebP version.
5. If a WebP image exists, the corresponding URL in the generated HTML is replaced with the `.webp` URL.
6. A `Vary: Accept` response header is added so browsers, CDNs, and reverse proxies cache WebP and non-WebP responses separately.
7. The W3 Total Cache page cache key is extended with `:webp` or `:no-webp`, ensuring W3 Total Cache stores separate cached pages for browsers with and without WebP support.

**Requirements**

* W3 Total Cache must be installed and active.
* Images must have been converted using **Media → W3TC Image Service**.

**Limitations**

This plugin **does not convert images** to WebP. Image conversion is handled entirely by W3 Total Cache ImageService. If no WebP version exists for an image, the original image URL remains unchanged.

== Installation ==

1. Upload the `sev-rewrite-free-webp-for-w3tc` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Ensure W3 Total Cache is installed and that images have been converted via W3TC ImageService.

== Frequently Asked Questions ==

= Does this plugin convert images to WebP? =

No. Image conversion is handled entirely by W3 Total Cache ImageService. This plugin only rewrites image URLs in the generated HTML when a WebP version already exists.

= Why is it called "Rewrite-Free"? =

Unlike traditional WebP solutions, this plugin does not require Apache or Nginx rewrite rules. No changes to `.htaccess` or web server configuration are needed. Instead, it rewrites image URLs in the generated HTML before the page is sent to the visitor.

= Does it work with multisite installations? =

Yes. The plugin supports WordPress multisite installations. Each site automatically uses its own W3 Total Cache ImageService-generated WebP images.

= Will non-WebP browsers be affected? =

No. The plugin only rewrites image URLs when the browser's `Accept` header includes `image/webp`. Other browsers continue to receive the original image formats.

= Does it work with lazy-loading plugins? =

Yes. The plugin replaces URLs in `src`, `srcset`, `data-src`, and `data-srcset` attributes, making it compatible with most lazy-loading solutions.

== Changelog ==

= 2.0.0 =
* Rename plugin to "SEV Rewrite-Free WebP for W3TC" to avoid confusion with W3 Total Cache plugin.
* Add translation support and German translation.

= 1.0.0 =
* Initial release.