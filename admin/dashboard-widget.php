<?php
/**
 * BSseo – Dashboard-Widget (Durchschnittswerte, nicht analysiert)
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert das Dashboard-Widget.
 */
function bsseo_add_dashboard_widget() {
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['analysis_enabled'] ) ) {
		return;
	}

	wp_add_dashboard_widget(
		'bsseo_dashboard_widget',
		__( 'BSseo – SEO & KI-Scores', 'bsseo' ),
		'bsseo_render_dashboard_widget',
		null,
		null,
		'normal'
	);
}

add_action( 'wp_dashboard_setup', 'bsseo_add_dashboard_widget' );

/**
 * Gibt den Widget-Inhalt aus.
 */
function bsseo_render_dashboard_widget() {
	global $wpdb;

	$post_types = get_post_types( array( 'public' => true ), 'names' );
	$pt_in     = implode( "','", array_map( 'esc_sql', $post_types ) );

	$total = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('" . $pt_in . "') AND post_status = 'publish'"
	);

	$with_scores = (int) $wpdb->get_var(
		"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_bsseo_score_updated' AND pm.meta_value != '0'
		WHERE p.post_type IN ('" . $pt_in . "') AND p.post_status = 'publish'"
	);

	$not_analyzed = $total - $with_scores;
	if ( $not_analyzed < 0 ) {
		$not_analyzed = 0;
	}

	if ( $with_scores === 0 ) {
		echo '<p>' . esc_html__( 'Noch keine Beiträge analysiert. Nutze in der Beitrags- oder Seitenbearbeitung den Button „Jetzt analysieren“.', 'bsseo' ) . '</p>';
		return;
	}

	$avg_seo = (float) $wpdb->get_var(
		"SELECT AVG(CAST(pm.meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish' AND p.post_type IN ('" . $pt_in . "')
		WHERE pm.meta_key = '_bsseo_seo_score'"
	);
	$avg_ai = (float) $wpdb->get_var(
		"SELECT AVG(CAST(pm.meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_status = 'publish' AND p.post_type IN ('" . $pt_in . "')
		WHERE pm.meta_key = '_bsseo_ai_score'"
	);

	echo '<p><strong>' . esc_html__( 'Durchschnitt (veröffentlichte Beiträge/Seiten)', 'bsseo' ) . '</strong></p>';
	echo '<p>' . esc_html__( 'SEO-Score:', 'bsseo' ) . ' ' . esc_html( round( $avg_seo, 0 ) ) . ' &nbsp; ' . esc_html__( 'KI-Score:', 'bsseo' ) . ' ' . esc_html( round( $avg_ai, 0 ) ) . '</p>';
	echo '<p>' . esc_html( sprintf( _n( '%d Beitrag/Seite analysiert.', '%d Beiträge/Seiten analysiert.', $with_scores, 'bsseo' ), $with_scores ) );
	if ( $not_analyzed > 0 ) {
		echo ' ' . esc_html( sprintf( _n( '%d noch nicht analysiert.', '%d noch nicht analysiert.', $not_analyzed, 'bsseo' ), $not_analyzed ) );
	}
	echo '</p>';
}
