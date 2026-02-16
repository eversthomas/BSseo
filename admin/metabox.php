<?php
/**
 * BSseo – Metabox für alle öffentlichen Post-Types
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert die BSseo-Metabox (nur im Classic Editor; im Block-Editor wird das Sidebar-Panel genutzt).
 */
function bsseo_add_metabox() {
	$post_types = get_post_types( array( 'public' => true ), 'names' );
	$settings   = bsseo_get_settings();
	$excluded   = isset( $settings['sitemap_excluded_post_types'] ) && is_array( $settings['sitemap_excluded_post_types'] )
		? $settings['sitemap_excluded_post_types']
		: array();

	foreach ( $post_types as $post_type ) {
		if ( in_array( $post_type, $excluded, true ) ) {
			continue;
		}
		// Im Block-Editor: Sidebar-Panel statt Metabox (siehe bsseo-sidebar.js).
		if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( $post_type ) ) {
			continue;
		}
		add_meta_box(
			'bsseo_metabox',
			__( 'BSseo', 'bsseo' ),
			'bsseo_render_metabox',
			$post_type,
			'normal',
			'default'
		);
	}
}

add_action( 'add_meta_boxes', 'bsseo_add_metabox' );

/**
 * Gibt den Metabox-Inhalt aus.
 *
 * @param WP_Post $post   Post-Objekt.
 * @param array   $box    Meta-Box-Argumente.
 */
function bsseo_render_metabox( $post, $box ) {
	wp_nonce_field( 'bsseo_metabox_save', 'bsseo_metabox_nonce' );

	$help   = bsseo_get_help_texts();
	$title  = get_post_meta( $post->ID, '_bsseo_title', true );
	$desc   = get_post_meta( $post->ID, '_bsseo_description', true );
	$focus  = get_post_meta( $post->ID, '_bsseo_focus_keyword', true );
	$canon  = get_post_meta( $post->ID, '_bsseo_canonical', true );
	$noindex = (int) get_post_meta( $post->ID, '_bsseo_noindex', true );
	$nofollow = (int) get_post_meta( $post->ID, '_bsseo_nofollow', true );
	$schema_type = get_post_meta( $post->ID, '_bsseo_schema_type', true );
	$og_image_id = (int) get_post_meta( $post->ID, '_bsseo_og_image_id', true );
	$sources = get_post_meta( $post->ID, '_bsseo_sources', true );
	$sources = is_array( $sources ) ? $sources : array();

	$seo_score = (int) get_post_meta( $post->ID, '_bsseo_seo_score', true );
	$ai_score  = (int) get_post_meta( $post->ID, '_bsseo_ai_score', true );
	$updated   = (int) get_post_meta( $post->ID, '_bsseo_score_updated', true );
	$checks    = get_post_meta( $post->ID, '_bsseo_checks', true );
	$checks    = is_array( $checks ) ? $checks : array();

	$settings = bsseo_get_settings();
	$analysis_enabled = ! empty( $settings['toggles']['analysis_enabled'] );
	?>
	<div class="bsseo-metabox" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>" data-default-title="<?php echo esc_attr( get_the_title( $post ) ); ?>">
		<?php if ( $analysis_enabled ) : ?>
			<div class="bsseo-scores" aria-live="polite">
				<span class="bsseo-score-label"><?php esc_html_e( 'SEO', 'bsseo' ); ?></span>
				<span id="bsseo-seo-score" class="bsseo-score-value"><?php echo $updated ? (int) $seo_score : '—'; ?></span>
				<span class="bsseo-score-label"><?php esc_html_e( 'KI', 'bsseo' ); ?></span>
				<span id="bsseo-ai-score" class="bsseo-score-value"><?php echo $updated ? (int) $ai_score : '—'; ?></span>
				<button type="button" id="bsseo-analyze-btn" class="button button-secondary"><?php esc_html_e( 'Jetzt analysieren', 'bsseo' ); ?></button>
				<span id="bsseo-analyze-status" class="bsseo-status" aria-live="polite"></span>
			</div>
			<div id="bsseo-checks-modal" class="bsseo-modal" role="dialog" aria-modal="true" aria-labelledby="bsseo-checks-title" hidden>
				<div class="bsseo-modal-inner">
					<h2 id="bsseo-checks-title"><?php esc_html_e( 'Analyse-Ergebnis', 'bsseo' ); ?></h2>
					<div id="bsseo-checks-list"></div>
					<button type="button" class="bsseo-modal-close"><?php esc_html_e( 'Schließen', 'bsseo' ); ?></button>
				</div>
			</div>
		<?php endif; ?>

		<p class="bsseo-field">
			<label for="bsseo_title"><?php echo esc_html( $help['title']['label'] ?? __( 'SEO-Titel', 'bsseo' ) ); ?></label>
			<?php bsseo_help_icon( 'title' ); ?>
			<input type="text" id="bsseo_title" name="bsseo_title" value="<?php echo esc_attr( $title ); ?>" class="widefat" />
			<span id="bsseo_title_counter" class="bsseo-counter">0</span> <?php esc_html_e( 'Zeichen', 'bsseo' ); ?>
			<span id="bsseo_title_preview" class="bsseo-preview" aria-live="polite"></span>
		</p>

		<p class="bsseo-field">
			<label for="bsseo_description"><?php echo esc_html( $help['description']['label'] ?? __( 'Meta-Beschreibung', 'bsseo' ) ); ?></label>
			<?php bsseo_help_icon( 'description' ); ?>
			<textarea id="bsseo_description" name="bsseo_description" rows="3" class="widefat"><?php echo esc_textarea( $desc ); ?></textarea>
			<span id="bsseo_description_counter" class="bsseo-counter">0</span> <?php esc_html_e( 'Zeichen', 'bsseo' ); ?>
		</p>

		<p class="bsseo-field">
			<label for="bsseo_focus_keyword"><?php echo esc_html( $help['focus_keyword']['label'] ?? __( 'Fokus-Keyword', 'bsseo' ) ); ?></label>
			<?php bsseo_help_icon( 'focus_keyword' ); ?>
			<input type="text" id="bsseo_focus_keyword" name="bsseo_focus_keyword" value="<?php echo esc_attr( $focus ); ?>" class="widefat" />
		</p>

		<p class="bsseo-field">
			<label for="bsseo_canonical"><?php echo esc_html( $help['canonical']['label'] ?? __( 'Canonical-URL', 'bsseo' ) ); ?></label>
			<?php bsseo_help_icon( 'canonical' ); ?>
			<input type="url" id="bsseo_canonical" name="bsseo_canonical" value="<?php echo esc_attr( $canon ); ?>" class="widefat" placeholder="https://" />
		</p>

		<p class="bsseo-field bsseo-checkboxes">
			<?php bsseo_help_icon( 'noindex' ); ?>
			<label><input type="checkbox" name="bsseo_noindex" value="1" <?php checked( $noindex, 1 ); ?> /> <?php echo esc_html( $help['noindex']['label'] ?? __( 'Noindex', 'bsseo' ) ); ?></label>
			<br />
			<?php bsseo_help_icon( 'nofollow' ); ?>
			<label><input type="checkbox" name="bsseo_nofollow" value="1" <?php checked( $nofollow, 1 ); ?> /> <?php echo esc_html( $help['nofollow']['label'] ?? __( 'Nofollow', 'bsseo' ) ); ?></label>
		</p>

		<p class="bsseo-field">
			<label for="bsseo_schema_type"><?php echo esc_html( $help['schema_type']['label'] ?? __( 'Schema-Typ', 'bsseo' ) ); ?></label>
			<?php bsseo_help_icon( 'schema_type' ); ?>
			<select id="bsseo_schema_type" name="bsseo_schema_type">
				<option value=""><?php esc_html_e( '— Automatisch —', 'bsseo' ); ?></option>
				<option value="Article" <?php selected( $schema_type, 'Article' ); ?>>Article</option>
				<option value="BlogPosting" <?php selected( $schema_type, 'BlogPosting' ); ?>>BlogPosting</option>
				<option value="WebPage" <?php selected( $schema_type, 'WebPage' ); ?>>WebPage</option>
				<option value="FAQPage" <?php selected( $schema_type, 'FAQPage' ); ?>>FAQPage</option>
				<option value="HowTo" <?php selected( $schema_type, 'HowTo' ); ?>>HowTo</option>
			</select>
		</p>

		<p class="bsseo-field">
			<label for="bsseo_og_image_id"><?php echo esc_html( $help['og_image']['label'] ?? __( 'OG-Bild', 'bsseo' ) ); ?></label>
			<?php bsseo_help_icon( 'og_image' ); ?>
			<?php
			$og_url = $og_image_id ? wp_get_attachment_image_url( $og_image_id, 'thumbnail' ) : '';
			?>
			<input type="hidden" id="bsseo_og_image_id" name="bsseo_og_image_id" value="<?php echo esc_attr( (string) $og_image_id ); ?>" />
			<button type="button" class="button bsseo-og-select"><?php esc_html_e( 'Bild wählen', 'bsseo' ); ?></button>
			<button type="button" class="button bsseo-og-remove" <?php echo $og_image_id ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Entfernen', 'bsseo' ); ?></button>
			<span id="bsseo_og_preview"><?php if ( $og_url ) : ?><img src="<?php echo esc_url( $og_url ); ?>" alt="" style="max-width:120px;height:auto;display:block;margin-top:4px;" /><?php endif; ?></span>
		</p>

		<p class="bsseo-field">
			<label><?php echo esc_html( $help['sources']['label'] ?? __( 'Quellen', 'bsseo' ) ); ?></label>
			<?php bsseo_help_icon( 'sources' ); ?>
			<div id="bsseo_sources_wrap">
				<?php
				foreach ( $sources as $i => $src ) {
					$t = isset( $src['title'] ) ? $src['title'] : '';
					$u = isset( $src['url'] ) ? $src['url'] : '';
					echo '<p class="bsseo-source-row"><input type="text" name="bsseo_sources_title[]" value="' . esc_attr( $t ) . '" placeholder="' . esc_attr__( 'Titel', 'bsseo' ) . '" class="regular-text" /> <input type="url" name="bsseo_sources_url[]" value="' . esc_attr( $u ) . '" placeholder="https://" class="regular-text" /></p>';
				}
				?>
				<p class="bsseo-source-row"><input type="text" name="bsseo_sources_title[]" value="" placeholder="<?php esc_attr_e( 'Titel', 'bsseo' ); ?>" class="regular-text" /> <input type="url" name="bsseo_sources_url[]" value="" placeholder="https://" class="regular-text" /></p>
			</div>
		</p>
	</div>
	<?php
}

/**
 * Hilfs-Icon mit Daten-Attributen für Modal (A11y).
 *
 * @param string $key Key aus help-texts.
 */
function bsseo_help_icon( $key ) {
	$help = bsseo_get_help_texts();
	$h   = $help[ $key ] ?? array( 'label' => $key, 'tooltip' => '', 'help' => '' );
	$tip = $h['tooltip'] ?? '';
	$txt = $h['help'] ?? $tip;
	if ( $txt === '' ) {
		return;
	}
	?>
	<button type="button" class="bsseo-help-icon" aria-label="<?php esc_attr_e( 'Hilfe anzeigen', 'bsseo' ); ?>" data-bsseo-help="<?php echo esc_attr( $key ); ?>" data-bsseo-text="<?php echo esc_attr( $txt ); ?>">?</button>
	<?php
}

/**
 * Speichert die Metabox-Daten (DEVELOPMENT-SEO-Verfeinerung §6.1).
 * Kein Speichern bei Autosave/Revision; Nonce + Capabilities + Sanitization; keine Doppel-Slashing.
 *
 * @param int $post_id Post-ID.
 */
function bsseo_save_metabox( $post_id ) {
	if ( ! isset( $_POST['bsseo_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsseo_metabox_nonce'] ) ), 'bsseo_metabox_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// Keine Autosave/Revision Sideeffects (§6.1).
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$fields = array(
		'bsseo_title'        => 'sanitize_text_field',
		'bsseo_description'  => 'sanitize_textarea_field',
		'bsseo_focus_keyword' => 'sanitize_text_field',
		'bsseo_canonical'    => 'esc_url_raw',
		'bsseo_schema_type'  => 'sanitize_text_field',
	);
	$meta_map = array(
		'bsseo_title'         => '_bsseo_title',
		'bsseo_description'   => '_bsseo_description',
		'bsseo_focus_keyword' => '_bsseo_focus_keyword',
		'bsseo_canonical'     => '_bsseo_canonical',
		'bsseo_schema_type'   => '_bsseo_schema_type',
	);
	foreach ( $fields as $name => $sanitize ) {
		if ( isset( $_POST[ $name ] ) && isset( $meta_map[ $name ] ) ) {
			$raw     = wp_unslash( $_POST[ $name ] );
			$value   = is_string( $raw ) ? $sanitize( $raw ) : '';
			$meta_key = $meta_map[ $name ];
			if ( $value === '' || $value === false ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	if ( isset( $_POST['bsseo_noindex'] ) ) {
		update_post_meta( $post_id, '_bsseo_noindex', 1 );
	} else {
		delete_post_meta( $post_id, '_bsseo_noindex' );
	}
	if ( isset( $_POST['bsseo_nofollow'] ) ) {
		update_post_meta( $post_id, '_bsseo_nofollow', 1 );
	} else {
		delete_post_meta( $post_id, '_bsseo_nofollow' );
	}

	$og_id = isset( $_POST['bsseo_og_image_id'] ) ? absint( $_POST['bsseo_og_image_id'] ) : 0;
	if ( $og_id > 0 ) {
		update_post_meta( $post_id, '_bsseo_og_image_id', $og_id );
	} else {
		delete_post_meta( $post_id, '_bsseo_og_image_id' );
	}

	if ( isset( $_POST['bsseo_sources_title'] ) && is_array( $_POST['bsseo_sources_title'] ) && isset( $_POST['bsseo_sources_url'] ) && is_array( $_POST['bsseo_sources_url'] ) ) {
		$sources = array();
		$titles  = array_map( function ( $v ) {
			return sanitize_text_field( wp_unslash( $v ) );
		}, $_POST['bsseo_sources_title'] );
		$urls    = array_map( function ( $v ) {
			return esc_url_raw( wp_unslash( $v ) );
		}, $_POST['bsseo_sources_url'] );
		foreach ( $titles as $i => $title ) {
			$url = isset( $urls[ $i ] ) ? $urls[ $i ] : '';
			if ( $title !== '' || $url !== '' ) {
				$sources[] = array( 'title' => $title, 'url' => $url );
			}
		}
		if ( ! empty( $sources ) ) {
			update_post_meta( $post_id, '_bsseo_sources', $sources );
		} else {
			delete_post_meta( $post_id, '_bsseo_sources' );
		}
	}
}

add_action( 'save_post', 'bsseo_save_metabox', 10, 1 );

/**
 * Enqueue Admin-Skripte und -Styles nur auf Post-Edit und Einstellungsseite.
 */
function bsseo_enqueue_admin_assets() {
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}
	$is_settings  = ( $screen->id === 'settings_page_bsseo' || ( isset( $_GET['page'] ) && $_GET['page'] === 'bsseo' ) );
	$is_post_edit = ( $screen->base === 'post' && in_array( $screen->post_type, get_post_types( array( 'public' => true ) ), true ) );

	if ( ! $is_settings && ! $is_post_edit ) {
		return;
	}

	wp_enqueue_style( 'bsseo-admin', BSSEO_URL . 'assets/admin/admin.css', array( 'dashicons' ), BSSEO_VERSION );
	if ( $is_settings ) {
		wp_enqueue_style( 'dashicons' );
	}
	if ( $is_post_edit ) {
		wp_enqueue_media();
	}
	wp_enqueue_script( 'bsseo-admin', BSSEO_URL . 'assets/admin/admin.js', array( 'jquery' ), BSSEO_VERSION, true );

	$post_id = 0;
	if ( $is_post_edit && isset( $_GET['post'] ) ) {
		$post_id = (int) $_GET['post'];
	} elseif ( $is_post_edit && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
		$post_id = (int) $GLOBALS['post']->ID;
	}

	$settings = bsseo_get_settings();
	wp_localize_script( 'bsseo-admin', 'bsseoAdmin', array(
		'ajaxurl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'bsseo_analyze' ),
		'postId'    => $post_id,
		'separator' => $settings['separator'] ?? '|',
		'siteName'  => $settings['site_name_override'] !== '' ? $settings['site_name_override'] : get_bloginfo( 'name' ),
		'i18n'      => array(
			'analyzing' => __( 'Analysiere…', 'bsseo' ),
			'done'     => __( 'Fertig.', 'bsseo' ),
			'error'    => __( 'Fehler.', 'bsseo' ),
			'saveFirst' => __( 'Bitte zuerst speichern.', 'bsseo' ),
			'chars'    => __( 'Zeichen', 'bsseo' ),
			'close'    => __( 'Schließen', 'bsseo' ),
			'help'     => __( 'Hilfe', 'bsseo' ),
			'noChecks' => __( 'Keine Details.', 'bsseo' ),
		),
	) );
}

add_action( 'admin_enqueue_scripts', 'bsseo_enqueue_admin_assets' );

/**
 * Enqueue BSseo-Sidebar-Panel im Block-Editor (Gutenberg).
 * Das Panel erscheint nur beim Bearbeiten von Beiträgen/Seiten (PluginDocumentSettingPanel).
 */
function bsseo_enqueue_block_editor_sidebar() {
	wp_enqueue_script(
		'bsseo-sidebar',
		BSSEO_URL . 'assets/admin/bsseo-sidebar.js',
		array(
			'wp-element',
			'wp-components',
			'wp-plugins',
			'wp-edit-post',
			'wp-data',
			'wp-block-editor',
		),
		BSSEO_VERSION,
		true
	);

	$help = bsseo_get_help_texts();
	$i18n = array(
		'title'         => array( 'label' => $help['title']['label'] ?? __( 'Titel in Suchmaschinen', 'bsseo' ), 'tooltip' => $help['title']['tooltip'] ?? '' ),
		'description'  => array( 'label' => $help['description']['label'] ?? __( 'Kurzbeschreibung für Google', 'bsseo' ), 'tooltip' => $help['description']['tooltip'] ?? '' ),
		'focus_keyword' => array( 'label' => $help['focus_keyword']['label'] ?? __( 'Hauptsuchbegriff', 'bsseo' ) ),
		'canonical'    => array( 'label' => $help['canonical']['label'] ?? __( 'Offizielle Adresse dieser Seite', 'bsseo' ) ),
		'noindex'      => array( 'label' => $help['noindex']['label'] ?? __( 'Nicht in Google anzeigen', 'bsseo' ) ),
		'nofollow'     => array( 'label' => $help['nofollow']['label'] ?? __( 'Links nicht bewerten', 'bsseo' ) ),
		'schema_type'  => array( 'label' => $help['schema_type']['label'] ?? __( 'Art der Seite (für Suchmaschinen)', 'bsseo' ) ),
		'og_image'     => array( 'label' => $help['og_image']['label'] ?? __( 'Vorschaubild in Sozialen Medien', 'bsseo' ) ),
		'sources'      => array( 'label' => $help['sources']['label'] ?? __( 'Quellenangaben', 'bsseo' ) ),
		'autoSchema'   => __( '— Automatisch —', 'bsseo' ),
		'selectImage'  => __( 'Bild wählen', 'bsseo' ),
		'changeImage'  => __( 'Bild ändern', 'bsseo' ),
		'removeImage'  => __( 'Entfernen', 'bsseo' ),
		'sourceTitle'  => __( 'Titel', 'bsseo' ),
	);
	wp_localize_script( 'bsseo-sidebar', 'bsseoSidebar', array( 'i18n' => $i18n ) );
}
add_action( 'enqueue_block_editor_assets', 'bsseo_enqueue_block_editor_sidebar' );
