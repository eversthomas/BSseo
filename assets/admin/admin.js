/**
 * BSseo – Admin (Metabox: Counter, Title-Preview, Analyse-Button, Help-Modals)
 */
(function ($) {
	'use strict';

	var $doc = $(document);
	var sep, siteName, defaultTitle;

	function init() {
		if (typeof bsseoAdmin === 'undefined') return;

		sep = bsseoAdmin.separator || '|';
		siteName = bsseoAdmin.siteName || '';
		var $metabox = $('.bsseo-metabox');
		defaultTitle = $metabox.data('default-title') || '';

		// Zeichenzähler + Title-Vorschau
		$('#bsseo_title').on('input', function () {
			var v = $(this).val();
			$('#bsseo_title_counter').text(v.length);
			updateTitlePreview(v);
		}).trigger('input');

		$('#bsseo_description').on('input', function () {
			$('#bsseo_description_counter').text($(this).val().length);
		}).trigger('input');

		// Hilfe-Icons: Modal mit Text (A11y: ESC, Fokus-Trap)
		$doc.on('click', '.bsseo-help-icon', function () {
			var text = $(this).data('bsseo-text');
			if (!text) return;
			openHelpModal(text);
		});

		// Analyse-Button
		$('#bsseo-analyze-btn').on('click', runAnalyze);

		// Checks-Modal schließen
		$doc.on('click', '#bsseo-checks-modal .bsseo-modal-close', closeChecksModal);
		$doc.on('keydown', function (e) {
			if (e.key === 'Escape') {
				closeHelpModal();
				closeChecksModal();
			}
		});

		// OG-Bild: Medienbibliothek
		$('.bsseo-og-select').on('click', openMediaPicker);
		$('.bsseo-og-remove').on('click', function () {
			$('#bsseo_og_image_id').val('');
			$('#bsseo_og_preview').empty();
			$(this).hide();
		});
	}

	function updateTitlePreview(titleVal) {
		var part = (titleVal && titleVal.trim()) ? titleVal.trim() : defaultTitle;
		var preview = part + ' ' + sep + ' ' + siteName;
		$('#bsseo_title_preview').text(preview).attr('title', preview);
	}

	function openHelpModal(text) {
		var $existing = $('#bsseo-help-modal');
		if ($existing.length) $existing.remove();

		var $modal = $(
			'<div id="bsseo-help-modal" class="bsseo-modal" role="dialog" aria-modal="true" aria-labelledby="bsseo-help-title">' +
			'<div class="bsseo-modal-inner">' +
			'<h2 id="bsseo-help-title">' + (bsseoAdmin.i18n && bsseoAdmin.i18n.help ? bsseoAdmin.i18n.help : 'Hilfe') + '</h2>' +
			'<p>' + escapeHtml(text) + '</p>' +
			'<button type="button" class="bsseo-modal-close">' + (bsseoAdmin.i18n && bsseoAdmin.i18n.close ? bsseoAdmin.i18n.close : 'Schließen') + '</button>' +
			'</div></div>'
		);
		$('body').append($modal);
		$modal.removeAttr('hidden');
		$modal.find('.bsseo-modal-close').focus();

		$modal.on('keydown', function (e) {
			if (e.key === 'Tab') {
				var focusable = $modal.find('button, [href], input, select, textarea').filter(':visible');
				var first = focusable.first();
				var last = focusable.last();
				if (e.shiftKey && $(document.activeElement).is(first)) {
					e.preventDefault();
					last.focus();
				} else if (!e.shiftKey && $(document.activeElement).is(last)) {
					e.preventDefault();
					first.focus();
				}
			}
		});
		$doc.on('click', '#bsseo-help-modal .bsseo-modal-close', closeHelpModal);
	}

	function closeHelpModal() {
		$('#bsseo-help-modal').remove();
	}

	function escapeHtml(s) {
		var div = document.createElement('div');
		div.textContent = s;
		return div.innerHTML;
	}

	function openChecksModal(checks) {
		var $list = $('#bsseo-checks-list').empty();
		if (checks && checks.length) {
			checks.forEach(function (c) {
				$list.append(
					'<p class="bsseo-check bsseo-check-' + escapeHtml(c.status || '') + '">' +
					'<strong>' + escapeHtml(c.label || '') + '</strong>: ' + escapeHtml(c.message || '') +
					'</p>'
				);
			});
		} else {
			$list.append('<p>' + (bsseoAdmin.i18n && bsseoAdmin.i18n.noChecks ? bsseoAdmin.i18n.noChecks : 'Keine Details.') + '</p>');
		}
		$('#bsseo-checks-modal').removeAttr('hidden').find('.bsseo-modal-close').focus();
		// Fokus-Trap
		$('#bsseo-checks-modal').on('keydown', function (e) {
			if (e.key === 'Tab') {
				var $m = $(this);
				var focusable = $m.find('button, [href], input').filter(':visible');
				var first = focusable.first();
				var last = focusable.last();
				if (e.shiftKey && $(document.activeElement).is(first)) {
					e.preventDefault();
					last.focus();
				} else if (!e.shiftKey && $(document.activeElement).is(last)) {
					e.preventDefault();
					first.focus();
				}
			}
		});
	}

	function closeChecksModal() {
		$('#bsseo-checks-modal').attr('hidden', 'hidden');
	}

	function runAnalyze() {
		var $btn = $('#bsseo-analyze-btn');
		var $status = $('#bsseo-analyze-status');
		// Post-ID aus localized script oder aus Metabox data-Attribut (Fallback)
		var postId = parseInt(bsseoAdmin.postId || $('.bsseo-metabox').data('post-id') || 0, 10);
		if (!postId) {
			$status.text(bsseoAdmin.i18n && bsseoAdmin.i18n.saveFirst ? bsseoAdmin.i18n.saveFirst : 'Bitte zuerst speichern.');
			return;
		}
		$btn.prop('disabled', true);
		$status.text(bsseoAdmin.i18n && bsseoAdmin.i18n.analyzing ? bsseoAdmin.i18n.analyzing : 'Analysiere…');

		$.post(bsseoAdmin.ajaxurl, {
			action: 'bsseo_analyze_content',
			bsseo_nonce: bsseoAdmin.nonce,
			post_id: postId
		})
			.done(function (r) {
				if (r.success && r.data) {
					$('#bsseo-seo-score').text(r.data.seo_score);
					$('#bsseo-ai-score').text(r.data.ai_score);
					$status.text(bsseoAdmin.i18n && bsseoAdmin.i18n.done ? bsseoAdmin.i18n.done : 'Fertig.');
					if (r.data.checks && r.data.checks.length) {
						openChecksModal(r.data.checks);
					}
				} else {
					$status.text(r.data && r.data.message ? r.data.message : (bsseoAdmin.i18n && bsseoAdmin.i18n.error ? bsseoAdmin.i18n.error : 'Fehler.'));
				}
			})
			.fail(function () {
				$status.text(bsseoAdmin.i18n && bsseoAdmin.i18n.error ? bsseoAdmin.i18n.error : 'Fehler.');
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	}

	function openMediaPicker() {
		if (typeof wp === 'undefined' || !wp.media) return;
		var input = document.getElementById('bsseo_og_image_id');
		var frame = wp.media({
			library: { type: 'image' },
			multiple: false
		});
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			input.value = att.id;
			$('#bsseo_og_preview').html($('<img>').attr('src', att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url).css({ maxWidth: '120px', height: 'auto', display: 'block', marginTop: '4px' }));
			$('.bsseo-og-remove').show();
		});
		frame.open();
	}

	$(function () {
		init();
	});
})(jQuery);
