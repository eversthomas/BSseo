<?php
/**
 * BSseo – Platzhalter-Parsing für Title/Description (DEVELOPMENT.md §4 A, Prompt 3)
 *
 * Unterstützt: %title%, %sitename%, %sep%, %excerpt%
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ersetzt Platzhalter in einer Vorlage.
 *
 * @param string $template   Vorlage (z. B. "%title% %sep% %sitename%").
 * @param array  $replacements Assoziatives Array: Platzhalter ohne % => Wert (z. B. 'title' => 'Mein Beitrag', 'sep' => '|').
 * @return string
 */
function bsseo_parse_template( $template, array $replacements ) {
	if ( $template === '' ) {
		return '';
	}
	$out = $template;
	foreach ( $replacements as $key => $value ) {
		$out = str_replace( '%' . $key . '%', (string) $value, $out );
	}
	// Verbleibende %foo%-Platzhalter entfernen (optional: leer ersetzen)
	$out = preg_replace( '/%[a-z_]+%/i', '', $out );
	$out = preg_replace( '/\s+/', ' ', $out );
	return trim( $out );
}

/**
 * Liefert Standard-Ersetzungen für den Dokumenttitel (Title-Tag).
 *
 * @param string $title_part  Der zentrale Titel (Beitragstitel oder eigener SEO-Titel).
 * @return array
 */
function bsseo_get_title_replacements( $title_part ) {
	$settings = bsseo_get_settings();
	$sep      = $settings['separator'] !== '' ? $settings['separator'] : '|';
	$sitename = $settings['site_name_override'] !== ''
		? $settings['site_name_override']
		: get_bloginfo( 'name', 'display' );

	return array(
		'title'    => $title_part,
		'sitename' => $sitename,
		'sep'      => $sep,
		'excerpt'  => '',
	);
}

/**
 * Baut den vollständigen Dokumenttitel aus Teil und Vorlage.
 * Standard-Vorlage: "%title% %sep% %sitename%"
 *
 * @param string $title_part  Zentraler Titel (z. B. Post-Titel oder _bsseo_title).
 * @param string $template    Optional. Vorlage mit Platzhaltern; leer = Standard.
 * @return string
 */
function bsseo_build_document_title( $title_part, $template = '' ) {
	$replacements = bsseo_get_title_replacements( $title_part );
	if ( $template === '' ) {
		$template = '%title% %sep% %sitename%';
	}
	return bsseo_parse_template( $template, $replacements );
}
