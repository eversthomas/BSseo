<?php
/**
 * BSseo – Zentrale Hilfsfunktionen
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ob BSseo überhaupt Head-Ausgabe (Meta, Schema, Canonical, Robots, Title) machen soll.
 * Master-Toggle „BSseo-Output im Frontend aktiv“ (head_output_active).
 *
 * @return bool
 */
function bsseo_should_output_head() {
	$settings = bsseo_get_settings();
	return ! empty( $settings['toggles']['head_output_active'] );
}

/**
 * Zählt Wörter Unicode-sicher (DE/Umlaute, ß). DEVELOPMENT-2.md.
 *
 * @param string $text Roher Text.
 * @return int Anzahl Wörter.
 */
function bsseo_count_words_unicode( $text ) {
	if ( ! is_string( $text ) || $text === '' ) {
		return 0;
	}
	$words = preg_split( '/\PL+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	return $words ? count( $words ) : 0;
}

/**
 * Normalisiert Text für Meta-Description (DEVELOPMENT-SEO-Verfeinerung §3.1).
 * Shortcodes/Block-Markup raus, Whitespace normalisiert, Länge soft bei Wortgrenze.
 *
 * @param string $text    Roher Text (kann HTML/Shortcodes enthalten).
 * @param int    $max_len Maximale Länge (soft); Standard 155.
 * @return string Bereinigter Text, ggf. mit '…' am Ende.
 */
function bsseo_normalize_meta_description( $text, $max_len = 155 ) {
	if ( ! is_string( $text ) || $text === '' ) {
		return '';
	}
	$text = strip_shortcodes( $text );
	$text = wp_strip_all_tags( $text );
	$text = preg_replace( '/\s+/', ' ', $text );
	$text = trim( $text );
	if ( $text === '' ) {
		return '';
	}
	$len = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
	if ( $len <= $max_len ) {
		return $text;
	}
	$cut = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max_len ) : substr( $text, 0, $max_len );
	$last_space = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );
	if ( $last_space !== false && $last_space > (int) ( $max_len * 0.6 ) ) {
		$cut = function_exists( 'mb_substr' ) ? mb_substr( $cut, 0, $last_space ) : substr( $cut, 0, $last_space );
	}
	return rtrim( $cut, " \t\n\r\0\x0B," ) . '…';
}
