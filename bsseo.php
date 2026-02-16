<?php
/**
 * Plugin Name: BSseo
 * Plugin URI:  https://github.com/eversthomas/BSseo
 * Description: Schlankes SEO-Plugin für klassische & KI-Suchmaschinen. Title, Description, Canonical, Robots, Schema, Sitemap. Open Source (GPL v3).
 * Version:     1.0.0
 * Author:      Tom Evers
 * Author URI:  https://bezugssysteme.de
 * License:     GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: bsseo
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BSSEO_VERSION', '1.0.0' );
define( 'BSSEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSSEO_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, 'bsseo_activation' );
function bsseo_activation() {
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'bsseo_deactivation' );
function bsseo_deactivation() {
	flush_rewrite_rules();
}

add_action( 'update_option_bsseo_settings', 'bsseo_ai_maybe_flush_rewrite_on_save', 10, 3 );
function bsseo_ai_maybe_flush_rewrite_on_save( $old_value, $value, $option ) {
	if ( ! is_array( $old_value ) || ! is_array( $value ) ) {
		return;
	}
	$old_pretty = ! empty( $old_value['ai']['pretty_urls'] );
	$new_pretty = ! empty( $value['ai']['pretty_urls'] );
	if ( $old_pretty !== $new_pretty ) {
		if ( $new_pretty ) {
			require_once BSSEO_PATH . 'ai/class-bsseo-ai-pretty-urls.php';
			BSseo_AI_PrettyUrls::add_rewrite_rules();
		}
		flush_rewrite_rules();
	}
}

add_action( 'init', 'bsseo_load_textdomain', 0 );
function bsseo_load_textdomain() {
	load_plugin_textdomain(
		'bsseo',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}

// Defaults und Settings-Zugriff (immer laden)
require_once BSSEO_PATH . 'includes/defaults.php';
require_once BSSEO_PATH . 'includes/helpers.php';

// Meta-Registrierung (init)
add_action( 'init', 'bsseo_register_meta', 5 );

function bsseo_register_meta() {
	require_once BSSEO_PATH . 'includes/register-meta.php';
	bsseo_register_post_meta();
}

// Konflikterkennung (immer, keine Ausgabe in Phase 1)
require_once BSSEO_PATH . 'core/conflict-guard.php';

// Content-Analyzer (AJAX aus Admin + Cron)
require_once BSSEO_PATH . 'core/content-analyzer.php';

// Admin: nur im Backend
if ( is_admin() ) {
	require_once BSSEO_PATH . 'admin/help-texts.php';
	require_once BSSEO_PATH . 'admin/settings-page.php';
	require_once BSSEO_PATH . 'admin/metabox.php';
	require_once BSSEO_PATH . 'admin/list-columns.php';
	require_once BSSEO_PATH . 'admin/dashboard-widget.php';
}

// Frontend: Meta Manager, Schema, Sitemap
if ( ! is_admin() ) {
	require_once BSSEO_PATH . 'includes/templates.php';
	require_once BSSEO_PATH . 'core/meta-manager.php';
	require_once BSSEO_PATH . 'core/schema-generator.php';
	require_once BSSEO_PATH . 'core/sitemap-enhancer.php';
}

// AI/LLM Endpoints (nur wenn Modul aktiv)
require_once BSSEO_PATH . 'ai/class-bsseo-ai-module.php';
add_action( 'init', array( 'BSseo_AI_Module', 'bootstrap' ), 10 );
