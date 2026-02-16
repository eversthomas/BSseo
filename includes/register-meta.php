<?php
/**
 * BSseo – Registrierung aller Post-Meta-Keys (DEVELOPMENT.md §6.1, §7)
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert alle BSseo-Post-Meta-Keys für öffentliche Post-Types.
 */
function bsseo_register_post_meta() {
	$post_types = get_post_types( array( 'public' => true ), 'names' );

	foreach ( $post_types as $post_type ) {
		bsseo_register_meta_for_post_type( $post_type );
	}
}

/**
 * Registriert alle Meta-Keys für einen Post-Type.
 *
 * @param string $post_type Post type name.
 */
function bsseo_register_meta_for_post_type( $post_type ) {
	$common = array(
		'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
			// Nicht von $allowed abhängen – Gutenberg speichert Meta über REST;
			// wenn Core $allowed hier false setzt, entsteht sonst 403.
			return current_user_can( 'edit_post', $post_id );
		},
		'show_in_rest'  => true,
	);

	$string_meta = array(
		'_bsseo_title'       => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'single'            => true,
		),
		'_bsseo_description' => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'single'            => true,
		),
		'_bsseo_focus_keyword' => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'single'            => true,
		),
		'_bsseo_canonical'   => array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'single'            => true,
		),
		'_bsseo_schema_type' => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'single'            => true,
		),
	);

	foreach ( $string_meta as $key => $args ) {
		register_post_meta( $post_type, $key, array_merge( $args, $common ) );
	}

	// Noindex / Nofollow: 0 oder 1
	register_post_meta( $post_type, '_bsseo_noindex', array_merge( $common, array(
		'type'              => 'integer',
		'sanitize_callback' => function ( $value ) {
			return absint( $value ) ? 1 : 0;
		},
		'single'            => true,
	) ) );

	register_post_meta( $post_type, '_bsseo_nofollow', array_merge( $common, array(
		'type'              => 'integer',
		'sanitize_callback' => function ( $value ) {
			return absint( $value ) ? 1 : 0;
		},
		'single'            => true,
	) ) );

	// OG-Bild: Attachment-ID
	register_post_meta( $post_type, '_bsseo_og_image_id', array_merge( $common, array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'single'            => true,
	) ) );

	// Quellen: Array von {title, url} – REST-Schema für Array erforderlich (WP 5.3+)
	register_post_meta( $post_type, '_bsseo_sources', array(
		'type'              => 'array',
		'sanitize_callback' => 'bsseo_sanitize_sources',
		'single'            => true,
		'auth_callback'     => $common['auth_callback'],
		'show_in_rest'      => array(
			'schema' => array(
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'title' => array( 'type' => 'string' ),
						'url'   => array( 'type' => 'string' ),
					),
				),
			),
		),
	) );

	// Analyse-Cache
	register_post_meta( $post_type, '_bsseo_seo_score', array_merge( $common, array(
		'type'              => 'integer',
		'sanitize_callback' => function ( $value ) {
			return min( 100, max( 0, absint( $value ) ) );
		},
		'single'            => true,
	) ) );

	register_post_meta( $post_type, '_bsseo_ai_score', array_merge( $common, array(
		'type'              => 'integer',
		'sanitize_callback' => function ( $value ) {
			return min( 100, max( 0, absint( $value ) ) );
		},
		'single'            => true,
	) ) );

	register_post_meta( $post_type, '_bsseo_score_updated', array_merge( $common, array(
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'single'            => true,
	) ) );

	// Analyse-Checks: Array (Detail für Modal) – REST-Schema für Array erforderlich (WP 5.3+)
	register_post_meta( $post_type, '_bsseo_checks', array(
		'type'              => 'array',
		'sanitize_callback' => 'bsseo_sanitize_checks',
		'single'            => true,
		'auth_callback'     => $common['auth_callback'],
		'show_in_rest'      => array(
			'schema' => array(
				'items' => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			),
		),
	) );
}

/**
 * Sanitizes _bsseo_sources array (items: title, url).
 *
 * @param mixed $value Raw value.
 * @return array
 */
function bsseo_sanitize_sources( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	$out = array();
	foreach ( $value as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$out[] = array(
			'title' => isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '',
			'url'   => isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '',
		);
	}
	return $out;
}

/**
 * Sanitizes _bsseo_checks array (detail for modal).
 *
 * @param mixed $value Raw value.
 * @return array
 */
function bsseo_sanitize_checks( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	return $value;
}
