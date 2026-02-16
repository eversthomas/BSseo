<?php
/**
 * BSseo – AI-Modul: Transient-Cache für Endpoint-Responses
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrapper für Transient-Caching der AI-Endpoint-Antworten.
 */
class BSseo_AI_Cache {

	/** @var string Präfix für Transient-Keys */
	const PREFIX = 'bsseo_ai_';

	/**
	 * Liest aus Cache.
	 *
	 * @param string $key Cache-Key (wird mit PREFIX versehen).
	 * @return mixed|false Cached value oder false.
	 */
	public static function get( $key ) {
		$full = self::PREFIX . $key;
		return get_transient( $full );
	}

	/**
	 * Schreibt in Cache.
	 *
	 * @param string $key   Cache-Key.
	 * @param mixed  $value Zu speichernder Wert (serialisierbar).
	 * @param int    $ttl   TTL in Sekunden.
	 * @return bool
	 */
	public static function set( $key, $value, $ttl = 0 ) {
		$full = self::PREFIX . $key;
		return set_transient( $full, $value, $ttl );
	}

	/**
	 * Prüft, ob Caching aktiv und TTL > 0 ist; liefert TTL aus Settings.
	 *
	 * @return int 0 wenn deaktiviert, sonst TTL in Sekunden.
	 */
	public static function get_ttl() {
		$settings = bsseo_get_settings();
		$ai       = isset( $settings['ai'] ) && is_array( $settings['ai'] ) ? $settings['ai'] : array();
		if ( empty( $ai['cache_enabled'] ) ) {
			return 0;
		}
		$ttl = isset( $ai['cache_ttl'] ) ? (int) $ai['cache_ttl'] : 6 * HOUR_IN_SECONDS;
		return max( 60, $ttl );
	}

	/**
	 * Erzeugt einen stabilen Cache-Key aus Endpoint und Parametern.
	 *
	 * @param string $route   z. B. meta, page, feed, llms.
	 * @param array  $params  Query-/Request-Parameter (sortiert für Konsistenz).
	 * @return string
	 */
	public static function build_key( $route, $params = array() ) {
		ksort( $params );
		$parts = array( $route );
		foreach ( $params as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$parts[] = $k . ':' . $v;
			} elseif ( is_array( $v ) ) {
				$parts[] = $k . ':' . wp_json_encode( $v );
			}
		}
		return md5( implode( '|', $parts ) );
	}
}
