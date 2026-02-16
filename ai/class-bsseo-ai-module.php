<?php
/**
 * BSseo – AI-Modul: Feature-Flag und Bootstrap (REST + Pretty URLs)
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lädt das AI-Modul nur wenn aktiviert; registriert REST und ggf. Pretty URLs.
 */
class BSseo_AI_Module {

	/**
	 * Ob das Modul aktiv ist (Endpoints + ggf. Pretty URLs).
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = bsseo_get_settings();
		$ai       = isset( $settings['ai'] ) && is_array( $settings['ai'] ) ? $settings['ai'] : array();
		return ! empty( $ai['enabled'] );
	}

	/**
	 * Ob Pretty URLs aktiv sind.
	 *
	 * @return bool
	 */
	public static function is_pretty_urls_enabled() {
		if ( ! self::is_enabled() ) {
			return false;
		}
		$settings = bsseo_get_settings();
		$ai       = isset( $settings['ai'] ) && is_array( $settings['ai'] ) ? $settings['ai'] : array();
		return ! empty( $ai['pretty_urls'] );
	}

	/**
	 * Bootstrap: lädt Klassen und hängt Hooks nur wenn Modul aktiv.
	 */
	public static function bootstrap() {
		if ( ! self::is_enabled() ) {
			return;
		}
		$path = defined( 'BSSEO_PATH' ) ? BSSEO_PATH : plugin_dir_path( dirname( __FILE__ ) );
		require_once $path . 'ai/class-bsseo-ai-cache.php';
		require_once $path . 'ai/class-bsseo-ai-data-builder.php';
		require_once $path . 'ai/class-bsseo-ai-routes.php';
		require_once $path . 'ai/class-bsseo-ai-pretty-urls.php';

		add_action( 'rest_api_init', array( 'BSseo_AI_Routes', 'register' ) );

		if ( self::is_pretty_urls_enabled() ) {
			add_action( 'init', array( 'BSseo_AI_PrettyUrls', 'add_rewrite_rules' ), 5 );
			add_filter( 'query_vars', array( 'BSseo_AI_PrettyUrls', 'add_query_vars' ) );
			add_action( 'template_redirect', array( 'BSseo_AI_PrettyUrls', 'handle_request' ), 1 );
		}
	}
}
