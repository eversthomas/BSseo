<?php
/**
 * BSseo – AI-Modul: Zentrale Datenlogik für meta, page, feed, llms (REST + Pretty URLs)
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut die Response-Daten für alle AI-Endpoints. Einzige Quelle für REST und Pretty URLs.
 */
class BSseo_AI_DataBuilder {

	/** Maximale Zeichen für trimmed content (ai/page). */
	const TRIM_CONTENT_CHARS = 2000;

	/** Erlaubte orderby-Werte für Feed. */
	const ORDERBY_WHITELIST = array( 'modified', 'date', 'title' );

	/** Erlaubte order-Werte. */
	const ORDER_WHITELIST = array( 'desc', 'asc' );

	/**
	 * Liest AI-Settings (nur ai-Teil).
	 *
	 * @return array
	 */
	public static function get_ai_settings() {
		$settings = bsseo_get_settings();
		return isset( $settings['ai'] ) && is_array( $settings['ai'] ) ? $settings['ai'] : array();
	}

	/**
	 * GET ai/meta – Site-weite Metadaten.
	 *
	 * @return array
	 */
	public static function get_meta() {
		$home = home_url( '/' );
		$rest = rest_url( 'bsseo/v1/ai/' );
		$ai   = self::get_ai_settings();
		$post_types = isset( $ai['post_types'] ) && is_array( $ai['post_types'] ) ? $ai['post_types'] : array( 'post', 'page' );
		if ( empty( $post_types ) && function_exists( 'bsseo_ai_default_post_types' ) ) {
			$post_types = bsseo_ai_default_post_types();
		}
		return array(
			'site' => array(
				'name'        => get_bloginfo( 'name', 'display' ),
				'description' => get_bloginfo( 'description', 'display' ),
				'home_url'    => $home,
				'language'    => function_exists( 'determine_locale' ) ? determine_locale() : get_locale(),
				'timezone'    => wp_timezone_string(),
				'wp'          => array( 'version' => get_bloginfo( 'version' ) ),
				'plugin'      => array(
					'name'    => 'BSseo',
					'version' => defined( 'BSSEO_VERSION' ) ? BSSEO_VERSION : '',
				),
			),
			'capabilities' => array(
				'endpoints' => array(
					'meta' => $rest . 'meta',
					'page' => $rest . 'page',
					'feed' => $rest . 'feed',
					'llms' => $rest . 'llms',
				),
			),
			'defaults' => array(
				'post_types'         => $post_types,
				'feed_limit_default' => (int) ( $ai['feed_limit_default'] ?? 50 ),
				'feed_limit_max'     => (int) ( $ai['feed_limit_max'] ?? 200 ),
			),
			'notes' => array(
				__( 'Noindex-Beiträge werden bei aktivierter Einstellung nicht im Feed gelistet.', 'bsseo' ),
				__( 'Passwortgeschützte Inhalte werden nie ausgeliefert.', 'bsseo' ),
			),
			'generated_at' => current_time( 'c' ),
		);
	}

	/**
	 * Findet einen Post anhand von id, slug+post_type, path oder url (gleiche Domain).
	 *
	 * @param array $params id, slug, post_type, path, url.
	 * @return WP_Post|null
	 */
	public static function resolve_post( $params ) {
		$id = isset( $params['id'] ) ? absint( $params['id'] ) : 0;
		if ( $id > 0 ) {
			$post = get_post( $id );
			return ( $post && $post->post_type ) ? $post : null;
		}

		$slug = isset( $params['slug'] ) ? sanitize_title( wp_unslash( $params['slug'] ) ) : '';
		$pt   = isset( $params['post_type'] ) ? sanitize_key( wp_unslash( $params['post_type'] ) ) : 'post';
		if ( $slug !== '' ) {
			$posts = get_posts( array(
				'name'        => $slug,
				'post_type'   => $pt,
				'post_status' => 'publish',
				'numberposts' => 1,
			) );
			if ( ! empty( $posts[0] ) ) {
				return $posts[0];
			}
		}

		$path = isset( $params['path'] ) ? wp_unslash( $params['path'] ) : '';
		if ( is_string( $path ) && $path !== '' ) {
			$path = '/' . trim( $path, '/' );
			if ( function_exists( 'url_to_postid' ) ) {
				$test_url = home_url( $path );
				$found_id = url_to_postid( $test_url );
				if ( $found_id > 0 ) {
					$post = get_post( $found_id );
					if ( $post && $post->post_status === 'publish' ) {
						return $post;
					}
				}
			}
		}

		$url = isset( $params['url'] ) ? esc_url_raw( wp_unslash( $params['url'] ) ) : '';
		if ( $url !== '' ) {
			$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
			$url_host  = wp_parse_url( $url, PHP_URL_HOST );
			if ( $home_host && $url_host && strtolower( $home_host ) === strtolower( $url_host ) && function_exists( 'url_to_postid' ) ) {
				$found_id = url_to_postid( $url );
				if ( $found_id > 0 ) {
					$post = get_post( $found_id );
					if ( $post && $post->post_status === 'publish' ) {
						return $post;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Prüft, ob der Post laut Settings ausgegeben werden darf (noindex, password).
	 *
	 * @param WP_Post $post Post.
	 * @return array { 'allowed' => bool, 'noindex' => bool, 'password_protected' => bool }
	 */
	public static function post_visibility( $post ) {
		$ai    = self::get_ai_settings();
		$noindex = (int) get_post_meta( $post->ID, '_bsseo_noindex', true );
		$pwd   = ! empty( $post->post_password );
		$respect_noindex  = ! empty( $ai['respect_noindex'] );
		$respect_password = ! empty( $ai['respect_password'] );

		$allowed = true;
		if ( $respect_password && $pwd ) {
			$allowed = false;
		}
		if ( $respect_noindex && $noindex ) {
			$allowed = false;
		}
		return array(
			'allowed'               => $allowed,
			'noindex'               => (bool) $noindex,
			'password_protected'    => $pwd,
		);
	}

	/**
	 * Liefert erlaubte Post-Typen für Feed/Page (aus Settings).
	 *
	 * @return array
	 */
	public static function allowed_post_types() {
		$ai = self::get_ai_settings();
		$pt = isset( $ai['post_types'] ) && is_array( $ai['post_types'] ) ? $ai['post_types'] : array( 'post', 'page' );
		if ( empty( $pt ) && function_exists( 'bsseo_ai_default_post_types' ) ) {
			$pt = bsseo_ai_default_post_types();
		}
		return $pt;
	}

	/**
	 * Featured-Image-Daten für einen Post (oder OG-Bild).
	 *
	 * @param WP_Post $post Post.
	 * @return array|null
	 */
	public static function get_featured_image( $post ) {
		$img_id = (int) get_post_meta( $post->ID, '_bsseo_og_image_id', true );
		if ( $img_id <= 0 ) {
			$img_id = get_post_thumbnail_id( $post->ID );
		}
		if ( $img_id <= 0 ) {
			return null;
		}
		$src = wp_get_attachment_image_src( $img_id, 'large' );
		if ( ! $src || empty( $src[0] ) ) {
			return null;
		}
		$meta = wp_get_attachment_metadata( $img_id );
		$alt  = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
		return array(
			'id'     => $img_id,
			'url'    => $src[0],
			'width'  => isset( $src[1] ) ? (int) $src[1] : 0,
			'height' => isset( $src[2] ) ? (int) $src[2] : 0,
			'alt'    => is_string( $alt ) ? $alt : '',
		);
	}

	/**
	 * SEO-Felder (BSseo oder WP-Fallback) für einen Post.
	 *
	 * @param WP_Post $post Post.
	 * @return array title, description, robots
	 */
	public static function get_seo_for_post( $post ) {
		$settings = bsseo_get_settings();
		$use_fallback = ! empty( $settings['toggles']['use_wp_fallback'] );
		$title = get_post_meta( $post->ID, '_bsseo_title', true );
		if ( ! is_string( $title ) || $title === '' ) {
			$title = $use_fallback ? get_the_title( $post ) : '';
		}
		$description = get_post_meta( $post->ID, '_bsseo_description', true );
		if ( ! is_string( $description ) || $description === '' ) {
			if ( $use_fallback && has_excerpt( $post->ID ) ) {
				$description = get_the_excerpt( $post->ID );
			} else {
				$description = $use_fallback && ! empty( $post->post_content ) ? wp_trim_words( strip_shortcodes( $post->post_content ), 35 ) : '';
			}
		}
		$noindex  = (int) get_post_meta( $post->ID, '_bsseo_noindex', true );
		$nofollow = (int) get_post_meta( $post->ID, '_bsseo_nofollow', true );
		$robots   = ( $noindex ? 'noindex,' : 'index,' ) . ( $nofollow ? 'nofollow' : 'follow' );
		if ( function_exists( 'bsseo_normalize_meta_description' ) ) {
			$description = bsseo_normalize_meta_description( $description, 155 );
		} else {
			$description = wp_strip_all_tags( $description );
			$description = wp_html_excerpt( $description, 155, '…' );
		}
		return array(
			'title'       => is_string( $title ) ? wp_strip_all_tags( $title ) : '',
			'description' => is_string( $description ) ? $description : '',
			'robots'      => $robots,
		);
	}

	/**
	 * GET ai/page – Einzelpost-Daten.
	 *
	 * @param array $params id, slug, post_type, path, url.
	 * @return array|null Response-Array oder null wenn nicht gefunden/nicht erlaubt.
	 */
	public static function get_page( $params ) {
		$post = self::resolve_post( $params );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return null;
		}
		$allowed_types = self::allowed_post_types();
		if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
			return null;
		}
		$vis = self::post_visibility( $post );
		if ( ! $vis['allowed'] ) {
			return null;
		}

		$permalink = get_permalink( $post->ID );
		$canonical = get_post_meta( $post->ID, '_bsseo_canonical', true );
		if ( ! is_string( $canonical ) || $canonical === '' ) {
			$canonical = $permalink;
		} else {
			$canonical = esc_url_raw( $canonical );
		}
		$seo = self::get_seo_for_post( $post );
		$lang = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		if ( function_exists( 'pll_get_post_language' ) && is_callable( 'pll_get_post_language' ) ) {
			$pll = pll_get_post_language( $post->ID );
			if ( is_string( $pll ) && $pll !== '' ) {
				$lang = $pll;
			}
		} elseif ( defined( 'WPML_PLUGIN_FILE' ) && function_exists( 'wpml_get_language_information' ) ) {
			$info = wpml_get_language_information( null, $post->ID );
			if ( ! empty( $info['language_code'] ) ) {
				$lang = $info['language_code'];
			}
		}

		$author_data = null;
		if ( ! empty( $post->post_author ) ) {
			$author_data = array(
				'id'   => (int) $post->post_author,
				'name' => get_the_author_meta( 'display_name', $post->post_author ),
			);
		}

		$taxonomies = array();
		$tax_names = get_object_taxonomies( $post->post_type, 'names' );
		foreach ( $tax_names as $tax ) {
			$terms = get_the_terms( $post->ID, $tax );
			if ( ! is_array( $terms ) ) {
				continue;
			}
			$taxonomies[ $tax ] = array();
			foreach ( $terms as $t ) {
				if ( $t instanceof WP_Term ) {
					$taxonomies[ $tax ][] = array(
						'id'   => $t->term_id,
						'name' => $t->name,
						'slug' => $t->slug,
					);
				}
			}
		}

		$images = array( 'featured' => self::get_featured_image( $post ) );
		if ( $images['featured'] === null ) {
			unset( $images['featured'] );
		}

		$ai = self::get_ai_settings();
		$content_level = isset( $ai['content_level'] ) ? $ai['content_level'] : 'metadata_only';
		$content = array();
		if ( $content_level === 'include_excerpt' || $content_level === 'include_trimmed_content' ) {
			$content['excerpt'] = has_excerpt( $post->ID ) ? get_the_excerpt( $post->ID ) : wp_trim_words( strip_shortcodes( $post->post_content ), 35 );
		}
		if ( $content_level === 'include_trimmed_content' && ! empty( $post->post_content ) ) {
			$text = strip_shortcodes( $post->post_content );
			$text = wp_strip_all_tags( $text );
			$text = preg_replace( '/\s+/', ' ', trim( $text ) );
			if ( $text !== '' ) {
				$len = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
				if ( $len > self::TRIM_CONTENT_CHARS ) {
					$text = ( function_exists( 'mb_substr' ) ? mb_substr( $text, 0, self::TRIM_CONTENT_CHARS ) : substr( $text, 0, self::TRIM_CONTENT_CHARS ) ) . '…';
				}
				$content['text'] = $text;
			}
		}

		return array(
			'id'            => $post->ID,
			'type'          => $post->post_type,
			'status'        => $post->post_status,
			'title'         => get_the_title( $post ),
			'slug'          => $post->post_name,
			'permalink'     => $permalink,
			'canonical'     => $canonical,
			'language'      => $lang,
			'modified_gmt'  => get_the_modified_date( 'c', $post ),
			'published_gmt' => get_the_date( 'c', $post ),
			'author'        => $author_data,
			'images'        => $images,
			'taxonomies'    => $taxonomies,
			'seo'           => $seo,
			'content'       => $content,
			'flags'         => array(
				'noindex'             => $vis['noindex'],
				'password_protected'  => $vis['password_protected'],
			),
			'generated_at'  => current_time( 'c' ),
		);
	}

	/**
	 * GET ai/feed – Liste von Beiträgen.
	 *
	 * @param array $params limit, page, orderby, order, post_type (array), cluster, taxonomy params.
	 * @return array { items, paging, generated_at }
	 */
	public static function get_feed( $params ) {
		$ai = self::get_ai_settings();
		$default_limit = (int) ( $ai['feed_limit_default'] ?? 50 );
		$max_limit     = (int) ( $ai['feed_limit_max'] ?? 200 );
		$limit         = isset( $params['limit'] ) ? absint( $params['limit'] ) : $default_limit;
		$limit         = max( 1, min( $max_limit, $limit ) );
		$page          = isset( $params['page'] ) ? max( 1, absint( $params['page'] ) ) : 1;
		$orderby       = isset( $params['orderby'] ) ? sanitize_key( $params['orderby'] ) : 'modified';
		$order         = isset( $params['order'] ) ? strtolower( sanitize_text_field( $params['order'] ) ) : 'desc';
		if ( ! in_array( $orderby, self::ORDERBY_WHITELIST, true ) ) {
			$orderby = 'modified';
		}
		if ( ! in_array( $order, self::ORDER_WHITELIST, true ) ) {
			$order = 'desc';
		}

		$post_types = self::allowed_post_types();
		if ( ! empty( $params['post_type'] ) ) {
			if ( is_array( $params['post_type'] ) ) {
				$requested = array_map( 'sanitize_key', $params['post_type'] );
			} else {
				$requested = array( sanitize_key( $params['post_type'] ) );
			}
			$post_types = array_intersect( $post_types, $requested );
		}
		if ( empty( $post_types ) ) {
			return array( 'items' => array(), 'paging' => array( 'limit' => $limit, 'returned' => 0, 'next' => null ), 'generated_at' => current_time( 'c' ) );
		}

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'paged'          => $page,
			'orderby'        => $orderby,
			'order'          => strtoupper( $order ),
			'fields'         => 'ids',
		);

		$tax_filters = ! empty( $ai['taxonomy_filters'] );
		if ( $tax_filters && ! empty( $params['category'] ) ) {
			$cat = sanitize_key( wp_unslash( $params['category'] ) );
			if ( $cat !== '' ) {
				$args['tax_query'] = array(
					array( 'taxonomy' => 'category', 'field' => 'slug', 'terms' => $cat ),
				);
			}
		}
		if ( $tax_filters && ! empty( $params['tag'] ) ) {
			$tag = sanitize_key( wp_unslash( $params['tag'] ) );
			if ( $tag !== '' ) {
				$args['tax_query'][] = array( 'taxonomy' => 'post_tag', 'field' => 'slug', 'terms' => $tag );
			}
		}
		if ( ! empty( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}

		$query = new WP_Query( $args );
		$ids   = $query->posts;
		$items = array();
		$respect_noindex  = ! empty( $ai['respect_noindex'] );
		$respect_password = ! empty( $ai['respect_password'] );
		$content_level   = isset( $ai['content_level'] ) ? $ai['content_level'] : 'metadata_only';
		$include_seo_desc = in_array( $content_level, array( 'include_excerpt', 'include_trimmed_content' ), true );

		foreach ( $ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}
			if ( $respect_password && ! empty( $post->post_password ) ) {
				continue;
			}
			if ( $respect_noindex && (int) get_post_meta( $post->ID, '_bsseo_noindex', true ) ) {
				continue;
			}
			$permalink = get_permalink( $post->ID );
			$canonical = get_post_meta( $post->ID, '_bsseo_canonical', true );
			if ( ! is_string( $canonical ) || $canonical === '' ) {
				$canonical = $permalink;
			}
			$seo_desc = '';
			if ( $include_seo_desc ) {
				$d = get_post_meta( $post->ID, '_bsseo_description', true );
				if ( is_string( $d ) && $d !== '' ) {
					$seo_desc = $d;
				} else {
					$seo_desc = has_excerpt( $post->ID ) ? get_the_excerpt( $post->ID ) : wp_trim_words( strip_shortcodes( $post->post_content ), 35 );
				}
				if ( function_exists( 'bsseo_normalize_meta_description' ) ) {
					$seo_desc = bsseo_normalize_meta_description( $seo_desc, 155 );
				}
			}
			$feat = self::get_featured_image( $post );
			$items[] = array(
				'id'            => $post->ID,
				'type'          => $post->post_type,
				'title'         => get_the_title( $post ),
				'permalink'     => $permalink,
				'modified_gmt'  => get_the_modified_date( 'c', $post ),
				'published_gmt' => get_the_date( 'c', $post ),
				'canonical'     => $canonical,
				'seo'           => array( 'description' => $seo_desc ),
				'images'        => array( 'featured' => $feat ),
			);
		}

		$total = (int) $query->found_posts;
		$next  = null;
		if ( $page * $limit < $total ) {
			$next_url = add_query_arg( array( 'page' => $page + 1, 'limit' => $limit ), rest_url( 'bsseo/v1/ai/feed' ) );
			$next     = $next_url;
		}

		return array(
			'items'        => $items,
			'paging'       => array(
				'limit'    => $limit,
				'returned' => count( $items ),
				'next'     => $next,
			),
			'generated_at' => current_time( 'c' ),
		);
	}

	/**
	 * GET ai/llms – Text für llms.txt (text/plain).
	 *
	 * @return string
	 */
	public static function get_llms_text() {
		$home = home_url( '/' );
		$rest = rest_url( 'bsseo/v1/ai/' );
		$lines = array(
			'# BSseo AI/LLM Endpoints',
			'',
			'Diese Seite beschreibt maschinenlesbare Endpoints für KI-Crawler und Indexing.',
			'',
			'## Endpoints (REST)',
			'Meta (Site-Infos): ' . $rest . 'meta',
			'Einzelne Seite: ' . $rest . 'page (Parameter: id, slug, post_type, path, url)',
			'Feed (Liste): ' . $rest . 'feed (Parameter: limit, page, orderby, order, post_type)',
			'llms.txt (diese Datei): ' . $rest . 'llms',
			'',
			'## Hinweise',
			'- Noindex-Beiträge werden bei aktivierter Einstellung nicht im Feed gelistet.',
			'- Passwortgeschützte Inhalte werden nie ausgeliefert.',
			'- Content kann als "metadata only", mit Excerpt oder mit gekürztem Text geliefert werden (Einstellung im Plugin).',
			'',
			'Generated: ' . current_time( 'c' ),
		);
		return implode( "\n", $lines );
	}
}
