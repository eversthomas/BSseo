<?php
/**
 * BSseo – Meta & Head (Title, Robots, Canonical, Meta Description)
 * DEVELOPMENT.md §4 A, §9. Nur aktiv wenn entsprechende Toggles an.
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dokumenttitel (pre_get_document_title).
 */
function bsseo_filter_document_title( $title ) {
	if ( ! bsseo_should_output_head() ) {
		return $title;
	}
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_title'] ) ) {
		return $title;
	}

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		$custom  = get_post_meta( $post_id, '_bsseo_title', true );
		if ( is_string( $custom ) && $custom !== '' ) {
			return bsseo_build_document_title( $custom );
		}
		// DEVELOPMENT-3 Phase 1: Bei ausgeschaltetem Fallback WordPress-Standard durchlassen.
		if ( empty( $settings['toggles']['use_wp_fallback'] ) ) {
			return $title;
		}
		return bsseo_build_document_title( get_the_title( $post_id ) );
	}

	if ( is_front_page() ) {
		$home_title = $settings['home_title'] ?? '';
		if ( $home_title !== '' ) {
			return bsseo_build_document_title( $home_title );
		}
	}

	return $title;
}

add_filter( 'pre_get_document_title', 'bsseo_filter_document_title', 10, 1 );

/**
 * Stellt sicher, dass ein <title>-Tag im Head ausgegeben wird (früh in wp_head).
 * Entfernt Core _wp_render_title_tag und gibt den gefilterten Titel aus, damit der Title
 * auch bei Themes, die den Title-Tag entfernen, erscheint.
 */
function bsseo_ensure_title_tag() {
	if ( ! bsseo_should_output_head() ) {
		return;
	}
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_title'] ) ) {
		return;
	}
	remove_action( 'wp_head', '_wp_render_title_tag', 1 );
	$title = wp_get_document_title();
	if ( $title !== '' ) {
		echo '<title>' . esc_html( $title ) . "</title>\n";
	}
}
add_action( 'wp_head', 'bsseo_ensure_title_tag', 0 );

/**
 * Robots-Meta über Core wp_robots (DEVELOPMENT-SEO-Verfeinerung §3.3).
 * Vollständiges Tag: index, follow, max-image-preview:large (bzw. noindex, nofollow bei Dev/Post).
 */
function bsseo_filter_wp_robots( $robots ) {
	if ( ! bsseo_should_output_head() ) {
		return $robots;
	}
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_robots'] ) ) {
		return $robots;
	}

	// Dev/Staging: Konstante BSSEO_NOINDEX_ENV erzwingt noindex, nofollow.
	if ( defined( 'BSSEO_NOINDEX_ENV' ) && BSSEO_NOINDEX_ENV ) {
		$robots['noindex']  = true;
		$robots['nofollow']  = true;
		return $robots;
	}

	if ( ! is_singular() ) {
		// Außerhalb von Singular: explizit index, follow + max-image-preview.
		$robots['index']             = true;
		$robots['follow']            = true;
		$robots['max-image-preview'] = 'large';
		return $robots;
	}

	$post_id  = get_queried_object_id();
	$noindex  = (int) get_post_meta( $post_id, '_bsseo_noindex', true );
	$nofollow = (int) get_post_meta( $post_id, '_bsseo_nofollow', true );

	if ( $noindex ) {
		$robots['noindex'] = true;
	}
	if ( $nofollow ) {
		$robots['nofollow'] = true;
	}

	// Vollständiges Tag: wenn nicht noindex/nofollow, explizit index, follow setzen.
	if ( empty( $robots['noindex'] ) ) {
		$robots['index'] = true;
	}
	if ( empty( $robots['nofollow'] ) ) {
		$robots['follow'] = true;
	}
	if ( ! isset( $robots['max-image-preview'] ) ) {
		$robots['max-image-preview'] = 'large';
	}

	return $robots;
}

add_filter( 'wp_robots', 'bsseo_filter_wp_robots', 10, 1 );

/**
 * Entfernt rel_canonical aus wp_head, wenn Toggle „Canonical ausgeben“ aus ist (DEVELOPMENT-2.md).
 */
function bsseo_maybe_remove_rel_canonical() {
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_canonical'] ) ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}
add_action( 'init', 'bsseo_maybe_remove_rel_canonical', 20 );

/**
 * Wenn Custom Canonical gesetzt ist: Core rel_canonical entfernen (DEVELOPMENT-SEO-Verfeinerung §3.2).
 * Laufzeit: wp_head, damit is_singular() und Post-Meta verfügbar sind.
 */
function bsseo_maybe_remove_rel_canonical_for_custom() {
	if ( ! bsseo_should_output_head() ) {
		return;
	}
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_canonical'] ) || ! is_singular() ) {
		return;
	}
	$post_id = get_queried_object_id();
	$custom  = get_post_meta( $post_id, '_bsseo_canonical', true );
	if ( is_string( $custom ) && $custom !== '' && esc_url_raw( $custom ) === $custom ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}
add_action( 'wp_head', 'bsseo_maybe_remove_rel_canonical_for_custom', 1 );

/**
 * Gibt eigenen Canonical-Link aus, wenn Custom Canonical gesetzt (§3.2 robuste Strategie).
 */
function bsseo_output_canonical() {
	if ( ! bsseo_should_output_head() ) {
		return;
	}
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_canonical'] ) || ! is_singular() ) {
		return;
	}
	$post_id = get_queried_object_id();
	$custom  = get_post_meta( $post_id, '_bsseo_canonical', true );
	if ( ! is_string( $custom ) || $custom === '' || esc_url_raw( $custom ) !== $custom ) {
		return;
	}
	echo '<link rel="canonical" href="' . esc_url( $custom ) . '" />' . "\n";
}
add_action( 'wp_head', 'bsseo_output_canonical', 1 );

/**
 * Canonical-URL (get_canonical_url) – liefert Custom-URL für andere Code-Stellen.
 */
function bsseo_filter_canonical_url( $canonical_url, $post ) {
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_canonical'] ) ) {
		return $canonical_url;
	}

	if ( ! $post instanceof WP_Post ) {
		return $canonical_url;
	}

	$custom = get_post_meta( $post->ID, '_bsseo_canonical', true );
	if ( is_string( $custom ) && $custom !== '' && esc_url_raw( $custom ) === $custom ) {
		return $custom;
	}

	return $canonical_url;
}
add_filter( 'get_canonical_url', 'bsseo_filter_canonical_url', 10, 2 );

/**
 * Meta-Description in wp_head (DEVELOPMENT-SEO-Verfeinerung §3.1).
 * Priorität: _bsseo_description → Excerpt → erster Text aus Content. Plain-Text, 120–155 Zeichen (soft).
 */
function bsseo_output_meta_description() {
	if ( ! bsseo_should_output_head() ) {
		return;
	}
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_meta_description'] ) ) {
		return;
	}

	$content = '';

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		$custom  = get_post_meta( $post_id, '_bsseo_description', true );
		if ( is_string( $custom ) && $custom !== '' ) {
			$content = $custom;
		} else {
			if ( empty( $settings['toggles']['use_wp_fallback'] ) ) {
				return;
			}
			$post = get_post( $post_id );
			if ( $post ) {
				$content = has_excerpt( $post_id )
					? get_the_excerpt( $post_id )
					: wp_trim_words( strip_shortcodes( $post->post_content ), 35 );
			}
		}
	} elseif ( is_front_page() ) {
		$content = $settings['home_description'] ?? '';
	}

	$content = bsseo_normalize_meta_description( is_string( $content ) ? $content : '', 155 );
	if ( $content === '' ) {
		return;
	}

	echo '<meta name="description" content="' . esc_attr( $content ) . '" />' . "\n";
}

add_action( 'wp_head', 'bsseo_output_meta_description', 8 );

/**
 * Open Graph und Twitter Card Meta-Tags (DEVELOPMENT-SEO-Verfeinerung Phase 2).
 * OG: type, title, description, url, image, site_name, locale; bei Posts article:published_time/modified_time.
 * Twitter: card, title, description, image. Toggle + Konflikt-Check.
 */
function bsseo_output_og_twitter() {
	if ( ! bsseo_should_output_head() ) {
		return;
	}
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_og_twitter'] ) ) {
		return;
	}
	if ( function_exists( 'bsseo_has_seo_conflict' ) && bsseo_has_seo_conflict() ) {
		return;
	}

	$title         = '';
	$description   = '';
	$url           = '';
	$type          = 'website';
	$image_url     = '';
	$date_published = '';
	$date_modified  = '';

	if ( is_singular() ) {
		$post_id     = get_queried_object_id();
		$post        = get_post( $post_id );
		$use_fallback = ! empty( $settings['toggles']['use_wp_fallback'] );
		$title       = get_post_meta( $post_id, '_bsseo_title', true );
		if ( ! is_string( $title ) || $title === '' ) {
			$title = $use_fallback && $post ? get_the_title( $post ) : '';
		}
		$description = get_post_meta( $post_id, '_bsseo_description', true );
		if ( ! is_string( $description ) || $description === '' ) {
			$description = $use_fallback && $post && has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : ( $use_fallback && $post ? wp_trim_words( strip_shortcodes( $post->post_content ), 35 ) : '' );
		}
		$canon = get_post_meta( $post_id, '_bsseo_canonical', true );
		$url   = is_string( $canon ) && $canon !== '' ? $canon : get_permalink( $post_id );
		$type  = 'article';

		if ( $post ) {
			$date_published = get_the_date( 'c', $post );
			$date_modified  = get_the_modified_date( 'c', $post );
		}

		$og_image_id = (int) get_post_meta( $post_id, '_bsseo_og_image_id', true );
		if ( $og_image_id > 0 ) {
			$image_url = wp_get_attachment_image_url( $og_image_id, 'large' );
		}
		if ( ! $image_url && $use_fallback && $post ) {
			$image_url = get_the_post_thumbnail_url( $post_id, 'large' );
		}
	} elseif ( is_front_page() ) {
		$title       = $settings['home_title'] ?? '';
		if ( $title === '' ) {
			$title = wp_get_document_title();
		}
		$description = $settings['home_description'] ?? '';
		$url         = home_url( '/' );
		$image_url   = '';
	} else {
		return;
	}

	$title       = is_string( $title ) ? wp_strip_all_tags( $title ) : '';
	$description = is_string( $description ) ? wp_strip_all_tags( $description ) : '';
	$description = preg_replace( '/\s+/', ' ', trim( $description ) );
	$description = wp_html_excerpt( $description, 200, '…' );
	$url         = is_string( $url ) ? esc_url_raw( $url ) : '';

	if ( $title === '' && ( ! is_singular() || ! empty( $settings['toggles']['use_wp_fallback'] ) ) ) {
		$title = wp_get_document_title();
	}

	$site_name = ( $settings['site_name_override'] ?? '' ) !== ''
		? $settings['site_name_override']
		: get_bloginfo( 'name', 'display' );
	// OG üblich: de_DE (nicht nur "de"); konsistent mit get_locale() / JSON-LD inLanguage.
	$locale = get_locale();

	// Open Graph
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
	if ( $site_name !== '' ) {
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
	}
	if ( $locale !== '' ) {
		echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '" />' . "\n";
	}
	if ( $title !== '' ) {
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	}
	if ( $description !== '' ) {
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
	}
	if ( $image_url !== '' ) {
		echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
	}
	if ( $type === 'article' && $date_published !== '' ) {
		echo '<meta property="article:published_time" content="' . esc_attr( $date_published ) . '" />' . "\n";
	}
	if ( $type === 'article' && $date_modified !== '' ) {
		echo '<meta property="article:modified_time" content="' . esc_attr( $date_modified ) . '" />' . "\n";
	}

	// Twitter Card (twitter:image wie og:image – §4.2)
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	if ( $title !== '' ) {
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
	}
	if ( $description !== '' ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
	}
	if ( $image_url !== '' ) {
		echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'bsseo_output_og_twitter', 9 );

/**
 * Debug-Kommentar im Head (nur bei WP_DEBUG und aktivem BSseo-Output).
 */
function bsseo_head_debug_comment() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && bsseo_should_output_head() ) {
		echo "<!-- BSSEO: head rendered -->\n";
	}
}
add_action( 'wp_head', 'bsseo_head_debug_comment', 99 );
