<?php
/**
 * BSseo – Schema.org JSON-LD (DEVELOPMENT-SEO-Verfeinerung Phase 3).
 * @graph: WebSite, WebPage, BlogPosting/Article, Organization, Person, ImageObject.
 * Nur bei output_schema und ohne Konflikt.
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gibt JSON-LD im Head aus (Priorität 5).
 */
function bsseo_output_schema() {
	if ( ! bsseo_should_output_head() ) {
		return;
	}
	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['output_schema'] ) || bsseo_has_seo_conflict() ) {
		return;
	}

	if ( ! is_singular() ) {
		return;
	}

	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$graph = bsseo_build_schema_graph( $post );
	if ( empty( $graph ) ) {
		return;
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	$data = apply_filters( 'bsseo_schema_data', $data, $post );
	if ( empty( $data['@graph'] ) ) {
		return;
	}

	$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( $json === false || $json === '' ) {
		return;
	}

	echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
}

add_action( 'wp_head', 'bsseo_output_schema', 5 );

/**
 * Baut das @graph-Array für die aktuelle Singular-Ansicht (§5.1, §5.2).
 * Reihenfolge: WebSite, Organization, Person, ImageObject, WebPage, BlogPosting.
 *
 * @param WP_Post $post Queried post.
 * @return array
 */
function bsseo_build_schema_graph( WP_Post $post ) {
	$graph = array();
	$settings = bsseo_get_settings();
	$schema_org = $settings['schema_org'] ?? array();
	$home = home_url( '/' );
	$permalink = get_permalink( $post );
	$title = get_the_title( $post );
	$date_published = get_the_date( 'c', $post );
	$date_modified = get_the_modified_date( 'c', $post );
	$author_id = (int) $post->post_author;
	$author_name = get_the_author_meta( 'display_name', $author_id );
	$org_name = isset( $schema_org['name'] ) ? trim( (string) $schema_org['name'] ) : '';
	$post_type = $post->post_type;
	$in_language = get_locale();
	$in_language_bcp47 = str_replace( '_', '-', $in_language );

	// WebSite (§5.1)
	$site_name = ( $settings['site_name_override'] ?? '' ) !== ''
		? $settings['site_name_override']
		: get_bloginfo( 'name', 'display' );
	$website = array(
		'@type'       => 'WebSite',
		'@id'         => $home . '#website',
		'url'         => $home,
		'name'        => $site_name !== '' ? $site_name : $home,
		'inLanguage'  => $in_language_bcp47,
	);
	$graph[] = $website;

	// Organization (optional)
	if ( $org_name !== '' ) {
		$organization = array(
			'@type' => 'Organization',
			'@id'   => $home . '#organization',
			'name'  => $org_name,
			'url'   => $home,
		);
		if ( ! empty( $schema_org['logo'] ) && esc_url_raw( $schema_org['logo'] ) === $schema_org['logo'] ) {
			$organization['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $schema_org['logo'],
			);
		}
		if ( ! empty( $schema_org['social_profiles'] ) && is_array( $schema_org['social_profiles'] ) ) {
			$same_as = array();
			foreach ( $schema_org['social_profiles'] as $url ) {
				$url = esc_url_raw( $url );
				if ( $url !== '' ) {
					$same_as[] = $url;
				}
			}
			if ( ! empty( $same_as ) ) {
				$organization['sameAs'] = $same_as;
			}
		}
		$graph[] = $organization;
	}

	// Person (Author): url = Author-Archiv-URL (/author/slug/), falls kein Profil-URL gesetzt.
	if ( $author_name !== '' ) {
		$author_archive_url = get_author_posts_url( $author_id );
		$person = array(
			'@type' => 'Person',
			'@id'   => $author_archive_url . '#person',
			'name'  => $author_name,
			'url'   => $author_archive_url,
		);
		$author_profile_url = get_the_author_meta( 'url', $author_id );
		if ( $author_profile_url !== '' && esc_url_raw( $author_profile_url ) === $author_profile_url ) {
			$person['url'] = $author_profile_url;
		}
		$graph[] = $person;
	}

	// ImageObject (Featured Image / OG Image) (§5.1)
	$image_id = (int) get_post_meta( $post->ID, '_bsseo_og_image_id', true );
	if ( $image_id <= 0 ) {
		$image_id = get_post_thumbnail_id( $post );
	}
	$image_object_id = null;
	if ( $image_id > 0 ) {
		$image_url = wp_get_attachment_image_url( $image_id, 'large' );
		if ( $image_url ) {
			$image_object_id = $image_url;
			$img_src = wp_get_attachment_image_src( $image_id, 'large' );
			$image_obj = array(
				'@type' => 'ImageObject',
				'@id'   => $image_url,
				'url'   => $image_url,
			);
			if ( $img_src && isset( $img_src[1], $img_src[2] ) && (int) $img_src[1] > 0 && (int) $img_src[2] > 0 ) {
				$image_obj['width'] = (int) $img_src[1];
				$image_obj['height'] = (int) $img_src[2];
			}
			$graph[] = $image_obj;
		}
	}

	// WebPage (isPartOf WebSite, primaryImageOfPage optional)
	$webpage = array(
		'@type'         => 'WebPage',
		'@id'           => $permalink . '#webpage',
		'name'          => $title,
		'url'           => $permalink,
		'inLanguage'    => $in_language_bcp47,
		'isPartOf'      => array( '@id' => $home . '#website' ),
		'datePublished' => $date_published,
		'dateModified'  => $date_modified,
	);
	if ( $image_object_id ) {
		$webpage['primaryImageOfPage'] = array( '@id' => $image_object_id );
	}
	if ( $author_id && $author_name !== '' ) {
		$webpage['author'] = array( '@id' => get_author_posts_url( $author_id ) . '#person' );
	}
	if ( $org_name !== '' ) {
		$webpage['publisher'] = array( '@id' => $home . '#organization' );
	}
	$webpage = apply_filters( 'bsseo_schema_webpage', $webpage, $post );
	$graph[] = $webpage;

	if ( $post_type === 'post' ) {
		// BlogPosting: mainEntityOfPage → WebPage, image → ImageObject (§5.1)
		$article = array(
			'@type'            => 'BlogPosting',
			'@id'              => $permalink . '#article',
			'headline'         => $title,
			'url'              => $permalink,
			'inLanguage'       => $in_language_bcp47,
			'datePublished'    => $date_published,
			'dateModified'     => $date_modified,
			'mainEntityOfPage' => array( '@id' => $permalink . '#webpage' ),
		);
		if ( $author_id && $author_name !== '' ) {
			$article['author'] = array( '@id' => get_author_posts_url( $author_id ) . '#person' );
		}
		if ( $org_name !== '' ) {
			$article['publisher'] = array( '@id' => $home . '#organization' );
		}
		if ( $image_object_id ) {
			$article['image'] = array( '@id' => $image_object_id );
		}
		$description = get_post_meta( $post->ID, '_bsseo_description', true );
		if ( is_string( $description ) && $description !== '' ) {
			$article['description'] = wp_strip_all_tags( $description );
			$article['description'] = wp_html_excerpt( $article['description'], 200, '…' );
		} else {
			$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( strip_shortcodes( $post->post_content ), 35 );
			if ( $excerpt !== '' ) {
				$article['description'] = wp_strip_all_tags( $excerpt );
			}
		}
		// Citations aus _bsseo_sources (§5.2)
		$sources = get_post_meta( $post->ID, '_bsseo_sources', true );
		if ( is_array( $sources ) && ! empty( $sources ) ) {
			$citation = array();
			foreach ( $sources as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$url = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
				$name = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : $url;
				if ( $url !== '' || $name !== '' ) {
					$citation[] = array(
						'@type' => 'CreativeWork',
						'url'   => $url,
						'name'  => $name,
					);
				}
			}
			if ( ! empty( $citation ) ) {
				$article['citation'] = $citation;
			}
		}
		$article = apply_filters( 'bsseo_schema_article', $article, $post );
		if ( ! empty( $article['headline'] ) ) {
			$graph[] = $article;
		}
	}

	return $graph;
}
