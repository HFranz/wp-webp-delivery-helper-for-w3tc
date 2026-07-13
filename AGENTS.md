# AGENTS.md

> Allgemeine WordPress-Sicherheits-/Coding-Regeln (Escaping, Nonces, WPCS, i18n, Hooks-only) sind in
> [`.github/instructions/wordpress.instructions.md`](.github/instructions/wordpress.instructions.md) definiert und
> greifen automatisch für alle Dateien in diesem Plugin (`applyTo: wp-content/plugins/**`). Dieses Dokument
> ergänzt sie um projektspezifisches Architektur- und Workflow-Wissen.

## Zweck & Architektur
Dieses WordPress-Plugin ergänzt **W3 Total Cache (W3TC)** um WebP-Auslieferung, ohne selbst zu konvertieren:

- `webp-delivery-helper-for-w3tc.php` – Bootstrap. Hookt sich nur ein, wenn `defined('W3TC')` (Laufzeit-Check statt `Requires Plugins`, wichtig für mu-plugins) via `plugins_loaded`.
- `includes/class-accept-header.php` – `Accept_Header::accepts()`: statische Utility, prüft `Accept`-Header nach RFC-7231-Spezifität (`image/webp` > `image/*` > `*/*`, `q=0` = abgelehnt).
- `includes/class-content-filter.php` – `Content_Filter::filter()`, gehookt an `the_content` (Priorität 20). Ersetzt `src`/`data-src`/`srcset`/`data-srcset` per Regex, prüft pro Bild-URL das Post-Meta `w3tc_imageservice['status'] === 'converted'` (von W3TC gesetzt) und tauscht die Dateiendung gegen `.webp`. Hat eigene, minimale `srcset`-Parser-Implementierung nach HTML-Spec (kein `explode(',', ...)`, da Kommas in Query-Strings vorkommen können).
- `includes/class-cache-handler.php` – `Cache_Handler`: sendet `Vary: Accept` (für CDN/Reverse-Proxy) via `send_headers` und erweitert den W3TC-Cache-Key um `:webp`/`:no-webp` via Filter `w3tc_pagecache_cache_key` (W3TC selbst ignoriert Vary-Header intern).

**Datenfluss:** Request → Accept-Header prüfen → `the_content` filtert URLs anhand W3TC-ImageService-Meta → Vary-Header + Cache-Key sorgen für getrennte Caches pro Client-Fähigkeit.

**Wichtig:** Das Plugin konvertiert keine Bilder selbst – reine URL-Umschreibung basierend auf bereits von W3TC erzeugten `.webp`-Dateien.

## Namespace & Konventionen
- Alle Klassen liegen im Namespace `WebPDeliveryHelperForW3TC` (kein globaler Namespace, keine Prefixe nötig).
- Jede Datei beginnt mit `if ( ! defined( 'ABSPATH' ) ) { die(); }`.
- Strikte Typisierung (`declare(strict_types=1)` in Tests), Scalar-Type-Hints und Return-Types überall in `includes/`.
- Per-Request-Caching via einfache Arrays (`$url_cache`, `$meta_cache` in `Content_Filter`) – kein Object Cache/Transients nötig, da alles innerhalb eines Requests bleibt.

## Tests (kein WP-Testsuite/wp-env!)
- `tests/bootstrap.php` definiert **eigene, minimale Stubs** für WP-Funktionen (`add_filter`, `apply_filters`, `wp_upload_dir`, `attachment_url_to_postid`, `get_post_meta`, etc.) statt der vollen WP-PHPUnit-Testsuite zu laden – lädt dann das echte Plugin und feuert `plugins_loaded`.
- Testdaten werden über die Helper-Klasse `WPTestStub` gesteuert (`WPTestStub::registerConverted()`, `::registerNotConverted()`, `::reset()` in `setUp()`/`tearDown()`).
- Ausführen: `composer test` bzw. `vendor/bin/phpunit` (kein Docker/wp-env erforderlich).
- Neue WP-Funktionsaufrufe im Plugin-Code erfordern einen passenden Stub in `tests/bootstrap.php`, sonst schlägt PHPUnit mit "undefined function" fehl.

## Weitere Dev-Workflows
- `composer lint:php` / `composer fix:php` – PHPCS/PHPCBF (WPCS).
- `composer make-pot` / `update-po` / `make-php` – i18n-Workflow via WP-CLI (`wp i18n ...`), Text-Domain `webp-delivery-helper-for-w3tc`, Sprachdateien in `languages/`.
- Keine Build-Pipeline für JS/CSS – das Plugin enthält keine Assets.
- `uninstall.php` macht bewusst nichts (keine Options/Tabellen zu bereinigen) – bei neuen Optionen dort Cleanup ergänzen.

## Beim Ändern von Code beachten
- Änderungen an `Accept_Header::accepts()` oder der Regex/Parsing-Logik in `Content_Filter` immer mit Tests in `tests/AcceptHeaderTest.php` bzw. `tests/ContentFilterTest.php` absichern (Edge Cases: `q=0`, Query-Strings mit Kommas, `.jpg`/`.jpeg`-Mismatch, Thumbnail-Suffixe `-300x300`).
- Neue Hooks/Filter nur im `plugins_loaded`-Guard in `webp-delivery-helper-for-w3tc.php` registrieren, damit sie inaktiv bleiben, wenn W3TC fehlt.

