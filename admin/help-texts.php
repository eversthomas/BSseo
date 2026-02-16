<?php
/**
 * BSseo – Help-Texte für UI-Felder (Label, Tooltip, Help, Example)
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liefert Help-Daten pro Feld/Key für Einstellungen und Metabox.
 *
 * @return array<string, array{ label: string, tooltip?: string, help?: string, example?: string }>
 */
function bsseo_get_help_texts() {
	return array(
		// --- Einstellungsseite: Allgemein ---
		'separator' => array(
			'label'   => __( 'Titel-Trennzeichen', 'bsseo' ),
			'tooltip' => __( 'Zeichen zwischen Seitentitel und Blogname im &lt;title&gt;-Tag (z. B. |, –, ·).', 'bsseo' ),
			'help'    => __( 'Wird in der Title-Vorschau und im Frontend verwendet.', 'bsseo' ),
			'example' => '|',
		),
		'site_name_override' => array(
			'label'   => __( 'Blogname für SEO', 'bsseo' ),
			'tooltip' => __( 'Optional: anderer Name nur für Title/Schema (leer = WordPress-Blogname).', 'bsseo' ),
			'help'    => __( 'Überschreibt den Blognamen nicht in der WordPress-Übersicht.', 'bsseo' ),
			'example' => '',
		),
		'home_title' => array(
			'label'   => __( 'Startseiten-Titel', 'bsseo' ),
			'tooltip' => __( 'Eigener &lt;title&gt; für die Startseite (leer = Standard).', 'bsseo' ),
			'example' => '',
		),
		'home_description' => array(
			'label'   => __( 'Startseiten-Meta-Beschreibung', 'bsseo' ),
			'tooltip' => __( 'Meta-Description nur für die Startseite.', 'bsseo' ),
			'example' => '',
		),
		// --- Schema.org ---
		'schema_org_name' => array(
			'label'   => __( 'Organisationsname', 'bsseo' ),
			'tooltip' => __( 'Name der Organisation für Schema.org (z. B. Firmenname).', 'bsseo' ),
			'help'    => __( 'Wird in JSON-LD ausgegeben, wenn Schema aktiv ist.', 'bsseo' ),
			'example' => '',
		),
		'schema_org_logo' => array(
			'label'   => __( 'Logo-URL', 'bsseo' ),
			'tooltip' => __( 'Vollständige URL zum Logo (Schema.org).', 'bsseo' ),
			'example' => 'https://example.com/logo.png',
		),
		'schema_org_social_profiles' => array(
			'label'   => __( 'Social-Media-Profile', 'bsseo' ),
			'tooltip' => __( 'Eine URL pro Zeile (z. B. Facebook, Twitter, Instagram).', 'bsseo' ),
			'help'    => __( 'Optional. Erscheint im Schema.org-Organization-Block.', 'bsseo' ),
			'example' => "https://facebook.com/example\nhttps://twitter.com/example",
		),
		// --- Toggles (Settings) ---
		'head_output_active' => array(
			'label'   => __( 'BSseo-Output im Frontend aktiv', 'bsseo' ),
			'tooltip' => __( 'Wenn aus: Keine Meta-Tags, kein Schema, kein Canonical von BSseo. Nützlich zum Debuggen oder bei Theme-Konflikten.', 'bsseo' ),
		),
		'output_title' => array(
			'label'   => __( 'SEO-Titel ausgeben', 'bsseo' ),
			'tooltip' => __( 'Steuert die Ausgabe des angepassten &lt;title&gt;-Tags.', 'bsseo' ),
		),
		'output_meta_description' => array(
			'label'   => __( 'Meta-Beschreibung ausgeben', 'bsseo' ),
			'tooltip' => __( '&lt;meta name="description"&gt; im Kopfbereich.', 'bsseo' ),
		),
		'output_robots' => array(
			'label'   => __( 'Robots-Meta ausgeben', 'bsseo' ),
			'tooltip' => __( 'noindex/nofollow aus den Beitragseinstellungen übernehmen.', 'bsseo' ),
		),
		'output_canonical' => array(
			'label'   => __( 'Canonical-URL ausgeben', 'bsseo' ),
			'tooltip' => __( '&lt;link rel="canonical"&gt; (eigene URL pro Beitrag möglich).', 'bsseo' ),
		),
		'output_og_twitter' => array(
			'label'   => __( 'OG / Twitter-Cards ausgeben', 'bsseo' ),
			'tooltip' => __( 'Open Graph und Twitter Card Meta-Tags für Vorschauen. Wenn dein Theme Social Meta schon ausgibt: hier deaktivieren.', 'bsseo' ),
			'help'    => __( 'Bei aktivem anderen SEO-Plugin standardmäßig aus. Bei Theme-Duplikaten: Toggle aus.', 'bsseo' ),
		),
		'output_schema' => array(
			'label'   => __( 'Schema.org (JSON-LD) ausgeben', 'bsseo' ),
			'tooltip' => __( 'Strukturierte Daten für Suchmaschinen (Article, WebPage, Organization).', 'bsseo' ),
		),
		'output_sitemap_tweaks' => array(
			'label'   => __( 'Sitemap-Anpassungen', 'bsseo' ),
			'tooltip' => __( 'lastmod setzen und noindex-Inhalte aus der Sitemap ausschließen.', 'bsseo' ),
		),
		'analysis_enabled' => array(
			'label'   => __( 'Content-Analyse aktivieren', 'bsseo' ),
			'tooltip' => __( 'SEO- und KI-Score sowie Analyse-Button in der Metabox.', 'bsseo' ),
		),
		'use_wp_fallback' => array(
			'label'   => __( 'Bei leeren SEO-Feldern WordPress-Daten nutzen', 'bsseo' ),
			'tooltip' => __( 'Wenn an: Ohne eigene Angaben werden Beitragstitel, Ausschnitt und Beitragsbild für Titel, Meta-Beschreibung und OG genutzt. Wenn aus: leere Felder bleiben leer (kein Fallback).', 'bsseo' ),
		),
		// --- Metabox-Felder (laienfreundlich) ---
		'title' => array(
			'label'   => __( 'Titel in Suchmaschinen', 'bsseo' ),
			'tooltip' => __( 'So erscheint der Titel bei Google. Leer = Ihr Beitragstitel.', 'bsseo' ),
			'help'    => __( 'Empfohlen: 30–70 Zeichen.', 'bsseo' ),
		),
		'description' => array(
			'label'   => __( 'Kurzbeschreibung für Google', 'bsseo' ),
			'tooltip' => __( 'Der Text unter dem Titel in den Suchergebnissen.', 'bsseo' ),
			'help'    => __( 'Empfohlen: 120–160 Zeichen.', 'bsseo' ),
		),
		'focus_keyword' => array(
			'label'   => __( 'Hauptsuchbegriff', 'bsseo' ),
			'tooltip' => __( 'Das wichtigste Suchwort für diese Seite (optional).', 'bsseo' ),
		),
		'canonical' => array(
			'label'   => __( 'Offizielle Adresse dieser Seite', 'bsseo' ),
			'tooltip' => __( 'Nur ausfüllen, wenn diese Seite woanders „echt“ veröffentlicht ist.', 'bsseo' ),
		),
		'noindex' => array(
			'label'   => __( 'Nicht in Google anzeigen', 'bsseo' ),
			'tooltip' => __( 'Seite von Suchmaschinen ausblenden.', 'bsseo' ),
		),
		'nofollow' => array(
			'label'   => __( 'Links nicht bewerten', 'bsseo' ),
			'tooltip' => __( 'Suchmaschinen sollen den Links auf dieser Seite nicht folgen.', 'bsseo' ),
		),
		'schema_type' => array(
			'label'   => __( 'Art der Seite (für Suchmaschinen)', 'bsseo' ),
			'tooltip' => __( 'z. B. Artikel, Anleitung. Meist automatisch – nur bei Bedarf ändern.', 'bsseo' ),
		),
		'og_image' => array(
			'label'   => __( 'Vorschaubild in Sozialen Medien', 'bsseo' ),
			'tooltip' => __( 'Bild, wenn jemand die Seite teilt (z. B. Facebook, X).', 'bsseo' ),
		),
		'sources' => array(
			'label'   => __( 'Quellenangaben', 'bsseo' ),
			'tooltip' => __( 'Literatur oder Links für bessere Nachvollziehbarkeit.', 'bsseo' ),
		),
		// --- AI/LLM Endpoints ---
		'ai_enabled' => array(
			'label'   => __( 'AI/LLM Endpoints aktivieren', 'bsseo' ),
			'tooltip' => __( 'REST-Endpunkte unter /wp-json/bsseo/v1/ai/* für KI-Crawler und maschinenlesbare Metadaten.', 'bsseo' ),
		),
		'ai_pretty_urls' => array(
			'label'   => __( 'Pretty URLs aktivieren', 'bsseo' ),
			'tooltip' => __( 'Zusätzlich /ai/meta/, /ai/page/, /ai/feed/ und /llms.txt als lesbare URLs (nur wenn Endpoints aktiv).', 'bsseo' ),
		),
		'ai_post_types' => array(
			'label'   => __( 'Post-Typen im Feed', 'bsseo' ),
			'tooltip' => __( 'Welche Inhaltstypen in ai/feed und ai/page erscheinen dürfen.', 'bsseo' ),
		),
		'ai_taxonomy_filters' => array(
			'label'   => __( 'Taxonomie-Filter erlauben', 'bsseo' ),
			'tooltip' => __( 'Feed-Parameter für Kategorie/Tag oder Custom Taxonomies zulassen.', 'bsseo' ),
		),
		'ai_content_level' => array(
			'label'   => __( 'Content-Feld-Level', 'bsseo' ),
			'tooltip' => __( 'Nur Metadaten; mit Ausschnitt; oder mit gekürztem Inhalt (strip_tags, Zeichenlimit).', 'bsseo' ),
		),
		'ai_feed_limit_default' => array(
			'label'   => __( 'Feed Standard-Limit', 'bsseo' ),
			'tooltip' => __( 'Anzahl Einträge pro Seite ohne limit-Parameter.', 'bsseo' ),
		),
		'ai_feed_limit_max' => array(
			'label'   => __( 'Feed Max-Limit', 'bsseo' ),
			'tooltip' => __( 'Obergrenze für den limit-Parameter.', 'bsseo' ),
		),
		'ai_respect_noindex' => array(
			'label'   => __( 'Noindex respektieren', 'bsseo' ),
			'tooltip' => __( 'Beiträge mit BSseo-Noindex nicht im Feed; ai/page liefert noindex-Flag.', 'bsseo' ),
		),
		'ai_respect_password' => array(
			'label'   => __( 'Passwortschutz respektieren', 'bsseo' ),
			'tooltip' => __( 'Passwortgeschützte Beiträge nie ausgeben.', 'bsseo' ),
		),
		'ai_cache_enabled' => array(
			'label'   => __( 'Responses cachen', 'bsseo' ),
			'tooltip' => __( 'Antworten per Transient cachen (TTL siehe unten).', 'bsseo' ),
		),
		'ai_cache_ttl' => array(
			'label'   => __( 'Cache TTL (Sekunden)', 'bsseo' ),
			'tooltip' => __( 'Lebensdauer des Caches (z. B. 21600 = 6 Stunden).', 'bsseo' ),
		),
		'ai_public_access' => array(
			'label'   => __( 'Öffentlich zugänglich', 'bsseo' ),
			'tooltip' => __( 'Nur veröffentlichte Inhalte; bei Aus: nur mit API-Key.', 'bsseo' ),
		),
		'ai_require_api_key' => array(
			'label'   => __( 'API-Key erforderlich', 'bsseo' ),
			'tooltip' => __( 'Header X-BSSEO-KEY muss gesetzt sein (Key unten).', 'bsseo' ),
		),
		'ai_api_key' => array(
			'label'   => __( 'API-Key', 'bsseo' ),
			'tooltip' => __( 'Nur wenn „API-Key erforderlich“ aktiv.', 'bsseo' ),
		),
		'ai_debug_headers' => array(
			'label'   => __( 'Debug-Header', 'bsseo' ),
			'tooltip' => __( 'X-BSSEO-Cache: HIT/MISS, X-BSSEO-Route in Antworten.', 'bsseo' ),
		),
	);
}
