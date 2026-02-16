<?php
/**
 * BSseo – Spalte „SEO / KI“ in Post-Listen
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fügt die Spalte „SEO / KI“ hinzu.
 *
 * @param array $columns Spalten.
 * @return array
 */
function bsseo_add_list_column( $columns ) {
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['analysis_enabled'] ) ) {
		return $columns;
	}

	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['bsseo_scores'] = __( 'SEO / KI', 'bsseo' );
		}
	}
	if ( ! isset( $new['bsseo_scores'] ) ) {
		$new['bsseo_scores'] = __( 'SEO / KI', 'bsseo' );
	}
	return $new;
}

/**
 * Gibt den Spalteninhalt aus.
 *
 * @param string $column  Spaltenname.
 * @param int    $post_id Post-ID.
 */
function bsseo_render_list_column( $column, $post_id ) {
	if ( $column !== 'bsseo_scores' ) {
		return;
	}

	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['analysis_enabled'] ) ) {
		echo '—';
		return;
	}

	$updated = (int) get_post_meta( $post_id, '_bsseo_score_updated', true );
	$seo     = (int) get_post_meta( $post_id, '_bsseo_seo_score', true );
	$ai      = (int) get_post_meta( $post_id, '_bsseo_ai_score', true );

	if ( ! $updated ) {
		echo '—';
		return;
	}

	$seo_class = 'bsseo-score-good';
	if ( $seo < 40 ) {
		$seo_class = 'bsseo-score-bad';
	} elseif ( $seo < 70 ) {
		$seo_class = 'bsseo-score-warning';
	}
	$ai_class = 'bsseo-score-good';
	if ( $ai < 40 ) {
		$ai_class = 'bsseo-score-bad';
	} elseif ( $ai < 70 ) {
		$ai_class = 'bsseo-score-warning';
	}

	printf(
		'<span class="bsseo-col-scores"><span class="bsseo-score-pill %s" title="%s">SEO: %d</span> <span class="bsseo-score-pill %s" title="%s">KI: %d</span></span>',
		esc_attr( $seo_class ),
		esc_attr__( 'SEO-Score', 'bsseo' ),
		(int) $seo,
		esc_attr( $ai_class ),
		esc_attr__( 'KI/Struktur-Score', 'bsseo' ),
		(int) $ai
	);
}

/**
 * Macht die Spalte sortierbar (nach SEO-Score).
 *
 * @param WP_Query $query Query-Objekt.
 */
function bsseo_sortable_column( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->get( 'orderby' ) === 'bsseo_scores' ) {
		$query->set( 'meta_key', '_bsseo_seo_score' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}

$public_types = get_post_types( array( 'public' => true ), 'names' );
foreach ( $public_types as $post_type ) {
	add_filter( "manage_{$post_type}_posts_columns", 'bsseo_add_list_column' );
	add_action( "manage_{$post_type}_posts_custom_column", 'bsseo_render_list_column', 10, 2 );
	add_filter( "manage_edit-{$post_type}_sortable_columns", 'bsseo_add_sortable_column' );
}

/**
 * Registriert sortierbare Spalte.
 *
 * @param array $columns Sortierbare Spalten.
 * @return array
 */
function bsseo_add_sortable_column( $columns ) {
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['analysis_enabled'] ) ) {
		return $columns;
	}
	$columns['bsseo_scores'] = 'bsseo_scores';
	return $columns;
}

add_action( 'pre_get_posts', 'bsseo_sortable_column' );
