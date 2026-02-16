<?php
/**
 * BSseo – Content-Analyse (DEVELOPMENT-SEO-Verfeinerung Phase 5).
 * Input: apply_filters('the_content'). Parsing: DOMDocument. Scores in Post-Meta.
 * Bei Save: Cache veralten; Recalc via Cron; manuell per „Jetzt analysieren“.
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-Handler: Analyse anstoßen (nur Admin, Nonce, edit_post).
 */
function bsseo_ajax_analyze_content() {
	check_ajax_referer( 'bsseo_analyze', 'bsseo_nonce' );

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	if ( $post_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Beitrag noch nicht gespeichert. Bitte zuerst speichern, dann „Jetzt analysieren“ ausführen.', 'bsseo' ) ) );
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Berechtigung fehlt.', 'bsseo' ) ) );
	}

	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['analysis_enabled'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Content-Analyse ist deaktiviert.', 'bsseo' ) ) );
	}

	$result = bsseo_run_analysis( $post_id );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array(
		'seo_score' => (int) $result['seo_score'],
		'ai_score'  => (int) $result['ai_score'],
		'checks'    => $result['checks'],
		'updated'   => (int) $result['updated'],
	) );
}

add_action( 'wp_ajax_bsseo_analyze_content', 'bsseo_ajax_analyze_content' );

/**
 * Nach save_post: Score als veraltet markieren (§7.2 „Score wird bei Save veraltet“), Cron planen.
 */
function bsseo_schedule_recalc_on_save( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_status !== 'publish' ) {
		return;
	}

	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['analysis_enabled'] ) ) {
		return;
	}

	$post_type_obj = get_post_type_object( $post->post_type );
	if ( ! $post_type_obj || ! $post_type_obj->public ) {
		return;
	}

	// Cache-Invalidierung: Score gilt bis zum nächsten Recalc als veraltet (Metabox zeigt „—“).
	delete_post_meta( $post_id, '_bsseo_score_updated' );

	// Deduping: nur planen, wenn noch nicht geplant
	if ( wp_next_scheduled( 'bsseo_recalc_score', array( $post_id ) ) ) {
		return;
	}

	wp_schedule_single_event( time() + 5, 'bsseo_recalc_score', array( $post_id ) );
}

add_action( 'save_post', 'bsseo_schedule_recalc_on_save', 20 );

/**
 * Cron-Callback: Analyse für einen Post ausführen (§7.2 „Recalc läuft, Cron Event existiert“).
 * Event: bsseo_recalc_score, Argument: $post_id.
 */
function bsseo_cron_recalc_score( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return;
	}

	$settings = bsseo_get_settings();
	if ( empty( $settings['toggles']['analysis_enabled'] ) ) {
		return;
	}

	bsseo_run_analysis( $post_id );
}

add_action( 'bsseo_recalc_score', 'bsseo_cron_recalc_score' );

/**
 * Prüft die Überschriften-Reihenfolge im DOM: eine H1, keine Level-Sprünge (z. B. H1 → H3).
 * DEVELOPMENT-2.md: Heading-Hierarchie.
 *
 * @param DOMElement|null $context Kontext-Knoten (z. B. Artikel-Body).
 * @param DOMXPath        $xpath  XPath-Instanz.
 * @return array{ ok: bool, message: string, status: string }
 */
function bsseo_validate_heading_sequence( $context, DOMXPath $xpath ) {
	$result = array( 'ok' => false, 'message' => __( 'H1–H6 für Gliederung nutzen.', 'bsseo' ), 'status' => 'info' );
	if ( ! $context instanceof DOMElement ) {
		return $result;
	}
	$nodes = $xpath->query( './/*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]', $context );
	if ( ! $nodes || $nodes->length === 0 ) {
		$result['message'] = __( 'Keine Überschriften H1–H6 gefunden.', 'bsseo' );
		return $result;
	}
	$levels = array();
	foreach ( $nodes as $node ) {
		$tag = strtolower( $node->nodeName );
		if ( preg_match( '/^h([1-6])$/', $tag, $m ) ) {
			$levels[] = (int) $m[1];
		}
	}
	$h1_count = 0;
	foreach ( $levels as $l ) {
		if ( $l === 1 ) {
			$h1_count++;
		}
	}
	if ( $h1_count === 0 ) {
		$result['message'] = __( 'Erste Überschrift sollte H1 sein.', 'bsseo' );
		$result['status']  = 'warning';
		return $result;
	}
	if ( $h1_count > 1 ) {
		$result['message'] = sprintf( __( 'Mehrere H1 gefunden: %d. Besser nur eine H1.', 'bsseo' ), $h1_count );
		$result['status']  = 'warning';
		return $result;
	}
	$prev = 0;
	foreach ( $levels as $level ) {
		if ( $prev > 0 && $level > $prev + 1 ) {
			$result['message'] = sprintf( __( 'Hierarchiesprung: von H%d zu H%d. Überschriften ohne Level-Sprung nutzen.', 'bsseo' ), $prev, $level );
			$result['status']  = 'warning';
			return $result;
		}
		$prev = $level;
	}
	$result['ok']      = true;
	$result['message'] = __( 'Struktur sinnvoll (eine H1, keine Sprünge).', 'bsseo' );
	$result['status'] = 'ok';
	return $result;
}

/**
 * Führt die Content-Analyse aus und speichert Ergebnisse in Post-Meta (§7.2).
 * Wird nur aus AJAX („Jetzt analysieren“) oder Cron aufgerufen – keine Queries im Frontend.
 *
 * @param int $post_id Post-ID.
 * @return array|WP_Error { seo_score, ai_score, checks, updated } oder WP_Error.
 */
function bsseo_run_analysis( $post_id ) {
	static $running = false;

	if ( $running ) {
		return new WP_Error( 'bsseo_reentrancy', __( 'Analyse läuft bereits.', 'bsseo' ) );
	}

	$running = true;

	try {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_status, array( 'publish', 'draft', 'pending' ), true ) ) {
			return new WP_Error( 'bsseo_invalid_post', __( 'Beitrag nicht gefunden oder nicht analysierbar.', 'bsseo' ) );
		}

		$settings   = bsseo_get_settings();
		$limits     = $settings['analysis_limits'];
		$max_chars  = isset( $limits['max_chars'] ) ? (int) $limits['max_chars'] : 120000;
		$max_nodes  = isset( $limits['max_dom_nodes'] ) ? (int) $limits['max_dom_nodes'] : 8000;
		$timeout_ms = isset( $limits['timeout_ms'] ) ? (int) $limits['timeout_ms'] : 2500;
		$timeout_sec = $timeout_ms / 1000.0;
		$start_time = microtime( true );

		// Content nur hier rendern (AJAX/Cron), nie im Frontend
		$html = apply_filters( 'the_content', $post->post_content );
		$html = is_string( $html ) ? $html : '';

		if ( strlen( $html ) > $max_chars ) {
			$html = substr( $html, 0, $max_chars );
		}

		if ( ( microtime( true ) - $start_time ) > $timeout_sec ) {
			$running = false;
			return new WP_Error( 'bsseo_timeout', __( 'Zeitüberschreitung beim Rendern.', 'bsseo' ) );
		}

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		// Fragment mit Wrapper laden (the_content kann mehrere Root-Elemente liefern)
		@$dom->loadHTML( '<div id="bsseo-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$root = $dom->getElementById( 'bsseo-root' );
		if ( ! $root ) {
			$root = $dom->documentElement;
		}

		$node_count = 0;
		$count_nodes = function ( DOMNode $n ) use ( &$count_nodes, &$node_count, $max_nodes, $start_time, $timeout_sec ) {
			if ( $node_count >= $max_nodes ) {
				return;
			}
			if ( ( microtime( true ) - $start_time ) > $timeout_sec ) {
				return;
			}
			$node_count++;
			if ( $n->hasChildNodes() ) {
				foreach ( $n->childNodes as $child ) {
					if ( $child instanceof DOMNode ) {
						$count_nodes( $child );
					}
				}
			}
		};
		if ( $root ) {
			$count_nodes( $root );
		}

		$checks = array();
		$seo_points = 0;
		$seo_max   = 0;
		$ai_points = 0;
		$ai_max    = 0;

		// Meta-Daten des Posts (nicht aus DOM)
		$title_meta   = get_post_meta( $post_id, '_bsseo_title', true );
		$desc_meta    = get_post_meta( $post_id, '_bsseo_description', true );
		$focus_kw     = get_post_meta( $post_id, '_bsseo_focus_keyword', true );
		$title_used   = is_string( $title_meta ) && $title_meta !== '' ? $title_meta : $post->post_title;
		$desc_used    = is_string( $desc_meta ) && $desc_meta !== '' ? $desc_meta : wp_trim_words( wp_strip_all_tags( $post->post_content ), 35 );

		// SEO-Checks
		$seo_max += 2;
		if ( strlen( $title_used ) >= 30 && strlen( $title_used ) <= 70 ) {
			$seo_points += 2;
			$checks[] = array( 'id' => 'title_length', 'status' => 'ok', 'label' => __( 'Titellänge', 'bsseo' ), 'message' => __( 'Titellänge im empfohlenen Bereich (30–70 Zeichen).', 'bsseo' ) );
		} else {
			$checks[] = array( 'id' => 'title_length', 'status' => 'warning', 'label' => __( 'Titellänge', 'bsseo' ), 'message' => sprintf( __( 'Empfohlen: 30–70 Zeichen, aktuell: %d.', 'bsseo' ), strlen( $title_used ) ) );
		}

		$seo_max += 2;
		if ( strlen( $desc_used ) >= 120 && strlen( $desc_used ) <= 160 ) {
			$seo_points += 2;
			$checks[] = array( 'id' => 'description_length', 'status' => 'ok', 'label' => __( 'Meta-Beschreibung', 'bsseo' ), 'message' => __( 'Länge im empfohlenen Bereich (120–160 Zeichen).', 'bsseo' ) );
		} else {
			$checks[] = array( 'id' => 'description_length', 'status' => 'warning', 'label' => __( 'Meta-Beschreibung', 'bsseo' ), 'message' => sprintf( __( 'Empfohlen: 120–160 Zeichen, aktuell: %d.', 'bsseo' ), strlen( $desc_used ) ) );
		}

		$h1_count = 0;
		$img_without_alt = 0;
		$link_count = 0;
		$headings = array();
		$list_count = 0;
		$table_count = 0;
		$word_count = 0;
		$para_count = 0;

		$context = $root ?: $dom->documentElement;
		if ( $context && ( microtime( true ) - $start_time ) < $timeout_sec ) {
			$xpath = new DOMXPath( $dom );
			$h1_list = $xpath->query( './/h1', $context );
			$h1_count = $h1_list ? $h1_list->length : 0;
			$img_list = $xpath->query( './/img', $context );
			if ( $img_list ) {
				foreach ( $img_list as $img ) {
					if ( ! $img->hasAttribute( 'alt' ) || trim( $img->getAttribute( 'alt' ) ) === '' ) {
						$img_without_alt++;
					}
				}
			}
			$link_list = $xpath->query( './/a[@href]', $context );
			$link_count = $link_list ? $link_list->length : 0;
			foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ) as $tag ) {
				$list = $xpath->query( './/' . $tag, $context );
				if ( $list ) {
					$headings[ $tag ] = $list->length;
				}
			}
			$list_nodes = $xpath->query( './/ul | .//ol', $context );
			$list_count = $list_nodes ? $list_nodes->length : 0;
			$table_nodes = $xpath->query( './/table', $context );
			$table_count = $table_nodes ? $table_nodes->length : 0;
			$para_nodes = $xpath->query( './/p', $context );
			$para_count = $para_nodes ? $para_nodes->length : 0;

			$text = $context ? $context->textContent : '';
			$word_count = bsseo_count_words_unicode( wp_strip_all_tags( $text ) );
		}

		$seo_max += 2;
		if ( $h1_count >= 1 && $h1_count <= 1 ) {
			$seo_points += 2;
			$checks[] = array( 'id' => 'h1', 'status' => 'ok', 'label' => __( 'Eine H1', 'bsseo' ), 'message' => __( 'Es gibt genau eine H1-Überschrift.', 'bsseo' ) );
		} elseif ( $h1_count === 0 ) {
			$checks[] = array( 'id' => 'h1', 'status' => 'warning', 'label' => __( 'H1', 'bsseo' ), 'message' => __( 'Keine H1-Überschrift gefunden.', 'bsseo' ) );
		} else {
			$checks[] = array( 'id' => 'h1', 'status' => 'warning', 'label' => __( 'H1', 'bsseo' ), 'message' => sprintf( __( 'Mehrere H1 gefunden: %d.', 'bsseo' ), $h1_count ) );
		}

		$seo_max += 2;
		if ( $img_without_alt === 0 && $link_count > 0 ) {
			$seo_points += 2;
			$checks[] = array( 'id' => 'images_links', 'status' => 'ok', 'label' => __( 'Bilder & Links', 'bsseo' ), 'message' => __( 'Bilder mit Alt-Text, Links vorhanden.', 'bsseo' ) );
		} elseif ( $img_without_alt > 0 ) {
			$checks[] = array( 'id' => 'images_links', 'status' => 'warning', 'label' => __( 'Bilder', 'bsseo' ), 'message' => sprintf( _n( '%d Bild ohne Alt-Text.', '%d Bilder ohne Alt-Text.', $img_without_alt, 'bsseo' ), $img_without_alt ) );
		} else {
			$checks[] = array( 'id' => 'images_links', 'status' => 'info', 'label' => __( 'Links', 'bsseo' ), 'message' => sprintf( _n( '%d Link.', '%d Links.', $link_count, 'bsseo' ), $link_count ) );
		}

		$seo_score = $seo_max > 0 ? (int) round( 100 * $seo_points / $seo_max ) : 0;
		$seo_score = min( 100, max( 0, $seo_score ) );

		// KI/Struktur-Score: Hierarchie (Reihenfolge + Sprungprüfung), Listen, Tabellen, Umfang, Lesbarkeit
		$ai_max += 2;
		$hierarchy_result = array( 'ok' => false, 'message' => __( 'H1–H6 für Gliederung nutzen.', 'bsseo' ), 'status' => 'info' );
		if ( $context && isset( $xpath ) ) {
			$hierarchy_result = bsseo_validate_heading_sequence( $context, $xpath );
		}
		if ( $hierarchy_result['ok'] ) {
			$ai_points += 2;
		}
		$checks[] = array( 'id' => 'heading_hierarchy', 'status' => $hierarchy_result['status'], 'label' => __( 'Überschriften-Hierarchie', 'bsseo' ), 'message' => $hierarchy_result['message'] );

		$ai_max += 2;
		if ( $list_count > 0 || $table_count > 0 ) {
			$ai_points += 2;
			$checks[] = array( 'id' => 'lists_tables', 'status' => 'ok', 'label' => __( 'Listen/Tabellen', 'bsseo' ), 'message' => sprintf( __( '%d Listen, %d Tabellen.', 'bsseo' ), $list_count, $table_count ) );
		} else {
			$checks[] = array( 'id' => 'lists_tables', 'status' => 'info', 'label' => __( 'Struktur', 'bsseo' ), 'message' => __( 'Listen oder Tabellen verbessern die Struktur.', 'bsseo' ) );
		}

		$ai_max += 2;
		if ( $word_count >= 300 ) {
			$ai_points += 2;
			$checks[] = array( 'id' => 'word_count', 'status' => 'ok', 'label' => __( 'Umfang', 'bsseo' ), 'message' => sprintf( _n( '%d Wort.', '%d Wörter.', $word_count, 'bsseo' ), $word_count ) );
		} else {
			$checks[] = array( 'id' => 'word_count', 'status' => 'info', 'label' => __( 'Umfang', 'bsseo' ), 'message' => sprintf( _n( '%d Wort.', '%d Wörter.', $word_count, 'bsseo' ), $word_count ) );
		}

		$ai_max += 2;
		$avg_words_per_para = $para_count > 0 ? $word_count / $para_count : 0;
		if ( $avg_words_per_para <= 80 && $para_count >= 1 ) {
			$ai_points += 2;
			$checks[] = array( 'id' => 'readability', 'status' => 'ok', 'label' => __( 'Lesbarkeit', 'bsseo' ), 'message' => __( 'Kurze Absätze.', 'bsseo' ) );
		} else {
			$checks[] = array( 'id' => 'readability', 'status' => 'info', 'label' => __( 'Lesbarkeit', 'bsseo' ), 'message' => __( 'Kurze Absätze verbessern die Lesbarkeit.', 'bsseo' ) );
		}

		$ai_score = $ai_max > 0 ? (int) round( 100 * $ai_points / $ai_max ) : 0;
		$ai_score = min( 100, max( 0, $ai_score ) );

		$updated = time();
		update_post_meta( $post_id, '_bsseo_seo_score', $seo_score );
		update_post_meta( $post_id, '_bsseo_ai_score', $ai_score );
		update_post_meta( $post_id, '_bsseo_score_updated', $updated );
		update_post_meta( $post_id, '_bsseo_checks', $checks );

		return array(
			'seo_score' => $seo_score,
			'ai_score'  => $ai_score,
			'checks'    => $checks,
			'updated'   => $updated,
		);
	} finally {
		$running = false;
	}
}
