# BSseo

Schlankes WordPress-SEO-Plugin für klassische und KI-Suchmaschinen. Alternative zu großen Suites wie Yoast oder RankMath – weniger Code, klare UX, WordPress-idiomatisch.

**Lizenz:** GPL v3  
**Mindestanforderung:** WordPress ≥ 6.4, PHP ≥ 7.4 (empfohlen PHP 8.1+)

---

## Funktionen

- **Meta & Head:** Titel (mit Separator), Meta-Description, Canonical, Robots (noindex/nofollow) – steuerbar über Toggles und Post-Meta
- **Open Graph & Twitter Cards:** og:title, og:description, og:url, og:type, og:image, og:site_name, og:locale, article:published_time/modified_time; Twitter Card, Title, Description, Image
- **Schema.org (JSON-LD):** @graph mit WebSite, WebPage, BlogPosting/Article, Organization, Person, ImageObject; saubere Referenzen über @id; optionale Quellen (citation)
- **Sitemap:** Erweiterung der Core-Sitemap (lastmod, Ausschluss noindex-Inhalte)
- **Content-Analyse:** SEO- und KI/Struktur-Score on-demand (Button „Jetzt analysieren“) und asynchron per WP-Cron; Überschriften-Hierarchie, Lesbarkeit, Quellen
- **Admin:** Metabox für alle öffentlichen Post-Typen (Title, Description, Focus Keyword, Canonical, noindex/nofollow, Schema-Typ, OG-Bild, Quellen); Listen-Spalte „SEO / KI“; Dashboard-Widget
- **Konflikt-Guard:** Erkennt andere SEO-Plugins und schaltet OG/Schema standardmäßig aus (manuell wieder aktivierbar)
- **Optional – AI/LLM Endpoints:** Maschinenlesbare JSON-Endpoints für KI-Crawler (meta, page, feed, llms.txt-ähnlich), in den Einstellungen aktivierbar

---

## Installation

1. Plugin als ZIP herunterladen oder Repo klonen nach `wp-content/plugins/BSseo`.
2. Unter **Plugins** im WordPress-Backend BSseo aktivieren.
3. Unter **Einstellungen → BSseo** Toggles und Optionen (Separator, Schema-Organisation, ggf. AI-Modul) einrichten.

---

## Einstellungen (Auszug)

- **Ausgabe steuern:** Master-Toggle „BSseo-Output im Frontend“, einzeln: Titel, Meta-Description, Robots, Canonical, OG/Twitter, Schema, Sitemap-Tweaks, Content-Analyse
- **Bei leeren SEO-Feldern:** „WordPress-Daten nutzen“ (Titel → Beitragstitel, Description → Ausschnitt/Inhalt, OG-Bild → Beitragsbild)
- **Schema.org:** Organisationsname, Logo-URL, Social-Profile
- **Startseite:** Optional eigener Titel und Beschreibung
- **AI/LLM Endpoints:** Optional aktivierbar (REST `/wp-json/bsseo/v1/ai/*`, Pretty URLs, API-Key, Caching)

---

## Projektstruktur

```
BSseo/
├── bsseo.php                 # Loader, Aktivierung, Textdomain
├── core/
│   ├── conflict-guard.php   # Erkennung anderer SEO-Plugins
│   ├── meta-manager.php     # Title, Robots, Canonical, Description, OG/Twitter
│   ├── schema-generator.php # JSON-LD @graph
│   ├── sitemap-enhancer.php # lastmod, noindex-Ausschluss
│   └── content-analyzer.php # Analyse, Cron, AJAX
├── admin/
│   ├── settings-page.php    # Einstellungsseite
│   ├── metabox.php          # Metabox Post-Edit
│   ├── list-columns.php     # Spalte SEO/KI
│   ├── dashboard-widget.php # Dashboard-Widget
│   └── help-texts.php       # Labels, Tooltips
├── includes/
│   ├── defaults.php         # Standardwerte, bsseo_get_settings()
│   ├── helpers.php          # Hilfsfunktionen, Unicode-Wortzählung
│   ├── templates.php        # Platzhalter-Parsing (%title%, %sitename%, …)
│   └── register-meta.php    # register_post_meta()
├── ai/                      # Optional: AI/LLM REST + Pretty URLs
├── assets/admin/            # admin.js, admin.css, bsseo-sidebar.js
├── languages/               # bsseo.pot
├── tests/                   # Fixtures (HTML-Quellcode-Vergleiche)
├── OFFEN-UND-PRUEFEN.md     # Offene Punkte & Prüfaufträge
└── README.md
```

---

## Entwicklung & offene Punkte

Offene Umsetzungen und Prüfaufträge sind in **OFFEN-UND-PRUEFEN.md** beschrieben (z. B. Vorlagen pro Post-Type/Kategorie, Bulk-Actions, Test-Harness, manuelle Vergleichsmatrix).

---

## Lizenz & Autor

- **Lizenz:** [GPL v3](https://www.gnu.org/licenses/gpl-3.0.html)
- **Autor:** Tom Evers – [bezugssysteme.de](https://bezugssysteme.de)
