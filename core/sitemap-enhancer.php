<?php
/**
 * BSseo – Sitemap-Anpassungen (lastmod, noindex-Ausschluss)
 * DEVELOPMENT.md §4 C, §9. Core-Sitemap via wp_sitemaps_* Filter.
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * lastmod aus post_modified_gmt zum Sitemap-Eintrag hinzufügen.
 *
 * @param array   $sitemap_entry Eintrag für die Sitemap.
 * @param WP_Post $post         Post-Objekt.
 * @param string  $post_type    Post-Type.
 * @return array
 */
function bsseo_sitemap_posts_entry( $sitemap_entry, $post, $post_type ) {
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_sitemap_tweaks'] ) ) {
		return $sitemap_entry;
	}

	if ( ! empty( $post->post_modified_gmt ) && $post->post_modified_gmt !== '0000-00-00 00:00:00' ) {
		$sitemap_entry['lastmod'] = date( DATE_W3C, strtotime( $post->post_modified_gmt . ' GMT' ) );
	}

	return $sitemap_entry;
}

add_filter( 'wp_sitemaps_posts_entry', 'bsseo_sitemap_posts_entry', 10, 3 );

/**
 * noindex-Beiträge aus der Sitemap ausschließen (_bsseo_noindex = 1).
 *
 * @param array  $args      WP_Query-Argumente.
 * @param string $post_type Post-Type.
 * @return array
 */
function bsseo_sitemap_posts_query_args( $args, $post_type ) {
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_sitemap_tweaks'] ) ) {
		return $args;
	}

	// Nur Einträge, bei denen _bsseo_noindex nicht gesetzt oder != 1 ist (Integer).
	$noindex_meta = array(
		'relation' => 'OR',
		array(
			'key'     => '_bsseo_noindex',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_bsseo_noindex',
			'value'   => 1,
			'compare' => '!=',
			'type'    => 'NUMERIC',
		),
	);

	if ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
		$args['meta_query'] = array(
			'relation' => 'AND',
			$args['meta_query'],
			$noindex_meta,
		);
	} else {
		$args['meta_query'] = $noindex_meta;
	}

	// Meta-Cache für diese Abfrage aktivieren, damit die meta_query greift.
	$args['update_post_meta_cache'] = true;

	return $args;
}

add_filter( 'wp_sitemaps_posts_query_args', 'bsseo_sitemap_posts_query_args', 10, 2 );
