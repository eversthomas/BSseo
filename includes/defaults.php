<?php
/**
 * BSseo – Standardwerte und Settings-Zugriff
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Standardwerte für bsseo_settings (DEVELOPMENT.md §6.2).
 *
 * @return array
 */
function bsseo_get_default_settings() {
	return array(
		'separator'                   => '|',
		'site_name_override'         => '',
		'home_title'                 => '',
		'home_description'          => '',
		'schema_org'                 => array(
			'name'           => '',
			'logo'           => '',
			'social_profiles' => array(),
		),
		'toggles'                    => array(
			'head_output_active'     => true,  // Master: BSseo-Output im Frontend (wp_head, robots, title)
			'output_title'           => true,
			'output_meta_description' => true,
			'output_robots'          => true,
			'output_canonical'       => true,
			'output_og_twitter'      => true,
			'output_schema'         => true,
			'output_sitemap_tweaks' => true,
			'analysis_enabled'      => true,
			'use_wp_fallback'       => true, // DEVELOPMENT-3 Phase 1: bei leeren SEO-Feldern WP-Daten nutzen
		),
		'sitemap_excluded_post_types' => array(),
		'analysis_limits'            => array(
			'max_chars'   => 120000,
			'max_dom_nodes' => 8000,
			'timeout_ms'   => 2500,
		),
		// AI/LLM Endpoints Modul (optional aktivierbar)
		'ai' => array(
			'enabled'                => false,
			'pretty_urls'            => false,
			'post_types'             => array( 'post', 'page' ),
			'taxonomy_filters'       => false,
			'content_level'          => 'metadata_only',
			'feed_limit_default'     => 50,
			'feed_limit_max'         => 200,
			'respect_noindex'        => true,
			'respect_password'       => true,
			'cache_enabled'          => true,
			'cache_ttl'              => 6 * HOUR_IN_SECONDS,
			'public_access'          => true,
			'require_api_key'        => false,
			'api_key'                => '',
			'debug_headers'          => false,
		),
	);
}

/**
 * Default-Post-Types für AI-Feed (post, page + alle public CPTs außer attachment).
 *
 * @return array
 */
function bsseo_ai_default_post_types() {
	$types = get_post_types( array( 'public' => true ), 'names' );
	$types = array_diff( $types, array( 'attachment' ) );
	return array_values( $types );
}

/**
 * Gibt die BSseo-Einstellungen zurück (gemerged mit Defaults).
 * Immer diese Funktion nutzen – kein get_option('bsseo_settings') direkt.
 *
 * @return array
 */
function bsseo_get_settings() {
	$saved = get_option( 'bsseo_settings', array() );
	$defaults = bsseo_get_default_settings();

	if ( ! is_array( $saved ) ) {
		return $defaults;
	}

	// Ein-Stufe-Merge für Top-Keys; verschachtelte Arrays (toggles, schema_org, analysis_limits) manuell mergen
	$out = array_merge( $defaults, $saved );

	$out['toggles'] = array_merge( $defaults['toggles'], is_array( $out['toggles'] ?? null ) ? $out['toggles'] : array() );
	$out['schema_org'] = array_merge( $defaults['schema_org'], is_array( $out['schema_org'] ?? null ) ? $out['schema_org'] : array() );
	$out['analysis_limits'] = array_merge( $defaults['analysis_limits'], is_array( $out['analysis_limits'] ?? null ) ? $out['analysis_limits'] : array() );
	$out['ai'] = array_merge( $defaults['ai'], is_array( $out['ai'] ?? null ) ? $out['ai'] : array() );

	return $out;
}
