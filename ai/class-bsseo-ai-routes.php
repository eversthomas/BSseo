<?php
/**
 * BSseo – AI-Modul: REST API Endpoints (bsseo/v1/ai/*)
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert die REST-Routen für ai/meta, ai/page, ai/feed, ai/llms.
 */
class BSseo_AI_Routes {

	/** Namespace */
	const NAMESPACE = 'bsseo/v1';

	/**
	 * Registriert alle Routen.
	 */
	public static function register() {
		register_rest_route( self::NAMESPACE, '/ai/meta', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'serve_meta' ),
			'permission_callback' => array( __CLASS__, 'permission_check' ),
		) );
		register_rest_route( self::NAMESPACE, '/ai/page', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'serve_page' ),
			'permission_callback' => array( __CLASS__, 'permission_check' ),
			'args'                 => array(
				'id'        => array( 'validate_callback' => function ( $v ) { return $v === '' || absint( $v ) >= 0; } ),
				'slug'      => array(),
				'post_type' => array(),
				'path'      => array(),
				'url'       => array(),
			),
		) );
		register_rest_route( self::NAMESPACE, '/ai/feed', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'serve_feed' ),
			'permission_callback' => array( __CLASS__, 'permission_check' ),
			'args'                 => array(
				'limit'     => array(),
				'page'      => array(),
				'orderby'   => array(),
				'order'     => array(),
				'post_type' => array(),
				'category'  => array(),
				'tag'       => array(),
			),
		) );
		register_rest_route( self::NAMESPACE, '/ai/llms', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'serve_llms' ),
			'permission_callback' => array( __CLASS__, 'permission_check' ),
		) );
	}

	/**
	 * Prüft Zugriff: öffentlich oder API-Key.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public static function permission_check( $request ) {
		$settings = bsseo_get_settings();
		$ai       = isset( $settings['ai'] ) && is_array( $settings['ai'] ) ? $settings['ai'] : array();
		if ( empty( $ai['require_api_key'] ) || empty( $ai['api_key'] ) ) {
			return true;
		}
		$key = $request->get_header( 'X-BSSEO-KEY' );
		if ( $key === null ) {
			$key = $request->get_param( 'api_key' );
		}
		if ( is_string( $key ) && $key !== '' && hash_equals( (string) $ai['api_key'], $key ) ) {
			return true;
		}
		return new WP_Error( 'rest_forbidden', __( 'API-Key erforderlich (Header X-BSSEO-KEY).', 'bsseo' ), array( 'status' => 401 ) );
	}

	/**
	 * Fügt Debug-Header hinzu, wenn aktiviert.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param string          $route    Route (meta, page, feed, llms).
	 * @param string          $cache    HIT oder MISS.
	 */
	public static function maybe_debug_headers( $response, $route, $cache = '' ) {
		$settings = bsseo_get_settings();
		$ai       = isset( $settings['ai'] ) && is_array( $settings['ai'] ) ? $settings['ai'] : array();
		if ( empty( $ai['debug_headers'] ) ) {
			return;
		}
		$response->header( 'X-BSSEO-Route', 'ai/' . $route );
		if ( $cache !== '' ) {
			$response->header( 'X-BSSEO-Cache', $cache );
		}
	}

	/**
	 * GET ai/meta.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function serve_meta( $request ) {
		$ttl = BSseo_AI_Cache::get_ttl();
		$key = BSseo_AI_Cache::build_key( 'meta', array() );
		if ( $ttl > 0 ) {
			$cached = BSseo_AI_Cache::get( $key );
			if ( $cached !== false ) {
				$response = new WP_REST_Response( $cached, 200 );
				self::maybe_debug_headers( $response, 'meta', 'HIT' );
				return $response;
			}
		}
		$data = BSseo_AI_DataBuilder::get_meta();
		if ( $ttl > 0 ) {
			BSseo_AI_Cache::set( $key, $data, $ttl );
		}
		$response = new WP_REST_Response( $data, 200 );
		self::maybe_debug_headers( $response, 'meta', 'MISS' );
		return $response;
	}

	/**
	 * GET ai/page.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function serve_page( $request ) {
		$params = array(
			'id'        => $request->get_param( 'id' ),
			'slug'      => $request->get_param( 'slug' ),
			'post_type' => $request->get_param( 'post_type' ),
			'path'      => $request->get_param( 'path' ),
			'url'       => $request->get_param( 'url' ),
		);
		$params = array_filter( $params, function ( $v ) { return $v !== null && $v !== ''; } );
		if ( empty( $params ) ) {
			return new WP_Error( 'rest_missing_param', __( 'Mindestens einer der Parameter id, slug, path oder url ist erforderlich.', 'bsseo' ), array( 'status' => 400 ) );
		}
		$ttl = BSseo_AI_Cache::get_ttl();
		$key = BSseo_AI_Cache::build_key( 'page', $params );
		if ( $ttl > 0 ) {
			$cached = BSseo_AI_Cache::get( $key );
			if ( $cached !== false ) {
				$response = new WP_REST_Response( $cached, 200 );
				self::maybe_debug_headers( $response, 'page', 'HIT' );
				return $response;
			}
		}
		$data = BSseo_AI_DataBuilder::get_page( $params );
		if ( $data === null ) {
			return new WP_Error( 'rest_not_found', __( 'Seite nicht gefunden oder nicht freigegeben.', 'bsseo' ), array( 'status' => 404 ) );
		}
		if ( $ttl > 0 ) {
			BSseo_AI_Cache::set( $key, $data, $ttl );
		}
		$response = new WP_REST_Response( $data, 200 );
		self::maybe_debug_headers( $response, 'page', 'MISS' );
		return $response;
	}

	/**
	 * GET ai/feed.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function serve_feed( $request ) {
		$post_type_param = $request->get_param( 'post_type' );
		if ( is_array( $post_type_param ) ) {
			$post_type_param = array_filter( $post_type_param );
		} elseif ( $post_type_param !== null && $post_type_param !== '' ) {
			$post_type_param = array( $post_type_param );
		} else {
			$post_type_param = null;
		}
		$params = array(
			'limit'     => $request->get_param( 'limit' ),
			'page'      => $request->get_param( 'page' ),
			'orderby'   => $request->get_param( 'orderby' ),
			'order'     => $request->get_param( 'order' ),
			'post_type' => $post_type_param,
			'category'  => $request->get_param( 'category' ),
			'tag'       => $request->get_param( 'tag' ),
		);
		$params = array_filter( $params, function ( $v ) { return $v !== null && $v !== '' && $v !== array(); } );
		$ttl = BSseo_AI_Cache::get_ttl();
		$key = BSseo_AI_Cache::build_key( 'feed', $params );
		if ( $ttl > 0 ) {
			$cached = BSseo_AI_Cache::get( $key );
			if ( $cached !== false ) {
				$response = new WP_REST_Response( $cached, 200 );
				self::maybe_debug_headers( $response, 'feed', 'HIT' );
				return $response;
			}
		}
		$data = BSseo_AI_DataBuilder::get_feed( $params );
		if ( $ttl > 0 ) {
			BSseo_AI_Cache::set( $key, $data, $ttl );
		}
		$response = new WP_REST_Response( $data, 200 );
		self::maybe_debug_headers( $response, 'feed', 'MISS' );
		return $response;
	}

	/**
	 * GET ai/llms (text/plain).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function serve_llms( $request ) {
		$ttl = BSseo_AI_Cache::get_ttl();
		$key = BSseo_AI_Cache::build_key( 'llms', array() );
		if ( $ttl > 0 ) {
			$cached = BSseo_AI_Cache::get( $key );
			if ( $cached !== false ) {
				$response = new WP_REST_Response( $cached, 200 );
				$response->header( 'Content-Type', 'text/plain; charset=utf-8' );
				self::maybe_debug_headers( $response, 'llms', 'HIT' );
				return $response;
			}
		}
		$data = BSseo_AI_DataBuilder::get_llms_text();
		if ( $ttl > 0 ) {
			BSseo_AI_Cache::set( $key, $data, $ttl );
		}
		$response = new WP_REST_Response( $data, 200 );
		$response->header( 'Content-Type', 'text/plain; charset=utf-8' );
		self::maybe_debug_headers( $response, 'llms', 'MISS' );
		return $response;
	}
}
