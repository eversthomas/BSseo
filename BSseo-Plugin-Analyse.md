# BSseo – WordPress Plugin Analyse & Bewertung

**Plugin-Version:** 1.0.0  
**Autor:** Tom Evers – [bezugssysteme.de](https://bezugssysteme.de)  
**Lizenz:** GPL v3  
**Anforderungen:** WordPress ≥ 6.4 / PHP ≥ 7.4  
**Analysierte Dateien:** 30 PHP-, JS- und Konfigurationsdateien (~3.900 Zeilen Code)  
**Analysedatum:** Februar 2026  

---

## Gesamtbewertung: 8,0 / 10 – Sehr gut (produktionsreif)

| Kriterium                       | Bewertung                                | Punkte   |
|---------------------------------|------------------------------------------|----------|
| Sicherheit & Input-Validierung  | Sehr gut – alle Ausgaben escaped         | 9,0/10   |
| Code-Qualität & Struktur        | Sehr gut – WordPress-idiomatisch         | 8,5/10   |
| WordPress-Konformität           | Ausgezeichnet – Hooks, APIs, REST        | 9,5/10   |
| Funktionsumfang (v1.0)          | Gut – Kernanforderungen vollständig      | 7,5/10   |
| KI/AI-Modul                     | Sehr gut – innovativ & sicher            | 9,0/10   |
| Wartbarkeit & Erweiterbarkeit   | Gut – Filter/Actions vorhanden           | 8,0/10   |
| Fehlerbehandlung                | Gut – WP_Error, try/finally              | 7,5/10   |
| Dokumentation (Code & README)   | Sehr gut – Kommentare & Roadmap          | 8,5/10   |
| Performance                     | Gut – Lazy-Loading, Cron, Cache          | 7,5/10   |
| Admin UX                        | Gut – Sidebar JS, Counter, Modal         | 7,5/10   |

> **Empfehlung: Für Produktionseinsatz geeignet.** Mit den beschriebenen Quick-Wins (Settings-Cache, mb_strlen, Checks-Sanitierung) ist eine Gesamtbewertung von 8,5+ realistisch.

---

## Inhalt

1. [Executive Summary](#1-executive-summary)
2. [Architektur & Codestruktur](#2-architektur--codestruktur)
3. [Sicherheitsanalyse](#3-sicherheitsanalyse)
4. [Kernfunktionen im Detail](#4-kernfunktionen-im-detail)
5. [KI/LLM-Endpoint-Modul](#5-killm-endpoint-modul-ai)
6. [WordPress-Konformität](#6-wordpress-konformität)
7. [Konflikt-Management](#7-konflikt-management)
8. [Performance-Analyse](#8-performance-analyse)
9. [Offene Punkte & Roadmap](#9-offene-punkte--roadmap)
10. [Konkrete Verbesserungsempfehlungen](#10-konkrete-verbesserungsempfehlungen)
11. [Gesamtfazit](#11-gesamtfazit)

---

## 1. Executive Summary

BSseo ist ein schlankes, fokussiertes WordPress-SEO-Plugin als bewusste Alternative zu Yoast SEO oder RankMath. Es verfolgt das Designprinzip „so wenig wie nötig, so viel wie sinnvoll" und setzt dabei konsequent auf WordPress-idiomatische Techniken.

**Stärken:** Sicherheitsarchitektur, WordPress-Konformität, KI-Endpoint-Modul, saubere Code-Organisation und ein durchdachtes Fallback-System.

**Verbesserungspotenzial:** Noch fehlende Features laut eigener Roadmap (Vorlagen, Bulk-Actions, Test-Harness), Fokus-Keyword-Analyse und marginale Sicherheitsdetails.

Die offene Roadmap in `OFFEN-UND-PRUEFEN.md` zeigt einen reifen Entwicklungsprozess: Was noch fehlt, ist transparent dokumentiert und priorisiert. Die für v1.0 wichtigen Features sind vollständig implementiert.

---

## 2. Architektur & Codestruktur

### 2.1 Verzeichnisstruktur

```
BSseo/
├── bsseo.php                     # Loader, Activation-Hooks, Textdomain, bedingtes Einbinden
├── core/
│   ├── conflict-guard.php        # Erkennung anderer SEO-Plugins (statisches Caching)
│   ├── meta-manager.php          # Title, Robots, Canonical, Description, OG/Twitter
│   ├── schema-generator.php      # JSON-LD @graph (WebSite, WebPage, BlogPosting, Org, Person)
│   ├── sitemap-enhancer.php      # lastmod, noindex-Ausschluss via wp_sitemaps_*-Filter
│   └── content-analyzer.php     # SEO- & KI-Score, AJAX, WP-Cron, DOMDocument-Parsing
├── admin/
│   ├── settings-page.php         # Settings API, Sanitize-Callbacks, UI
│   ├── metabox.php               # Classic-Editor-Metabox + Speicher-Handler
│   ├── list-columns.php          # Spalte "SEO / KI" in Post-Listen
│   ├── dashboard-widget.php      # Dashboard-Übersicht
│   └── help-texts.php            # Zentrale Label/Tooltip-Texte
├── includes/
│   ├── defaults.php              # Standardwerte, bsseo_get_settings() mit 3-Ebenen-Merge
│   ├── helpers.php               # bsseo_should_output_head(), Unicode-Wortzählung, normalize
│   ├── templates.php             # %title%, %sitename%, %sep%, %excerpt% Platzhalter
│   └── register-meta.php        # register_post_meta() für alle öffentlichen Post-Types
├── ai/
│   ├── class-bsseo-ai-module.php     # Feature-Flag, Bootstrap (lädt nur wenn enabled)
│   ├── class-bsseo-ai-routes.php     # REST-Routen: meta, page, feed, llms
│   ├── class-bsseo-ai-data-builder.php # Zentrale Datenlogik für alle Endpoints
│   ├── class-bsseo-ai-cache.php      # Transient-Cache-Wrapper
│   └── class-bsseo-ai-pretty-urls.php # Optionale Pretty URLs (/ai/meta etc.)
├── assets/admin/
│   ├── admin.js                  # jQuery: Counter, Title-Preview, AJAX-Analyse, Modals
│   ├── admin.css                 # Admin-Styles
│   └── bsseo-sidebar.js          # Gutenberg-Sidebar-Panel
├── languages/bsseo.pot           # Vollständige Übersetzungsbasis
└── tests/                        # HTML-Fixtures für manuelle Head-Vergleiche
```

### 2.2 Lade-Strategie

Der Loader in `bsseo.php` trennt sauber zwischen Admin- und Frontend-Code:

```php
// Admin: nur im Backend
if ( is_admin() ) {
    require_once BSSEO_PATH . 'admin/help-texts.php';
    require_once BSSEO_PATH . 'admin/settings-page.php';
    // ...
}

// Frontend: Meta Manager, Schema, Sitemap
if ( ! is_admin() ) {
    require_once BSSEO_PATH . 'includes/templates.php';
    require_once BSSEO_PATH . 'core/meta-manager.php';
    // ...
}

// AI-Modul: nur wenn Feature-Flag aktiv
add_action( 'init', array( 'BSseo_AI_Module', 'bootstrap' ), 10 );
// bootstrap() prüft intern: if ( ! self::is_enabled() ) { return; }
```

**Bewertung:** Die bedingte Ladelogik verhindert unnötigen Overhead. Das Feature-Flag-Pattern für das AI-Modul ist vorbildlich – kein einziger Byte Code wird geladen, wenn das Modul deaktiviert ist.

**Ausnahme:** `content-analyzer.php` wird immer geladen (auch im Frontend), weil es sowohl AJAX- als auch Cron-Callbacks registriert. Das ist korrekt, aber der `bsseo_run_analysis()`-Aufruf im Frontend wird durch Capability-Checks und das explizite `is_admin()`-Gate im AJAX-Handler verhindert.

---

## 3. Sicherheitsanalyse

### 3.1 Ausgabe-Escaping

Alle Frontend-Ausgaben nutzen die korrekten WordPress-Escape-Funktionen – keine direkten `echo`-Ausgaben ohne Escaping gefunden:

| Kontext            | Funktion            | Beispiel                                         |
|--------------------|---------------------|--------------------------------------------------|
| HTML-Text          | `esc_html()`        | Labels, Fehlermeldungen                          |
| HTML-Attribute     | `esc_attr()`        | `content="..."` in Meta-Tags                     |
| URLs in HTML       | `esc_url()`         | `href`, `src`, OG-Image-URL                      |
| URLs im Speicher   | `esc_url_raw()`     | Canonical, Logo, Social-Profiles                 |
| JSON-LD            | `wp_json_encode()`  | `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` |
| Textarea           | `esc_textarea()`    | Meta-Description im Metabox-Formular             |

### 3.2 Nonce-Schutz

- **Metabox-Speicherung:** `wp_nonce_field('bsseo_metabox_save', 'bsseo_metabox_nonce')` + `check_admin_referer()`
- **AJAX-Handler:** `check_ajax_referer('bsseo_analyze', 'bsseo_nonce')`
- **Einstellungsseite:** WordPress Settings API übernimmt CSRF-Schutz automatisch

### 3.3 Capability-Checks

```php
// Settings-Seite
if ( ! current_user_can( 'manage_options' ) ) { return; }

// AJAX-Analyse
if ( ! current_user_can( 'edit_post', $post_id ) ) {
    wp_send_json_error( array( 'message' => __( 'Berechtigung fehlt.', 'bsseo' ) ) );
}

// REST API-Key (timing-sicher)
if ( is_string( $key ) && $key !== '' && hash_equals( (string) $ai['api_key'], $key ) ) {
    return true;
}
```

`hash_equals()` statt `==` für den API-Key-Vergleich verhindert Timing-Angriffe – das ist Best Practice für Credential-Vergleiche.

### 3.4 Input-Sanitierung

Die Settings-Sanitierung in `bsseo_sanitize_settings()` ist umfassend:

```php
$out['separator']           = sanitize_text_field( $input['separator'] );
$out['home_description']    = sanitize_textarea_field( $input['home_description'] );
$out['schema_org']['logo']  = esc_url_raw( $input['schema_org']['logo'] );

// Enum-Whitelist
$content_level = sanitize_key( $input['ai']['content_level'] );
$out['ai']['content_level'] = in_array( $content_level,
    array( 'metadata_only', 'include_excerpt', 'include_trimmed_content' ), true )
    ? $content_level : $ai_defaults['content_level'];

// Numerische Bereichsvalidierung
$out['ai']['feed_limit_max'] = max( 10, min( 500, absint( $input['ai']['feed_limit_max'] ) ) );
```

### 3.5 Schwachstellen (minor)

> ⚠️ **`bsseo_sanitize_checks()`** gibt das Checks-Array ohne weitere Validierung zurück (`return $value`). Da dieser Wert intern generiert und nur im Admin-Backend angezeigt wird, ist das Risiko gering – sollte aber dennoch item-weise bereinigt werden.

```php
// Aktuell (unsicher):
function bsseo_sanitize_checks( $value ) {
    if ( ! is_array( $value ) ) { return array(); }
    return $value; // ← kein Item-Validierung
}

// Empfehlung:
function bsseo_sanitize_checks( $value ) {
    if ( ! is_array( $value ) ) { return array(); }
    $out = array();
    foreach ( $value as $item ) {
        if ( ! is_array( $item ) ) { continue; }
        $out[] = array(
            'id'      => sanitize_key( $item['id'] ?? '' ),
            'status'  => sanitize_key( $item['status'] ?? '' ),
            'label'   => sanitize_text_field( $item['label'] ?? '' ),
            'message' => sanitize_text_field( $item['message'] ?? '' ),
        );
    }
    return $out;
}
```

---

## 4. Kernfunktionen im Detail

### 4.1 Meta-Manager (`core/meta-manager.php`)

Der Meta-Manager steuert alle Head-Ausgaben. Die Implementierung ist durchdacht:

**Title-Tag:**
```php
// Entfernt Core-Title-Tag und gibt eigenen aus – robust gegen Themes die ihn entfernen
function bsseo_ensure_title_tag() {
    remove_action( 'wp_head', '_wp_render_title_tag', 1 );
    $title = wp_get_document_title();
    if ( $title !== '' ) {
        echo '<title>' . esc_html( $title ) . "\n";
    }
}
add_action( 'wp_head', 'bsseo_ensure_title_tag', 0 );
```

**Robots-Meta:** Nutzt `wp_robots`-Filter statt direkter Ausgabe – konform mit WordPress-Core. Unterstützt `BSSEO_NOINDEX_ENV`-Konstante für Staging-Umgebungen.

**Canonical:** Doppelter Schutz – Core-`rel_canonical` wird entfernt wenn Custom-Canonical gesetzt ist, eigener `<link>`-Tag wird ausgegeben. Zusätzlich `get_canonical_url`-Filter für Drittcode.

**OG/Twitter:** Vollständige Implementierung:
- `og:type`, `og:url`, `og:site_name`, `og:locale`, `og:title`, `og:description`, `og:image`
- `article:published_time`, `article:modified_time` für Post-Typen
- `twitter:card` (summary_large_image), `twitter:title`, `twitter:description`, `twitter:image`

**Hook-Prioritäten** (korrekt gestaffelt):

| Priorität | Aktion                     |
|-----------|----------------------------|
| 0         | Title-Tag (vor allem)      |
| 1         | Canonical                  |
| 5         | Schema.org JSON-LD         |
| 8         | Meta-Description           |
| 9         | OG/Twitter                 |
| 99        | Debug-Kommentar (nur DEBUG)|

### 4.2 Schema-Generator (`core/schema-generator.php`)

Das JSON-LD `@graph` ist nach aktuellem Stand der Technik implementiert:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    { "@type": "WebSite",    "@id": "https://example.com/#website" },
    { "@type": "Organization","@id": "https://example.com/#organization" },
    { "@type": "Person",     "@id": "https://example.com/author/tom/#person" },
    { "@type": "ImageObject","@id": "https://example.com/img/hero.jpg" },
    { "@type": "WebPage",    "@id": "https://example.com/post/#webpage",
      "isPartOf": { "@id": "https://example.com/#website" },
      "primaryImageOfPage": { "@id": "https://example.com/img/hero.jpg" }
    },
    { "@type": "BlogPosting","@id": "https://example.com/post/#article",
      "mainEntityOfPage": { "@id": "https://example.com/post/#webpage" },
      "image": { "@id": "https://example.com/img/hero.jpg" },
      "citation": [{ "@type": "CreativeWork", "url": "...", "name": "..." }]
    }
  ]
}
```

**Highlights:**
- Saubere `@id`-Referenzen zwischen allen Knoten – kein redundantes Duplizieren von Daten
- `ImageObject` mit `width`/`height` aus WordPress-Bildmetadaten
- **Citation-Support** via `_bsseo_sources` – für ein schlankes SEO-Plugin einzigartig
- `inLanguage` als BCP 47 (`de_DE` → `de-DE`)
- Filter `bsseo_schema_data`, `bsseo_schema_webpage`, `bsseo_schema_article` für Dritterweiterungen

> ⚠️ **Hinweis:** Der `_bsseo_schema_type`-Wert aus der Metabox (Article, BlogPosting, WebPage, FAQPage, HowTo) wird im Schema-Generator noch nicht ausgewertet. Das Feld ist sichtbar in der UI, hat aber noch keinen Effekt.

### 4.3 Content-Analyzer (`core/content-analyzer.php`)

Der Content-Analyzer berechnet zwei Score-Dimensionen mittels `DOMDocument`-Parsing:

**SEO-Score (max. 8 Punkte):**

| Check                      | Punkte | Kriterium                                    |
|----------------------------|--------|----------------------------------------------|
| Titellänge                 | 2      | 30–70 Zeichen                                |
| Meta-Description-Länge     | 2      | 120–160 Zeichen                              |
| H1-Überschrift             | 2      | Genau eine H1 im Content                     |
| Bilder mit Alt-Text + Links| 2      | Alle `<img>` mit `alt`, mindestens 1 `<a>`   |

**KI/Struktur-Score (max. 8 Punkte):**

| Check                      | Punkte | Kriterium                                    |
|----------------------------|--------|----------------------------------------------|
| Überschriften-Hierarchie   | 2      | Eine H1, keine Level-Sprünge (z.B. H1 → H3) |
| Listen / Tabellen          | 2      | Mind. 1 `<ul>`, `<ol>` oder `<table>`        |
| Wortanzahl                 | 2      | ≥ 300 Wörter (Unicode-safe)                  |
| Lesbarkeit                 | 2      | Ø ≤ 80 Wörter pro Absatz                    |

**Schutzmechanismen:**
```php
function bsseo_run_analysis( $post_id ) {
    static $running = false;              // Reentranz-Schutz
    if ( $running ) { return new WP_Error(...); }
    $running = true;

    try {
        // Limits: max 120.000 Zeichen, 8.000 DOM-Knoten, 2,5s Timeout
        // apply_filters('the_content') NUR hier, nie im Frontend
        // DOMDocument mit libxml_use_internal_errors(true)
    } finally {
        $running = false;                 // Reset garantiert durch finally
    }
}
```

> ⚠️ **Schwachstelle:** `strlen()` statt `mb_strlen()` für Title/Description-Längenprüfung. Bei Umlauten (ä, ö, ü, ß) oder anderen Multibyte-Zeichen werden Byte-Längen statt Zeichen-Längen verglichen → falsche Score-Ergebnisse.

```php
// Aktuell (fehlerhaft bei Umlauten):
if ( strlen( $title_used ) >= 30 && strlen( $title_used ) <= 70 ) { ... }

// Korrekt:
if ( mb_strlen( $title_used ) >= 30 && mb_strlen( $title_used ) <= 70 ) { ... }
```

### 4.4 Sitemap-Enhancer (`core/sitemap-enhancer.php`)

Erweitert die WordPress-Core-Sitemap ohne eigene XML-Generierung:

```php
// lastmod aus post_modified_gmt
add_filter( 'wp_sitemaps_posts_entry', 'bsseo_sitemap_posts_entry', 10, 3 );

// noindex-Beiträge ausschließen via meta_query
add_filter( 'wp_sitemaps_posts_query_args', 'bsseo_sitemap_posts_query_args', 10, 2 );
```

Der Ausschluss von `noindex`-Posts via `meta_query` ist korrekt implementiert mit `NOT EXISTS OR != 1`-Logik. Gut: `update_post_meta_cache: true` ist gesetzt, damit der Join greift.

### 4.5 Template-Parser (`includes/templates.php`)

```php
// Unterstützte Platzhalter: %title%, %sitename%, %sep%, %excerpt%
function bsseo_parse_template( $template, array $replacements ) {
    $out = $template;
    foreach ( $replacements as $key => $value ) {
        $out = str_replace( '%' . $key . '%', (string) $value, $out );
    }
    // Verbleibende unbekannte Platzhalter entfernen
    $out = preg_replace( '/%[a-z_]+%/i', '', $out );
    return trim( preg_replace( '/\s+/', ' ', $out ) );
}
```

Solide Basis – ist auf Vorlagen pro Post-Type/Kategorie (v1.1-Roadmap) ausgelegt.

---

## 5. KI/LLM-Endpoint-Modul (`ai/`)

Das AI-Modul ist das innovativste Element des Plugins. Es bietet maschinenlesbare JSON-Endpoints für KI-Crawler und LLM-Systeme – für ein WordPress-Plugin 2026 wegweisend.

### 5.1 Endpoints

| Endpoint                  | Methode | Beschreibung                                                |
|---------------------------|---------|-------------------------------------------------------------|
| `/bsseo/v1/ai/meta`       | GET     | Site-weite Metadaten, Capabilities, Endpoint-URLs           |
| `/bsseo/v1/ai/page`       | GET     | Post-Daten per `id`, `slug`, `path` oder `url`              |
| `/bsseo/v1/ai/feed`       | GET     | Post-Liste mit Paginierung, Sortierung, Post-Type-Filter    |
| `/bsseo/v1/ai/llms`       | GET     | `llms.txt`-ähnliches Textformat für LLM-Crawler             |

Optionale Pretty URLs: `/ai/meta`, `/ai/page`, `/ai/feed`, `/ai/llms` (via Rewrite Rules).

### 5.2 Sicherheit & Access Control

```php
public static function permission_check( $request ) {
    $settings = bsseo_get_settings();
    $ai = $settings['ai'];

    // Öffentlicher Zugriff wenn kein API-Key konfiguriert
    if ( empty( $ai['require_api_key'] ) || empty( $ai['api_key'] ) ) {
        return true;
    }

    // API-Key aus Header oder Query-Parameter
    $key = $request->get_header( 'X-BSSEO-KEY' )
        ?? $request->get_param( 'api_key' );

    // Timing-sicherer Vergleich
    if ( is_string( $key ) && hash_equals( $ai['api_key'], $key ) ) {
        return true;
    }

    return new WP_Error( 'rest_forbidden', '...', array( 'status' => 401 ) );
}
```

**Datenschutz-Mechanismen:**
- Noindex-Posts werden nicht ausgeliefert (`respect_noindex`, konfigurierbar)
- Passwortgeschützte Posts werden **immer** ausgeschlossen (`post_password`)
- Domain-Validierung beim URL-Lookup verhindert Cross-Domain-Abfragen
- Whitelist für `orderby` (`modified`, `date`, `title`) und `order` (`asc`, `desc`)
- Post-Type-Whitelist (nur in Settings konfigurierte Types werden ausgeliefert)

### 5.3 Caching

```php
class BSseo_AI_Cache {
    const PREFIX = 'bsseo_ai_';

    // Cache-Key: md5 über Route + sortierte Parameter → stabil, kollisionsarm
    public static function build_key( $route, $params = array() ) {
        ksort( $params );
        // ...
        return md5( implode( '|', $parts ) );
    }

    // TTL-Bereich: 60s–1 Woche, Standard: 6 Stunden
    public static function get_ttl() { ... }
}
```

**Debug-Headers** (optional aktivierbar):
- `X-BSSEO-Route: ai/page`
- `X-BSSEO-Cache: HIT` oder `MISS`

### 5.4 DataBuilder – Sicherheitsrelevante Details

```php
// URL-Lookup: Domain-Validierung
$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
$url_host  = wp_parse_url( $url, PHP_URL_HOST );
if ( strtolower( $home_host ) === strtolower( $url_host ) ) {
    $found_id = url_to_postid( $url );
    // ...
}

// Inhaltstiefe konfigurierbar: metadata_only | include_excerpt | include_trimmed_content
// Maximale Zeichen bei trimmed content: 2.000
const TRIM_CONTENT_CHARS = 2000;
```

---

## 6. WordPress-Konformität

### 6.1 Hook-Architektur

BSseo nutzt konsequent WordPress-eigene APIs und vermeidet Direct Output wo immer möglich:

| WordPress-API                     | Verwendung in BSseo                                  |
|-----------------------------------|------------------------------------------------------|
| `wp_robots`-Filter                | Robots-Meta statt direkter Ausgabe                   |
| `pre_get_document_title`-Filter   | Title-Manipulation                                   |
| `wp_head`-Aktion                  | Alle Head-Ausgaben mit gestaffelten Prioritäten      |
| `wp_sitemaps_posts_entry`-Filter  | lastmod-Ergänzung in Core-Sitemap                    |
| `wp_sitemaps_posts_query_args`    | noindex-Ausschluss in Core-Sitemap                   |
| `register_post_meta()`            | Alle Meta-Keys mit REST-Schema                       |
| `register_setting()` + Settings API | Admin-Einstellungen mit sanitize_callback          |
| WP-REST-API                       | AI-Endpoints via `register_rest_route()`             |
| WP-Cron                           | Asynchrone Score-Berechnung                          |

### 6.2 Block-Editor (Gutenberg) Support

```javascript
// bsseo-sidebar.js registriert ein Seitenleisten-Panel
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/edit-post';
// Felder nutzen WP-eigene Komponenten und lesen/schreiben Post-Meta via REST
```

**Voraussetzung:** `register_post_meta()` mit `show_in_rest: true` für alle Meta-Keys. Die `auth_callback`-Implementierung ist besonders durchdacht:

```php
'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
    // Nicht von $allowed abhängen – Gutenberg speichert Meta über REST;
    // wenn Core $allowed hier false setzt, entsteht sonst 403.
    return current_user_can( 'edit_post', $post_id );
},
```

Der Kommentar erklärt die Entscheidung – das zeigt Expertise mit Gutenberg-Eigenheiten.

### 6.3 Settings-Merge (3-Ebenen)

```php
function bsseo_get_settings() {
    $saved    = get_option( 'bsseo_settings', array() );
    $defaults = bsseo_get_default_settings();

    $out = array_merge( $defaults, $saved );

    // Verschachtelte Arrays manuell mergen (array_merge überschreibt komplett)
    $out['toggles']         = array_merge( $defaults['toggles'],         $out['toggles'] ?? array() );
    $out['schema_org']      = array_merge( $defaults['schema_org'],      $out['schema_org'] ?? array() );
    $out['analysis_limits'] = array_merge( $defaults['analysis_limits'], $out['analysis_limits'] ?? array() );
    $out['ai']              = array_merge( $defaults['ai'],              $out['ai'] ?? array() );

    return $out;
}
```

Neue Defaults bei Plugin-Updates werden automatisch für bestehende Installationen übernommen – keine Migrations-Skripte nötig.

---

## 7. Konflikt-Management

Der `conflict-guard.php` erkennt bekannte SEO-Plugins und verhindert doppelte Meta-Tags:

```php
function bsseo_has_seo_conflict() {
    static $conflict = null;
    if ( $conflict !== null ) { return $conflict; } // statisches Caching

    $conflict = class_exists( 'WPSEO_Options', false )    // Yoast SEO
        || defined( 'RANK_MATH_VERSION' )                  // RankMath
        || defined( 'SEOPRESS_VERSION' )                   // SEOPress
        || class_exists( 'All_in_One_SEO_Pack', false )   // AIOSEO (alt)
        || class_exists( 'AIOSEO_Plugin', false );         // AIOSEO (neu)

    return $conflict;
}
```

**Verhalten bei Konflikt:**
- Admin-Notice mit Hinweis auf mögliche Doppel-Tags
- OG/Twitter-Ausgabe standardmäßig deaktiviert
- Schema-Ausgabe standardmäßig deaktiviert
- Alle Einstellungen manuell überschreibbar (bewusste Entscheidung)

**Erkannte Plugins:**

| Plugin            | Erkennungsmethode          | Status |
|-------------------|---------------------------|--------|
| Yoast SEO         | `class_exists(WPSEO_Options)` | ✅    |
| RankMath          | `defined(RANK_MATH_VERSION)`  | ✅    |
| SEOPress          | `defined(SEOPRESS_VERSION)`   | ✅    |
| AIOSEO            | `class_exists(AIOSEO_Plugin)` | ✅    |
| The SEO Framework | —                             | ❌ fehlt |
| Slim SEO          | —                             | ❌ fehlt |

> **Empfehlung:** Erweiterung um `class_exists('The_SEO_Framework\Core')` und `defined('SLIM_SEO_VERSION')` für vollständige Abdeckung.

---

## 8. Performance-Analyse

### 8.1 Positive Aspekte

- **Lazy-Loading:** AI-Modul wird komplett nicht geladen wenn `enabled: false`
- **Admin/Frontend-Trennung:** `is_admin()`-Gate verhindert Frontend-Overhead durch Admin-Code
- **On-Demand-Analyse:** Content-Analyse nur per Button-Klick oder asynchron via WP-Cron – nie automatisch im Frontend
- **Transient-Cache:** AI-Endpoints mit konfigurierbarem TTL (60s–1 Woche)
- **Statisches Caching:** `bsseo_has_seo_conflict()` nutzt `static $conflict = null` – einmalige Prüfung pro Request
- **Cron-Deduplication:** `wp_next_scheduled()` verhindert mehrfache Cron-Jobs für denselben Post
- **DOM-Limits:** max. 120.000 Zeichen, 8.000 Knoten, 2,5s Timeout im Analyzer

### 8.2 Schwachstellen

**`bsseo_get_settings()` ohne In-Memory-Cache:**

```php
// Aktuell: jeder Aufruf = ein get_option() = eine DB-Query
function bsseo_get_settings() {
    $saved = get_option( 'bsseo_settings', array() ); // DB-Query!
    // ...
}

// Empfehlung: statisches Caching
function bsseo_get_settings() {
    static $cache = null;
    if ( $cache !== null ) { return $cache; }
    $saved = get_option( 'bsseo_settings', array() );
    // ... merge ...
    $cache = $out;
    return $cache;
}
```

`bsseo_get_settings()` wird pro Request mehrfach aufgerufen (Title-Filter, Robots-Filter, Canonical, OG/Twitter, Schema, Sitemap, Content-Analyzer, AI-Modul). Mit statischem Cache wäre das eine einzige DB-Query statt 6–10.

**Sitemap-Performance bei großen Sites:**

Die `meta_query` in `bsseo_sitemap_posts_query_args()` erzeugt einen `LEFT JOIN` auf die `postmeta`-Tabelle. Bei Sites mit 10.000+ Posts und schlecht indexierter `postmeta`-Tabelle kann das zu Timeouts führen.

**`register_post_meta()` in Schleife:**

```php
$post_types = get_post_types( array( 'public' => true ), 'names' );
foreach ( $post_types as $post_type ) {
    bsseo_register_meta_for_post_type( $post_type );
}
```

Bei Sites mit vielen Custom Post Types (z.B. 15+) werden entsprechend viele Meta-Keys registriert. Performance-unkritisch, aber erwähnenswert.

---

## 9. Offene Punkte & Roadmap

Aus `OFFEN-UND-PRUEFEN.md` – gut dokumentiert und priorisiert:

### 9.1 Noch nicht implementiert

| Feature                                              | Phase   | Priorität | Abhängigkeit    |
|------------------------------------------------------|---------|-----------|-----------------|
| Vorlagen pro Post-Type (SEO-Titel, Description)      | Phase 2 | Hoch      | Phase 1 ✅      |
| Vorlagen pro Kategorie (Term-Meta)                   | Phase 3 | Mittel    | Phase 2         |
| Bulk-Action: SEO-Felder bearbeiten                   | Phase 4 | Mittel    | unabhängig      |
| Bulk-Action: Leere Felder aus WP-Daten füllen        | Phase 5 | Niedrig   | Phase 4         |
| Test-Harness / Head-Extractor (`tools/extract-head.php`) | Tool | Hoch   | unabhängig      |

### 9.2 Zu prüfen / optional

| Punkt                                    | Status                          |
|------------------------------------------|---------------------------------|
| Vergleichsmatrix (manuell ausfüllen)     | Fixtures in `tests/` vorhanden  |
| AI-Endpoints manuelle Test-Checkliste    | Dokumentation fehlt             |
| Fokus-Keyword in Analyse integrieren     | Feld vorhanden, kaum genutzt    |
| jQuery vs. Vanilla in `admin.js`         | Entscheidung dokumentieren      |
| BlogPosting vs. Article nach Post-Format | Schema-Erweiterung (optional)   |

---

## 10. Konkrete Verbesserungsempfehlungen

### 10.1 Sofortmaßnahmen (Quick-Wins vor v1.0-Release)

**1. Settings-Cache hinzufügen** *(2 Zeilen, großer Impact)*

```php
function bsseo_get_settings() {
    static $cache = null;
    if ( $cache !== null ) { return $cache; }
    $saved    = get_option( 'bsseo_settings', array() );
    $defaults = bsseo_get_default_settings();
    $out      = array_merge( $defaults, $saved );
    // ... nested merges ...
    $cache = $out;
    return $cache;
}
```

Cache-Invalidierung bei Settings-Save über `add_action('update_option_bsseo_settings', function() { /* static reset */ })` – oder einfach den static-Cache bei Seitenaufruf leben lassen (er ist Request-scoped, kein Problem).

**2. `mb_strlen` statt `strlen` im Content-Analyzer**

```php
// In bsseo_run_analysis():
if ( mb_strlen( $title_used ) >= 30 && mb_strlen( $title_used ) <= 70 ) { ... }
if ( mb_strlen( $desc_used ) >= 120 && mb_strlen( $desc_used ) <= 160 ) { ... }
```

**3. `bsseo_sanitize_checks()` absichern** *(siehe Abschnitt 3.5)*

**4. Schema-Typ aus Metabox-Feld tatsächlich auswerten**

```php
// In bsseo_build_schema_graph():
$schema_type_override = get_post_meta( $post->ID, '_bsseo_schema_type', true );

// Für BlogPosting-Block:
$article_type = 'BlogPosting'; // Default
if ( $schema_type_override !== '' && $post_type === 'post' ) {
    $article_type = $schema_type_override; // Article, BlogPosting, etc.
}
$article = array( '@type' => $article_type, ... );
```

### 10.2 Kurzfristig (v1.1)

- **Test-Harness** (`tools/extract-head.php`): Priorität hoch – ermöglicht automatisierte Regressionstests gegen die Fixtures in `tests/`
- **Vorlagen pro Post-Type**: Wichtigste User-Facing-Feature für den Alltag (Titelformat konfigurieren)
- **Fokus-Keyword-Checks**: Einfacher Check – Keyword in Title? In Description? In Content? Mit Score-Beitrag
- **Konflikt-Guard erweitern**: The SEO Framework, Slim SEO

### 10.3 Mittelfristig (v1.2)

- **Bulk-Actions**: Stark wertvoll für Sites mit vielen Posts ohne SEO-Felder
- **jQuery → Vanilla JS** in `admin.js` (optional, da WP jQuery ohnehin lädt)
- **PHPCS / WordPress Coding Standards** im CI/CD
- **BreadcrumbList-Schema**: sobald Breadcrumb-Unterstützung kommt
- **`_schema_type` FAQPage/HowTo**: eigene Schema-Blöcke für diese Typen

---

## 11. Gesamtfazit

BSseo ist ein qualitativ hochwertiges WordPress-SEO-Plugin, das seinen Anspruch als schlanke Alternative zu Yoast und RankMath überzeugend einlöst. Der Code zeigt durchgehend WordPress-Expertise:

✅ Korrekte Hook-Prioritäten und Filter-Architektur  
✅ Saubere Settings-API-Nutzung mit vollständigen Sanitize-Callbacks  
✅ Konformes REST-Meta-Handling (Gutenberg-kompatibel)  
✅ Solide Sicherheitsarchitektur (Nonces, Capabilities, Timing-sicheres API-Key-Handling)  
✅ Feature-Flag-Pattern für das AI-Modul  
✅ Vorbildlicher Komparator `BSSEO_NOINDEX_ENV` für Staging-Umgebungen  
✅ Citation-Support im Schema (@graph mit `CreativeWork`)  
✅ Saubere BCP 47-Sprachattribute im Schema  

Das **KI/LLM-Endpoint-Modul** ist für 2026 zeitgemäß und innovativ. Die `llms.txt`-Orientierung, die Domain-Validierung beim URL-Lookup, und das optionale API-Key-System zeigen, dass das Modul mit Bedacht entworfen wurde.

Die **offene Roadmap** in `OFFEN-UND-PRUEFEN.md` zeigt einen reifen Entwicklungsprozess: Was noch fehlt, ist transparent dokumentiert und priorisiert. Die für v1.0 wichtigen Features sind vollständig implementiert.

**Mit den beschriebenen Quick-Wins (Settings-Cache, mb_strlen, Checks-Sanitierung, Schema-Typ) ist die Bewertung sofort auf 8,5/10 anhebbar.**

---

*Erstellt mit Claude (Anthropic) | Februar 2026 | Analysebasis: BSseo v1.0.0 Quellcode*
