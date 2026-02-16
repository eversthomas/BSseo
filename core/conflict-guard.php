<?php
/**
 * BSseo – Erkennung anderer SEO-Plugins (DEVELOPMENT.md §5.1)
 *
 * Phase 1: Nur Erkennung, kein Admin-Hinweis und kein Verhalten.
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prüft, ob ein bekanntes SEO-Plugin aktiv ist (Konfliktpotenzial).
 *
 * @return bool True, wenn mindestens eines erkannt wurde.
 */
function bsseo_has_seo_conflict() {
	static $conflict = null;

	if ( $conflict !== null ) {
		return $conflict;
	}

	$conflict = false;

	// Prüfung über Klassen/Konstanten, damit es im Frontend und Cron ohne plugin.php funktioniert.
	$conflict = class_exists( 'WPSEO_Options', false )                    // Yoast SEO
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| class_exists( 'All_in_One_SEO_Pack', false )                  // AIOSEO
		|| class_exists( 'AIOSEO_Plugin', false );                       // AIOSEO (neuere Versionen)

	return $conflict;
}
