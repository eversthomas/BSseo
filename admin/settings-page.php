<?php
/**
 * BSseo – Einstellungsseite (Toggles, Separator, Schema.org)
 *
 * @package BSseo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize-Callback für bsseo_settings.
 *
 * @param array $input Ungesicherte Eingabe.
 * @return array
 */
function bsseo_sanitize_settings( $input ) {
	if ( ! is_array( $input ) ) {
		return bsseo_get_default_settings();
	}

	$defaults = bsseo_get_default_settings();
	$out     = array();

	// Separator & optionale Texte
	$out['separator']           = isset( $input['separator'] ) ? sanitize_text_field( $input['separator'] ) : $defaults['separator'];
	$out['site_name_override'] = isset( $input['site_name_override'] ) ? sanitize_text_field( $input['site_name_override'] ) : '';
	$out['home_title']         = isset( $input['home_title'] ) ? sanitize_text_field( $input['home_title'] ) : '';
	$out['home_description']   = isset( $input['home_description'] ) ? sanitize_textarea_field( $input['home_description'] ) : '';

	// Toggles: Checkboxen kommen als 1 oder fehlen
	$toggle_keys = array_keys( $defaults['toggles'] );
	$out['toggles'] = array();
	foreach ( $toggle_keys as $key ) {
		$out['toggles'][ $key ] = ! empty( $input['toggles'][ $key ] );
	}

	// Schema.org
	$out['schema_org'] = array(
		'name'            => isset( $input['schema_org']['name'] ) ? sanitize_text_field( $input['schema_org']['name'] ) : '',
		'logo'            => isset( $input['schema_org']['logo'] ) ? esc_url_raw( $input['schema_org']['logo'] ) : '',
		'social_profiles' => array(),
	);
	if ( ! empty( $input['schema_org']['social_profiles'] ) && is_string( $input['schema_org']['social_profiles'] ) ) {
		$lines = array_filter( array_map( 'trim', explode( "\n", $input['schema_org']['social_profiles'] ) ) );
		foreach ( $lines as $url ) {
			$url = esc_url_raw( $url );
			if ( $url !== '' ) {
				$out['schema_org']['social_profiles'][] = $url;
			}
		}
	}

	// Unverändert übernehmen (Phase 2: keine UI)
	$out['sitemap_excluded_post_types'] = isset( $input['sitemap_excluded_post_types'] ) && is_array( $input['sitemap_excluded_post_types'] )
		? array_map( 'sanitize_key', $input['sitemap_excluded_post_types'] )
		: $defaults['sitemap_excluded_post_types'];
	$out['analysis_limits'] = $defaults['analysis_limits'];

	// AI/LLM Modul
	$out['ai'] = array();
	$ai_defaults = $defaults['ai'];
	$out['ai']['enabled']          = ! empty( $input['ai']['enabled'] );
	$out['ai']['pretty_urls']     = ! empty( $input['ai']['pretty_urls'] ) && ! empty( $input['ai']['enabled'] );
	$out['ai']['post_types']      = isset( $input['ai']['post_types'] ) && is_array( $input['ai']['post_types'] )
		? array_map( 'sanitize_key', array_filter( $input['ai']['post_types'] ) )
		: $ai_defaults['post_types'];
	$out['ai']['taxonomy_filters'] = ! empty( $input['ai']['taxonomy_filters'] );
	$content_level = isset( $input['ai']['content_level'] ) ? sanitize_key( $input['ai']['content_level'] ) : '';
	$out['ai']['content_level']   = in_array( $content_level, array( 'metadata_only', 'include_excerpt', 'include_trimmed_content' ), true )
		? $content_level
		: $ai_defaults['content_level'];
	$out['ai']['feed_limit_default'] = isset( $input['ai']['feed_limit_default'] ) ? absint( $input['ai']['feed_limit_default'] ) : $ai_defaults['feed_limit_default'];
	$out['ai']['feed_limit_default'] = max( 1, min( (int) ( $ai_defaults['feed_limit_max'] ?? 200 ), $out['ai']['feed_limit_default'] ) );
	$out['ai']['feed_limit_max']     = isset( $input['ai']['feed_limit_max'] ) ? absint( $input['ai']['feed_limit_max'] ) : $ai_defaults['feed_limit_max'];
	$out['ai']['feed_limit_max']     = max( 10, min( 500, $out['ai']['feed_limit_max'] ) );
	$out['ai']['feed_limit_default'] = min( $out['ai']['feed_limit_default'], $out['ai']['feed_limit_max'] );
	$out['ai']['respect_noindex']    = ! empty( $input['ai']['respect_noindex'] );
	$out['ai']['respect_password']   = ! empty( $input['ai']['respect_password'] );
	$out['ai']['cache_enabled']      = ! empty( $input['ai']['cache_enabled'] );
	$out['ai']['cache_ttl']          = isset( $input['ai']['cache_ttl'] ) ? absint( $input['ai']['cache_ttl'] ) : $ai_defaults['cache_ttl'];
	$out['ai']['cache_ttl']          = max( 60, min( WEEK_IN_SECONDS, $out['ai']['cache_ttl'] ) );
	$out['ai']['public_access']      = ! empty( $input['ai']['public_access'] );
	$out['ai']['require_api_key']    = ! empty( $input['ai']['require_api_key'] );
	$out['ai']['api_key']            = isset( $input['ai']['api_key'] ) ? sanitize_text_field( $input['ai']['api_key'] ) : '';
	$out['ai']['debug_headers']      = ! empty( $input['ai']['debug_headers'] );

	return $out;
}

/**
 * Registriert Menü und Einstellungsseite.
 */
function bsseo_add_settings_page() {
	add_options_page(
		__( 'BSseo', 'bsseo' ),
		__( 'BSseo', 'bsseo' ),
		'manage_options',
		'bsseo',
		'bsseo_render_settings_page'
	);
}

add_action( 'admin_menu', 'bsseo_add_settings_page' );

/**
 * Registriert die Option und Sektionen.
 */
function bsseo_register_settings() {
	register_setting(
		'bsseo',
		'bsseo_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'bsseo_sanitize_settings',
		)
	);

	add_settings_section(
		'bsseo_toggles',
		__( 'Ausgabe steuern', 'bsseo' ),
		function () {
			echo '<p class="description">' . esc_html__( 'Aktiviere oder deaktiviere die jeweiligen Ausgaben im Frontend.', 'bsseo' ) . '</p>';
		},
		'bsseo',
		array()
	);

	add_settings_section(
		'bsseo_general',
		__( 'Allgemein', 'bsseo' ),
		'__return_false',
		'bsseo',
		array()
	);

	add_settings_section(
		'bsseo_schema',
		__( 'Schema.org (Organisation)', 'bsseo' ),
		function () {
			echo '<p class="description">' . esc_html__( 'Angaben für strukturierte Daten (JSON-LD). Optional.', 'bsseo' ) . '</p>';
		},
		'bsseo',
		array()
	);
}

add_action( 'admin_init', 'bsseo_register_settings' );

/**
 * Gibt die Einstellungsseite aus.
 */
function bsseo_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = bsseo_get_settings();
	$help     = bsseo_get_help_texts();

	$toggle_icons = array(
		'head_output_active'      => 'dashicons-admin-generic',
		'output_title'            => 'dashicons-editor-textcolor',
		'output_meta_description' => 'dashicons-media-text',
		'output_robots'           => 'dashicons-visibility',
		'output_canonical'        => 'dashicons-admin-links',
		'output_og_twitter'       => 'dashicons-share',
		'output_schema'           => 'dashicons-chart-bar',
		'output_sitemap_tweaks'   => 'dashicons-list-view',
		'analysis_enabled'       => 'dashicons-performance',
		'use_wp_fallback'        => 'dashicons-editor-paragraph',
	);
	$toggle_keys = array_keys( $toggle_icons );
	$logo_path  = BSSEO_PATH . 'assets/logo.webp';
	$has_logo   = file_exists( $logo_path );
	?>
	<div class="wrap bsseo-settings-wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( bsseo_has_seo_conflict() ) : ?>
			<div class="notice notice-warning" style="margin: 1em 0;">
				<p>
					<?php esc_html_e( 'BSseo hat ein anderes SEO-Plugin erkannt. BSseo ist eine Alternative – paralleler Betrieb kann doppelte Tags erzeugen. OG/Twitter und Schema sind bei Konflikt standardmäßig aus, können hier aber aktiviert werden.', 'bsseo' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<form action="options.php" method="post">
			<?php settings_fields( 'bsseo' ); ?>

			<div class="bsseo-settings-section bsseo-section-toggles">
				<h2 class="bsseo-section-title">
					<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					<?php esc_html_e( 'Ausgabe steuern', 'bsseo' ); ?>
				</h2>
				<p class="description bsseo-section-desc"><?php esc_html_e( 'Aktiviere oder deaktiviere die jeweiligen Ausgaben im Frontend.', 'bsseo' ); ?> <?php esc_html_e( 'Wenn Sie keine eigenen SEO-Felder in Beiträgen pflegen, nutzt BSseo automatisch Beitragstitel, Ausschnitt und Beitragsbild (Toggle „WordPress-Daten nutzen“).', 'bsseo' ); ?></p>
				<div class="bsseo-toggles-grid">
					<?php
					foreach ( $toggle_keys as $key ) {
						$h    = $help[ $key ] ?? array( 'label' => $key );
						$label = $h['label'];
						$tip   = $h['tooltip'] ?? '';
						$val   = ! empty( $settings['toggles'][ $key ] );
						$icon  = $toggle_icons[ $key ];
						?>
						<div class="bsseo-toggle-card">
							<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
							<div class="bsseo-toggle-content">
								<label for="bsseo_toggle_<?php echo esc_attr( $key ); ?>">
									<input type="checkbox" name="bsseo_settings[toggles][<?php echo esc_attr( $key ); ?>]" id="bsseo_toggle_<?php echo esc_attr( $key ); ?>" value="1" <?php checked( $val ); ?> />
									<strong><?php echo esc_html( $label ); ?></strong>
								</label>
								<?php if ( $tip ) : ?>
									<span class="description"><?php echo wp_kses( $tip, array( 'a' => array( 'href' => true ), 'code' => array() ) ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>

			<div class="bsseo-settings-columns">
				<div class="bsseo-settings-section bsseo-section-general">
					<h2 class="bsseo-section-title">
						<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
						<?php esc_html_e( 'Allgemein', 'bsseo' ); ?>
					</h2>
					<table class="form-table" role="presentation">
						<?php
						$h = $help['separator'] ?? array();
						?>
						<tr>
							<th scope="row">
								<label for="bsseo_separator"><?php echo esc_html( $h['label'] ?? __( 'Titel-Trennzeichen', 'bsseo' ) ); ?></label>
								<?php if ( ! empty( $h['tooltip'] ) ) : ?>
									<span class="description" style="display:block;font-weight:normal;"><?php echo esc_html( strip_tags( $h['tooltip'] ) ); ?></span>
								<?php endif; ?>
							</th>
							<td><input type="text" name="bsseo_settings[separator]" id="bsseo_separator" value="<?php echo esc_attr( $settings['separator'] ); ?>" class="small-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="bsseo_site_name_override"><?php echo esc_html( $help['site_name_override']['label'] ?? __( 'Blogname für SEO', 'bsseo' ) ); ?></label></th>
							<td><input type="text" name="bsseo_settings[site_name_override]" id="bsseo_site_name_override" value="<?php echo esc_attr( $settings['site_name_override'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="bsseo_home_title"><?php echo esc_html( $help['home_title']['label'] ?? __( 'Startseiten-Titel', 'bsseo' ) ); ?></label></th>
							<td><input type="text" name="bsseo_settings[home_title]" id="bsseo_home_title" value="<?php echo esc_attr( $settings['home_title'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="bsseo_home_description"><?php echo esc_html( $help['home_description']['label'] ?? __( 'Startseiten-Meta-Beschreibung', 'bsseo' ) ); ?></label></th>
							<td><textarea name="bsseo_settings[home_description]" id="bsseo_home_description" rows="2" class="large-text"><?php echo esc_textarea( $settings['home_description'] ); ?></textarea></td>
						</tr>
					</table>
				</div>

				<div class="bsseo-settings-section bsseo-section-schema">
					<h2 class="bsseo-section-title">
						<span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
						<?php esc_html_e( 'Schema.org (Organisation)', 'bsseo' ); ?>
					</h2>
					<p class="description bsseo-section-desc"><?php esc_html_e( 'Angaben für strukturierte Daten (JSON-LD). Optional.', 'bsseo' ); ?></p>
					<?php $schema = $settings['schema_org']; ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="bsseo_schema_name"><?php echo esc_html( $help['schema_org_name']['label'] ?? __( 'Organisationsname', 'bsseo' ) ); ?></label></th>
							<td><input type="text" name="bsseo_settings[schema_org][name]" id="bsseo_schema_name" value="<?php echo esc_attr( $schema['name'] ?? '' ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="bsseo_schema_logo"><?php echo esc_html( $help['schema_org_logo']['label'] ?? __( 'Logo-URL', 'bsseo' ) ); ?></label></th>
							<td><input type="url" name="bsseo_settings[schema_org][logo]" id="bsseo_schema_logo" value="<?php echo esc_attr( $schema['logo'] ?? '' ); ?>" class="large-text" placeholder="https://" /></td>
						</tr>
						<tr>
							<th scope="row">
								<label for="bsseo_schema_social"><?php echo esc_html( $help['schema_org_social_profiles']['label'] ?? __( 'Social-Media-Profile', 'bsseo' ) ); ?></label>
								<span class="description" style="display:block;font-weight:normal;"><?php echo esc_html( $help['schema_org_social_profiles']['tooltip'] ?? '' ); ?></span>
							</th>
							<td>
								<textarea name="bsseo_settings[schema_org][social_profiles]" id="bsseo_schema_social" rows="4" class="large-text" placeholder="https://facebook.com/..."><?php echo esc_textarea( implode( "\n", $schema['social_profiles'] ?? array() ) ); ?></textarea>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="bsseo-settings-section bsseo-section-ai">
				<h2 class="bsseo-section-title">
					<span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span>
					<?php esc_html_e( 'AI/LLM Meta Endpoints', 'bsseo' ); ?>
				</h2>
				<p class="description bsseo-section-desc"><?php esc_html_e( 'Optionale maschinenlesbare Endpoints für KI-Crawler und Indexing. REST: /wp-json/bsseo/v1/ai/*', 'bsseo' ); ?></p>
				<?php
				$ai      = isset( $settings['ai'] ) && is_array( $settings['ai'] ) ? $settings['ai'] : array();
				$pt_list = get_post_types( array( 'public' => true ), 'names' );
				$pt_list = array_diff( $pt_list, array( 'attachment' ) );
				$ai_pt   = isset( $ai['post_types'] ) && is_array( $ai['post_types'] ) ? $ai['post_types'] : array( 'post', 'page' );
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html( $help['ai_enabled']['label'] ?? __( 'AI/LLM Endpoints aktivieren', 'bsseo' ) ); ?></th>
						<td>
							<label for="bsseo_ai_enabled">
								<input type="checkbox" name="bsseo_settings[ai][enabled]" id="bsseo_ai_enabled" value="1" <?php checked( ! empty( $ai['enabled'] ) ); ?> />
								<?php echo esc_html( $help['ai_enabled']['tooltip'] ?? '' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( $help['ai_pretty_urls']['label'] ?? __( 'Pretty URLs', 'bsseo' ) ); ?></th>
						<td>
							<label for="bsseo_ai_pretty_urls">
								<input type="checkbox" name="bsseo_settings[ai][pretty_urls]" id="bsseo_ai_pretty_urls" value="1" <?php checked( ! empty( $ai['pretty_urls'] ) ); ?> <?php disabled( empty( $ai['enabled'] ) ); ?> />
								<?php echo esc_html( $help['ai_pretty_urls']['tooltip'] ?? '' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( $help['ai_post_types']['label'] ?? __( 'Post-Typen', 'bsseo' ) ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $pt_list as $pt ) : ?>
									<?php $obj = get_post_type_object( $pt ); ?>
									<label style="display:inline-block;margin-right:12px;">
										<input type="checkbox" name="bsseo_settings[ai][post_types][]" value="<?php echo esc_attr( $pt ); ?>" <?php checked( in_array( $pt, $ai_pt, true ) ); ?> />
										<?php echo esc_html( $obj ? $obj->labels->singular_name : $pt ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description"><?php echo esc_html( $help['ai_post_types']['tooltip'] ?? '' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( $help['ai_taxonomy_filters']['label'] ?? __( 'Taxonomie-Filter', 'bsseo' ) ); ?></th>
						<td>
							<label for="bsseo_ai_taxonomy_filters">
								<input type="checkbox" name="bsseo_settings[ai][taxonomy_filters]" id="bsseo_ai_taxonomy_filters" value="1" <?php checked( ! empty( $ai['taxonomy_filters'] ) ); ?> />
								<?php echo esc_html( $help['ai_taxonomy_filters']['tooltip'] ?? '' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( $help['ai_content_level']['label'] ?? __( 'Content-Feld-Level', 'bsseo' ) ); ?></th>
						<td>
							<select name="bsseo_settings[ai][content_level]" id="bsseo_ai_content_level">
								<option value="metadata_only" <?php selected( ( $ai['content_level'] ?? '' ) === 'metadata_only' ); ?>><?php esc_html_e( 'Nur Metadaten', 'bsseo' ); ?></option>
								<option value="include_excerpt" <?php selected( ( $ai['content_level'] ?? '' ) === 'include_excerpt' ); ?>><?php esc_html_e( 'Mit Ausschnitt', 'bsseo' ); ?></option>
								<option value="include_trimmed_content" <?php selected( ( $ai['content_level'] ?? '' ) === 'include_trimmed_content' ); ?>><?php esc_html_e( 'Mit gekürztem Inhalt', 'bsseo' ); ?></option>
							</select>
							<p class="description"><?php echo esc_html( $help['ai_content_level']['tooltip'] ?? '' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( $help['ai_feed_limit_default']['label'] ?? __( 'Feed Standard-Limit', 'bsseo' ) ); ?></th>
						<td>
							<input type="number" name="bsseo_settings[ai][feed_limit_default]" id="bsseo_ai_feed_limit_default" value="<?php echo esc_attr( (string) ( $ai['feed_limit_default'] ?? 50 ) ); ?>" min="1" max="500" class="small-text" />
							<p class="description"><?php echo esc_html( $help['ai_feed_limit_default']['tooltip'] ?? '' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( $help['ai_feed_limit_max']['label'] ?? __( 'Feed Max-Limit', 'bsseo' ) ); ?></th>
						<td>
							<input type="number" name="bsseo_settings[ai][feed_limit_max]" id="bsseo_ai_feed_limit_max" value="<?php echo esc_attr( (string) ( $ai['feed_limit_max'] ?? 200 ) ); ?>" min="10" max="500" class="small-text" />
							<p class="description"><?php echo esc_html( $help['ai_feed_limit_max']['tooltip'] ?? '' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( __( 'Indexing / Robots', 'bsseo' ) ); ?></th>
						<td>
							<label for="bsseo_ai_respect_noindex"><input type="checkbox" name="bsseo_settings[ai][respect_noindex]" id="bsseo_ai_respect_noindex" value="1" <?php checked( ! empty( $ai['respect_noindex'] ) ); ?> /> <?php echo esc_html( $help['ai_respect_noindex']['label'] ?? __( 'Noindex respektieren', 'bsseo' ) ); ?></label>
							<br />
							<label for="bsseo_ai_respect_password"><input type="checkbox" name="bsseo_settings[ai][respect_password]" id="bsseo_ai_respect_password" value="1" <?php checked( ! empty( $ai['respect_password'] ) ); ?> /> <?php echo esc_html( $help['ai_respect_password']['label'] ?? __( 'Passwortschutz respektieren', 'bsseo' ) ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( __( 'Caching', 'bsseo' ) ); ?></th>
						<td>
							<label for="bsseo_ai_cache_enabled"><input type="checkbox" name="bsseo_settings[ai][cache_enabled]" id="bsseo_ai_cache_enabled" value="1" <?php checked( ! empty( $ai['cache_enabled'] ) ); ?> /> <?php echo esc_html( $help['ai_cache_enabled']['label'] ?? __( 'Responses cachen', 'bsseo' ) ); ?></label>
							<br />
							<label for="bsseo_ai_cache_ttl"><?php echo esc_html( $help['ai_cache_ttl']['label'] ?? __( 'TTL (Sekunden)', 'bsseo' ) ); ?></label>
							<input type="number" name="bsseo_settings[ai][cache_ttl]" id="bsseo_ai_cache_ttl" value="<?php echo esc_attr( (string) ( $ai['cache_ttl'] ?? 21600 ) ); ?>" min="60" max="<?php echo esc_attr( WEEK_IN_SECONDS ); ?>" class="small-text" />
							<p class="description"><?php echo esc_html( $help['ai_cache_ttl']['tooltip'] ?? '' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( __( 'Zugriff', 'bsseo' ) ); ?></th>
						<td>
							<label for="bsseo_ai_public_access"><input type="checkbox" name="bsseo_settings[ai][public_access]" id="bsseo_ai_public_access" value="1" <?php checked( ! empty( $ai['public_access'] ) ); ?> /> <?php echo esc_html( $help['ai_public_access']['label'] ?? __( 'Öffentlich zugänglich', 'bsseo' ) ); ?></label>
							<br />
							<label for="bsseo_ai_require_api_key"><input type="checkbox" name="bsseo_settings[ai][require_api_key]" id="bsseo_ai_require_api_key" value="1" <?php checked( ! empty( $ai['require_api_key'] ) ); ?> /> <?php echo esc_html( $help['ai_require_api_key']['label'] ?? __( 'API-Key erforderlich', 'bsseo' ) ); ?></label>
							<br />
							<label for="bsseo_ai_api_key"><?php echo esc_html( $help['ai_api_key']['label'] ?? __( 'API-Key', 'bsseo' ) ); ?></label>
							<input type="text" name="bsseo_settings[ai][api_key]" id="bsseo_ai_api_key" value="<?php echo esc_attr( $ai['api_key'] ?? '' ); ?>" class="regular-text" autocomplete="off" />
							<p class="description"><?php echo esc_html( $help['ai_api_key']['tooltip'] ?? '' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html( __( 'Debug', 'bsseo' ) ); ?></th>
						<td>
							<label for="bsseo_ai_debug_headers"><input type="checkbox" name="bsseo_settings[ai][debug_headers]" id="bsseo_ai_debug_headers" value="1" <?php checked( ! empty( $ai['debug_headers'] ) ); ?> /> <?php echo esc_html( $help['ai_debug_headers']['label'] ?? __( 'Debug-Header', 'bsseo' ) ); ?></label>
						</td>
					</tr>
				</table>
			</div>

			<?php submit_button( __( 'Einstellungen speichern', 'bsseo' ) ); ?>
		</form>

		<footer class="bsseo-admin-footer">
			<div class="bsseo-admin-footer-inner">
				<?php if ( $has_logo ) : ?>
					<div class="bsseo-admin-footer-logo">
						<img src="<?php echo esc_url( BSSEO_URL . 'assets/logo.webp' ); ?>" alt="<?php esc_attr_e( 'BSseo', 'bsseo' ); ?>" width="120" height="40" />
					</div>
				<?php endif; ?>
				<p class="bsseo-admin-footer-disclaimer">
					<?php esc_html_e( 'Die Nutzung des Plugins erfolgt auf eigene Gefahr. Der Anbieter übernimmt keine Haftung für Schäden oder Datenverluste.', 'bsseo' ); ?>
				</p>
				<p class="bsseo-admin-footer-links">
					<a href="<?php echo esc_url( 'https://github.com/eversthomas/BSseo' ); ?>" target="_blank" rel="noopener noreferrer">BSseo</a> · <?php esc_html_e( 'Open Source (GPL v3)', 'bsseo' ); ?> · <a href="<?php echo esc_url( 'https://bezugssysteme.de' ); ?>" target="_blank" rel="noopener noreferrer">bezugssysteme.de</a>
				</p>
			</div>
		</footer>
	</div>
	<?php
}
