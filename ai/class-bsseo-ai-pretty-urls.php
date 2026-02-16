<?php
/**
 * BSseo – AI-Modul: Pretty URLs (Rewrites + Handler für /ai/meta/, /ai/page/, /ai/feed/, /llms.txt)
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert Rewrite-Regeln und liefert Response für Pretty-URL-Anfragen (gleiche Daten wie REST).
 */
class BSseo_AI_PrettyUrls {

	/** Query var für AI-Endpoint. */
	const QUERY_VAR = 'bsseo_ai';

	/**
	 * Registriert Rewrite-Regeln.
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule( '^ai/meta/?$', 'index.php?' . self::QUERY_VAR . '=meta', 'top' );
		add_rewrite_rule( '^ai/page/?$', 'index.php?' . self::QUERY_VAR . '=page', 'top' );
		add_rewrite_rule( '^ai/feed/?$', 'index.php?' . self::QUERY_VAR . '=feed', 'top' );
		add_rewrite_rule( '^ai/llms/?$', 'index.php?' . self::QUERY_VAR . '=llms', 'top' );
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=llms', 'top' );
	}

	/**
	 * Registriert Query Var.
	 *
	 * @param array $vars Bestehende Vars.
	 * @return array
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Template-Redirect: wenn bsseo_ai gesetzt, gleiche Logik wie REST aufrufen und exit.
	 */
	public static function handle_request() {
		$action = get_query_var( self::QUERY_VAR );
		if ( $action === '' || $action === false ) {
			return;
		}
		$action = sanitize_key( $action );
		if ( ! in_array( $action, array( 'meta', 'page', 'feed', 'llms' ), true ) ) {
			return;
		}

		// Zugriff prüfen (API-Key wenn erforderlich)
		$settings = bsseo_get_settings();
		$ai       = isset( $settings['ai'] ) && is_array( $settings['ai'] ) ? $settings['ai'] : array();
		if ( ! empty( $ai['require_api_key'] ) && ! empty( $ai['api_key'] ) ) {
			$key = isset( $_SERVER['HTTP_X_BSSEO_KEY'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_BSSEO_KEY'] ) ) : '';
			if ( $key === '' && isset( $_GET['api_key'] ) ) {
				$key = sanitize_text_field( wp_unslash( $_GET['api_key'] ) );
			}
			if ( ! is_string( $key ) || $key === '' || ! hash_equals( (string) $ai['api_key'], $key ) ) {
				status_header( 401 );
				header( 'Content-Type: application/json; charset=utf-8' );
				echo wp_json_encode( array( 'code' => 'rest_forbidden', 'message' => __( 'API-Key erforderlich (Header X-BSSEO-KEY).', 'bsseo' ) ) );
				exit;
			}
		}

		$request_params = array();
		if ( isset( $_GET['id'] ) ) {
			$request_params['id'] = absint( $_GET['id'] );
		}
		if ( isset( $_GET['slug'] ) ) {
			$request_params['slug'] = sanitize_text_field( wp_unslash( $_GET['slug'] ) );
		}
		if ( isset( $_GET['post_type'] ) ) {
			$request_params['post_type'] = is_array( $_GET['post_type'] ) ? array_map( 'sanitize_key', wp_unslash( $_GET['post_type'] ) ) : sanitize_key( wp_unslash( $_GET['post_type'] ) );
		}
		if ( isset( $_GET['path'] ) ) {
			$request_params['path'] = sanitize_text_field( wp_unslash( $_GET['path'] ) );
		}
		if ( isset( $_GET['url'] ) ) {
			$request_params['url'] = esc_url_raw( wp_unslash( $_GET['url'] ) );
		}
		if ( isset( $_GET['limit'] ) ) {
			$request_params['limit'] = absint( $_GET['limit'] );
		}
		if ( isset( $_GET['page'] ) ) {
			$request_params['page'] = absint( $_GET['page'] );
		}
		if ( isset( $_GET['orderby'] ) ) {
			$request_params['orderby'] = sanitize_key( wp_unslash( $_GET['orderby'] ) );
		}
		if ( isset( $_GET['order'] ) ) {
			$request_params['order'] = sanitize_text_field( wp_unslash( $_GET['order'] ) );
		}
		if ( isset( $_GET['category'] ) ) {
			$request_params['category'] = sanitize_key( wp_unslash( $_GET['category'] ) );
		}
		if ( isset( $_GET['tag'] ) ) {
			$request_params['tag'] = sanitize_key( wp_unslash( $_GET['tag'] ) );
		}

		$debug_headers = ! empty( $ai['debug_headers'] );
		$cache_ttl    = BSseo_AI_Cache::get_ttl();

		if ( $action === 'meta' ) {
			$key = BSseo_AI_Cache::build_key( 'meta', array() );
			if ( $cache_ttl > 0 ) {
				$cached = BSseo_AI_Cache::get( $key );
				if ( $cached !== false ) {
					self::send_json( $cached, 200, $debug_headers ? 'meta' : null, 'HIT' );
					return;
				}
			}
			$data = BSseo_AI_DataBuilder::get_meta();
			if ( $cache_ttl > 0 ) {
				BSseo_AI_Cache::set( $key, $data, $cache_ttl );
			}
			self::send_json( $data, 200, $debug_headers ? 'meta' : null, 'MISS' );
			return;
		}

		if ( $action === 'page' ) {
			if ( empty( $request_params['id'] ) && empty( $request_params['slug'] ) && empty( $request_params['path'] ) && empty( $request_params['url'] ) ) {
				status_header( 400 );
				header( 'Content-Type: application/json; charset=utf-8' );
				echo wp_json_encode( array( 'code' => 'rest_missing_param', 'message' => __( 'Mindestens einer der Parameter id, slug, path oder url ist erforderlich.', 'bsseo' ) ) );
				exit;
			}
			$key = BSseo_AI_Cache::build_key( 'page', $request_params );
			if ( $cache_ttl > 0 ) {
				$cached = BSseo_AI_Cache::get( $key );
				if ( $cached !== false ) {
					self::send_json( $cached, 200, $debug_headers ? 'page' : null, 'HIT' );
					return;
				}
			}
			$data = BSseo_AI_DataBuilder::get_page( $request_params );
			if ( $data === null ) {
				status_header( 404 );
				header( 'Content-Type: application/json; charset=utf-8' );
				echo wp_json_encode( array( 'code' => 'rest_not_found', 'message' => __( 'Seite nicht gefunden oder nicht freigegeben.', 'bsseo' ) ) );
				exit;
			}
			if ( $cache_ttl > 0 ) {
				BSseo_AI_Cache::set( $key, $data, $cache_ttl );
			}
			self::send_json( $data, 200, $debug_headers ? 'page' : null, 'MISS' );
			return;
		}

		if ( $action === 'feed' ) {
			$key = BSseo_AI_Cache::build_key( 'feed', $request_params );
			if ( $cache_ttl > 0 ) {
				$cached = BSseo_AI_Cache::get( $key );
				if ( $cached !== false ) {
					self::send_json( $cached, 200, $debug_headers ? 'feed' : null, 'HIT' );
					return;
				}
			}
			$data = BSseo_AI_DataBuilder::get_feed( $request_params );
			if ( $cache_ttl > 0 ) {
				BSseo_AI_Cache::set( $key, $data, $cache_ttl );
			}
			self::send_json( $data, 200, $debug_headers ? 'feed' : null, 'MISS' );
			return;
		}

		if ( $action === 'llms' ) {
			$key = BSseo_AI_Cache::build_key( 'llms', array() );
			if ( $cache_ttl > 0 ) {
				$cached = BSseo_AI_Cache::get( $key );
				if ( $cached !== false ) {
					self::send_text( $cached, 200, $debug_headers ? 'llms' : null, 'HIT' );
					return;
				}
			}
			$data = BSseo_AI_DataBuilder::get_llms_text();
			if ( $cache_ttl > 0 ) {
				BSseo_AI_Cache::set( $key, $data, $cache_ttl );
			}
			self::send_text( $data, 200, $debug_headers ? 'llms' : null, 'MISS' );
		}
	}

	/**
	 * Sendet JSON-Response und beendet.
	 *
	 * @param array       $data   Daten.
	 * @param int         $code   HTTP-Code.
	 * @param string|null $route  Für X-BSSEO-Route.
	 * @param string      $cache  HIT oder MISS.
	 */
	private static function send_json( $data, $code = 200, $route = null, $cache = '' ) {
		status_header( $code );
		header( 'Content-Type: application/json; charset=utf-8' );
		if ( $route !== null ) {
			header( 'X-BSSEO-Route: ai/' . $route );
			if ( $cache !== '' ) {
				header( 'X-BSSEO-Cache: ' . $cache );
			}
		}
		header( 'Cache-Control: public, max-age=' . ( BSseo_AI_Cache::get_ttl() ?: 3600 ) );
		echo wp_json_encode( $data );
		exit;
	}

	/**
	 * Sendet text/plain und beendet.
	 *
	 * @param string      $data   Text.
	 * @param int         $code   HTTP-Code.
	 * @param string|null $route  Für X-BSSEO-Route.
	 * @param string      $cache  HIT oder MISS.
	 */
	private static function send_text( $data, $code = 200, $route = null, $cache = '' ) {
		status_header( $code );
		header( 'Content-Type: text/plain; charset=utf-8' );
		if ( $route !== null ) {
			header( 'X-BSSEO-Route: ai/' . $route );
			if ( $cache !== '' ) {
				header( 'X-BSSEO-Cache: ' . $cache );
			}
		}
		header( 'Cache-Control: public, max-age=' . ( BSseo_AI_Cache::get_ttl() ?: 3600 ) );
		echo $data;
		exit;
	}
}
