# BSseo – Offene Punkte & Prüfaufträge

Stand: 2026-02-16  
Diese Datei enthält **nur** das, was noch nicht umgesetzt ist oder geprüft werden sollte. Bereits umgesetzte Punkte aus den früheren Entwicklungsdokumenten sind hier nicht aufgeführt.

---

## 1. Noch nicht umgesetzt

### 1.1 Vorlagen pro Post-Type (DEVELOPMENT-3 Phase 2)

- **Ziel:** Pro Post-Type (z. B. post, page) konfigurierbare Vorlagen für SEO-Titel und Meta-Description. Bei leerem SEO-Feld wird die Vorlage mit Platzhaltern angewendet.
- **Fehlend:** In `includes/defaults.php`: z. B. `templates_post_title`, `templates_post_description`, `templates_page_title`, `templates_page_description`. In `core/meta-manager.php` (und Schema/OG): Logik „wenn Feld leer und Fallback an → Vorlage laden und `bsseo_parse_template()` anwenden“. Einstellungsseite: Felder „Titel-Vorlage“ / „Beschreibungs-Vorlage“ pro Post-Type mit Platzhalter-Hinweis (`%title%`, `%sitename%`, `%sep%`, `%excerpt%`).
- **Referenz:** DEVELOPMENT-3.md Phase 2 (vollständiger Lieferumfang und Tests).

### 1.2 Vorlagen pro Kategorie (DEVELOPMENT-3 Phase 3)

- **Ziel:** Für Beiträge (post) zusätzlich Vorlagen pro Kategorie. Bei leerem SEO-Feld: zuerst Kategorie-Vorlage, sonst Post-Type-Vorlage bzw. Standard-Fallback.
- **Fehlend:** Term-Meta pro Kategorie (z. B. `bsseo_title_template`, `bsseo_description_template`), Registrierung in BSseo, Abfrage in `meta-manager.php`/Schema. Beim Bearbeiten einer Kategorie: optionale Felder „BSseo Titel-Vorlage“, „BSseo Beschreibungs-Vorlage“. Neues Modul z. B. `admin/taxonomy-templates.php`.
- **Referenz:** DEVELOPMENT-3.md Phase 3.

### 1.3 Bulk-Action: SEO-Felder bearbeiten (DEVELOPMENT-3 Phase 4)

- **Ziel:** In Beiträgen- und Seiten-Listen eine Bulk Action „BSseo: SEO-Felder bearbeiten“. Nach Auswahl → Admin-Seite (oder Modal) mit Tabelle: pro Zeile Post-Titel, Felder SEO-Titel, Meta-Description (optional Fokus-Keyword, Canonical). Button „Alle speichern“ mit Nonce und Capability-Check.
- **Fehlend:** Datei z. B. `admin/bulk-edit-seo.php` (Bulk Action anmelden, Admin-Seite rendern, Speicher-Handler). In `bsseo.php` im Admin-Bereich einbinden.
- **Referenz:** DEVELOPMENT-3.md Phase 4.

### 1.4 Bulk-Action: Leere Felder aus WordPress-Daten füllen (DEVELOPMENT-3 Phase 5)

- **Ziel:** Bulk Action „BSseo: Leere Felder aus WordPress-Daten füllen“. Für ausgewählte Einträge mit **leeren** SEO-Feldern: einmalig setzen SEO-Titel = Beitragstitel, Meta-Description = Excerpt/gekürzter Inhalt, OG-Bild = Beitragsbild. Keine Überschreibung bereits befüllter Felder.
- **Fehlend:** Zweite Bulk Action + Handler (in gleicher Datei wie Phase 4 oder eigene `admin/bulk-fill-seo.php`). Rückmeldung „Für X Beiträge wurden leere SEO-Felder gefüllt.“
- **Referenz:** DEVELOPMENT-3.md Phase 5.

### 1.5 Test-Harness / Head-Extractor (DEVELOPMENT-SEO-Verfeinerung §2)

- **Ziel:** Tool, das aus HTML deterministisch Title, meta description, robots, OG, Twitter, canonical, JSON-LD extrahiert (JSON-Ausgabe für Diffing). „Golden Master“-Vergleich für BSseo vs. Baseline.
- **Fehlend:** `/tools/extract-head.php` (oder vergleichbarer Pfad). Fixtures liegen in `tests/` (quellcode-beitrag-ohnePlugin.html, quellcode-beitrag-bsseo.html, quellcode-beitrag-mathRank.html).
- **Referenz:** DEVELOPMENT-SEO-Verfeinerung.md §2.2, §2.3.

---

## 2. Prüfen / optional dokumentieren

### 2.1 Vergleichsmatrix (docs/compare.md)

- **Auftrag:** Die Tabelle in `docs/compare.md` (bzw. die darin beschriebene Vergleichsmatrix) manuell ausfüllen: Für jede Zeile (Title, meta description, canonical, robots, OG-Felder, Twitter-Felder, JSON-LD) die Spalten **Vorhanden?** und **Inhalt korrekt?** mit **Y** oder **N** eintragen. Dazu die Fixtures (z. B. `tests/quellcode-beitrag-bsseo.html`) und echten Frontend-Ausgaben nutzen.
- **Hinweis:** Die Datei `docs/compare.md` wurde im Zuge der Aufräumaktion entfernt. Die Prüflogik bleibt: Head-Ausgabe von BSseo gegen Zielbild (genau eine meta description, ein canonical, noindex über wp_robots, OG/Twitter vollständig, JSON-LD @graph mit Referenzen) abgleichen.

### 2.2 AI/LLM Endpoints – manuelle Test-Checkliste

- **Auftrag:** Die „Manuelle Test-Checkliste“ in der AI-Endpoint-Dokumentation durchgehen (Modul AUS/AN, Pretty URLs, ai/page Lookup, ai/feed, CPT, API-Key, Cache, Debug-Header). Die Dokumentation lag in `docs/ai-endpoints.md`; Inhalt bei Bedarf in Plugin-Handbuch oder Wiki übernehmen.

### 2.3 focus_keyword in der Analyse (optional v1.1)

- **Status:** focus_keyword wird geladen, fließt aber kaum in den Score ein.
- **Optionen:** Einfachen Check (Keyword in Title/Description/Content) einbauen oder als „geplant für spätere Version“ im Changelog/Backlog führen.

### 2.4 jQuery vs. Vanilla im Admin (Dokumentation)

- **Status:** `admin.js` nutzt jQuery. Entwicklungsidee war teils „Vanilla“.
- **Empfehlung:** Entscheidung festhalten: „Im Admin darf jQuery genutzt werden (WP lädt es ohnehin).“ Oder Refactoring auf Vanilla für v1.1 planen.

### 2.5 Schema-Erweiterung (optional v1.1)

- **Nicht Blocker:** BlogPosting vs. Article nach Post-Type/Format; mainEntityOfPage, url, inLanguage sind bereits umgesetzt. Optional: BreadcrumbList, wenn Breadcrumbs ins Plugin kommen.

---

## 3. Abhängigkeiten der offenen Umsetzungen

```
Phase 2 (Vorlagen Post-Type)   → baut auf Phase 1 (use_wp_fallback, bereits umgesetzt)
Phase 3 (Vorlagen Kategorie)   → baut auf Phase 2
Phase 4 (Bulk bearbeiten)     → unabhängig
Phase 5 (Bulk füllen)         → baut auf Phase 4 (gleiche Bulk-Infrastruktur)
Test-Harness (extract-head)   → unabhängig
```

Empfohlene Reihenfolge: **Phase 2 → 3** (Vorlagen), dann **Phase 4 → 5** (Bulk). Test-Harness und Prüfaufträge können parallel erfolgen.
